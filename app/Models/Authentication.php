<?php

namespace App\Models;
require_once dirname(__FILE__).'./../../vendor/callback/WXBizMsgCrypt.php';

use Illuminate\Database\Eloquent\Model;
use App\Models\WXBizMsgCrypt;

// 参数, 设置代开发模板时获取
define("ENCODING_AES_KEY", "GosdFgeYEKCBqbXXGobVjZ2CC2qhNeAD5jfju34WKqK");
define("TOKEN", "Z3h85t9XbYRdKpe2pc");
define("CORP_ID", "ww1cf3388b933cfc25");

// 测试用的数值
// define("ENCODING_AES_KEY", "jWmYm7qr5nMoAUwZRjGtBxmz3KA1tkAj3ykkR6q2B2C");
// define("TOKEN", "QDG6eK");
// define("CORP_ID", "wx5823bf96d3bd56c7");

class Authentication extends Base{

    /**
     * 解密四个参数并返回解密后的明文
     * @param $verifyMsgSignature
     * @param $verifyTimestamp
     * @param $verifyNonce
     * @param $verifyEchostr
     * @return int|string 验证成功返回消息体解密后的明文，验证失败打印报错并返回-1
     */
    public function verifyUrl($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $verifyEchostr)
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
    public function decryptMsg($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $postData)
    {

        // 明文
        $result = "";

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

        return $suiteTicket;
    }

    /**
     * 获取suite access token
     * @return string
     */
    public function getSuiteAccessToken(){

        $suiteAccessToken = '';

        // 从数据库读取suite access token
        

        // // 如果数据库中的token存在且没有过期, 直接返回
        // if (){

        // }

        // // 否则, 重新从微信后台获取
        // else{
        //     $suiteAccessToken = $this->getNewSuiteAccessToken();
        // }

        return $suiteAccessToken;
    }

    /**
     * 获取新的suite access token
     * @return string 
     */
    private function getNewSuiteAccessToken()
    {

        $suite_access_token_url = 'cgi-bin/service/get_suite_token';

        // 从数据库读取参数
        $suiteId = ''; // suiteID从数据库中获取
        $suiteSecret = ''; // suiteSecret从数据库中获取
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
    public function getPermanentCode()
    {

        $permanentCode = '';

        // // 检查数据库中是否存在 (企业永久授权)
        // if (){

        // }
        // // 否则, 向企业微信后台发送request获取一个新的
        // else{
        //     $permanentCode = $this->getNewPermanentCode();
        // }

        return $permanentCode;
    }

    /**
     * 获取新的企业永久授权
     * @return string 加密后的data数据
     */
    private function getNewPermanentCode(): string
    {

        $get_permanent_code = '';

        // 获取suite access token
        $suiteAccessToken = $this->getSuiteAccessToken();

        // 获取临时授权码
        $authCode = $this->getAuthCode();

        // 抓取getSuiteAccessToken
        $url = $this->base_url.$this->$get_permanent_code.'?suite_access_token='.$suiteAccessToken;
        $postData = array(
            "auth_code" => $authCode
        );

        // 发送request得到resonse, 并转换格式
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

        // 从数据库中读取读取数据库
        $accessToken = '';
        $accessTokenExpire = '';

        // // 如果access token存在数据库且未过期
        // if (){


        // }

        // //  如果access token依旧为空, 则新建
        // if ($accessToken == ''){

        //     $accessTokenInfo = $this->getNewAccessToken();
        //     $accessToken = $accessTokenInfo['access_token'];
        //     $expiresIn = $accessTokenInfo['expires_in'] + time();

        //     // 储存到数据库
        //     //Todo:保存到数据库
        // }

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