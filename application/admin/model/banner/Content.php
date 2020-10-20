<?php

namespace app\admin\model\banner;

use think\Model;

class Content extends Model
{
    // 表名
    protected $name = 'banner_content';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'is_time_text',
        'endtime_text'
    ];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    public function getIsTimeList()
    {
        return ['0' => __('Is_time 0'),'1' => __('Is_time 1')];
    }


    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getIsTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_time']) ? $data['is_time'] : '');
        $list = $this->getIsTimeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setEndtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


    public function manage()
    {
        return $this->belongsTo('Manage', 'manage_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
