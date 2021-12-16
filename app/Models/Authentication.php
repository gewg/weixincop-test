<?php

namespace App\Models;
include_once '../../vendor/callback/WXBizMsgCrypt.php';

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
    public function verifyUrl($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $verifyEchostr){
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
     * @return void
     */
    public function getAuthCode($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $postData){

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
}