<?php

namespace App\Http\Controllers;
require dirname(__FILE__).'./../../../vendor/callback/WXBizMsgCrypt.php';

use App\Models\Base;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;
use App\Models\Authentication;
use App\Models\WXBizMsgCrypt;
use Illuminate\Support\Facades\DB;

class AuthenticationController extends Base{

    /**
     * 在企业设置回调url时, 企业微信后台验证url的可用性
     * GET Request
     * @param Request $request
     * @return string 直接返回给微信后台的明文
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
        // 验证成功则直接response明文给微信后台
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

        // 获取包体
        $postData = $request->getContent();

        // // 获得验证组建
        $authentication = new Authentication();
        // 获取解密后的明文，并转换成simplexml方便处理
        $decrypMsg = $authentication->decryptMsg($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $postData);
        
        // 如果失败，返回fail
        if ($decrypMsg == -1){
            return 'fail';
        }

        // 如果解密成功，与数据库交互
        $xml = simplexml_load_string($decrypMsg);

        // 获取suite_id和auth_code 或者 suite_ticket, 因为有两种推送body, 所以有一者会为空
        $suiteId = '';
        $authCode = '';
        $suiteTicket = '';
        foreach($xml->children() as $value){
            // 获取suiteId
            if ($value->getName() == 'SuiteId'){
                $suiteId = $value;
            }
            // 获取临时授权码
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
            // Todo:获取企业永久授权码, 并且在数据库中初始化信息
        }else{
            // 更新suiteTicket到数据库
            $map = [
                'suite_id' => $suiteId
            ];
            $insert = [
                'suite_ticket' => $suiteTicket
            ];
            DB::table('suite_info')->updateOrInsert($map, $insert);
        }

        // 企业微信后台规定回调url处理完要返回success
        return 'success';
    }

    /**
     * 在模板授权成功时，在数据库中初始化用户企业的信息
     * @return void
     */
    private function initialCorp(){

        // Todo：向repair_corp_id_info插入一条新的公司数据
    }

    /**
     * 获取suite access token
     * @param Request $request
     * @return array 自定义的返回结构体buildReturn
     */
    public function getSuiteAccessToken(): array
    {

        $authentication = new Authentication();
        $suiteAccessToken = $authentication->getSuiteAccessToken();

        if ($suiteAccessToken == ''){
            return $this->buildReturn(env('RETURN_FAIL'), 'access_suite_token获取失败');
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), 'access_suite_token获取成功', $suiteAccessToken);
    }

    /**
     * 获取企业永久授权码
     * @param Request $request
     * @return array 自定义的返回结构体buildReturn
     */
    public function getSecret(): array
    {

        $authentication = new Authentication();
        $secret = $authentication->getPermanentCode();

        if ($secret == ''){
            return $this->buildReturn(env('RETURN_FAIL'), 'access_suite_token获取失败');
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), 'access_suite_token获取成功', $secret);
    }

    /**
     * 获取企业的access token
     * @return string access token
     */
    public function getAccessToken($corpId): string
    {

        // 获取suite access token包体
        $authentication = new Authentication();
        $accessToken = $authentication->getAccessToken();

        if ($accessToken == ''){
            return $this->buildReturn(env('RETURN_FAIL'), 'access_token获取失败');
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), 'access_suite_token获取成功', $accessToken);
    }
}
