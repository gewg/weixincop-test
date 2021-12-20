<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/29
 * Time: 11:53
 */

namespace App\Models;

use function Composer\Autoload\includeFile;
use Illuminate\Database\Eloquent\Model;
use App\Models\Image;
use App\Models\OrderCopy;
use App\Models\OrderPrincipal;
use App\Models\Equipment;
use App\Models\Teacher;
use App\Models\Common;

class Order extends Base
{
    protected $table = 'order';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    /**
     * 添加报修单
     *
     * @param $template
     * @param $param
     * @param $branch_id
     * @param $teacher_id
     * @param $copy_list
     * @param $principal_list
     * @return array
     */
    public function addOrder($template, $param, $branch_id, $teacher_id, $copy_list, $principal_list)
    {

        $order_data = [];
        $time_list = ['malfuntcion_time','report_time'];
        foreach ($template as $k => $v) {
            if (in_array($k,$time_list) && $v == 0) {
                $order_data[$k] = date('Y-m-d H:i:s', '000000011');
            } elseif ($v == 0) {
                $order_data[$k] = -1;
            } else {
                $order_data[$k] = $param[$k] ? $param[$k] : '';
            }
        }

        unset($order_data['image']);

        $order_data['state'] = 0;
        $order_data['no'] = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $order_data['branch_id'] = $branch_id;
        $order_data['teacher_id'] = $teacher_id;
        $order_data['add_time'] = date('Y-m-d H:i:s');

        $order_id = Order::insertGetId($order_data);
        if (!$order_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        $image_list = [];
        if ($template['image']) {
            $image_list = json_decode($param['image'], 1);
        }

        //插入图片
        foreach ($image_list as $v) {
            $image_data  = [];
            $image_data['order_id'] = $order_id;
            $image_data['image_url'] = $v;
            $image_data['add_time'] = date('Y-m-d H:i:s');
            Image::insert($image_data);
        }

        //插入抄送人
        foreach ($copy_list as $v) {
            $copy_data = [
                'order_id' => $order_id,
                'teacher_id' => $v,
                'add_time' => date('Y-m-d H:i:s')
            ];
            OrderCopy::insertGetId($copy_data);
        }

        //插入负责人
        foreach ($principal_list as $v) {
            $principal_data = [
                'order_id' => $order_id,
                'teacher_id' => $v,
                'add_time' => date('Y-m-d H:i:s')
            ];
            OrderPrincipal::insertGetId($principal_data);
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功',['order_id' => $order_id]);
    }

    /**
     * 报修单列表
     *
     * @param $branch_id
     * @param $teacher_id
     * @param $offset
     * @param $limit
     * @param int $state
     * @return array
     */
    public function getList($branch_id, $teacher_id, $offset, $limit, $state = -1, $type = 1)
    {
        $map = [
            'branch_id' => $branch_id,
//            'teacher_id' => $teacher_id,
            'is_del' => 0
        ];
        if ($state > -1) {
            $map['state'] = $state;
        }
        if ($type == 1) {
            $map['teacher_id'] = $teacher_id;
        }
        if ($type == 3) {
            if (!in_array($branch_id, [4,17])) {
                $map['state'] = 3;
            }
        }
        $order_s = Order::where($map);

        if ($type == 2) {
            //获取负责的报修单
            $order_list = OrderPrincipal::where('teacher_id', $teacher_id)->pluck('order_id')->toArray();
            $order_s = $order_s->whereIn('id',$order_list);
        }
        if ($type == 3) {
            $order_list = OrderCopy::where('teacher_id', $teacher_id)->pluck('order_id')->toArray();
            $order_s = $order_s->whereIn('id',$order_list);
        }
        $total =  $order_s->count();
        $page_total = $order_s->paginate($limit)->lastPage();
        $common = new Common();

        $order_list = $order_s->select('id',"equipment_type",'equipment_name','equipment_model','add_time','state','emergency_level','teacher_id')
            ->orderBy('add_time','DESC')
            ->offset($offset)->limit($limit)
            ->get()->toArray();

        foreach ($order_list as $k => $v) {
            $map = [
                'id' => $v['equipment_type'],
                'is_del' => 0
            ];
            $type_info = Equipment::where($map)->select('title', 'level', 'parent_id')->first();
            if (!$type_info) {
                $order_list[$k]['title'] = '';
            }
            if ($type_info->level == 0) {
                $order_list[$k]['title'] = $type_info->title ? : '';
            } else {
                $parent_title = Equipment::where('id', $type_info->parent_id)
                    ->value('title');
                if (!$parent_title) {
                    $order_list[$k]['title'] = $type_info->title ? : '';
                } else {
                    $order_list[$k]['title'] = $parent_title."-".$type_info->title;
                }
            }

            $image_url = Image::where('order_id',$v['id'])->where('is_del', 0)->value('image_url');
            $order_list[$k]['image_url'] = $image_url ? $image_url : '';
            $order_list[$k]['teacher_name'] = Teacher::where('id',$v['teacher_id'])->value('title');
//            $order_list[$k]['add_time'] = date('Y-m-d',strtotime($v['add_time']));
            $order_list[$k]['add_time'] = $common->changeTimeFormat($order_list[$k]['add_time']);
            if ($v['state'] > 0 && $v['state'] < 4) {
                //查询处理人
                $map = [
                    'type' => 1,
                    'order_id' => $v['id'],
                    'is_del' => 0
                ];
                $record_teacher_id = Record::where($map)->value('teacher_id');
                if ($record_teacher_id == $teacher_id) {
                    $order_list[$k]['is_deal'] = 1;
                } else{
                    $order_list[$k]['is_deal'] = 0;
                }
            } else {
                $order_list[$k]['is_deal'] = 0;
            }
        }

        $data = [
            'page' => $offset + 1,
            'page_total' => $page_total,
            'total' => $total,
            'order_list' => $order_list
        ];
        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功', $data);
    }

    public function orderDetail($order_id, $branch_id, $type=1)
    {
        $map = [
            'id' => $order_id,
            'branch_id' => $branch_id,
            'is_del' => 0
        ];
        $info = Order::where($map)->first();
        if (!$info) {
            return $this->buildReturn(env('RETURN_FAIL'), '报修单错误');
        }

        $info = $info->toArray();

        $time_list = ['malfuntcion_time','report_time'];
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
            'emergency_level',
            'mobile',
        ];
        $order_info = [];
        $template = [];
        $common = new Common();

        foreach ($temp_list as $v) {
            if (in_array($v,$time_list) && $info[$v] == '1970-01-01 08:00:11') {
                $template[$v] = 0;
                $order_info[$v] = '';
                continue;
            }
            if ($info[$v] == -1) {
                $order_info[$v] = '';
                $template[$v] = 0;
                continue;
            }
            $order_info[$v] = $info[$v];
            $template[$v] = 1;
            if ($v == 'equipment_type') {
                # 保修单类型
                $type_info = Equipment::where('id', $info[$v])->select('title', 'level', 'parent_id')->first();
                # 二级类型处理
                if ($type_info) {
                    $type_info = $type_info->toArray();
                } else {
                    $order_info['equipment_title'] = '';
                }
                if ($type_info['level'] == 0) {
                    $order_info['equipment_title'] = $type_info['title'];
                } else {
                    $parent_title = Equipment::where('id', $type_info['parent_id'])->value('title');
                    $order_info['equipment_title'] = $parent_title ? "{$parent_title}-{$type_info['title']}": $type_info['title'];
                }

            }
        }

        $order_info['add_time'] = $common->changeTimeFormat($info['add_time']);
        $state = $order_info['state'] = $info['state'];
        $order_info['no'] = $info['no'];
        $teacher_avatar = Teacher::where('id', $info['teacher_id'])->value('avatar');
        $order_info['teacher_avatar'] = $teacher_avatar ? $teacher_avatar : '';
        $teacher_name = Teacher::where('id', $info['teacher_id'])->value('title');
        $order_info['teacher_name'] = $teacher_name ? $teacher_name : '';
        $order_info['teacher_id'] = $info['teacher_id'];
        if ($order_info['malfuntcion_time']) {
//            $order_info['malfuntcion_time'] = date('Y-m-d', strtotime($order_info['malfuntcion_time']));
            $order_info['malfuntcion_time'] =  $common->changeTimeFormat($order_info['malfuntcion_time']);
        }

        $map = [
            'order_id' => $order_id,
            'is_del' => 0
        ];
        $image_list = Image::where($map)->pluck('image_url')->toArray();

        $teacher_list = OrderCopy::where($map)->pluck('teacher_id')->toArray();
        $copy_list = [];
        foreach ($teacher_list as $v) {
            $copy_list[] = [
                'id' => $v,
                'name' => Teacher::where('is_del',0)
                ->where('id',$v)->value('title')
            ];
        }

        $teacher_list = OrderPrincipal::where($map)->pluck('teacher_id')->toArray();
        $principal_list = [];
        foreach ($teacher_list as $v) {
            $principal_list[] = [
                'id' => $v,
                'name' => Teacher::where('is_del',0)
                    ->where('id',$v)->value('title')
            ];
        }

        //获取报修评价
        $record_list = Record::where('order_id', $order_id)->where('type','<=',$state)->orderBy('add_time','DESC')->get()->toArray();

        $process_info = [];
        foreach ($record_list as $k=>$v) {
            $process_info[$k]['type'] = $v['type'];
            $process_info[$k]['remark'] = $v['remark'];
//            $process_info[$k]['add_time'] = $v['add_time'];
            $process_info[$k]['add_time'] = $common->changeTimeFormat($v['add_time']);
            $process_info[$k]['teacher_name'] = Teacher::where('id',$v['teacher_id'])->value('title');
            $process_info[$k]['teacher_id'] = $v['teacher_id'];
            $process_info[$k]['avatar'] = Teacher::where('id',$v['teacher_id'])->value('avatar');
        }

        $data = [
            'order_info' => $order_info,
            'copy_list' => $copy_list,
            'principal' => $principal_list,
            'image_list' => $image_list,
            'process_info' => $process_info,
            'template' => $template
        ];
        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功', $data);
    }
}