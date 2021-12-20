<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/27
 * Time: 18:11
 */

namespace App\Http\Controllers;

use App\Models\Base;
use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\Process;
use App\Models\ProcessPrincipal;
use App\Models\ProcessCopy;

class ProcessController extends Base
{
    /**
     * 添加报修流程
     *
     * @param Request $request
     * @return array
     */
    public function addProcess(Request $request)
    {
        $title = $request->input('title', '');
        $branch_id = $request->session()->get('branch_id');
        $type_list = $request->input('type_list', '');
        $principal_list = $request->input('principal_list', '');
        $copy_list = $request->input('copy_list', '');
        $copy = $request->input('copy', 0);
        $principal = $request->input('principal', 0);
        $child_list = $request->input('child_list');


        if (!$title) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数title');
        }
        if (!$copy_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数copy_list');
        }
        if (!$principal_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数principal_list');
        }
        if (!$type_list && !$child_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数type_list');
        }
        $child_list = json_decode($child_list, 1);
        if (!$type_list && !$child_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数type_list');
        }

        # 检查流程是否已经存在
        $map = [
            'branch_id' => $branch_id,
            'title' => $title,
            'is_del' => 0
        ];
        if (Process::where($map)->value('id')) {
            return $this->buildReturn(env('RETURN_FAIL'), '流程名称重复');
        }

        if (!$child_list) {
            # 没有子类型，报修类型为一级类型
            $type_list = json_decode($type_list,1);
        } else {
            # 有子类型
            $type_list = [];
            foreach ($child_list as $v) {
                # 获取一级类型下子类型列表
                $map = [
                    'parent_id' => $v['id'],
                    'is_del' => 0,
                    'branch_id' => $branch_id,
                    'level' => 1
                ];
                $child = Equipment::where($map)->select('id', 'title')->get()->toArray();
                foreach ($child as $vv) {
                    if (in_array($vv['id'], $v['child'])) {
                        $type_list[] = $vv['id'];
                    }
                }
            }
        }

        if (!$type_list) {
            return $this->buildReturn(0, '未绑定合法报修类型');
        }
//        $type_list = json_decode($type_list,1);
        $principal_list = json_decode($principal_list,1);
        $copy_list = json_decode($copy_list,1);

        $param = [
            'type_list' => $type_list,
            'principal_list' => $principal_list,
            'copy_list' => $copy_list,
        ];

        $process = new Process();
        $add_result = $process->addProcess($title, $copy, $principal, $branch_id,$param);
        if ($add_result['state']) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }
        return $this->buildReturn(env('RETURN_FAIL'), $add_result['message']);
    }

    /**
     * 获取流程列表
     * @param Request $request
     * @return array
     */
    public function processList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');

        $page = $request->input('offset',1);
        $limit = $request->input('limit', 10);

        $process = new Process();
        $offset = ($page - 1)*$limit;
        $list = $process->getList($branch_id, $offset, $limit);

        return $this->buildReturn(env('RETURN_SUCCESS'), '', $list);
    }

    /**
     * 删除流程
     *
     * @param Request $request
     * @return array
     */
    public function processDel(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $process_id = $request->input('process_id', '');

        if (!$process_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数process_id');
        }

        $data = [
            'is_del' => 1,
            'del_time' => date('Y-m-d H:i:s')
        ];
        $process = new Process();
        $result = Process::where('id',$process_id)
            ->where('branch_id', $branch_id)
            ->update($data);

        //将流程下面的报修流程全部取消绑定
        $type_data = [
            'process_id' => 0
        ];
        Equipment::where('branch_id',$branch_id)->where('process_id', $process_id)
            ->where('is_del',0)->update($type_data);

        if ($result) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }
        return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
    }

    /**
     * 获取流程详情
     *
     * @param Request $request
     * @return array
     */
    public function processDetail(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $process_id = $request->input('process_id', '');

        if (!$process_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数process_id');
        }

        $map = [
            'branch_id' => $branch_id,
            'id' => $process_id,
            'is_del' => 0
        ];

        $process = new Process();
        $process_copy = new ProcessCopy();
        $process_principal = new ProcessPrincipal();
        $info = Process::where($map)->select('id','title', 'copy', 'principal')
            ->first();
        if (!$info) {
            return $this->buildReturn(env('RETURN_FAIL'), '获取流程错误');
        }
        $info = $info->toArray();

        $type_list = Equipment::where('process_id',$process_id)->where('is_del',0)
            ->select('id','title', 'level', 'parent_id')->get()->toArray();


        $copy_list = $process_copy->getTeacherList($process_id);
        $principal_list = $process_principal->getTeacherList($process_id);

        $data = [
            'info' => $info,
            'type_list' => $type_list,
            'copy_list' => $copy_list,
            'principal_list' => $principal_list
        ];

        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功', $data);
    }

    /**
     * 更新流程
     *
     * @param Request $request
     * @return array
     */
    public function processEdit(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $process_id = $request->input('process_id', '');

        $title = $request->input('title', '');
        $type_list = $request->input('type_list', '');
        $principal_list = $request->input('principal_list', '');
        $copy_list = $request->input('copy_list', '');
        $copy = $request->input('copy', 0);
        $principal = $request->input('principal', 0);
        $child_list = $request->input('child_list');

        if (!$process_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数process_id');
        }
        if (!$title) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数title');
        }
        if (!$copy_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数copy_list');
        }
        if (!$principal_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数principal_list');
        }
        if (!$type_list && !$child_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数type_list');
        }
        $child_list = json_decode($child_list,1);
        if (!$type_list && !$child_list) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数type_list');
        }
        # 判断流程名
        $map = [
            'branch_id' => $branch_id,
            'title' => $title,
            'is_del' => 0
        ];
        $id = Process::where($map)->value('id');
        if ($id && $id != $process_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '流程名称重复');
        }

        if (!$child_list) {
            # 没有子类型，报修类型为一级类型
            $type_list = json_decode($type_list,1);
        } else {
            # 有子类型
            $type_list = [];
            foreach ($child_list as $v) {
                # 获取一级类型下子类型列表
                $map = [
                    'parent_id' => $v['id'],
                    'is_del' => 0,
                    'branch_id' => $branch_id,
                    'level' => 1
                ];
                $child = Equipment::where($map)->select('id', 'title')->get()->toArray();
                foreach ($child as $vv) {
                    if (in_array($vv['id'], $v['child'])) {
                        $type_list[] = $vv['id'];
                    }
                }
            }
        }

        if (!$type_list) {
            return $this->buildReturn(0, '未绑定合法报修类型');
        }
//        $type_list = json_decode($type_list,1);
        $principal_list = json_decode($principal_list,1);
        $copy_list = json_decode($copy_list,1);

        $param = [
            'type_list' => $type_list,
            'principal_list' => $principal_list,
            'copy_list' => $copy_list,
        ];

        $process = new Process();
        $result = $process->editProcess($process_id, $title, $copy, $principal, $branch_id,$param);
        if ($result['state']) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }
        return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
    }
}