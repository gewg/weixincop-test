<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/27
 * Time: 18:07
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\ProcessCopy;
use App\Models\ProcessPrincipal;
use App\Models\Equipment;
use App\Models\Teacher;

class Process extends Base
{
    protected $table = 'process';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    /**
     * 添加流程
     *
     * @param $title
     * @param $copy
     * @param $principal
     * @return array
     */
    public function addProcess($title, $copy, $principal, $branch_id,$param)
    {
        //检查参数
        foreach ($param['type_list'] as $v) {
            $map = [
                'id' => $v,
                'branch_id' => $branch_id,
                'is_del' => 0
            ];


            if (!Equipment::where($map)->first()) {
                return $this->buildReturn(env('RETURN_FAIL'), '故障类型不合法');
            }
        }

        foreach ($param['principal_list'] as $v) {
            $map = [
                'id' => $v,
                'branch_id' => $branch_id,
                'is_del' => 0
            ];
            if (!Teacher::where($map)->first()) {
                return $this->buildReturn(env('RETURN_FAIL'), '负责人不合法');
            }
        }
        foreach ($param['copy_list'] as $v) {
            $map = [
                'id' => $v,
                'branch_id' => $branch_id,
                'is_del' => 0
            ];
            if (!Teacher::where($map)->first()) {
                return $this->buildReturn(env('RETURN_FAIL'), '抄送人不合法');
            }
        }

        //插入流程表中
        $process_data = [
            'title' => $title,
            'copy' => $copy,
            'principal' => $principal,
            'branch_id' => $branch_id,
            'add_time' => date('Y-m-d H:i:s')
        ];
        $process_id = Process::insertGetId($process_data);


        $equipment = new Equipment();
        //更新故障类型
        foreach ($param['type_list'] as $v) {
            $type_data = [
                'process_id' => $process_id
            ];

            $type_result = $equipment->updateType($v, $type_data);

            if (!$type_result['state']) {
                return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
            }
        }

        //添加负责人
        foreach ($param['principal_list'] as $v) {
            $principal_data = [
                'process_id' => $process_id,
                'teacher_id' => $v,
                'add_time' => date('Y-m-d H:i:s')
            ];
            $principal_id = ProcessPrincipal::insertGetId($principal_data);
        }

        foreach ($param['copy_list'] as $v) {
            $principal_data = [
                'process_id' => $process_id,
                'teacher_id' => $v,
                'add_time' => date('Y-m-d H:i:s')
            ];
            $copy_id = ProcessCopy::insertGetId($principal_data);
        }

        return $this->buildReturn(1,'',[]);
    }

    /**
     * 报修流程列表
     *
     * @param $branch_id
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getList($branch_id, $offset=0, $limit=10)
    {
        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0
        ];

        $process_s = Process::where($map);

        $total = $process_s->count();
        $last_page = $process_s->paginate($limit)->lastPage();

        $process_list = $process_s->select('id','title', 'copy', 'principal')
            ->orderBy('add_time','DESC')
            ->offset($offset)->limit($limit)
            ->get()->toArray();

        $equipment = new Equipment();

        foreach ($process_list as $k=>$v) {
            $type = Equipment::where('process_id',$v['id'])->where('is_del',0)
                ->select('id','title','level', 'parent_id')->get()->toArray();

            $type_list = [];
            $child_list = [];
            # 报修分类处理
            $ids = [];
            foreach ($type as $kk => $vv) {
                if ($vv['level'] == 1) {
                    $parent_title = Equipment::where('id', $vv['parent_id'])
                        ->value('title');
                    if (!in_array($vv['parent_id'], $ids)) {
                        $type_list[] = [
                            'id' => $vv['parent_id'],
                            'title' => $parent_title
                        ];
                        $ids[] = $vv['parent_id'];
                    }
                    $child_list[] = [
                        'child_id' => $vv['id'],
                        'title' => $vv['title']
                    ];
                } else {
                    # 一级分类
                    $type_list[] = [
                        'id' => $vv['id'],
                        'title' => $vv['title']
                    ];
                }
            }

            $process_list[$k]['type_list'] = $type_list;
            $process_list[$k]['child_list'] = $child_list;
            $process_list[$k]['copy_list'] = ProcessCopy::getTeacherList($v['id']);
            $process_list[$k]['principal_list'] = ProcessPrincipal::getTeacherList($v['id']);
            $name_list = [];
            foreach ($process_list[$k]['type_list'] as $vv) {
                $name_list[] = $vv['title'];
            }
            $process_list[$k]['type_title_list'] = implode(',',$name_list);
            $name_list = [];
            foreach ($process_list[$k]['child_list'] as $vv) {
                $name_list[] = $vv['title'];
            }
            $process_list[$k]['child_title_list'] = implode(',',$name_list);
            $name_list = [];
            foreach ($process_list[$k]['copy_list'] as $vv) {
                $name_list[] = $vv['name'];
            }
            $process_list[$k]['copy_name_list'] = implode(',',$name_list);
            $name_list = [];
            foreach ($process_list[$k]['principal_list'] as $vv) {
                $name_list[] = $vv['name'];
            }
            $process_list[$k]['principal_name_list'] = implode(',',$name_list);
        }

        $list = [
            'process_list' => $process_list,
            'total' => $total,
            'page' => $offset+1,
            'page_total' => $last_page
        ];
        return $list;
    }


    public function editProcess($process_id, $title, $copy, $principal, $branch_id,$param)
    {
        //检查参数
        foreach ($param['type_list'] as $v) {
            $map = [
                'id' => $v,
                'branch_id' => $branch_id,
                'is_del' => 0
            ];


            if (!Equipment::where($map)->first()) {
                return $this->buildReturn(env('RETURN_FAIL'), '故障类型不合法');
            }
        }

        foreach ($param['principal_list'] as $v) {
            $map = [
                'id' => $v,
                'branch_id' => $branch_id,
                'is_del' => 0
            ];
            if (!Teacher::where($map)->first()) {
                return $this->buildReturn(env('RETURN_FAIL'), '负责人不合法');
            }
        }
        foreach ($param['copy_list'] as $v) {
            $map = [
                'id' => $v,
                'branch_id' => $branch_id,
                'is_del' => 0
            ];
            if (!Teacher::where($map)->first()) {
                return $this->buildReturn(env('RETURN_FAIL'), '抄送人不合法');
            }
        }

        //将流程下面的报修流程全部取消绑定
        $type_data = [
            'process_id' => 0
        ];
        Equipment::where('branch_id',$branch_id)->where('process_id', $process_id)
            ->where('is_del',0)->update($type_data);

        //删除流程下的负责人与抄送人
        $data = [
            'is_del' => 1,
            'del_time' => date('Y-m-d H:i:s')
        ];
        ProcessCopy::where('process_id', $process_id)
            ->where('is_del',0)->update($data);
        ProcessPrincipal::where('process_id', $process_id)
            ->where('is_del',0)->update($data);

        Process::where('id',$process_id)->update([
            'title' => $title,
            'copy' => $copy,
            'principal' => $principal,
            'update_time' => date('Y-m-d H:i:s')
        ]);

        $equipment = new Equipment();
        //更新故障类型
        foreach ($param['type_list'] as $v) {
            $type_data = [
                'process_id' => $process_id
            ];

            $type_result = $equipment->updateType($v, $type_data);

            if (!$type_result['state']) {
                return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
            }
        }

        //添加负责人
        foreach ($param['principal_list'] as $v) {
            $principal_data = [
                'process_id' => $process_id,
                'teacher_id' => $v,
                'add_time' => date('Y-m-d H:i:s')
            ];
            $principal_id = ProcessPrincipal::insertGetId($principal_data);
        }

        foreach ($param['copy_list'] as $v) {
            $principal_data = [
                'process_id' => $process_id,
                'teacher_id' => $v,
                'add_time' => date('Y-m-d H:i:s')
            ];
            $copy_id = ProcessCopy::insertGetId($principal_data);
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }
}