<?php

namespace app\admin\model;

use think\Model;

class AuthGroup extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function getNameAttr($value, $data)
    {
        return __($value);
    }

    public static function fetchChannelRulesById($id)
    {
        return self::where('id' , $id)->value('channel_rules');
    }

    public static function saveChannelRulesById($id , $channelRules)
    {
        $model = new self();
        return $model->save(['channel_rules' => $channelRules] , ['id' => $id]);
    }

}
