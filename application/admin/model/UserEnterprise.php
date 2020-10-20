<?php

namespace app\admin\model;

use think\Model;
use \app\common\controller\Frontend;

class UserEnterprise extends Model
{
    // 表名
    protected $name = 'user_enterprise';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'level_text',
        'apply_level_text',
        'is_aptitude_text',
        'establish_time_text',
        'start_time_text',
        'end_time_text',
        'status_text',
        'is_excellent_text',
        'is_active_text',
        'is_index_recruit_text',
    ];


    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });

        self::beforeUpdate(function ($row) {
            //检测状态是否改变
            if (isset($row->status)) {
                $beforeStatus = db('user_enterprise')->field('status,user_id')->where('id', $row->id)->find();
                if ($beforeStatus['status'] != $row->status) {
                    $row->statusChange = true;
                    $row->user_id = $beforeStatus['user_id'];
                }
            }

            //将数组转为字符串
            if (is_array($row->supplier_type)) {
                $row->supplier_type = implode(',', $row->supplier_type);
            }

        });

        self::beforeInsert(function ($row) {

            //将数组转为字符串
            if (is_array($row->supplier_type)) {
                $row->supplier_type = implode(',', $row->supplier_type);
            }

        });

        self::afterDelete(function ($row) {
            //企业删除后，企业对应的文章全部下架
            db('cms_archives')->where('user_id', $row->user_id)->update(['status' => 'pulloff']);
            //修改会员企业认证状态
            db('user')->where('id', $row->user_id)->update(['enterprise_id' => null]);
        });

        self::afterWrite(function ($row) {
            //状态改变提醒消息
            if (isset($row->statusChange)) {
                createTips($row->user_id, '11');
            }

            //更新会员认证状态
            if (isset($row->user_id)) {
                if (isset($row->status) && $row->status > 0) {
                    db('user')->where('id', $row->user_id)->update(['enterprise_id' => $row->id]);
                } else {
                    db('user')->where('id', $row->user_id)->update(['enterprise_id' => null]);
                }
            }

            //检测敏感信息
            $is_sensitive = checkSensitive($row->getData());
            db('user_enterprise')->where('id', $row->id)->update(['is_sensitive' => "$is_sensitive"]);
        });
    }


    public function getLevelList()
    {
        return ['1' => __('Level 1'),'2' => __('Level 2'),'3' => __('Level 3'),'4' => __('Level 4')];
    }

    public function getApplyLevelList()
    {
        return ['0' => __('Apply_level 0'),'2' => __('Apply_level 2'),'3' => __('Apply_level 3'),'4' => __('Apply_level 4')];
    }

    public function getIsAptitudeList()
    {
        return ['1' => __('Is_aptitude 1'),'2' => __('Is_aptitude 2')];
    }     

    public function getStatusList()
    {
        return ['0' => __('Status 0'),'1' => __('Status 1'),'2' => __('Status 2'),'-1' => __('Status -1')];
    }

    public function getIsExcellentList()
    {
        return ['0' => __('Is_excellent 0'),'1' => __('Is_excellent 1')];
    }

    public function getIsActiveList()
    {
        return ['0' => __('Is_active 0'),'1' => __('Is_active 1')];
    }

    public function getIsIndexRecruitList()
    {
        return ['0' => __('Is_index_recruit 0'),'1' => __('Is_index_recruit 1')];
    }


    public function getLevelTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['level']) ? $data['level'] : '');
        $list = $this->getLevelList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getApplyLevelTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['apply_level']) ? $data['apply_level'] : '');
        $list = $this->getApplyLevelList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsAptitudeTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['is_aptitude']) ? $data['is_aptitude'] : '');
        $list = $this->getIsAptitudeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getEstablishTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['establish_time']) ? $data['establish_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['start_time']) ? $data['start_time'] : '');

        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['end_time']) ? $data['end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsExcellentTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_excellent']) ? $data['is_excellent'] : '');
        $list = $this->getIsExcellentList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getIsActiveTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_active']) ? $data['is_active'] : '');
        $list = $this->getIsActiveList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getIsIndexRecruitTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_index_recruit']) ? $data['is_index_recruit'] : '');
        $list = $this->getIsIndexRecruitList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setEstablishTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setStartTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setEndTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
