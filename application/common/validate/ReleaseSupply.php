<?php

namespace app\common\validate;

use think\Validate;

class ReleaseSupply extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        ['category', 'require', '产品分类必填'],
        ['title', 'require', '产品名称必填'],
        ['image', 'require', '产品图片必填'],
        ['content', 'require', '详情介绍必填'],
        ['tags', 'require', '产品关键词必填'],
        ['price', 'require', '商品售价必填'],
        ['total', 'require', '供应总量必填'],
        ['number', 'require', '数量必填'],
        ['description', 'require', '产品描述必填'],
        ['unit', 'require', '单位必填'],
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
