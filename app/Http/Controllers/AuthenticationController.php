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
     * GET Request
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
     * 企业微信后台总共会推送两种类型的requests
     * 1. 用户企业授权第三方服务商开发时, 接收企业微信后台推送的临时授权码
     * 2. 每10分钟推送一次的suiteTicket
     * POST Request
     * @param Request $request
     * @return string|void
     */
    public function getAuthOrTicket(Request $request){

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

        // 获取suite_id和auth_code 或者 suite_ticket
        $suiteId = '';
        $authCode = '';
        $suiteTicket = '';
        foreach($xml->children() as $value){
            // 获取临时授权码
            if ($value->getName() == 'SuiteId'){
                $suiteId = $value;
            }
            else if($value->getName() == 'AuthCode'){
                $authCode = $value;
            }
            // 更新suiteTicket
            else if($value->getName() == 'SuiteTicket'){
                $suiteTicket = $value;
            }
        }

        // 保存到数据库, 因为有两种可能发生的推送, 所以authCode或suiteTicket可能为空
        if ($authCode != ''){
            // Todo:保存authCode到数据库
        }else{
            // Todo:保存suiteTicket到数据库
        }

        // 企业微信后台规定回调url处理完要返回success
        return 'success';
    }

    /**
     *
     * @param Request $request
     * @return void
     */
    private function getSuiteTicket(Request $request){

    }
}
