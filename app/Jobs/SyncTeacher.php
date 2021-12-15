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

class SyncTeacher extends Job
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
		$this->cron_runLock(true);
        echo "SyncTeacher start_time".date("Y-m-d H:i:s\n");
		$branch_ids = Branch::where('is_del',0)->orderBy('add_time',"DESC")->pluck('id')->toArray();
		
		foreach ($branch_ids as $branch_id) {
			$campus = new BranchCampus($branch_id);
			$campus_info = $campus->getCampusInfo();
			if (!$campus_info) {
				continue;
			}
			$branch_info = $campus->getSchoolInfo();
			//更新学校logo
			if ($branch_info['state']) {
				$update_result = Branch::where('id',$branch_id)->update(['logo'=>$branch_info['data']['url']]);			
			}
		}		
		
        foreach ($branch_ids as $branch_id) {
//			if ($branch_id != 4) {
//				continue;
//			}
            $corp_id = Branch::where('id', $branch_id)->value('corp_id');
            $campus = new BranchCampus($branch_id);
            $wxWork = new WxWork($corp_id);
			
			$map = [
				'branch_id' => 1
			];
			$campus_info = $campus->getCampusInfo();
			if ($campus_info) {
            	$res = $campus->getTeacherDeptList();
				
            	if ($res['state'] == env('RETURN_FAIL')) {
					continue;
	            }
	            $dept_list = $res['data'][0]['child'];
    	        $dept_list_flat = $campus->getDepartList($dept_list);
        	    //企业微信获取用户列表
	            foreach ($dept_list_flat as $dept) {
    	            $dept_id = $dept['departid'];
        	        $wx_dept_id = $dept['wxdepartid'];
            	    $dept_title = $dept['departname'];
	                // 从企业微信获取用户列表
    	            $teacher_list = $wxWork->getWxDeptMemDetail($wx_dept_id, $branch_id);
					$teacher_list = json_decode($teacher_list,1);
            	    $teacher_list = $teacher_list['data']['user_list'];
                	foreach ($teacher_list as $teacher) {
						if (!$teacher) {
							continue;
						}
	                    $teacher_model = new Teacher();
						# 获取腾讯智慧校园用户id
    	                $campus_user_id = $teacher_model->getCampusUserId($branch_id, $teacher['userid']);
        	            $campus_user_id = $campus_user_id['data'];
						# 获取腾讯智慧校园用户信息
            	        $res = $campus->getCampusUserInfo($campus_user_id);
                	    $res = $res['data'];
                    	if ($res['name']) {		
	                   		 $teacher_info = [
    	                	    'branch_id' => $branch_id,
        	        	        'title' => $res['name'] ? : '',
            		            'tel' => $res['cellphone'] ? : '',
            	    	        'wx_user_id' => $teacher['userid'],
        	            	    'campus_user_id' => $campus_user_id,
		                        'dept_title' => $dept_title,
	    	                    'avatar' => $res['head'] ? : '',
								'add_time' => date('Y-m-d H:i:s')
	                    	];
						} else {
							$teacher_info = [
								'branch_id' => $branch_id,
                                'title' => $teacher['name'] ? : '',
                                'tel' => $teacher['mobile'] ? : '',
                                'wx_user_id' => $teacher['userid'],
                                'campus_user_id' => '',
                                'dept_title' => $dept_title,
                                'avatar' => $teacher['avatar'] ? : '',
                                'add_time' => date('Y-m-d H:i:s')	
							];
						}
						$where_map = [
							'branch_id' => $branch_id,
							'wx_user_id' => $teacher['userid'] ? : '',
							'is_del' => 0
						];
    	                $is_exist = Teacher::where($where_map)->value('id');
                    	if ($is_exist) {
                        	$dept_info = Teacher::where(['id' => $is_exist])->select('dept_title', 'avatar')->first();
	                        if (!$dept_info->dept_title || !$dept_info->avatar) {
    	                        Teacher::where('id', $is_exist)->update([
									'dept_title' => $dept_title,
									'avatar' => $teacher['avatar']
								]);
        	                }
            	        } else {
                    	    $teacherModel = new Teacher();
                	        $teacherModel->insertGetId($teacher_info);
                    	}
	                }
    	        }
				continue;
            	#  腾讯智慧校园获取用户列表
	            foreach ($dept_list as $dept) {
    	            $dept_id = $dept['departid'];
        	        $dept_title = $dept['departname'];
                	$res = $campus->searchUserList(2, $dept_id, 2);

	                if ($res['state'] == env('RETURN_FAIL')) {
    	                continue;
        	        }
            	    $teacher_list = $res['data']['dataList'];
                	foreach ($teacher_list as $teacher) {
                    	$res = $campus->getCampusUserInfo($teacher['userid']);

	                    $res = $res['data'];
    	                $teacher_info = [
        	                'branch_id' => $branch_id,
            	            'title' => $res['name'] ? : '',
                	        'tel' => $res['cellphone'] ? : '',
                    	    'wx_user_id' => $res['wxuserid'] ? : '',
                        	'campus_user_id' => $teacher['userid'],
	                        'dept_title' => $dept_title,
    	                    'avatar' => $res['head'] ? : '',
							'add_time' => date('Y-m-d H:i:s')
            	        ];
                	    $is_exist = Teacher::where([
                    	    'branch_id' => $branch_id,
                        	'wx_user_id' => $res['wxuserid'],
	                        'is_del' => 0,
    	                ])->value('id');
        	            if ($is_exist) {
            	            $title = Teacher::where(['id' => $is_exist])->value('dept_title');
                	        if (!$title) {
                    	        Teacher::where('id', $is_exist)->update(['dept_title' => $dept_title]);
                        	}
	                    } else {
    	                    $teacherModel = new Teacher();
        	                $teacherModel->insertGetId($teacher_info);
         	           }
            	    }
           		}
			} else {
				
                $dept_list = json_decode($wxWork->getDeptList($branch_id),1);
                foreach ($dept_list['data']['dep_list'] as $dept) {

                    $wx_dept_id = $dept['id'];
                    $dept_title = $dept['name'];
//                    // 从企业微信获取用户列表
                    $teacher_list = $wxWork->getWxDeptMemDetail($wx_dept_id, $branch_id,0);
                    $teacher_list = json_decode($teacher_list,1);
                    $teacher_list = $teacher_list['data']['user_list'];
                    foreach ($teacher_list as $teacher) {
                        if (!$teacher) {
                            continue;
                        }
                        $teacher_info = [
                            'branch_id' => $branch_id,
                            'title' => $teacher['name'] ? : '',
                            'tel' => $teacher['mobile'] ? : '',
                            'wx_user_id' => $teacher['userid'],
                            'campus_user_id' => '',
                            'dept_title' => $dept_title,
                            'avatar' => $teacher['avatar'] ? : '',
                            'add_time' => date('Y-m-d H:i:s')
                        ];
                        $is_exist = Teacher::where([
                            'branch_id' => $branch_id,
                            'wx_user_id' => $teacher['userid'] ? : '',
                            'is_del' => 0,
                        ])->value('id');
                        if ($is_exist) {
                            $title = Teacher::where(['id' => $is_exist])->value('dept_title');
                            if (!$title) {
                                Teacher::where('id', $is_exist)->update(['dept_title' => $dept_title]);
                            }
                        } else {
                            $teacherModel = new Teacher();
                            $teacherModel->insertGetId($teacher_info);
                        }
                    }
                }
			}
        }
		$this->cron_runLock(false);
    }
	public function cron_runLock($lock, $expire = 300) {
		$file = "/opt/ci123/www/html/sc-edu/campus/repair/api/repair/storage/logs/syn".'.lock';
		if (!$lock) {
			if (file_exists($file)) {
				unlink($file);
			}
		return true;
		}
		$now_time = time();
		if (file_exists($file)) {
			$last_time = intval(file_get_contents($file));
			if ($now_time - $last_time < $expire) {
				echo "\n\n".date('Y-m-d H:i:s')."\tlocked\n\n";
				exit;
			}
		}
		file_put_contents($file, $now_time);
	}
}
