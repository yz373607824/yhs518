<?php

namespace app\common\validate;

use think\Validate;

class ContributeRecipe extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        ['title', 'require', '文章标题必填'],
        ['description', 'require', '简介必填'],
        ['purpose', 'require', '用途必填'],
        ['content', 'require', '详情及配置方法必填'],
        ['tags', 'require', '标签必填'],
        'price' => ['regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/'],
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'price' => '售价格式错误，不能为负数，保留两位小数',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];

}
