<?php
/**
 * This file is only for test.
 * You can keep it in local without pushing to github or cloud
 */

namespace App\Http\Controllers;

use App\Models\Base;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;
use App\Models\Authentication;
use Illuminate\Support\Facades\DB;

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

        $sReplyMsg = "11";

        $wxcpt = new WXBizMsgCrypt(TOKEN, ENCODING_AES_KEY, CORP_ID);
        $errCode = $wxcpt->EncryptMsg($sReplyMsg, $sTimeStamp, $sNonce, $sEncryptMsg);
    }
}