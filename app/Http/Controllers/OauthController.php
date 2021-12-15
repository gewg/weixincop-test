<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/23
 * Time: 16:39
 */

namespace App\Http\Controllers;

include_once "/vendor/callback/WXBizMsgCrypt.php";

use App\Models\Base;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;
use App\Models\Teacher;
use App\Models\BranchCampus;
use App\Models\Branch;
use App\Models\WxWork;

// 参数
define("ENCODING_AES_KEY", "Z55OiJcdeFealHPHM3xdrncbVMqwGh5VxEk3GPwYmry");
define("TOKEN", "qvULhYkh777DeLdjNrW2");
define("CORP_ID", "ww1cf3388b933cfc25");

class OauthController extends Base
{
    /**
     * 获取access_token
     *
     * @param Request $request
     * @return bool|string
     */
    public function index(Request $request)
    {
        # 获取参数
        $branch_no = $request->input('branch_no', '');
        $type = $request->input('type', '0');
        $order_id = $request->input('order_id', '0');
        $role = $request->input('role', 0);

        if (!$branch_no) {
            exit('error: 缺少参数');
        }
        $branch = new Branch();
        $check_result = $branch->checkBranchNo($branch_no);
        if (!$check_result['state']) {
            exit('error: 参数错误');
        }

        # 获取企业微信配置
        $qywx_info = $branch->getBranchWxInfo();
        if (!$qywx_info['state']) {
            exit('error: 企业微信配置获取失败');
        }
        $app_info = $qywx_info['data'];

        # 获取回调地址
        $back_url = 'https://campus.sc-edu.com/repair/api/repair/public/login';
        $state = json_encode([
            'branch_no' => $branch_no,
            'type' => $type,
            'order_id' => $order_id,
            'role' => $role
        ]);

        $url = $this->getOauthUrl($app_info['corp_id'], $back_url, $state);
        if (!$url) {
            exit('error: 回调地址配置失败');
        }
        return redirect($url);
    }


    /**
     * 用户登录
     *
     * @param Request $request
     */
    public function login(Request $request)
    {
        $state = $request->input('state', '');
        $code = $request->input('code', '');

        $state = json_decode($state, 1);

        $branch_no = $state['branch_no'];
        if (!$branch_no || !$code) {
            exit('error: 登录失败');
        }
        $branch = new Branch();
        $check_result = $branch->checkBranchNo($branch_no);
        if (!$check_result['state']) {
            exit('error: 登录参数错误');
        }
        $branch_id = $check_result['data']['branch_id'];
        $teacher = new Teacher($branch_id);
        $login_result = $teacher->userLogin($code);
		if (!$login_result['state'] && $login_result['message'] == '您暂无权限，请联系管理员') {	
//            exit('error: 您暂无权限，请联系管理员');
            return redirect("https://campus.sc-edu.com/repair/noteacher/noteacher.html");
		}
		if (!$login_result['state']) {
//            exit('error:'.$login_result['message']);
            return redirect("https://campus.sc-edu.com/repair/noteacher/noteacher.html");
		}

        $data = [
            'user_id' => $login_result['data']['user_id'],
            'branch_id' => $login_result['data']['branch_id'],
            'wx_user_id' => $login_result['data']['wx_user_id']
        ];

        $token = \Illuminate\Support\Facades\Crypt::encrypt(json_encode($data));

        $type = $state['type'];
        $order_id = $state['order_id'];
        $role = $state['role'];
		$url = "https://campus.sc-edu.com/repair/home.html?token={$token}&type={$type}&role={$role}&order_id={$order_id}";
        return redirect($url);
    }

    /**
     * 用户登录
     *
     * @param Request $request
     */
    public function web_login(Request $request)
    {
        $branch_no = $request->input('state', '');
        $code = $request->input('code', '');

        if (!$branch_no || !$code) {
            exit('error: 登录失败');
        }
        $branch = new Branch();
        $check_result = $branch->checkBranchNo($branch_no);
        if (!$check_result['state']) {
            exit('error: 登录参数错误');
        }
        $branch_id = $check_result['data']['branch_id'];
        $teacher = new Teacher($branch_id);
        $login_result = $teacher->userLogin($code);
        if (!$login_result['state']) {
            exit('error: 登录错误');
        }

        $data = [
            'user_id' => $login_result['data']['user_id'],
            'branch_id' => $login_result['data']['branch_id'],
            'wx_user_id' => $login_result['data']['wx_user_id']
        ];

        $token = \Illuminate\Support\Facades\Crypt::encrypt(json_encode($data));


        $url = "https://campus.sc-edu.com/repair/home.html?token={$token}&type=0&role=0&order_id=0";
        return redirect($url);
    }

    /**
     * 二维码授权
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector
     */
    public function webOauth(Request $request)
    {
        # 获取参数
        $branch_no = $request->input('branch_no', '');
        if (!$branch_no) {
            exit('error: 缺少参数');
        }
        $branch = new Branch();
        $check_result = $branch->checkBranchNo($branch_no);

        $qywx_info = $branch->getBranchWxInfo();
        if (!$qywx_info['state']) {
            exit('error: 企业微信配置获取失败');
        }
        $app_info = $qywx_info['data'];
        $corp_id = $app_info['corp_id'];
        $agent_id = $app_info['agent_id'];

        $back_url = "https://campus.sc-edu.com/repair/api/repair/public/qr_login";
        $back_url = urlencode($back_url);

//        $state = json_encode([
//            'branch_no' => $branch_no,
//            'type' => '0',
//            'order_id' => '0',
//            'role' => '0'
//        ]);
        $url = "https://open.work.weixin.qq.com/wwopen/sso/qrConnect?appid={$corp_id}&agentid={$agent_id}&redirect_uri={$back_url}&state={$branch_no}";

        return redirect($url);
    }

    /**
     * 获取企业微信授权url
     *
     * @param $corp_id
     * @param $back_url
     * @param $scope
     * @param $state
     * @return string
     */
    private function getOauthUrl($corp_id, $back_url, $state = 0, $scope = 'snsapi_base')
    {
        if (!$corp_id || !$back_url) {
            return '';
        }

        $base_url = "https://open.weixin.qq.com/connect/oauth2/authorize?";

        $url = $base_url."appid={$corp_id}&redirect_uri={$back_url}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";

        return $url;
    }


    public function test()
    {
        var_dump('test');
        die();
        $branch_id = 1;
        $corp_id = Branch::where('id', $branch_id)->value('corp_id');
        $campus = new BranchCampus(1);
        $wxWork = new WxWork($corp_id);
        $res = $campus->getTeacherDeptList();
        if ($res['state'] == env('RETURN_FAIL')) {
            return $res['message'];
        }
        $dept_list = $res['data'][0]['child'];

        $dept_list_flat = $campus->getDepartList($dept_list);

        //企业微信获取用户列表
//        foreach ($dept_list_flat as $dept) {
//            $dept_id = $dept['departid'];
//            $wx_dept_id = $dept['wxdepartid'];
//            $dept_title = $dept['departname'];
//
//            // 从企业微信获取用户列表
//            $teacher_list = $wxWork->getWxDeptMemDetail($wx_dept_id, $branch_id);
//            var_dump($teacher_list);
//            $teacher_list = $teacher_list['data']['user_list'];
//            foreach ($teacher_list as $teacher) {
//                $teacher_model = new Teacher();
//                $campus_user_id = $teacher_model->getCampusUserId($branch_id, $teacher['userid']);
//                $campus_user_id = $campus_user_id['data'];
//                $res = $campus->getCampusUserInfo($campus_user_id);
//                $res = $res['data'];
//                if (!$res['name']) continue;
//                $teacher_info = [
//                    'branch_id' => $branch_id,
//                    'title' => $res['name'] ? : '',
//                    'tel' => $res['cellphone'] ? : '',
//                    'wx_user_id' => $teacher['userid'],
//                    'campus_user_id' => $campus_user_id,
//                    'dept_title' => $dept_title,
//                    'avatar' => $res['head'] ? : '',
//                ];
//                $is_exist = Teacher::where([
//                    'branch_id' => $branch_id,
//                    'wx_user_id' => $res['wxuserid'] ? : '',
//                    'is_del' => 0,
//                ])->value('id');
//                if ($is_exist) {
//                    $title = Teacher::where(['id' => $is_exist])->value('dept_title');
//                    if (!$title) {
//                        Teacher::where('id', $is_exist)->update(['dept_title' => $dept_title]);
//                    }
//                } else {
//                    $teacherModel = new Teacher();
//                    $teacherModel->insertGetId($teacher_info);
//                }
//            }
//        }

        #  腾讯智慧校园获取用户列表
        foreach ($dept_list as $dept) {
            $dept_id = $dept['departid'];
            $dept_title = $dept['departname'];
            $is_admin = 0;
            $is_jdc = 0;
            $is_guard = 0;

            $res = $campus->searchUserList(2, $dept_id, 2);

            if ($res['state'] == env('RETURN_FAIL')) {
                continue;
            }
            $teacher_list = $res['data']['dataList'];
            foreach ($teacher_list as $teacher) {
                $res = $campus->getCampusUserInfo($teacher['userid']);

                $res = $res['data'];
                $teacher_info = [
                    'branch_id' => $branch_id,
                    'title' => $res['name'] ? : '',
                    'tel' => $res['cellphone'] ? : '',
                    'wx_user_id' => $res['wxuserid'] ? : '',
                    'campus_user_id' => $teacher['userid'],
                    'dept_title' => $dept_title,
                    'avatar' => $res['head'] ? : '',
                ];
                $is_exist = Teacher::where([
                    'branch_id' => $branch_id,
                    'wx_user_id' => $res['wxuserid'],
                    'is_del' => 0,
                ])->value('id');
                if ($is_exist) {
                    $title = Teacher::where(['id' => $is_exist])->value('dept_title');
                    if (!$title) {
                        Teacher::where('id', $is_exist)->update(['dept_title' => $dept_title]);
                    }
                } else {
                    $teacherModel = new Teacher();
                    $teacherModel->insertGetId($teacher_info);
                }
            }
        }
    }

    /**
     * 用户登录
     *
     * @param Request $request
     */
    public function getOauth(Request $request)
    {
        $user_id = $request->input('user_id', '');
        $branch_id = $request->input('branch_id', '');
        $wx_user_id = $request->input('wx_user_id', '');


        $data = [
            'user_id' => $user_id,
            'branch_id' => $branch_id,
            'wx_user_id' => $wx_user_id
        ];

        $token = \Illuminate\Support\Facades\Crypt::encrypt(json_encode($data));

		var_dump($token);

        //$url = "https://campus.sc-edu.com/repair/home.html?token={$token}&type=0&role=0&order_id=0";
        //return redirect($url);
    }

    /**
     * 处理企业微信向回调url推送的请求
     * 回调url总共会被推送两种信息, 都需要处理:
     * 1. 用户企业授权服务商提供的代开发模板时, 会向服务商设置的回调url发送授权成功通知
     * 2.
     *
     * @param Request $request
     */
    public function getAuthentication(Request $request){

        // 获取url中的参数
        $msg_signature = $request->query('msg_signature');
        $timestamp = $request->query('timestamp');
        $nonce = $request->query('nonce');
        $echostr = $request->query('echostr');

        // 需要返回的明文
        $sEchoStr = "";

        // 验证url
        $wxcpt = new WXBizMsgCrypt(TOKEN, ENCODING_AES_KEY, CORP_ID);
        $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $sEchoStr);
        if ($errCode == 0) {
            var_dump($sEchoStr);
            //
            // 验证URL成功，将sEchoStr返回
            // HttpUtils.SetResponce($sEchoStr);
        } else {
            print("ERR: " . $errCode . "\n\n");
        }
    }

    /**
     * 在企业微信设置回调url时, 验证url有效性
     */
    public function getUrlEffectiveness(Request $request){

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

//    /**
//     * 测试url
//     */
//    public function getTestTest(Request $request){
//        print("AAA");
//    }
}
