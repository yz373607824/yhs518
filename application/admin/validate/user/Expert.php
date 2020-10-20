<?php

namespace app\admin\validate\user;

use think\Validate;

class Expert extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'nickname' => 'require',
        'name' => 'require',
        'sex' => 'require',
        'avatar' => 'require',
        'mobile' => 'regex:/^1\d{10}$/',
        'email' => 'require|email',
        'education' => 'require',
        'id_just_image' => 'require',
        'id_back_image' => 'require',
        'id_hand_image'=> 'require',
        'company' => 'require',
        'province' => 'require',
        'city' => 'require',
        'county' => 'require',
        'address' => 'require',
        'level' => 'require',
        'job' => 'require',
        'technosphere' => 'require',
        'workage' => 'require',
        'service'  => 'require',
        'deadline_starttime' => 'require',
        'deadline_endtime' => 'require',
        'expert_application_token' => 'token:expert_application_token',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'nickname.require' => '昵称不能为空',
        'name.require' => '姓名不能为空',
        'sex.require' => '性别不能为空',
        'avatar.require' => '头像不能为空',
        'education.require' => '学历不能为空',
        'company.require' => '单位不能为空',
        'province.require' => '省份不能为空',
        'id_just_image.require' => '个人身份证正面不能为空',
        'id_back_image.require' => '个人身份证反面不能为空',
        'id_hand_image.require' => '个人手持身份证正面不能为空',
        'city.require' => '城市不能为空',
        'county.require' => '区县不能为空',
        'address.require' => '详细地址不能为空',
        'level.require' => '申请职称不能为空',
        'job.require' => '现任单位职务不能为空',
        'technosphere.require' => '从事技术领域不能为空',
        'workage.require' => '从业工龄不能为空',
        'service.require' => '专家服务项不能为空',
        'deadline_starttime.require' => '专家期限开始时间不能为空',
        'deadline_endtime.require' => '专家期限结束时间不能为空',
        'email' => 'Email is incorrect',
        'mobile' => 'Mobile is incorrect',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];
    
}
