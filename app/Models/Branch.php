<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/23
 * Time: 17:23
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

use App\Models\BranchWx;

class Branch extends Base
{
    protected $table = 'branch';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    private $branch_id;

    public function __construct($branch_id = 0)
    {
        if ($branch_id) {
            $this->branch_id = $branch_id;
        }
    }

    /**
     * 检查branch_no
     *
     * @param $branch_no
     * @return array
     */
    public function checkBranchNo($branch_no)
    {
        $map = [
            'branch_no' => $branch_no,
            'is_del' => 0
        ];
        $this->branch_id = $this->where($map)->value('id');
        $branch_info = $this->where($map)->select('id', 'state')->first();
		if (!$branch_info) {	
            return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
		}
		$this->branch_id = $branch_info->id;
		if ($branch_info->state == 1) {
			echo "<script>alert('请联系管理员开放权限'); window.close();</script>";
			exit();
		}
        if (!$this->branch_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
        }

		# 最后使用时间
		$this->where('id', $this->branch_id)->update(['last_login' => date('Y-m-d H:i:s')]);
        $data = [
            'branch_id' => $this->branch_id
        ];
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $data);
    }

    /**
     * 获取机构企业微信配置
     *
     * @return array
     */
    public function getBranchWxInfo()
    {
        $branch_wx = new BranchWx();
        $app_info = $branch_wx->getAppInfo($this->branch_id);
        return $this->buildReturn($app_info['state'], $app_info['message'], $app_info['data']);
    }

    /**
     * 中心用户登录
     *
     * @return array
     */
    public function userLogin()
    {
        $branch_wx = new BranchWx();
        $branch_wx->getUserInfo($this->branch_id);
        return $this->buildReturn(env('RETURN_SUCCESS'), '');
    }
}
