<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/27
 * Time: 10:47
 */

namespace App\Http\Controllers;

use App\Models\Base;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use App\Models\Teacher;
use App\Models\BranchCampus;
use App\Models\Branch;
use App\Models\WxWork;

class TeacherController extends Base
{

    public function getinfo(Request $request)
    {
        $teacher_id = $request->session()->get('teacher_id');
        $branch_id = $request->session()->get('branch_id');
        var_dump($teacher_id);
        var_dump($branch_id);
    }
    /**
     * 获取登录用户信息
     *
     * @param Request $request
     * @return array
     */
    public function userInfo(Request $request)
    {
        $teacher_id = $request->session()->get('teacher_id');
        $branch_id = $request->session()->get('branch_id');


        //获取用户信息
        $map = [
            'id' => $teacher_id,
            'is_del' => 0
        ];
        $user_info = Teacher::where($map)->select('id','title as name','power','avatar','dept_title')->first()->toArray();

        //获取中心信息
        $map = [
            'id' => $branch_id,
            'is_del' => 0
        ];
        $branch_info = Branch::where($map)->select('title','logo','branch_no','id')->first()->toArray();

        $data = [
            'user_info' => $user_info,
            'branch_info' => $branch_info
        ];
        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功',$data);
    }

    /**
     * 管理员列表
     *
     * @param Request $request
     * @return array
     */
    public function searchAdmin(Request $request)
    {
        $key_word = $request->input('key_word', '');
        $branch_id = $request->session()->get('branch_id');

        $page = $request->input('offset',1);
        $limit = $request->input('limit', 10);

        //todo 判断是否为管理员

        $offset = ($page - 1)*$limit;
        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0,
            'power' => 1
        ];

        $teacher_s = Teacher::where($map);

        $total = $teacher_s->count();
        $last_page = $teacher_s->paginate($limit)->lastPage();

        if ($key_word) {
            $teacher_s = Teacher::where($map)->where("title",'like',"%{$key_word}%");
            $total = $teacher_s->count();
            $last_page = $teacher_s->paginate($limit)->lastPage();
        }

        $teacher_list = $teacher_s->select('id','title as name','tel')
            ->orderBy('update_time',"DESC")
            ->offset($offset)->limit($limit)
            ->get()->toArray();



        foreach ($teacher_list as $k=>$v) {
            $teacher_list[$k]['power'] = '管理员';
        }

        $data = [
            'teacher' => $teacher_list,
            'total' => $total,
            'page' => $offset+1,
            'page_total' => $last_page
        ];
        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功',$data);
    }

    /**
     * 搜索用户
     *
     * @param Request $request
     * @return array
     */
    public function searchTeacher(Request $request)
    {
        $key_word = $request->input('key_word', '');
        $branch_id = $request->session()->get('branch_id');

        if (!$key_word) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功',['teacher_list' => []]);
        }

        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0,
            'power' => 0
        ];
        $teacher_list = Teacher::where($map)->where(function ($q) use ($key_word) {
            $q->where('title', 'like',"%{$key_word}%")
                ->orWhere('tel', 'like',"%{$key_word}%");
        })->select('id','title','tel')->limit(5)->get()->toArray();

        return $this->buildReturn(env('RETURN_SUCCESS'), '数据获取成功',['teacher_list' => $teacher_list]);
    }

    /**
     * 添加管理员
     *
     * @param Request $request
     * @return array
     */
    public function addAdmin(Request $request)
    {
        $teacher_id = $request->input('teacher_id', '');
        $branch_id = $request->session()->get('branch_id');

        if (!$teacher_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数teacher_id');
        }
        //教师是否存在
        $map = [
            'branch_id' => $branch_id,
            'id' => $teacher_id,
            'is_del' => 0
        ];
        $teacher_info = Teacher::where($map)->first();
        if (!$teacher_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        //更新用户权限
        $data = [
            'power' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ];
        $update_result = Teacher::where('id',$teacher_id)->update($data);

        if (!$update_result) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }

    /**
     * 添加管理员
     *
     * @param Request $request
     * @return array
     */
    public function delAdmin(Request $request)
    {
        $teacher_id = $request->input('teacher_id', '');
        $branch_id = $request->session()->get('branch_id');

        if (!$teacher_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数teacher_id');
        }
        //教师是否存在
        $map = [
            'branch_id' => $branch_id,
            'id' => $teacher_id,
            'is_del' => 0
        ];
        $teacher_info = Teacher::where($map)->first();
        if (!$teacher_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        //更新用户权限
        $data = [
            'power' => 0,
            'update_time' => date('Y-m-d H:i:s')
        ];
        $update_result = Teacher::where('id',$teacher_id)->update($data);

        if (!$update_result) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }

    /**
     * 编辑管理员
     *
     * @param Request $request
     * @return array
     */
    public function editAdmin(Request $request)
    {
        $teacher_id = $request->input('teacher_id', '0');
        $admin_id = $request->input('admin_id','0');
        $branch_id = $request->session()->get('branch_id');

        if (!$teacher_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数teacher_id');
        }

        if (!$admin_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数admin_id');
        }

        //教师是否存在
        $map = [
            'branch_id' => $branch_id,
            'id' => $teacher_id,
            'is_del' => 0
        ];
        $teacher_info = Teacher::where($map)->first();
        if (!$teacher_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        //更新用户权限
        $data = [
            'power' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ];
        $update_result = Teacher::where('id',$teacher_id)->update($data);

        if (!$update_result) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }


        //教师是否存在
        $map = [
            'branch_id' => $branch_id,
            'id' => $admin_id,
            'is_del' => 0
        ];
        $teacher_info = Teacher::where($map)->first();
        if (!$teacher_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        //更新用户权限
        $data = [
            'power' => 0,
            'update_time' => date('Y-m-d H:i:s')
        ];
        $update_result = Teacher::where('id',$admin_id)->update($data);

        if (!$update_result) {
            return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
    }

    /**
     * 获取用户列表
     *
     * @param Request $request
     * @return array
     */
    public function userList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $key_word = $request->input('key_word','0');

        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0
        ];

        $user_list = Teacher::where($map);
        if ($key_word) {
            $user_list->where('title','like',"%{$key_word}%");
        }
        $list = $user_list->select('id','title','avatar')->get()->toArray();

        if ($user_list) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功', ['user_list' => $list]);
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功', ['user_list' => []]);
    }

    /**
     * 获取部门列表
     *
     * @param Request $request
     * @return array
     */
    public function getDeptList(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');

        $map = [
            'is_del' => 0,
            'branch_id' => $branch_id
        ];

        $list = Teacher::where($map)->select('dept_title')->distinct()->get()->toArray();

        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功', ['dept_list' => $list]);
    }

    public function getDeptTeacher(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $dept_title = $request->input('dept_title','0');

        $map = [
            'is_del' => 0,
            'branch_id' => $branch_id,
            'dept_title' => $dept_title
        ];
        $list = Teacher::where($map)->select('id','title')->get()->toArray();
        return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功', ['teacher_list' => $list]);
    }
}