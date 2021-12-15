<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/24
 * Time: 17:09
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BranchCampus extends Base
{
    protected $table = 'branch_campus';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    private $branch_id = 0;

    public function __construct($branch_id)
    {
        $this->branch_id = $branch_id;
    }
	
	public function getCampusInfo()
	{
		$campus_info = $this->where([
            'branch_id' => $this->branch_id,
            //'branch_id' => 12,
            'is_del' => 0,
        ])->first();
		if ($campus_info) {
			return $campus_info->toArray();
		}
		return [];
	}
    /**
     * 获取部门列表
     *
     * @return array
     */
    public function getTeacherDeptList()
    {
        $campus_info = $this->where([
            'branch_id' => $this->branch_id,
            'is_del' => 0,
        ])->first();
        if ($campus_info) {
            $campus_info = $campus_info->toArray();
        } else {
            return $this->buildReturn(env('RETURN_FAIL'), '');
        }
        if (!$campus_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '无campus配置');
        }

        $object_id = $campus_info['object_id'];
        $obj_type = $campus_info['obj_type'];
        $dev_code = $campus_info['dev_code'];
        $dev_type = $campus_info['dev_type'];
        $dev_key = $campus_info['dev_key'];

        $timestamp = time();
		$user_type = 2;
        $arr = [
            'timestamp' => $timestamp,
            'objectid' => $object_id,
            'objType' => $obj_type,
            'devCode' => $dev_code,
            'devType' => $dev_type,
            'usertype' => $user_type,
        ];
        $sign = $this->buildSign($arr, $dev_key);

        $url = "https://open.campus.qq.com/api/open/getDepartmentInfoList?"
            ."devCode={$dev_code}&devType={$dev_type}&objType={$obj_type}&objectid={$object_id}"
            ."&timestamp={$timestamp}&usertype={$user_type}&sign={$sign}";
        $result_json = file_get_contents($url);
        $res = json_decode($result_json,true);

        if($res['code'] != 0){
            return $this->buildReturn(env('RETURN_FAIL'), $res['msg']);
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $res['data']);
    }

    /**
     * 腾讯智慧校园签名
     *
     * @param $signData
     * @param $key
     * @return bool|string
     */
    protected function buildSign($signData, $key) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $strSign = 'sign';
        if (empty($signData) || empty($key)) {
            return false;
        }
        if (is_string($signData)) {
            $urlstr = urldecode($signData);
            $urlarr = explode('&', $urlstr);
            foreach($urlarr as $k => $v){
                if (strpos($v, $strSign) !== false) {
                    unset($urlarr[$k]);
                }
            }
        }
        if (is_array($signData)) {
            if (isset($signData[$strSign])) {
                unset($signData[$strSign]);
            }
            $urlstr = urldecode(http_build_query($signData));
            $urlarr = explode('&', $urlstr);
        }
        sort($urlarr, SORT_STRING);
        $arg = implode('&', $urlarr);
        $sign = strtoupper(md5($arg . '&key=' . $key));//把最终的字符串签名，获得签名结果
        return $sign;
    }

    public function getDepartList($dept)
    {
        $dept_list = [];
        foreach ($dept as $v) {
            if (isset($v['child'])) {
                $list = $this->getDepartList($v['child']);
                foreach ($list as $vv) {
                    $dept_list[] = $vv;
                }
            } else {
                $dept_list[] = $v;
            }
        }
        return $dept_list;
    }

    public function getCampusUserInfo($campus_user_id)
    {
        $campus_info = $this->where([
            'branch_id' => $this->branch_id,
            'is_del' => 0,
        ])->first();

        if (!$campus_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '无campus配置');
        }

        $object_id = $campus_info['object_id'];
        $obj_type = $campus_info['obj_type'];
        $dev_code = $campus_info['dev_code'];
        $dev_type = $campus_info['dev_type'];
        $dev_key = $campus_info['dev_key'];

        $timestamp = time();
        $arr = [
            'timestamp' => $timestamp,
            'objectid' => $object_id,
            'objType' => $obj_type,
            'devCode' => $dev_code,
            'devType' => $dev_type,
            'userid' => $campus_user_id,
        ];
        $sign = $this->buildSign($arr, $dev_key);

        $url = "https://open.campus.qq.com/api/open/getUserInfo?"
            ."devCode={$dev_code}&devType={$dev_type}&objType={$obj_type}&objectid={$object_id}"
            ."&timestamp={$timestamp}&userid={$campus_user_id}&sign={$sign}";
        $result_json = file_get_contents($url);
        $res = json_decode($result_json,true);

        if($res['code'] != 0){
            return $this->buildReturn(env('RETURN_FAIL'), $res['msg']);
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $res['data']);
    }

    /**
     * 在智慧校园系统内查询用户
     * @param int $search_type 搜索类型。1-按wxuserid搜索；2-按手机号搜索；3-按学号/教工号搜索
     * @param string $keyword 搜索关键字，完全匹配。
     * @param int $user_type
     * @return array
     */
    public function searchUser($search_type, $keyword, $user_type = 0)
    {
        $campus_info = $this->where([
            'branch_id' => $this->branch_id,
            'is_del' => 0,
        ])->first();

        if (!$campus_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '无campus配置');
        }

        $object_id = $campus_info['object_id'];
        $obj_type = $campus_info['obj_type'];
        $dev_code = $campus_info['dev_code'];
        $dev_type = $campus_info['dev_type'];
        $dev_key = $campus_info['dev_key'];

        $timestamp = time();
        $arr = [
            'timestamp' => $timestamp,
            'objectid' => $object_id,
            'objType' => $obj_type,
            'devCode' => $dev_code,
            'devType' => $dev_type,
            'searchtype' => $search_type,
            'keyword' => $keyword,
        ];
        if ($user_type) {
            $arr['usertype'] = $user_type;
        }
        $sign = $this->buildSign($arr, $dev_key);
        $url = "https://open.campus.qq.com/api/open/searchUser?"
            ."devCode={$dev_code}&devType={$dev_type}&keyword={$keyword}&objType={$obj_type}"
            ."&objectid={$object_id}&searchtype={$search_type}&timestamp={$timestamp}";
        if ($user_type) {
            $url .= "&usertype={$user_type}";
        }
        $url .= "&sign={$sign}";
        $result_json = file_get_contents($url);
        $res = json_decode($result_json,true);
        if($res['code'] != 0){
            return $this->buildReturn(env('RETURN_FAIL'), $res['msg']);
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $res['data']);
    }

    public function searchUserList($search_type, $dept_id, $level)
    {
        $campus_info = $this->where([
            'branch_id' => $this->branch_id,
            'is_del' => 0,
        ])->first();

        if (!$campus_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '无campus配置');
        }

        $object_id = $campus_info['object_id'];
        $obj_type = $campus_info['obj_type'];
        $dev_code = $campus_info['dev_code'];
        $dev_type = $campus_info['dev_type'];
        $dev_key = $campus_info['dev_key'];

        $timestamp = time();
        $arr = [
            'timestamp' => $timestamp,
            'objectid' => $object_id,
            'objType' => $obj_type,
            'devCode' => $dev_code,
            'devType' => $dev_type,
            'usertype' => $search_type,
            'departid' => $dept_id,
            'level' => $level,
            'pageSize' => 100
        ];
        $sign = $this->buildSign($arr, $dev_key);
        $url = "https://open.campus.qq.com/api/common/searchUser?"
            ."departid={$dept_id}&devCode={$dev_code}&devType={$dev_type}&level={$level}&objType={$obj_type}"
            ."&objectid={$object_id}&pageSize=100&usertype={$search_type}&timestamp={$timestamp}&sign={$sign}";
        $result_json = file_get_contents($url);
        $res = json_decode($result_json,true);
        if($res['code'] != 0){
            return $this->buildReturn(env('RETURN_FAIL'), $res['msg']);
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $res['data']);
    }

    public function getDeptInfo($dept_id)
    {
        $campus_info = $this->where([
            'branch_id' => $this->branch_id,
            'is_del' => 0,
        ])->first();

        if (!$campus_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '无campus配置');
        }
        $object_id = $campus_info['object_id'];
        $obj_type = $campus_info['obj_type'];
        $dev_code = $campus_info['dev_code'];
        $dev_type = $campus_info['dev_type'];
        $dev_key = $campus_info['dev_key'];

        $timestamp = time();
        $arr = [
            'timestamp' => $timestamp,
            'objectid' => $object_id,
            'objType' => $obj_type,
            'devCode' => $dev_code,
            'devType' => $dev_type,
            'departid' => $dept_id,
			'devType' => $dev_type
        ];
        $sign = $this->buildSign($arr, $dev_key);
        $url = "https://open.campus.qq.com/api/open/getDepartInfoById?"
            ."departid={$dept_id}&devCode={$dev_code}&devType={$dev_type}&objType={$obj_type}"
            ."&objectid={$object_id}&timestamp={$timestamp}&sign={$sign}&devType={$dev_type}";
        $result_json = file_get_contents($url);
        $res = json_decode($result_json,true);
        if($res['code'] != 0){
            return $this->buildReturn(env('RETURN_FAIL'), $res['msg']);
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $res['data']);
    }
	public function getSchoolInfo()
    {
        $campus_info = $this->where([
            'branch_id' => $this->branch_id,
            'is_del' => 0,
        ])->first();
        if (!$campus_info) {
            return $this->buildReturn(env('RETURN_FAIL'), '无campus配置');
        }
        $object_id = $campus_info['object_id'];
        $obj_type = $campus_info['obj_type'];
        $dev_code = $campus_info['dev_code'];
        $dev_type = $campus_info['dev_type'];
        $dev_key = $campus_info['dev_key'];

        $timestamp = time();
        $arr = [
            'timestamp' => $timestamp,
            'objectid' => $object_id,
            'objType' => $obj_type,
            'devCode' => $dev_code,
            'devType' => $dev_type,
        ];
        $sign = $this->buildSign($arr, $dev_key);
        $url = "https://open.campus.qq.com/api/open/getObjectInfo?"
            ."devCode={$dev_code}&devType={$dev_type}&objType={$obj_type}"
            ."&objectid={$object_id}&timestamp={$timestamp}&sign={$sign}&devType={$dev_type}";
        $result_json = file_get_contents($url);
        $res = json_decode($result_json,true);
        if($res['code'] != 0){
            return $this->buildReturn(env('RETURN_FAIL'), $res['msg']);
        }
		$url = $res['data']['logo'];
		$data = [];
		if(preg_match("/^(http:\/\/|https:\/\/).*$/",$url)){
			$data['url'] = $url;
		} else {
			$data['url'] = "http://p.qpic.cn/smartcampus/0/{$url}/0";
		}
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $data);
    }

}
