<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/29
 * Time: 14:28
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Image extends Base
{
    protected $table = 'image';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';
}