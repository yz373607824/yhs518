<?php

namespace app\admin\validate\expert;

use think\Validate;

class Locale extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'linkman' => 'require',
        'enterprise' => 'require',
        'mobile' => 'regex:/^1\d{10}$/',
        'reservation_time' => 'require',
        'reservation_address' => 'require',
        'title' => 'require',
        'question_description' => 'require',
        'locale_token' => 'token:locale_token',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'linkman.require' => '联系人不能为空',
        'enterprise.require' => '企业名称不能为空',
        'mobile' => '手机格式错误',
        'reservation_time.require' => '预约时间不能为空',
        'reservation_address.require' => '预约地址不能为空',
        'title.require' => '标题不能为空',
        'question_description.require' => '问题描述不能为空',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];
    
}
