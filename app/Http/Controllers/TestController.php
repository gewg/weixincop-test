<?php
/**
 * This file is used to test
 */

namespace App\Http\Controllers;

require dirname(__FILE__).'./../../../vendor/callback/WXBizMsgCrypt.php';

use App\Models\Base;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;
use App\Models\Authentication;
use Illuminate\Support\Facades\DB;
use App\Models\WXBizMsgCrypt;

define("ENCODING_AES_KEY", "GosdFgeYEKCBqbXXGobVjZ2CC2qhNeAD5jfju34WKqK");
define("TOKEN", "Z3h85t9XbYRdKpe2pc");
define("CORP_ID", "ww1cf3388b933cfc25");

class TestController extends Base{
    

    public function getTest(){
        echo DB::table('corp_id_info')->get();
    }

    public function postTest(Request $request){

        $verifyMsgSignature = $request->query('msg_signature');
        $verifyTimestamp = $request->query('timestamp');
        $verifyNonce = $request->query('nonce');
        $verifyEchostr = $request->query('echostr');

        $sReplyMsg = "<xml>
        <SuiteId><![CDATA[ww4asffe99e54c0fxxxx]]></SuiteId>
        <InfoType> <![CDATA[suite_ticket]]></InfoType>
        <TimeStamp>1403610513</TimeStamp>
        <SuiteTicket><![CDATA[asdfasfdasdfasdf]]></SuiteTicket>
    </xml>";

        $sEncryptMsg = '';
        $result = ''; 

        $wxcpt = new WXBizMsgCrypt(TOKEN, ENCODING_AES_KEY, CORP_ID);
        $errCode = $wxcpt->EncryptMsg($sReplyMsg, $verifyTimestamp, $verifyNonce, $sEncryptMsg);
        echo $sEncryptMsg;
        //$a =  "<xml><ToUserName><![CDATA[wx5823bf96d3bd56c7]]></ToUserName><Encrypt><![CDATA[RypEvHKD8QQKFhvQ6QleEB4J58tiPdvo+rtK1I9qca6aM/wvqnLSV5zEPeusUiX5L5X/0lWfrf0QADHHhGd3QczcdCUpj911L3vg3W/sYYvuJTs3TUUkSUXxaccAS0qhxchrRYt66wiSpGLYL42aM6A8dTT+6k4aSknmPj48kzJs8qLjvd4Xgpue06DOdnLxAUHzM6+kDZ+HMZfJYuR+LtwGc2hgf5gsijff0ekUNXZiqATP7PF5mZxZ3Izoun1s4zG4LUMnvw2r+KqCKIw+3IQH03v+BCA9nMELNqbSf6tiWSrXJB3LAVGUcallcrw8V2t9EL4EhzJWrQUax5wLVMNS0+rUPA3k22Ncx4XXZS9o0MBH27Bo6BpNelZpS+/uh9KsNlY6bHCmJU9p8g7m3fVKn28H3KDYA5Pl/T8Z1ptDAVe0lXdQ2YoyyH2uyPIGHBZZIs2pDBS8R07+qN+E7Q==]]></Encrypt><AgentID><![CDATA[218]]></AgentID></xml>";
        $errCode = $wxcpt->DecryptMsg($verifyMsgSignature, $verifyTimestamp, $verifyNonce, $request->getContent(), $result);
        var_dump($result);
    }
}