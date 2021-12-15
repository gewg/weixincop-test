<?php
/**
 * Created by PhpStorm.
 * User: bitzo
 * Date: 2019/2/25
 * Time: 18:19
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequestLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $url = $request->fullUrl();
        $method = $request->method();
        $dir = '/opt/ci123/www/html/sc-edu/tmp/wx/qywx_third/visitor/' . date('Y/m');
        $filename = $dir . '/' . date('d') . '.log';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($filename, '[ ' . date('Y-m-d H:i:s') . " ]\t{$method}\t{$url}\n".var_export($request->all(), 1)."\n=======================================================================\n", FILE_APPEND);
        return $next($request);
    }
}
