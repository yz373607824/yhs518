<?php

namespace app\common\validate;

use think\Validate;

class ReleaseJobWanted extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        ['title', 'require', '期望职位必填'],
        ['name', 'require', '姓名必填'],
        ['content', 'require', '个人简历必填'],
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];

}
