<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/24
 * Time: 12:01
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\BranchWx;
use Illuminate\Support\Facades\Crypt;
use App\Models\BranchCampus;

class Teacher extends Base
{
    protected $table = 'teacher';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    private $branch_id = 0;

    public function __construct($branch_id = 0)
    {
        if ($branch_id) {
            $this->branch_id = $branch_id;
        }
    }

    /**
     * 中心用户登录
     *
     * @return array
     */
    public function userLogin($code)
    {
        $branch_wx = new BranchWx();
        $result = $branch_wx->getUserInfo($this->branch_id, $code);
		if (!$result['state']) {
			return $this->buildReturn(env('RETURN_FAIL'), '登录失败');
		}

		$map = [
            'branch_id' => $this->branch_id,
            'wx_user_id' => $result['data']['UserId'],
            'is_del' => 0,
        ];
		$teacher_id = $this->where($map)->value('id');
		if (!$teacher_id) {
		    //获取腾讯智慧校园用户信息
            $campus = new BranchCampus($this->branch_id);
			
            $user_result = $campus->searchUser(1,$result['data']['UserId']);
			
			if (!$user_result['state']) {
                return $this->buildReturn(env('RETURN_FAIL'), '用户信息获取错误');
			}
			$user_info = $campus->getCampusUserInfo($user_result['data'][0]['userid']); 
			if (!$user_info['data']['name']) {
                return $this->buildReturn(env('RETURN_FAIL'), '用户信息获取错误');
            }
			$dep_info = $campus->getDeptInfo($user_info['data']['departid']);
			$user_data = [
				'branch_id' => $this->branch_id,
				'title' => $user_info['data']['name'] ? : '',
				'tel' => $user_info['data']['cellphone'] ? : '',
				'wx_user_id' => $user_info['data']['wxuserid'],
				'campus_user_id' => $user_result['data'][0]['userid'],
				'avatar' => $user_info['data']['head'],
				'add_time' => date('Y-m-d H:i:s')
			];
			$dep_info = $campus->getDeptInfo($user_info['data']['departid']);
			$user_data['dept_title'] = $dep_info['data']['departname'];
			$teacher_id = $this->insertGetId($user_data);
			if ($user_info['state'] != '1') {
            	return $this->buildReturn(env('RETURN_FAIL'), '您暂无权限，请联系管理员');
			}
        }
		# 设置session
        $data = [
            'user_id' => $teacher_id,
            'branch_id' => $this->branch_id,
            'wx_user_id' => $result['data']['UserId']
        ];
        return $this->buildReturn(env('RETURN_SUCCESS'), '获取成功', $data);
    }

    public function getCampusUserId($branch_id, $wx_user_id)
    {
        $campus_user_id = $this->where([
            'branch_id' => $branch_id,
            'wx_user_id' => $wx_user_id,
            'is_del' => 0,
        ])->value('campus_user_id');

        if ($campus_user_id) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '', $campus_user_id);
        }

        $campus = new BranchCampus($branch_id);
        $res = $campus->searchUser(1, $wx_user_id);

        if ($res['state'] == env('RETURN_FAIL')) {
            return $this->buildReturn(env('RETURN_FAIL'), $res['message']);
        }

        $campus_info = $res['data'][0];

        return $this->buildReturn(env('RETURN_SUCCESS'), '', $campus_info['userid']);
    }

}
