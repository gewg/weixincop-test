<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/29
 * Time: 14:48
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class OrderPrincipal extends Base
{
    protected $table = 'order_principal';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';
}