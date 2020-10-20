<?php

namespace app\admin\model\user;

use app\common\model\Config;
use think\Model;

class Expert extends Model
{
    // 表名
    protected $name = 'user_expert';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'sex_text',
        'service_text',
//        'service_starttime_text',
//        'service_endtime_text',
        'deadline_starttime_text',
        'deadline_endtime_text',
        'status_text'
    ];
    
    public static function init()
    {
        self::afterWrite(function ($row) {
            // if (isset($row['adept'])) {
            //     $tags = array_filter(explode(',', $row['adept']));
            //     if ($tags) {
            //         $tagslist = \app\admin\model\cms\Tags::where('name', 'in', $tags)->select();
            //         foreach ($tagslist as $k => $v) {
            //             $archives = explode(',', $v['archives']);
            //             if (!in_array($row['id'], $archives)) {
            //                 $archives[] = $row['id'];
            //                 $v->archives = implode(',', $archives);
            //                 $v->nums++;
            //                 $v->save();
            //             }
            //             $tags = array_diff($tags, [$v['name']]);
            //         }
            //         $list = [];
            //         foreach ($tags as $k => $v) {
            //             $list[] = ['name' => $v, 'archives' => $row['id'], 'nums' => 1, 'type' => 0];
            //         }
            //         if ($list) {
            //             (new \app\admin\model\cms\Tags())->saveAll($list);
            //         }
            //     }
            // }

            //检测敏感信息
            $is_sensitive = checkSensitive($row->getData());
            db('user_expert')->where('id', $row->id)->update(['is_sensitive' => "$is_sensitive"]);
        });
    }

    public function getSexList()
    {
        return ['男' => __('Sex 男'),'女' => __('Sex 女')];
    }

    public function getLevelList()
    {
        return ['1' => __('Level 1'),'2' => __('Level 2'),'3' => __('Level 3')];
    }

    public function getServiceList()
    {
        return ['online' => __('Service online'),'locale' => __('Service locale')];
    }     

    public function getStatusList()
    {
        return ['0' => __('Status 0'),'1' => __('Status 1'),'2' => __('Status 2')];
    }

    public function getIndex_recommendList()
    {
        return ['0' => __('Index_recommend 0'),'1' => __('Index_recommend 1')];
    }

    public function getIndex_list_recommendList()
    {
        return ['0' => __('Index_list_recommend 0'),'1' => __('Index_list_recommend 1')];
    }

    public function getMobile_index_recommendList()
    {
        return ['0' => __('Mobile_index_recommend 0'),'1' => __('Mobile_index_recommend 1')];
    }


    public function getSexTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getServiceTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['service']) ? $data['service'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getServiceList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


//    public function getServiceStarttimeTextAttr($value, $data)
//    {
//        $value = $value ? $value : (isset($data['service_starttime']) ? $data['service_starttime'] : '');
//        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
//    }
//
//
//    public function getServiceEndtimeTextAttr($value, $data)
//    {
//        $value = $value ? $value : (isset($data['service_endtime']) ? $data['service_endtime'] : '');
//        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
//    }


    public function getDeadlineStarttimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['deadline_starttime']) ? $data['deadline_starttime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getDeadlineEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['deadline_endtime']) ? $data['deadline_endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setServiceAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

//    protected function setServiceStarttimeAttr($value)
//    {
//        return $value && !is_numeric($value) ? strtotime($value) : $value;
//    }
//
//    protected function setServiceEndtimeAttr($value)
//    {
//        return $value && !is_numeric($value) ? strtotime($value) : $value;
//    }

    protected function setDeadlineStarttimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setDeadlineEndtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
