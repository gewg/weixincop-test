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
        $authentication = new Authentication();

        // 获取解码后明文
        $echoStr = $authentication -> verifyUrl($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $verifyEchostr);

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
        $verifyMsgSignature = $request->query('msg_signature');
        $verifyTimestamp = $request->query('timestamp');
        $verifyNonce = $request->query('nonce');
        $verifyEchostr = $request->query('echostr');
        // 获取包体
        $postData = $request->getContent();

        // 获得验证组建
        $authentication = new Authentication();
        // 获取解密后的明文，并转换成simplexml方便处理
        $xml = simplexml_load_string($authentication->getAuthCode($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $postData));

        //$xml = simplexml_load_string($request->getContent());

        // 获取suite id和auth code
        $suiteId = '';
        $authCode = '';
        foreach($xml->children() as $value){
            if ($value->getName() == 'SuiteId'){
                $suiteId = $value;
            }
            else if($value->getName() == 'AuthCode'){
                $authCode = $value->children();
                break;
            }
        }

        // 用authCode获得企业永久授权码


        // 企业微信后台规定回调url处理完要返回success
        return 'success';
    }
}
