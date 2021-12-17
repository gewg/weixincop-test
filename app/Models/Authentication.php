<?php

namespace App\Models;
include_once '../../vendor/callback/WXBizMsgCrypt.php';

use Illuminate\Database\Eloquent\Model;

// 参数, 设置代开发模板时获取
define("ENCODING_AES_KEY", "Z55OiJcdeFealHPHM3xdrncbVMqwGh5VxEk3GPwYmry");
define("TOKEN", "qvULhYkh777DeLdjNrW2");
define("CORP_ID", "ww1cf3388b933cfc25");

class Authentication extends Base{

    /**
     * 验证消息体签名的正确性
     * @param $verifyMsgSignature
     * @param $verifyTimestamp
     * @param $verifyNonce
     * @param $verifyEchostr
     * @return int|string 验证成功返回消息体解密后的明文，验证失败打印报错并返回-1
     */
    public function verifyUrl($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $verifyEchostr): int|string
    {
        // 明文
        $result = '';

        // 验证url并获得明文
        $wxcpt = new WXBizMsgCrypt(TOKEN, ENCODING_AES_KEY, CORP_ID);
        $errCode = $wxcpt->VerifyURL($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $verifyEchostr, $result);

        if ($errCode == 0) {
            return $result;
        } else {
            print("ERR: " . $errCode . "\n\n");
            return -1;
        }
    }

    /**
     * 签名验证, 成功后解密数据包获得明文
     * @param $verifyMsgSignature
     * @param $verifyTimestamp
     * @param $verifyNonce
     * @param $verifyEchostr
     * @return int/string
     */
    public function decryptMsg($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $postData): int|string
    {

        // 明文
        $result = '';

        // 验证签名, 并解密
        $wxcpt = new WXBizMsgCrypt(TOKEN, ENCODING_AES_KEY, CORP_ID);
        $errCode = $wxcpt->DecryptMsg($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $postData, $result);

        if ($errCode == 0){
            return $result;
        }else{
            print("ERR: " . $errCode . "\n\n");
            return -1;
        }
    }

    /**
     * 从数据库中读取auth code
     * @return void
     */
    private function getAuthCode(){

        $authCode = '';

        // 从数据库中读取
        //Todo:从数据库中读取

        return $authCode;
    }

    /**
     * 从数据库中读取suite ticket
     * @return void
     */
    private function getSuiteTicket(){

        $suiteTicket = '';

        // 从数据库中读取suite ticket
        //Todo:从数据库读取

        return $suiteTicket;
    }

    /**
     * 获取suite access token
     * @return string
     */
    public function getSuiteAccessToken(){

        $suiteAccessToken = '';

        // 从数据库读取suite access token


        // 如果没有过期, 直接返回
        if (){

        }

        // 否则, 重新获取
        else{
            $suiteAccessToken = $this->getNewSuiteAccessToken();
        }

        return $suiteAccessToken;
    }

    /**
     * 获取新的suite access token
     * @return string 加密后的data数据
     */
    private function getNewSuiteAccessToken(): string
    {

        $suite_access_token_url = 'cgi-bin/service/get_suite_token';

        // 从数据库读取参数
        $suiteId = ; // 应用后台获取
        $suiteSecret = ; // 应用后台获取
        $suiteTicket = $this->getSuiteTicket();

        // 抓取getSuiteAccessToken
        $url = $this->base_url.$this->$suite_access_token_url;
        $postData = array(
            "suit_id" => $suiteId,
            "suiteSecret" => $suiteSecret,
            "suiteTicket" => $suiteTicket
        );

        // 转换格式
        $xml = simplexml_load_string($this->httpRequestJson($url, $postData));

        // 获取suite access token和过期时间
        $suiteAccessToken = '';
        $expiresIn = '';
        foreach($xml->children() as $value){
            if ($value->getName() == 'suite_access_token'){
                $suiteAccessToken = $value;
            }
            else if($value->getName() == 'expires_in') {
                $expiresIn = $value;
            }
        }

        // 保存到数据库


        return $suiteAccessToken;
    }

    /**
     * 获取企业永久授权码
     * @return string
     */
    public function getPermanentCode(): string
    {

        $permanentCode = '';

        // 检查数据库中是否保存
        if (){

        }
        // 否则, 获取一个新的
        else{
            $permanentCode = $this->getNewPermanentCode();
        }

        return $permanentCode;
    }

    /**
     * 获取新的企业永久授权
     * @return string 加密后的data数据
     */
    private function getNewPermanentCode(): string
    {

        $get_permanent_code = 'cgi-bin/service/get_permanent_code';

        // 获取suite access token
        $suiteAccessToken = $this->getSuiteAccessToken();

        // 获取临时授权码
        $authCode = $this->getAuthCode();

        // 抓取getSuiteAccessToken
        $url = $this->base_url.$this->$get_permanent_code.'?suite_access_token='.$suiteAccessToken;
        $postData = array(
            "auth_code" => $authCode
        );

        // 转换格式
        $xml = simplexml_load_string($this->httpRequestJson($url, $postData));

        // 获取permanent_code
        $permanentCode = '';

        foreach($xml->children() as $value){
            if ($value->getName() == 'permanent_code'){
                $permanentCode = $value;
            }
        }

        // 保存到数据库
        //Todo:保存永久授权码到数据库

        return $permanentCode;
    }

    /**
     *
     * @return string access token
     */
    public function getAccessToken(){

        $accessToken = '';

        // 读取数据库

        // 检查access token的可用性
        // 检查数据库中access token是否存在
        if (){

            // 如果存在，检查有效期
            if (){

            }

        }

        //  如果access token依旧为空, 则新建
        if ($accessToken == ''){

            $accessTokenInfo = $this->getNewAccessToken();
            $accessToken = $accessTokenInfo['access_token'];
            $expiresIn = $accessTokenInfo['expires_in'] + time();

            // 储存到数据库
            //Todo:保存到数据库
        }

        return $accessToken;
    }

    /**
     * @return string access token
     */
    private function getNewAccessToken($corpId){

        $accessTokenInfo = '';

        $getTokenUrl = 'cgi-bin/gettoken';

        // 获取企业永久授权码
        $secret = $this->getPermanentCode();

        $url = $this->base_url.$this->$getTokenUrl."?corpid={$corpId}&corpsecret={$secret}";
        $tokenInfo = json_decode(file_get_contents($url), 1);

        // 如果报错, accessToken留空
        if ($tokenInfo['errcode']) {}
        else {
            $accessTokenInfo = $tokenInfo;
        }

        return $accessTokenInfo;
    }

}