<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Crypt;
use App\Models\Teacher;

class Auth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $url = $request->path();
        if (in_array($url, ['oauth/login', 'login'])) {
            return $next($request);
        }

        $token = $request->input('token');
        if (!$token) {
            return [
                'state' => -1,
                'message' => '未登录',
            ];
        }
        $decrypted = Crypt::decrypt($token);

        $map = [
            'id' => $decrypted['user_id'],
            'is_del' => 0,
            'branch_id' => $decrypted['branch_id']
        ];
//        $user_info = Teacher::where($map)->select('id','title as name','power','avatar','dept_title')->first();
//        if (!$user_info) {
//            return [
//                'state' => -1,
//                'message' => '登录失败',
//            ];
//        }

        $decrypted = json_decode($decrypted, true);
        $request->session()->put('branch_id', $decrypted['branch_id']);
        $request->session()->put('teacher_id', $decrypted['user_id']);
        $request->session()->put('wx_user_id', $decrypted['wx_user_id']);
        return $next($request);
    }
}
