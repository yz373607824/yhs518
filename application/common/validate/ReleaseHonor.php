<?php

namespace app\common\validate;

use think\Validate;

class ReleaseHonor extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        ['title', 'require', '标题必填'],
        ['image', 'require', '图片必传'],
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
