<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/29
 * Time: 9:46
 */

namespace App\Http\Controllers;

use App\Models\Base;
use App\Models\Branch;
use App\Models\OrderCopy;
use App\Models\OrderPrincipal;
use Illuminate\Http\Request;
use App\Models\Process;
use App\Models\Equipment;
use App\Models\ProcessPrincipal;
use App\Models\ProcessCopy;
use App\Models\Order;
use App\Models\Teacher;
use App\Models\Weixin;
use Illuminate\Support\Facades\DB;
use App\Models\Record;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RepairController extends Base
{
    /**
     * 根据故障设备类型获取报修流程
     *
     * @param Request $request
     * @return array
     */
    public function getProcess(Request $request)
    {
        $type_id = $request->input('type_id', '');
        $branch_id = $request->session()->get('branch_id');

        if (!$type_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数type_id');
        }

        //获取process_id
        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0,
            'id' => $type_id
        ];
        $type_info = Equipment::where($map)->select('id','process_id')->first();
        if (!$type_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '参数type_id错误');
        }

        if ($type_info['process_id'] == 0) {
            $data = [
                'copy' => 1,
                'principal' => 1,
                'copy_list' => [],
                'principal_list' => []
            ];
            return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功', ['process_info' => $data]);
        }
        $map = [
            'id' => $type_info['process_id'],
            'is_del' => 0
        ];
        $process_info = Process::where($map)->select('principal','copy')->first();

        $copy_list = ProcessCopy::getTeacherList($type_info['process_id']);
        $principal_list = ProcessPrincipal::getTeacherList($type_info['process_id']);

        $data = [
            'copy' => $process_info['copy'],
            'principal' => $process_info['principal'],
            'copy_list' => $copy_list,
            'principal_list' => $principal_list
        ];
        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功', ['process_info' => $data]);

    }

    /**
     * 新建报修单
     *
     * @param Request $request
     * @return array
     */
    public function addRepair(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $teacher_id = $request->session()->get('teacher_id');
        $template_info = $request->input('template_info', '');
        $copy_list = json_decode($request->input('copy_list', ''), 1);
        $principal_list = json_decode($request->input('principal_list', ''), 1);

//        $template_info = '{"equipment_type": 2,"equipment_name": 2,"equipment_model": 2,"equipment_no": 1,"department": 0,"malfuntcion_time": 0,"report_time": 0,"malfuntcion_area": 2,"malfuntcion_description": 2,"image": 1,"emergency_level": 0}';
        if (!$template_info) {
            return $this->buildReturn(env('RETURN_FAIL'), 'template_info');
        }

        $template_list = json_decode($template_info, 1);

        $temp_list = [
            'equipment_type',
            'equipment_name',
            'equipment_model',
            'equipment_no',
            'department',
            'malfuntcion_time',
            'report_time',
            'malfuntcion_area',
            'malfuntcion_description',
            'image',
            'emergency_level',
            'mobile',
        ];

        $template_value = [];
        foreach ($temp_list as $v) {
            $template_value[$v] = $template_list[$v];
        }

        $param = [];
        foreach ($template_value as $k => $v) {
            if ($v >= 1) {
                $param[$k] = $request->input($k, '');
            }
            if ($v > 1) {
                if (!$param[$k] && $k != 'equipment_name') {
                    return $this->buildReturn(env('RETURN_FAIL'), '缺少参数'.$this->getValue($k));
                }
            }

        }
        $order = new Order();
        $result = $order->addOrder($template_value, $param, $branch_id, $teacher_id, $copy_list, $principal_list);

        if ($result['state']) {

            $principal_user_list = [];
            foreach ($principal_list as $v) {
                $principal_user_list[] = Teacher::where('id',$v)->value('wx_user_id');
            }

            $title = '收到新的报修单';
            $description = '您有一个新的报修单，点击查看详情';
//            $url = 'https://campus.sc-edu.com/repair/recorddetail.html';
            $branch_no = Branch::where('id',$branch_id)->value('branch_no');
            $url = 'https://campus.sc-edu.com/repair/api/repair/public/'."?branch_no={$branch_no}&type=1&order_id={$result['data']['order_id']}&role=2";
            $weixin = new Weixin($branch_id);
            $send_result = $weixin->sendCardMsg(implode('|',$principal_user_list),$title,$description, $url);
            //return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功',['order_id' => $result['data']['order_id']]);

            # 抄送人发送信息
            if (in_array($branch_id, [4,17])) {
				
                $copy_user_list = [];
                foreach ($copy_list as $v) {
                    $copy_user_list[] = Teacher::where('id',$v)->value('wx_user_id');
                }
                $title = '收到新的报修单抄送';
                $description = '您收到一个新的报修单抄送信息，点击查看详情';
                $url = 'https://campus.sc-edu.com/repair/api/repair/public/'
                    ."?branch_no={$branch_no}&type=1&order_id={$result['data']['order_id']}&role=3";
                $weixin = new Weixin($branch_id);
                $send_result = $weixin->sendCardMsg(implode('|',$copy_user_list),$title,$description, $url);
            }
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功',['order_id' => $result['data']['order_id']]);
        }
        return $this->buildReturn(env('RETURN_FAIL'), $result['message']);
    }

    /**
     * 撤销报修单
     *
     * @param Request $request
     * @return array
     */
    public function unsetRepair(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $order_id = $request->input('order_id',0);

        if (!$order_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数order_id');
        }

        $map = [
            'is_del' => 0,
            'id' => $order_id,
            'branch_id' => $branch_id
        ];
        $order_info = Order::where($map)->first();
        if (!$order_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '报修单错误');
        }

        if ($order_info['state'] != 0) {
            return $this->buildReturn(env('RETURN_FAIL'), '报修单在处理中，无法撤销');
        }
        $data = [
            'state' => 4
        ];
        Order::where($map)->update($data);
        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }

    /**
     * 用户提交的报修列表
     *
     * @param Request $request
     * @return array
     */
    public function orderList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $teacher_id = $request->session()->get('teacher_id');
        $page = $request->input('offset',1);
        $limit = $request->input('limit', 10);

        $type = $request->input('type',1);
        $state = $request->input('state', -1);

        $offset = ($page - 1)*$limit;

        $order = new Order();
        $list_info = $order->getList($branch_id, $teacher_id, $offset, $limit, $state, $type);

        if ($list_info['state']) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功', $list_info['data']);
        }
        return $this->buildReturn(env('RETURN_FAIL'), $list_info['message']);
    }

    /**
     * 获取报修单详情
     *
     * @param Request $request
     * @return array
     */
    public function orderdetail(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $teacher_id = $request->session()->get('teacher_id');
        $order_id = $request->input('order_id',0);
        $type = $request->input('type',1);

        if (!$order_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数order_id');
        }
        $order = new Order();
        $detail_info = $order->orderDetail($order_id, $branch_id);

        $map = [
            'type' => 1,
            'order_id' => $order_id,
            'is_del' => 0
        ];
        $record_teacher_id = Record::where($map)->value('teacher_id');
        if ($record_teacher_id == $teacher_id) {
            $is_deal = 1;
        } else{
            $is_deal = 0;
        }
        $detail_info['data']['is_deal'] = $is_deal;
        if ($detail_info['state']) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功', $detail_info['data']);
        }
        return $this->buildReturn(env('RETURN_FAIL'), $detail_info['message']);
    }

    /**
     * 催办
     *
     * @param Request $request
     * @return array
     */
    public function urgeRepair(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $order_id = $request->input('order_id',0);

        if (!$order_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数order_id');
        }

        $map = [
            'order_id' => $order_id,
        ];

        $branch_no = Branch::where('id',$branch_id)->value('branch_no');

        $urge_list = Db::table('order_urge')->where($map)->where('add_time','>',date('Y-m-d 0:0:0'))->count();

        if ($urge_list > 1) {
            return $this->buildReturn(env('RETURN_FAIL'), '今日催办次数上限');
        }

        $last_time = Db::table('order_urge')->where($map)->value('add_time');

        if (strtotime($last_time) > (time() - (3*60))) {
            return $this->buildReturn(env('RETURN_FAIL'), '刚刚已经催办过了');
        }
        $map = [
            'order_id' => $order_id,
            'is_del' => 0,
        ];
        $principal_list = OrderPrincipal::where($map)->get()->toArray();
        $teacher_list = [];
        foreach ($principal_list as $v) {
            $teacher_list[] = Teacher::where('id',$v['teacher_id'])->value('wx_user_id');
        }

        if (!$teacher_list) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '发送成功');
        }
        $title = '报修单催办';
        $description = '您有一个新的报修单催办，点击查看详情';
//        $url = 'https://campus.sc-edu.com/repair/recorddetail.html';
        $url = 'https://campus.sc-edu.com/repair/api/repair/public/'
            ."?branch_no={$branch_no}&type=1&order_id={$order_id}&role=2";

        $weixin = new Weixin($branch_id);
        $send_result = $weixin->sendCardMsg(implode('|',$teacher_list),$title,$description, $url);
        if ($send_result['state']) {
            //todo 插入到数据库
            $data = [
                'order_id' => $order_id,
                'add_time' => date('Y-m-d H:i:s')
            ];
            DB::table('order_urge')->insertGetId($data);
            return $this->buildReturn(env('RETURN_SUCCESS'), '发送成功');
        }
        return $this->buildReturn(env('RETURN_FAIL'), $send_result['message']);

    }

    /**
     * 添加维修记录
     *
     * @param Request $request
     * @return array
     */
    public function addRecord(Request $request)
    {
        $teacher_id = $request->session()->get('teacher_id');
        $branch_id = $request->session()->get('branch_id');
        $type = $request->input('type',0);
        $remark = $request->input('remark',0);
        $order_id = $request->input('order_id',0);

        if (!$type) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数type');
        }
        if (!$remark) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数remark');
        }
        if (!$order_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数order_id');
        }

        //判断报修单状态
        $order_state = Order::where('id',$order_id)->where('is_del',0)
            ->value('state');

        $check_state = $this->checkState($order_state);
        if (!$check_state) {
            return $this->buildReturn(env('RETURN_FAIL'), '报修单状态不符');
        }

        if ($check_state != $type) {
            return $this->buildReturn(env('RETURN_FAIL'), '报修单状态');
        }

        $branch_no = Branch::where('id', $branch_id)->value('branch_no');
        if ($type == 1) {
            // 给报修人发消息
            $report_teacher_id = $order_state = Order::where('id',$order_id)->where('is_del',0)
                ->value('teacher_id');
            $report_user_id = Teacher::where('id',$report_teacher_id)->value('wx_user_id');

            if ($report_teacher_id) {
                $title = '报修单处理中';
                $description = '您有一个报修单正在处理中，点击查看详情';
                $url = 'https://campus.sc-edu.com/repair/api/repair/public/'
                    ."?branch_no={$branch_no}&type=1&order_id={$order_id}&role=1";

                $weixin = new Weixin($branch_id);
                $send_result = $weixin->sendCardMsg($report_user_id,$title,$description, $url);
            }
        }

        if ($type == 2) {
            // 给报修人发消息
            $report_teacher_id = $order_state = Order::where('id',$order_id)->where('is_del',0)
                ->value('teacher_id');
            $report_user_id = Teacher::where('id',$report_teacher_id)->value('wx_user_id');

            if ($report_teacher_id) {
                $title = '报修单处理完成';
                $description = '您有一个报修单已经处理完成，点击查看详情';
                $url = 'https://campus.sc-edu.com/repair/api/repair/public/'
                    ."?branch_no={$branch_no}&type=1&order_id={$order_id}&role=1";

                $weixin = new Weixin($branch_id);
                $send_result = $weixin->sendCardMsg($report_user_id,$title,$description, $url);
            }
        }

        if ($type == 3) {
            $map = [
                'order_id' => $order_id,
                'is_del' => 0,
                'type' => 1
            ];
            $teacher_id = Record::where($map)->value('teacher_id');
            if (!$teacher_id) {
                return $this->buildReturn(env('RETURN_FAIL'), '报修单状态错误');
            }
            $teacher_user = Teacher::where('id',$teacher_id)->value('wx_user_id');

            $title = '报修单完成';
            $description = '您负责的报修单有反馈，点击查看详情';
            $url = 'https://campus.sc-edu.com/repair/api/repair/public/'
                ."?branch_no={$branch_no}&type=1&order_id={$order_id}&role=2";
            $weixin = new Weixin($branch_id);
            $send_result = $weixin->sendCardMsg($teacher_user,$title,$description, $url);

            $copy_list = OrderCopy::where('order_id', $order_id)->where('is_del',0)
                ->pluck('teacher_id')->toArray();

            $copy_user_list = [];
            foreach ($copy_list as $v) {
                $copy_user_list[] = Teacher::where('id',$v)->value('wx_user_id');
            }

            $title = '报修单完成';
            $description = '报修单已完成，点击查看详情';
            $url = 'https://campus.sc-edu.com/repair/api/repair/public/'
                ."?branch_no={$branch_no}&type=1&order_id={$order_id}&role=3";
            $weixin = new Weixin($branch_id);
            $send_result = $weixin->sendCardMsg(implode('|',$copy_user_list),$title,$description, $url);
        }

        //添加报修
        $record = new Record();
        $add_result = $record->addRecord($teacher_id,$order_id,$remark,$type);

        if (!$add_result['state']) {
            return $this->buildReturn(env('RETURN_FAIL'), $add_result['message']);
        }

        // 报修单状态
        $order_data = [
            'state' => $type,
        ];
        Order::where('id',$order_id)->update($order_data);

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }

    /**
     * 获取模板对应的信息
     *
     * @param $key
     * @return mixed
     */
    private function getValue($key)
    {
        $temp_list = [
            'equipment_type' => '故障设备类型',
            'equipment_name' => '设备名称',
            'equipment_model' => '规格型号',
            'equipment_no' => '设备编号',
            'department' => '使用部门',
            'malfuntcion_time' => '故障日期',
            'report_time' => '报修时间',
            'malfuntcion_area' => '故障设备地点',
            'malfuntcion_description' => '故障描述',
            'image' => '故障图片',
            'emergency_level' => '紧急程度',
        ];

        return $temp_list[$key];
    }

    private function checkState($key)
    {
        $data =  [
            0 => 1,
            1 => 2,
            2 => 3
        ];
        $key_list = ['0','1','2'];
        if (!in_array($key, $key_list)) {
            return[];
        }
        return $data[$key];
    }

    /**
     * 报修单记录
     *
     * @param Request $request
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function recordList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $start_time = $request->input('start_time',0);
        $end_time = $request->input('end_time',0);
        $state = $request->input('state',-1);
        $type_id = $request->input('type_id',0);
        $add_user = $request->input('add_user',0);
        $order_no = $request->input('order_no',0);
        $export = $request->input('export', 0);
        $export = 0;

        $page = $request->input('offset',1);
        $limit = $request->input('limit', 10);

        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0
        ];

        if ($state > -1) {
            $map['state'] = $state;
        }
        if ($type_id) {
            $map['equipment_type'] = $type_id;
        }

        $order_s = Order::where($map);
        if ($order_no) {
            $order_s->where('no','like',"%{$order_no}%");
        }
        if ($add_user) {
            $teacher_list = Teacher::where('title','like',"%$add_user%")->pluck('id')->toArray();
            $order_s->whereIn('teacher_id',$teacher_list);
        }
        if ($start_time) {
            $order_s->where('add_time','>',$start_time);
        }
        if ($end_time) {
            $order_s->where('add_time','<=',$end_time);
        }


        $state_list = ['未处理','处理中','已处理','已完成','已撤销'];
        $offset = ($page - 1)*$limit;
        $total = $order_s->count();
        $last_page = $order_s->paginate($limit)->lastPage();

        $list = $order_s->select('id as order_id','teacher_id','equipment_type','no as order_no','state','add_time')
            ->offset($offset)->limit($limit)->orderBy('add_time','DESC')
            ->get()->toArray();

        if ($export) {

            $data = $order_s->select('id as order_id','teacher_id','equipment_type','no as order_no','state','add_time')
                ->get()->toArray();
            $branch_title = Branch::where('id',$branch_id)->value('title');
            foreach ($data as $k => $v) {
                $user_name = Teacher::where('id',$v['teacher_id'])->value('title');
                $data[$k]['user_name'] = $user_name ? $user_name : '';
                $type_info = Equipment::where('id',$v['equipment_type'])
                    ->select('level','title', 'parent_id')->first();
                if (!$type_info) {
                    $data[$k]['type'] = '';
                } else {
                    if ($type_info->level == 0) {
                        $data[$k]['type'] = $type_info->title;
                    } else {
                        # 获取父级类型
                        $father_title = Equipment::where('id',$v['parent_id'])
                            ->value('title');
                        $data[$k]['type'] = "{$father_title}-{$type_info->title}";
                    }
                }
            }
            $key_list = ['order_no','user_name','type','add_time','state'];
            $export_list = [];
            foreach ($data as $k => $v) {
                foreach ($key_list as $vv) {
                    if ($vv == 'state') {
                        $export_list[$k][] = $state_list[$v['state']];
                        continue;
                    }
                    $export_list[$k][] = $v[$vv];
                }
            }
            $this->export($branch_title.'报修记录.xlsx',$export_list);
            return;
        }

        foreach ($list as $k => $v) {
            $list[$k]['user_name'] = Teacher::where('id',$v['teacher_id'])->value('title');
//            $list[$k]['type'] = Equipment::where('id',$v['equipment_type'])->value('title');
            $type_info = Equipment::where('id',$v['equipment_type'])
                ->select('level','title', 'parent_id')->first();
            if (!$type_info) {
                $list[$k]['type'] = '';
            } else {
                if ($type_info->level == 0) {
                    $list[$k]['type'] = $type_info->title;
                } else {
                    # 获取父级类型
                    $father_title = Equipment::where('id',$type_info->parent_id)
                        ->value('title');
                    $list[$k]['type'] = "{$father_title}-{$type_info->title}";
                }
            }
        }

        $data = [
            'order_list' => $list,
            'total' => $total,
            'page_total' => $last_page,
            'page' => $page
        ];

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功',$data);
    }


    /**
     * 报修单记录
     *
     * @param Request $request
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function recordExport(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $start_time = $request->input('start_time',0);
        $end_time = $request->input('end_time',0);
        $state = $request->input('state',-1);
        $type_id = $request->input('type_id',0);
        $add_user = $request->input('add_user',0);
        $order_no = $request->input('order_no',0);
        $export = $request->input('export', 1);
        $export = 1;

        $page = $request->input('offset',1);
        $limit = $request->input('limit', 10);

        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0
        ];

        if ($state > -1) {
            $map['state'] = $state;
        }
        if ($type_id) {
            $map['equipment_type'] = $type_id;
        }
//        if ($add_user) {
//            $map['teacher_id'] = $add_user;
//        }
//        if ($order_no) {
//            $map['no'] = $order_no;
//        }

        $order_s = Order::where($map);
        if ($order_no) {
            $order_s->where('no','like',"%{$order_no}%");
        }
        if ($add_user) {
            $teacher_list = Teacher::where('title','like',"%$add_user%")->pluck('id')->toArray();
            $order_s->whereIn('teacher_id',$teacher_list);
        }
        if ($start_time) {
            $order_s->where('add_time','>',$start_time);
        }
        if ($end_time) {
            $order_s->where('add_time','<=',$end_time);
        }


        $state_list = ['未处理','处理中','已处理','已完成','已撤销'];

        $order_s = Order::where($map);
        if ($start_time) {
            $order_s->where('add_time','>',$start_time);
        }
        if ($end_time) {
            $order_s->where('add_time','<=',$end_time);
        }
        $data = $order_s->select('id as order_id','teacher_id','equipment_type','no as order_no','state','add_time')
            ->get()->toArray();
        $branch_title = Branch::where('id',$branch_id)->value('title');
        foreach ($data as $k => $v) {
            $user_name = Teacher::where('id',$v['teacher_id'])->value('title');
            $data[$k]['user_name'] = $user_name ? $user_name : '';
//            $type = Equipment::where('id',$v['equipment_type'])->value('title');
//            $data[$k]['type'] = $type ? $type : '';
            $type_info = Equipment::where('id',$v['equipment_type'])
                ->select('level','title', 'parent_id')->first();
            if (!$type_info) {
                $data[$k]['type'] = '';
            } else {
                if ($type_info->level == 0) {
                    $data[$k]['type'] = $type_info->title;
                } else {
                    # 获取父级类型
                    $father_title = Equipment::where('id',$v['parent_id'])
                        ->value('title');
                    $data[$k]['type'] = "{$father_title}-{$type_info->title}";
                }
            }
        }
        $key_list = ['order_no','user_name','type','add_time','state'];
        $export_list = [];
        foreach ($data as $k => $v) {
            foreach ($key_list as $vv) {
                if ($vv == 'state') {
                    $export_list[$k][] = $state_list[$v['state']];
                    continue;
                }
                $export_list[$k][] = $v[$vv];
            }
        }
        $this->export($branch_title.'报修记录.xlsx',$export_list);
        return 0;
    }
    /**
     * 导出excel
     *
     * @param $fileName
     * @param $data
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export($fileName, $data)
    {
        $title_list = ['维修编号','报修人','设备类型','提交时间','维修状态'];
        array_unshift($data, $title_list);
        $cellData = [
            1 => [
                '1',
                '1'
            ]
        ];
        // 告诉浏览器输出07Excel文件
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
// 告诉浏览器输出浏览器名称
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
// 禁止缓存
        header('Cache-Control: max-age=0');

        $style_array = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('sheet1');
//        $sheet->getStyle('A2')->applyFromArray($style_array);
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(30);
        foreach ($data as $row => $cols) {
            $row += 1;
//            $kk = 0;
            foreach ($cols as $col => $cellValue) {
                $col += 1;
                $sheet->setCellValueByColumnAndRow($col, $row, " ".$cellValue);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }

    /**
     * 删除报修单
     *
     * @param Request $request
     * @return array
     */
    public function orderDel(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $order_id = $request->input('order_id',0);
        $order_list = $request->input('order_list',0);

        if (!$order_id && !$order_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数');
        }
        if ($order_id) {
            $map = [
                'branch_id' => $branch_id,
                'id' => $order_id
            ];
            $order_date = [
                'is_del' => 1
            ];

            $result = Order::where($map)->update($order_date);
            if ($result) {
                return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
            }
        } elseif ($order_list) {
            $order_ids = json_decode($order_list, 1);
            $map = [
                'branch_id' => $branch_id,
            ];
            $order_date = [
                'is_del' => 1
            ];

            $result = Order::where($map)->whereIn('id',$order_ids)->update($order_date);
            if ($result) {
                return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
            }
        }

        return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
    }

    /**
     * 报修单统计
     *
     * @param Request $request
     * @return array
     */
    public function recordCount(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');

        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0
        ];
        $total_data = [
            'total' =>Order::where($map)->where('state','<',4)->count(),
            'un_deal' => Order::where($map)->where('state',0)->count(),
            'dealing' => Order::where($map)->where('state','=',1)->count(),
            'has_deal' => Order::where($map)->where('state',2)->count(),
            'complete' => Order::where($map)->where('state',3)->count(),
        ];


        $order_today = Order::where($map)->where('add_time','>',date('Y-m-d 0:0:0'));
        $today_data = [
            'total' => Order::where($map)->where('state','<',4)->where('add_time','>',date('Y-m-d 0:0:0'))->count(),
            'un_deal' => Order::where($map)->where('add_time','>',date('Y-m-d 0:0:0'))->where('state',0)->count(),
            'dealing' => Order::where($map)->where('add_time','>',date('Y-m-d 0:0:0'))->where('state',1)->count(),
            'has_deal' => Order::where($map)->where('add_time','>',date('Y-m-d 0:0:0'))->whereIn('state',[2,3])->count(),
        ];

        $current_time = time();
        $c_year = floor(date("Y",$current_time));
        $c_month = floor(date("m",$current_time));

        $start_time = "{$c_year}年{$c_month}月";
        $last_month = '';
        $month_title_list = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $c_month-$i;
            $c_year = $month == 0 ? ($c_year-1) : $c_year;

            $month = $month <= 0 ? 12+$month : $month;
            $last_month = $month;
            $month_title_list[] = $month."月";
            $date = $c_year."-".$month."-1";
            $firstday = date('Y-m-01 0:0:0', strtotime($date));
            $lastday = date('Y-m-t 0:0:0', strtotime($date));
            $month_list[$month] = [
                'total' => Order::where($map)->where('add_time','>',$firstday)
                    ->where('state','<',4)
                    ->where('add_time','<',$lastday)->count(),
                'un_deal' => Order::where($map)->where('add_time','>',$firstday)->where('state',0)
                    ->where('add_time','<',$lastday)->count(),
                'has_deal' => Order::where($map)->where('add_time','>',$firstday)->whereIn('state',[2,3])
                    ->where('add_time','<',$lastday)->count(),
            ];
        }

        $end_time = "{$c_year}年{$last_month}月";

        $new_list = array_reverse($month_list);

        $half_year = [
            'time' => $end_time."-".$start_time,
            'month_list' => array_reverse($month_title_list)
        ];
        foreach ($new_list as $k=>$v) {
            $half_year['total'][$k] = $v['total'];
            $half_year['un_deal'][$k] = $v['un_deal'];
            $half_year['has_deal'][$k] = $v['has_deal'];
        }

        $data = [
            'total_data' => $total_data,
            'today_data' => $today_data,
            'half_year' => $half_year
        ];

        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功', $data);
    }
}
