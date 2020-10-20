<?php

namespace app\admin\model\user;

use think\Model;

class Bill extends Model
{
    // 表名
    protected $name = 'user_bill';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'type_text'
    ];

    public function getTypeList()
    {
        return ['score' => __('金豆'), 'points' => __('金币'), 'alipay' => __('支付宝'), 'wechat' => __('微信')];
    }

    public function getOrderTypeList()
    {
        return ['1' => __('在线提问'), '2' => __('现场服务'), '3' => __('配方'), '4' => __('知识·经验'), '5' => __('抢购活动')];
    }

    public function getTypeAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getOrderTypeAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['order_type']) ? $data['order_type'] : '');
        $list = $this->getOrderTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
