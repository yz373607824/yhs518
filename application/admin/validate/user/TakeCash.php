<?php

namespace app\admin\validate\user;

use think\Validate;

class Takecash extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => 'require',
        'type' => 'require',
        'cardno' => 'require',
        'username' => 'require',
        'mobile' => 'require',
        'amount' => 'require',
        'apply_task_cash_token' => 'token:apply_task_cash_token'
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '银行名称不能为空',
        'type.require' => '账号类型不能为空',
        'cardno.require' => '银行卡号不能为空',
        'username.require' => '开户人姓名不能为空',
        'mobile.require' => '手机号码不能为空',
        'amount.require' => '提现金额不能为空',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];
    
}
