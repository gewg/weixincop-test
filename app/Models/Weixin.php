<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/30
 * Time: 9:33
 */

namespace App\Models;


use http\Env;
use App\Models\BranchWx;

class Weixin extends Base
{
    private $branch_id;

    public function __construct($branch_id = 0)
    {
        $this->branch_id = $branch_id;
    }

    /**
     * @param $media_id
     * @return array
     */
    public function getImgUrlByMediaId($media_id)
    {
        $branch = new BranchWx();
        $res = $branch->getAppInfo($this->branch_id);
        if ($res['state'] == env('RETURN_FAIL')) {
            return $this->buildReturn(env('RETURN_FAIL'), $res['message']);
        }

        $access_token = $res['data']['access_token'];
        $url = "https://qyapi.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$media_id}";
        $arr = $this->curl_file($url);
        $res = json_decode($arr, true);

        if ($res && $res['errcode']) {
            return $this->buildReturn(env('RETURN_FAIL'), $res['errmsg']);
        }
        $res = $this->saveFile($arr);
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $res);
    }
    //curl 获取文件数据
    private function curl_file($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0); // 只取body头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // curl_exec执行成功后返回执行的结果；不设置的话，curl_exec执行成功则返回true
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //保存文件到本地
    private function saveFile($file_content)
    {
        $root = '/opt/ci123/www/html/sc-edu/upload/campus/repair/';
        $dir = date('Y/m/d');
        $name = md5(uniqid().mt_rand()).'.jpg';
        if (!is_dir($root.'/'.$dir)) {
            mkdir($root.'/'.$dir, 0775, true);
        }
        $new_file = $root.'/'.$dir.'/'.$name;

        $file = fopen($new_file,"w");
        fwrite($file, $file_content);
        fclose($file);
        $file_url = "https://cdn.sc-edu.com/campus/repair/" . $dir . '/' . $name;
        return $file_url;
    }

    public function sendCardMsg($to_user, $title, $description, $url, $btntxt = '查看详情')
    {
        $branch_id = $this->branch_id;
        $branch_wx = new BranchWx();
        $app_info = $branch_wx->getAppInfo($this->branch_id);

        $agent_id = $app_info['data']['agent_id'];
        $data = [
            'touser' => $to_user,
            'agentid' => $agent_id,
            'msgtype' => 'textcard',
            'textcard' => [
                'title' => $title,
                'description' => $description,
                'url' => $url,
                'btntxt' => $btntxt
            ],
        ];
        $access_token = $app_info['data']['access_token'];
        $http_url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$access_token}";

        $res = json_decode($this->httpRequestJson($http_url,json_encode($data)),1);

		$file = "/opt/ci123/www/html/sc-edu/tmp/campus/repair/send.log";
		$log = json_encode($data). "\n".json_encode($res);
		file_put_contents($file, date('Y-m-d H:i:s')." {$log} \n\n", FILE_APPEND);
        $is_success = $res['errcode'] == 0 ? env('RETURN_SUCCESS') : env('RETURN_FAIL');
        return $this->buildReturn($is_success, $res['errmsg'], $res);
    }
}
