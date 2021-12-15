<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/27
 * Time: 15:19
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Equipment extends Base
{
    protected $table = 'equipment_type';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    /**
     * 更新故障设备类型属性
     *
     * @param $type_id
     * @param $data
     * @return array
     */
    public function updateType($type_id, $data)
    {
        if (!$type_id || !$data) {
            return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
        }

        $data_key = ['is_del', 'title', 'process_id'];
        foreach ($data as $k=>$v) {
            if (!in_array($k,$data_key)) {
                return $this->buildReturn(env('RETURN_FAIL'), '参数错误');
            }
        }

//        $data['update_time'] = date('Y-m-d H:i:s');
        $update_result = Equipment::where("id",$type_id)->update($data);
        if ($update_result) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }
        return $this->buildReturn(env('RETURN_FAIL'), '操作失败');
    }
}