<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/23
 * Time: 17:21
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BranchWx extends Base
{
    protected $table = 'branch_wx';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    private $token_url = "cgi-bin/gettoken?";
    private $dep_list = "cgi-bin/department/list?";
    private $user_info = "cgi-bin/user/getuserinfo?";

    /**
     * 获取企业微信app配置信息
     *
     * @param $branch_id
     * @return array
     */
    public function getAppInfo($branch_id)
    {
        if (!$branch_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
        }

        $map = [
            'branch_id' => $branch_id,
            'is_del' => 0
        ];
        $app_info = $this->where($map)->select(['corp_id','secret','agent_id','access_token','expire','update_time'])->first()->toArray();

        # 判断是否需要更新token
        if (!$app_info['access_token']) {
            $token_info = $this->updateToken($app_info['corp_id'], $app_info['secret']);
            if ($token_info['state']) {
                $app_info['access_token'] = $token_info['data']['access_token'];
                $app_info['expire'] = $token_info['data']['expire'];
            }
        }

        #检查token过期时间
        if ($app_info['expire'] < time()) {
            $token_info = $this->updateToken($app_info['corp_id'], $app_info['secret']);
            if ($token_info['state']) {
                $app_info['access_token'] = $token_info['data']['access_token'];
                $app_info['expire'] = $token_info['data']['expire'];
            }
        }

        #检查token是否有效
//        if (!$this->checkToken($app_info['access_token'])) {
//            $token_info = $this->updateToken($app_info['corp_id'], $app_info['secret']);
//            if ($token_info['state']) {
//                $app_info['access_token'] = $token_info['data']['access_token'];
//                $app_info['expire'] = $token_info['data']['expire'];
//            }
//        };

        if ($app_info) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '获取成功', $app_info);
        }
        return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
    }

    /**
     * 获取用户授权信息
     *
     * @param $branch_id
     * @param $code
     * @return array
     */
    public function getUserInfo($branch_id, $code)
    {
        if (!$branch_id) {
            return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
        }

        $app_info = $this->getAppInfo($branch_id);
        if (!$app_info['state']) {
            return $this->buildReturn($app_info['state'], $app_info['message'], $app_info['data']);
        }

        $url = $this->base_url.$this->user_info."access_token={$app_info['data']['access_token']}&code={$code}";

        $user_info = json_decode(file_get_contents($url), 1);

        if ($user_info['errcode'] == 42001) {
            $token_result = $this->updateToken($app_info['data']['corp_id'], $app_info['data']['secret']);
            if (!$token_result['state']) {
                return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
            }

            $url = $this->base_url.$this->user_info."access_token={$token_result['data']['access_token']}&code={$code}";

            $user_info = json_decode(file_get_contents($url), 1);
        }

        if ($user_info['errcode']) {
            return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), '获取成功', $user_info);
    }

    /**
     * 更新token
     *
     * @param $corp_id
     * @param $secret
     * @return array
     */
    private function updateToken($corp_id, $secret)
    {
        $url = $this->base_url.$this->token_url."corpid={$corp_id}&corpsecret={$secret}";
        $token_info = json_decode(file_get_contents($url), 1);
        if ($token_info['errcode']) {
            return $this->buildReturn(env('RETURN_FAIL'), 'token获取失败');
        }

        # 更新数据库token
        $map = [
            'corp_id' => $corp_id,
            'is_del' => 0
        ];
        $data = [
            'access_token' => $token_info['access_token'],
            'expire' => $token_info['expires_in'] + time()
        ];
        $this->where($map)->update($data);

        return $this->buildReturn(env('RETURN_SUCCESS'), 'token获取成功', $data);

    }


    public function getJsApiTicket($branch_id)
    {
        //获取ticket
        $ticket = BranchWx::where('id', $branch_id)->value('jsapi_ticket');
        $ticket_expire = BranchWx::where('id', $branch_id)->value('ticket_expire');
        if ($ticket && time() < $ticket_expire) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '', $ticket);
        }

        //获取token
        $app_info = $this->getAppInfo($branch_id);

        if ($app_info['state'] == env('RETURN_FAIL')) {
            return $this->buildReturn(env('RETURN_FAIL'), $app_info['message']);
        }
        $access_token = $app_info['data']['access_token'];
        $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token={$access_token}";
        $res = file_get_contents($url);
        $res = json_decode($res, true);

        if ($res['errcode'] != 0) {
            return $this->buildReturn(env('RETURN_FAIL'), $res['errmsg']);
        }
        $ticket = $res['ticket'];
        $expire = date('Y-m-d H:i:s', time() + $res['expires_in']);
        $this->where('id', $branch_id)->update([
            'jsapi_ticket' => $ticket,
            'ticket_expire' => strtotime($expire),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        return $this->buildReturn(env('RETURN_SUCCESS'), '', $ticket);
    }
    /**
     * 检查token是否有效
     *
     * @param $token
     * @return bool
     */
    private function checkToken($token)
    {
        $url = $this->base_url.$this->dep_list."access_token={$token}";
        $result = json_decode(file_get_contents($url), 1);
        if ($result['errcode'] == '40014') {
            return false;
        }
        return true;
    }

    /**
     * 获取用户列表
     */
    public function getDeptUserList($branch_id)
    {
        $app_info = $this->getAppInfo($branch_id);
        var_dump($app_info);
    }
}
