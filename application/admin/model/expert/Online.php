<?php

namespace app\admin\model\expert;

use think\Model;

class Online extends Model
{
    // 表名
    protected $name = 'expert_online';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'is_reply_text',
        'is_rollback_text'
    ];
    

    
    public function getIsReplyList()
    {
        return ['0' => __('Is_reply 0'),'1' => __('Is_reply 1')];
    }     

    public function getIsRollbackList()
    {
        return ['0' => __('Is_rollback 0'),'1' => __('Is_rollback 1')];
    }     


    public function getIsReplyTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['is_reply']) ? $data['is_reply'] : '');
        $list = $this->getIsReplyList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsRollbackTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['is_rollback']) ? $data['is_rollback'] : '');
        $list = $this->getIsRollbackList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function expert()
    {
        return $this->belongsTo('app\admin\model\user\Expert', 'expert_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
