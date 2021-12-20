<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/24
 * Time: 16:36
 */

namespace App\Jobs;
use App\Models\Teacher;
use App\Models\BranchCampus;
use App\Models\Branch;
use App\Models\WxWork;

class DeletTser extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        var_dump('test');
        # 获取教师列表
        $limit = 50;

        $total = Teacher::where('is_del', 0)->count('id');
        var_dump($total);
        for ($i = 0; $i <= $total + $limit; $i += $limit) {
            var_dump($i);
            $list = Teacher::where('is_del', 0)->offset($i)->limit($limit)->get()->toArray();
            foreach ($list as $v) {
//                var_dump($v);
                if (!$v['title'] && !$v['wx_user_id']) {
                    Teacher::where('id', $v['id'])->update(['is_del' => 1]);
                }
            }
        }
    }
}