<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/28
 * Time: 16:58
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Template extends Base
{
    protected $table = 'template';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';


}