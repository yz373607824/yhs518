<?php

namespace app\common\validate;

use think\Validate;

class ReleasePurchase extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        ['category', 'require', '产品分类必填'],
        ['title', 'require', '产品名称必填'],
        ['tags', 'require', '产品关键词必填'],
        ['standard', 'require', '产品规格必填'],
        ['unit', 'require', '单位必填'],
        ['number', 'require', '数量必填'],
        ['contacts', 'require', '联系人必填'],
        ['tel', 'require', '联系电话必填'],
        ['end_time', 'require', '采购截止日期必填'],
        ['content', 'require', '详细描述必填'],
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
