<?php

namespace app\common\validate;

use think\Validate;

class ReleaseRecruit extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        ['title', 'require', '职位必填'],
        ['location', 'require', '地点必填'],
        ['number', 'require', '招聘人数必填'],
        ['company', 'require', '公司必填'],
        ['content', 'require', '详情必填'],
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
