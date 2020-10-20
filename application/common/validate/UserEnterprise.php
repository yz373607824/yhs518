<?php

namespace app\common\validate;

use think\Validate;

class UserEnterprise extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        ['user_id', 'require', '会员id必填'],
        ['enterprise_type', 'require', '企业类型必填'],
        ['business_type', 'require', '商家类型必填'],
        ['supplier_type', 'require', '供应商类别必填'],
        //['certificate_type', 'require', '证件类型必填'],
        ['license_image', 'require', '营业执照必填'],
        ['is_aptitude', 'require', '一般纳税人资质必填'],
        ['company', 'require', '企业名称必填'],
        ['nature', 'require', '企业性质必填'],
        //['license', 'require', '营业执照必填'],
        ['establish_time', 'require', '成立时间必填'],
        ['code', 'require', '企业信用统一代码必填'],
        ['corporation', 'require', '法人姓名必填'],
        ['capital', 'require', '注册资本必填'],
        ['contact_name', 'require', '企业入驻联系人必填'],
        ['contact_tel', 'require', '企业入驻联系电话必填'],
        ['id_number', 'require', '身份证号码必填'],
        ['id_just_image', 'require', '企业入驻人身份证正面必填'],
        ['id_back_image', 'require', '企业入驻人身份证反面必填'],
        ['id_hand_image', 'require', '企业入驻人手持身份证正面照片必填'],
        ['email', 'require', '电子邮箱必填'],
        ['province', 'require', '省份'],
        ['city', 'require', '城市'],
        ['area', 'require', '地区'],
        ['address', 'require', '详细地址'],
        ['brand', 'require', '主要品牌必填'],
        ['product', 'require', '主营产品必填'],
        //['product_image', 'require', '产品资料必填'],
        ['introduce', 'require', '公司介绍必填'],
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
