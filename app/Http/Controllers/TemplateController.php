<?php
/**
 * Created by PhpStorm.
 * User: xiaoxiaonan
 * Date: 2019/5/28
 * Time: 16:57
 */

namespace App\Http\Controllers;

use App\Models\Base;
use Illuminate\Http\Request;
use App\Models\Template;

class TemplateController extends Base
{

    /**
     * 获取模板
     *
     * @param Request $request
     * @return array
     */
    public function getTemplate(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');

        $template = Template::where('branch_id',$branch_id)
            ->select('branch_id',
                'equipment_type' ,
                'equipment_name',
                'equipment_model',
                'equipment_no',
                'department',
                'malfuntcion_time',
//                'report_time',
                'malfuntcion_area',
                'malfuntcion_description',
                'image',
                'emergency_level',
                'mobile')
            ->first();


        if (!$template) {
            $data = [
                'branch_id' => $branch_id,
                'equipment_type' => 2,
                'equipment_name' => 2,
                'equipment_model' => 1,
                'equipment_no' => 0,
                'department' => 0,
                'malfuntcion_time' => 0,
//                'report_time' => 2,
                'malfuntcion_area' => 2,
                'malfuntcion_description' => 2,
                'image' => 0,
                'emergency_level' => 0,
                'mobile' => 0,
                'add_time' => date('Y-m-d H:i:s')
            ];
            $id = Template::insertGetId($data);
            if ($id) {
                return $this->buildReturn(env('RETURN_SUCCESS'), '',['template' => $data]);
            }
            return $this->buildReturn(env('RETURN_FAIL'), '获取失败');
        }
        return $this->buildReturn(env('RETURN_SUCCESS'), '',['template' => $template->toArray()]);
    }

    /**
     * 修改模板
     *
     * @param Request $request
     * @return array
     */
    public function editTemplate(Request $request)
    {
        $branch_id = $request->session()->get('branch_id');
        $equipment_no = $request->input('equipment_no','0');
        $department = $request->input('department','0');
        $malfuntcion_time = $request->input('malfuntcion_time','0');
        $image = $request->input('image','0');
        $emergency_level = $request->input('emergency_level','0');
        $equipment_model = $request->input('equipment_model','0');
        $mobile = $request->input('mobile','0');

        $data = [
            'equipment_no' => $equipment_no,
            'department' => $department,
            'malfuntcion_time' => $malfuntcion_time,
            'image' => $image,
            'emergency_level' => $emergency_level,
            'update_time' => date('Y-m-d H:i:s'),
            'equipment_model' => $equipment_model,
            'mobile' => $mobile
        ];

        $result = Template::where('branch_id', $branch_id)->update($data);
        if ($result) {
            return $this->buildReturn(env('RETURN_SUCCESS'), '操作成功');
        }
        return $this->buildReturn(env('RETURN_FAIL'), '操作失败');

    }
}