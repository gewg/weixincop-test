<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/27
 * Time: 18:08
 */
namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\Teacher;

class ProcessCopy extends Base
{
    protected $table = 'copy';
    const CREATED_AT = 'add_time';
    const UPDATED_AT = 'update_time';

    /**
     * 获取抄送教师列表
     *
     * @param $process_id
     * @return array
     */
    public function getTeacherList($process_id)
    {
        // 抄送人列表
        $map = [
            'process_id' => $process_id,
            'is_del' => 0
        ];
        $list = ProcessCopy::where($map)->get()->toArray();

        $teacher_list = [];
        foreach ($list as $v) {
            $teacher['teacher_id'] = $v['teacher_id'];
            $teacher['name'] = Teacher::where('id',$v['teacher_id'])->where('is_del',0)
                ->value('title');
            $teacher['avatar'] = Teacher::where('id',$v['teacher_id'])->where('is_del',0)
                ->value('avatar');
            if (!$teacher['name']) {
                continue;
            }
            $teacher_list[] = $teacher;
        }
        return $teacher_list;
    }

}