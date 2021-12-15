<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/24
 * Time: 17:39
 */

namespace App\Models;


use http\Env;
use App\Models\BranchWx;

class WxWork extends Base
{
    private $corp_id;

    public function __construct($corp_id)
    {
        $this->corp_id = $corp_id;
    }

    /**
     * 获取部门详情
     *
     * @param $dept_id
     * @return false|mixed|string
     */
    public function getWxDeptMemDetail($dept_id, $branch_id, $fetch=1) {
        # 获取信息

        $branch_wx = new BranchWx();
        $token_info = $branch_wx->getAppInfo($branch_id);

        $access_token = $token_info['data']['access_token'];

        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token={$access_token}&department_id={$dept_id}&fetch_child={$fetch}";
        $res = file_get_contents($url);
        $res = json_decode($res, true);

        if ($res['errcode'] == 0) {
            $data = [
                'state' => 1,
                'message' => '',
                'data' => [
                    'user_list' => $res['userlist']
                ]
            ];
            return json_encode($data);
        }
        $data = [
            'state' => 0,
            'message' => '获取失败',
            'data' => [

            ]
        ];
        return json_encode($data);
    }

    /**
     * 获取部门列表
     */
    public function getDeptList($branch_id)
    {
        # 获取信息
        $branch_wx = new BranchWx();
        $token_info = $branch_wx->getAppInfo($branch_id);
        $access_token = $token_info['data']['access_token'];

        $url = "https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token={$access_token}";
        $res = file_get_contents($url);
        $res = json_decode($res, true);
        if ($res['errcode'] == 0) {
            $data = [
                'state' => 1,
                'message' => '',
                'data' => [
                    'dep_list' => $res['department']
                ]
            ];
            return json_encode($data);
        }
        $data = [
            'state' => 0,
            'message' => '获取失败',
            'data' => [

            ]
        ];
        return json_encode($data);
    }
}
