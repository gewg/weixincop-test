<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/29
 * Time: 14:43
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class OrderCopy extends Base
{
    protected $table = 'order_copy';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';
}