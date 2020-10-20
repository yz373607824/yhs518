<?php

namespace app\common\validate;

use think\Validate;

class ReleaseActivity extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        ['title', 'require', '标题必填'],
        ['image', 'require', '首页图片必填'],
        ['images', 'require', '产品图片必填'],
        ['format', 'require', '规格必填'],
        ['number', 'require', '总量必填'],
        ['unit_price', 'require', '单价必填'],
        ['price', 'require', '售价必填'],
        ['unit', 'require', '单位必填'],
        ['location', 'require', '仓库地址必填'],
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
