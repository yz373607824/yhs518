<?php

namespace app\admin\model\message;

use think\Model;
use \app\common\controller\Frontend;

class Message extends Model
{
    // 表名
    protected $name = 'message';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'send_user_text'
    ];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            /**
             * 发送平台群发
             * 数据格式：1-3,1-4,2-1,2-2,2-3
             * 多种会员类型以,分隔
             * 单个会员类型格式：1-3 => type-level
             * type:会员类型，1：企业会员，2：专家
             * level：对应会员类型的等级
             */
            $sendUser = explode(',', $row->send_user);

            $sendIds = [];
            foreach ($sendUser as $v) {
                list($type, $level) = explode('-', $v);
                switch ($type) {
                    //企业会员
                    case 1:
                        $ids = db('user_enterprise')->where('level', $level)->column('user_id');
                        $sendIds = array_merge($sendIds, $ids);
                        break;
                    //专家
                    case 2:
                        $ids = db('user_expert')->where('level', $level)->column('user_id');
                        $sendIds = array_merge($sendIds, $ids);
                        break;
                    //个人认证会员
                    case 3:
                        $ids = db('user_verify')->column('user_id');
                        $sendIds = array_merge($sendIds, $ids);
                        break;
                    //非个人认证会员
                    case 4:
                        $ids = db('user')->where(['verify_id' => null, 'enterprise_id' => null, 'expert_id' => null])->column('id');
                        $sendIds = array_merge($sendIds, $ids);
                        break;
                }
            }

            //插入发送记录
            foreach ($sendIds as $user_id) {
                if (!db('message_record')->where(['message_id' => $row->id, 'user_id' => $user_id])->find()) {

                    db('message_record')->insert(['message_id' => $row->id, 'user_id' => $user_id]);

                    //消息提醒
                    $frontend = new Frontend();
                    $frontend->createTips($user_id, '3');
                }

            }
        });

        self::afterDelete(function ($row) {
            //删除平台群发
            db('message_record')->where('message_id', $row->id)->delete();
        });
    }
    

    
    public function getSendUserList()
    {
        return ['1-1' => __('Send_user 1-1'),'1-2' => __('Send_user 1-2'),'1-3' => __('Send_user 1-3'),'1-4' => __('Send_user 1-4'),'2-1' => __('Send_user 2-1'),'2-2' => __('Send_user 2-2'),'2-3' => __('Send_user 2-3'),'3-1' => __('Send_user 3-1'),'4-1' => __('Send_user 4-1')];
    }     


    public function getSendUserTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['send_user']) ? $data['send_user'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getSendUserList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }

    protected function setSendUserAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }


}
