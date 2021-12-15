<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/30
 * Time: 9:49
 */


namespace App\Http\Controllers;

use App\Models\Base;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\BranchWx;
use App\Models\Weixin;

class WeixinController extends Base
{
    public function getJSSDK(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $url = $request->input('url', '');

        //获取corp
        $branch_info = Branch::where('id', $branch_id)->first();
        $corp_id = $branch_info['corp_id'];
        $timestamp = time();

        //获取jssdk
        $branch_wx = new BranchWx();
        $res = $branch_wx->getJsApiTicket($branch_id);

        if ($res['state'] == env('RETURN_FAIL')) {
            return $this->buildReturn(env('RETURN_FAIL'), $res['message']);
        }
        $ticket = $res['data'];
        $noncestr = md5($timestamp);
        $str = "jsapi_ticket={$ticket}&noncestr={$noncestr}&timestamp={$timestamp}&url={$url}";
        $signature = sha1($str);
        return $this->buildReturn(env('RETURN_SUCCESS'), '', [
            'appId' => $corp_id,
            'timestamp' => $timestamp,
            'nonceStr' => $noncestr,
            'signature' => $signature,
        ]);
    }

    public function getImgUrl(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $media_id = $request->input('media_id');

        if(!$media_id){
            return $this->buildReturn(env('RETURN_FAIL'),'缺少参数media_id');
        }

        $weixin = new Weixin($branch_id);
        $res = $weixin->getImgUrlByMediaId($media_id);
        if ($res['state'] == env('RETURN_FAIL')) {
            return $this->buildReturn(env('RETURN_FAIL'), $res['message']);
        }

        return $this->buildReturn(env('RETURN_SUCCESS'), '', [
            'img_url' => $res['data'],
        ]);
    }
}
