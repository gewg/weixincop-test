<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/30
 * Time: 14:23
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\Teacher;

class Record extends Base
{
    protected $table = 'order_record';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    public function addRecord($teacher_id, $order_id, $remark, $type=0)
    {
        if (!$teacher_id || !$order_id || !$remark) {
            return $this->buildReturn(env('RETURN_FAIL'), '缺少参数');
        }
        $data = [
            'teacher_id' => $teacher_id,
            'order_id' => $order_id,
            'type' => $type,
            'remark' => $remark,
            'add_time' => date('Y-m-d H:i:s')
        ];
        $record_id = $this->insertGetId($data);
        if ($record_id) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }
        return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
    }
}