<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//授权
$app->get('/', 'OauthController@index');
$app->get('/login', 'OauthController@login');
$app->get('/qr_login', 'OauthController@web_login');
$app->get('/web_login', 'OauthController@webOauth');
$app->post('/get_token', 'OauthController@getOauth');

//代开发
$app->get('/suite/receive', 'AuthenticationController@checkUrlVerification');
$app->post('/suite/receive', 'AuthenticationController@getAuthOrTicket');

// test
// $app->get('/test_test_test', 'OauthController@getTestTest');

//$app->get('/test', 'OauthController@test');

$app->group(['middleware' => 'auth'], function () use ($app) {

    $app->get('/infotest', 'OauthController@getinfo');
    # 获取用户信息
    $app->post('/login_mess', 'TeacherController@userInfo');
    $app->post('/user_list', 'TeacherController@userList');
    $app->post('/dept_list', 'TeacherController@getDeptList');
    $app->post('/dept_teacher', 'TeacherController@getDeptTeacher');

    # 权限设置
    $app->post('/teacher_list', 'TeacherController@searchAdmin');
    $app->post('/teacher_search', 'TeacherController@searchTeacher');
    $app->post('/add_admin', 'TeacherController@addAdmin');
    $app->post('/del_admin', 'TeacherController@delAdmin');
    $app->post('/edit_admin', 'TeacherController@editAdmin');

    # 故障设备类型
    $app->post('/add_type', 'EquipmentController@add_type');
    $app->post('/type_list', 'EquipmentController@typeList');
    $app->post('/del_type', 'EquipmentController@delType');
    $app->post('/edit_type', 'EquipmentController@editType');
    $app->post('/child_list', 'EquipmentController@getChildTypeList');

    # 故障流程
    $app->post('/process/add', 'ProcessController@addProcess');
    $app->post('/process/list', 'ProcessController@processList');
    $app->post('/process/del', 'ProcessController@processDel');
    $app->post('/process/detail', 'ProcessController@processDetail');
    $app->post('/process/edit', 'ProcessController@processEdit');
    $app->post('/process/type_list', 'EquipmentController@getList');

    # 模板操作
    $app->post('/template/info', 'TemplateController@getTemplate');
    $app->post('/template/edit', 'TemplateController@editTemplate');

    # 报修
    $app->post('/repair/get_process', 'RepairController@getProcess');
    $app->post('/repair/type_list', 'EquipmentController@getTypeList');
    $app->post('/repair/add', 'RepairController@addRepair');
    $app->post('/repair/list', 'RepairController@orderList');
    $app->post('/repair/detail', 'RepairController@orderdetail');
    $app->post('/repair/unset', 'RepairController@unsetRepair');
    $app->post('/repair/urge', 'RepairController@urgeRepair');
    $app->post('/record/add', 'RepairController@addRecord');

    $app->post('/record/list', 'RepairController@recordList');
    $app->post('/record/del_order', 'RepairController@orderDel');
    $app->post('/record/count', 'RepairController@recordCount');
    $app->get('/record/export', 'RepairController@recordExport');

    # JS_SDK
    $app->get('/wx/sdk', 'WeixinController@getJSSDK');
    $app->get('/wx/getImg', 'WeixinController@getImgUrl');

});

$app->get('/myTest', function () use ($app) {

    $data = [
        'branch_id' => 1,
        'user_id' => 1,
        'wx_user_id' => 'C2hRh83LSaMdone'
    ];
    $token = \Illuminate\Support\Facades\Crypt::encrypt(json_encode($data));
    var_dump($token);
});