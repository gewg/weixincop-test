<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/27
 * Time: 15:21
 */

namespace App\Http\Controllers;

use App\Models\Base;
use Cassandra\Type;
use Illuminate\Http\Request;
use App\Models\Equipment;

class EquipmentController extends Base
{
    /**
     * 添加故障类型
     *
     * @param Request $request
     * @return array
     */
    public function add_type(Request $request)
    {
        $title = $request->input('title', '');
        $branch_id = $request->session()->get('branch_id');
        $child_list = $request->input('child_list');

        if (!$title) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数title');
        }
        $child_list = json_decode($child_list, 1);

        https://campus.sc-edu.com/repair/api/api_dev/public/
        //检查是否有相同的类型
        $map = [
            'branch_id' => $branch_id,
            'title' => $title,
            'is_del' => 0,
            'level' => 0
        ];

        # 没有子分类并且名称重复直接返回类型重复
        $type_id = Equipment::where($map)->value('id');
        if ($type_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '故障类型重复');
        }

        # 类型不存在则直接生成一个
        if (!$type_id) {
            $data = [
                'title' => $title,
                'branch_id' => $branch_id,
                'add_time' => date('Y-m-d H:i:s')
            ];

            $type_id = Equipment::insertGetId($data);
        }

        # 无一级类型返回失败
        if (!$type_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        # 没有子类型直接返回失败
        if (!$child_list) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }

        # 子类型去重
        $child_list = array_unique($child_list);

        # 获取一级类型下所有的子类型
        $map = [
            'parent_id' => $type_id,
            'is_del' => 0,
            'level' => 1,
            'branch_id' => $branch_id
        ];
        $type_child_list = Equipment::where($map)
            ->select('title')
            ->get()->toArray();

        # 检查是否已经有子类型
        $child_list_flip = array_flip($child_list);

        foreach ($type_child_list as $v) {
            if (in_array($v['title'], $child_list)) {
                //todo 删除
                unset($child_list[$child_list_flip[$v['title']]]);
            }
        }

        # 插入子类型
        foreach ($child_list as $v) {
            $data = [
                'title' => $v,
                'branch_id' => $branch_id,
                'level' => 1,
                'parent_id' => $type_id,
                'add_time' => date('Y-m-d H:i:s')
            ];
            $child_id = Equipment::insertGetId($data);
            if (!$child_id) {
                return $this->buildReturn(0, '二级类型'.$v."添加失败");
            }
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }


    /**
     * 获取类型列表
     *
     * @param Request $request
     * @return array
     */
    public function typeList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $page = $request->input('offset',1);
        $limit = $request->input('limit', 10);

        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0,
            'level' => 0
        ];
        $offset = ($page - 1)*$limit;
        $total = Equipment::where($map)->count();
        $page_total = Equipment::where($map)->paginate($limit)->lastPage();
        $type_list = Equipment::where($map)
            ->offset($offset)->limit($limit)
            ->orderBy('add_time','DESC')->select('id as type_id','title')
            ->get()->toArray();
        # 查询子分类
        foreach ($type_list as $k =>$v) {
            $where = [
                'level' => 1,
                'is_del' => 0,
                'branch_id' => $branch_id,
                'parent_id' => $v['type_id']
            ];
            $child_list = Equipment::where($where)->select('id as chile_type_id','title')->get()->toArray();
            $child = [];
            foreach ($child_list as $vv) {
                $child[] = $vv['title'];
            }
            $child = join(',', $child);
            $type_list[$k]['child'] = $child;
            $type_list[$k]['child_list'] = $child_list;
        }

        $data = [
            'page_total' => $page_total,
            'page' => $page,
            'total' => $total,
            'type_list' => $type_list,
        ];
        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功', $data);

    }

    /**
     * 删除故障类型
     *
     * @param Request $request
     * @return array
     */
    public function delType(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $type_id = $request->input('type_id',0);

        if (!$type_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数type_id');
        }

        $map = [
            'is_del' => 0,
            'branch_id' => $branch_id,
            'id' => $type_id
        ];

        # 检查类型是否存在
        $check = Equipment::where($map)->select('id', 'level')->first();
        if (!$check) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }
        $type_info = $check->toArray();

        # 更新类型
        $equipment = new Equipment();
        $result = $equipment->updateType($type_id,['is_del'=>1]);
        if ($result['state'] != 1) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        # 判断子类型
        if ($type_info['level'] == 1) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }

        Equipment::where('is_del', 0)
            ->where('parent_id', $type_id)
            ->update(['is_del' => 1, 'del_time' => date('Y-m-d H:i:s')]);
        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }

    /**
     * 修改故障类型
     *
     * @param Request $request
     * @return array
     */
    public function editType(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $type_id = $request->input('type_id',0);
        $title = $request->input('title',0);
        $child_list = $request->input('child_list');

        if (!$type_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数type_id');
        }
        if (!$title) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数title');
        }

        # 检查类型是否存在
        $map = [
            'is_del' => 0,
            'branch_id' => $branch_id,
            'id' => $type_id
        ];

        $check = Equipment::where($map)->select('title', 'id', 'level', 'parent_id')->first();
        if (!$check) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }
        $title_original = $check->title;

        # 更新
        $equipment = new Equipment();
        if ($title != $title_original) {
            //检查是否有相同的类型
            $map = [
                'branch_id' => $branch_id,
                'title' => $title,
                'is_del' => 0
            ];

            # 二级类型，在一级类型下不重复
            if ($check->level == 1) {
                $map['level'] = 1;
                $map['parent_id'] = $check->parent_id;
            }
            $id = Equipment::where($map)->value('id');

            # 检查类型是否重复
            if ($id && $id  != $type_id) {
                if ($check->level == 1) {
                    return $this->buildReturn(env('RETURN_FAIL'), '二级故障类型重复');
                }
                return $this->buildReturn(env('RETURN_FAIL'), '故障类型重复');
            }

            $equipment->updateType($type_id,['title'=>$title]);
        }


        # 如果是修改二级类型，更新后直接返回成功
        if ($check->level == 1) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }

        # 没有二级类型直接返回成功
        if (!$child_list) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }
        $child_list = json_decode($child_list, 1);
        # 解析json为空直接返回成功。
        if (!$child_list) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }

        $child_list = array_unique($child_list);
        # 一级类型获取子类型
        $map = [
            'level' => 1,
            'parent_id' => $type_id,
            'is_del' => 0
        ];
        $child_list_original = Equipment::where($map)
            ->select('title', 'id')->get()
            ->toArray();

        $child_title_list_original = [];

        # 遍历原子类型，新列表中没有的类型删除
        foreach ($child_list_original as $v) {
            if (!in_array($v['title'], $child_list)) {
                // 删除该子类型
                Equipment::where('id', $v['id'])->update(['is_del' => 1]);
            } else {
                $child_title_list_original[] = $v['title'];
            }
        }

        # 遍历新子类型，原列表中没有的类型添加
        foreach ($child_list as $v) {
            if (!in_array($v, $child_title_list_original)) {
                // 添加新子类型
                Equipment::insertGetId([
                    'title' => $v,
                    'branch_id' => $branch_id,
                    'level' => 1,
                    'parent_id' => $type_id,
                    'add_time' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }

    /**
     * 获取故障类型列表
     *
     * @param Request $request
     * @return array
     */
    public function getList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $type_id = $request->input('type_id',0);
        $type_list = $request->input('type_list',0);
        $type_list = json_decode($type_list, 1);

        $map = [
            'is_del' => 0,
            'branch_id' => $branch_id,
            'level' => 0
        ];
        $list = Equipment::where($map)
            ->where('process_id', '=', 0)
            ->select('id','title')->get()->toArray();
        $data = [];
        # 判断二级是否全部绑定
        foreach ($list as $v) {
            $map = [
                'is_del' => 0,
                'parent_id' => $v['id']
            ];
			$has_child = Equipment::where($map)->first();
 			if (!$has_child) {
				$data[] = $v;
				continue;
			}
            $child = Equipment::where($map)
                ->where('process_id', 0)->first();
 			if ($type_list && in_array($v['id'], $type_list)) {
 			    continue;
            }
            if ($child) {
                $data[] = $v;
            }
        }
        if ($type_id){
//            $info = Equipment::where('id', $type_id)->select('id','title')->first();
//            $data[] = [
//                'id' => $info->id,
//                'title' => $info->title
//            ];
        }


        if ($type_list) {
            foreach ($type_list as $v) {
                $info = Equipment::where('id', $v)->select('id','title')->first();
                $data[] = [
                    'id' => $info->id,
                    'title' => $info->title
                ];
            }
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功', ['list' => $data]);
    }

    /**
     * 获取故障类型列表
     *
     * @param Request $request
     * @return array
     */
    public function getTypeList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');

        $map = [
            'is_del' => 0,
            'branch_id' => $branch_id,
            'level' => 0
        ];
        $list = Equipment::where($map)
            ->select('id','title')->get()->toArray();
        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功', ['list' => $list]);
    }

    /**
     * Description：获取子类型
     * @param Request $request
     * @return array
     * Author:xiaoxiaonan
     * Date: 2019/12/13 10:29
     */
    public function getChildTypeList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $type_id = $request->input('type_id',0);
        $type = $request->input('type',0);
        $list = $request->input('list',0);

        if (!$type_id) {
            return $this->buildReturn(0, '缺少参数type_id');
        }

        # 检查是否存在
        $map = [
            'id' => $type_id,
            'branch_id' => $branch_id,
            'level' => 0,
            'is_del' => 0
        ];
        $type_info = Equipment::where($map)->select('title')->first();
        if (!$type_info) {
            return $this->buildReturn(0, '报修类型不存在');
        }

        $list = json_decode($list, 1);

        $choose_list = [];
        if ($list) {
            $choose_list = Equipment::whereIn('id', $list)
                ->select('id as child_id', 'title')->get()->toArray();
        }


        # 获取子类型
        $map = [
            'is_del' => 0,
            'level' => 1,
            'parent_id' => $type_id
        ];
        $child_list = Equipment::where($map);
        if ($type == 2) {
            $child_list = $child_list->where('process_id', 0);
        }
        $child_list = $child_list->select('id as child_id', 'title')->get()->toArray();

        if ($choose_list) {
            $child_list = array_merge($child_list, $choose_list);
        }

        return $this->buildReturn(1, '数据获取成功',
            [
                'type_id' => $type_id,
                'type_title' => $type_info->title,
                'child_list' => $child_list
            ]
        );
    }
}
