<?php

namespace App\Http\Controllers;

use App\Models\Base;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;
use App\Models\Authentication;

class AuthenticationController extends Base{

    /**
     * 在企业设置回调url时, 企业微信后台验证url的可用性
     * @param Request $request
     */
    public function checkUrlVerification(Request $request){

        // 获取url中的参数
        $verifyMsgSignature = $request->query('msg_signature');
        $verifyTimestamp = $request->query('timestamp');
        $verifyNonce = $request->query('nonce');
        $verifyEchostr = $request->query('echostr');

        // 获得验证组建
        $echoStr = new Authentication($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $verifyEchostr);

        // 若验证不通过, echoStr会被赋值为-1
        // 验证成功则response明文
        if ($echoStr != -1){
            return $echoStr;
        }
    }

    /**
     * 用户企业授权第三方服务商开发时, 接收企业微信后台推送的临时授权码
     * @param Request $request
     * @return string|void
     */
    public function getAuthCode(Request $request){

        // 获取url中的参数
        $msg_signature = $request->query('msg_signature');
        $timestamp = $request->query('timestamp');
        $nonce = $request->query('nonce');
        $echostr = $request->query('echostr');

        // 返回给企业微信后台的明文
        $res_echostr = "";

        // 验证并解密
        $wxcpt = new WXBizMsgCrypt(TOKEN, ENCODING_AES_KEY, CORP_ID);
        $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $res_echostr);

        // 若验证成功, 则打印并返回
        if ($errCode == 0) {
            var_dump($res_echostr);
            return $res_echostr;
        } else {
            print("ERR: " . $errCode . "\n\n");
        }
    }
}
