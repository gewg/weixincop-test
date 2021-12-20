<?php
/**
 * 公共方法
 *
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/6/10
 * Time: 9:26
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Common extends Model
{
    /**
     * 修改时间格式
     *
     * @param $time
     * @return string
     */
    public function changeTimeFormat($time)
    {
        //如果是本年，不显示年
        $year_now = date('Y');
        $year = date('Y',strtotime($time));
        if ($year == $year_now) {
            $new_time = date('m-d',strtotime($time));
        } else {
            $new_time = date('Y-m-d',strtotime($time));
        }
        return $new_time;
    }
}