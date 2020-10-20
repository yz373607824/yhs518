<?php

namespace app\admin\model\cms;

use app\common\model\Config;
use think\Model;
use traits\model\SoftDelete;

class Archives extends Model
{

    use SoftDelete;

    // 表名
    protected $name = 'cms_archives';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';
    // 追加属性
    protected $append = [
        'flag_text',
        'status_text',
        'publishtime_text',
        'url',
    ];

    public function getUrlAttr($value, $data)
    {
        $diyname = $data['diyname'] ? $data['diyname'] : $data['id'];
        return addon_url('cms/archives/index', [':id' => $data['id'], ':diyname' => $diyname, ':channel' => $data['channel_id']]);
    }

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $channel = Channel::get($row['channel_id']);
            $row->getQuery()->where($pk, $row[$pk])->update(['model_id' => $channel ? $channel['model_id'] : 0, 'weigh' => $row[$pk]]);
            Channel::where('id', $row['channel_id'])->setInc('items');
        });
        self::beforeWrite(function ($row) {
            //关键词和描述处理
//            if (isset($row['content']) && (!isset($row['description']) || !$row['description'])) {
//                $row['description'] = mb_substr(strip_tags(htmlspecialchars_decode($row['content'])), 0, 80);
//            }
//            if (isset($row['title']) && (!isset($row['keywords']) || !$row['keywords'])) {
//                $row['keywords'] = mb_substr(strip_tags($row['title']), 0, 80);
//            }
            $channel = Channel::get($row['channel_id']);
            //推荐字段
            $fields = db('cms_fields')->where(['model_id' => $channel['model_id'], 'isrecommend' => 1])->column('title', 'name');
            $recommends = [];

            //在更新之前对数组进行处理
            foreach ($row->getData() as $k => $value) {
                if (is_array($value) && isset($value['field'])) {
                    $value = json_encode(Config::getArrayData($value), JSON_UNESCAPED_UNICODE);
                } else {
                    $value = is_array($value) ? implode(',', $value) : $value;
                }
                $row->$k = $value;

                //是否推荐
                if (isset($fields[$k]) && $value == 1) {
                    $recommends[] = $fields[$k];
                }
            }

            //推荐位
            $row->recommends = implode(',', $recommends);
        });
        self::afterWrite(function ($row) {

            if (isset($row['channel_id'])) {
                //在更新成功后刷新副表、TAGS表数据、栏目表
                $channel = Channel::get($row->channel_id);
                if ($channel) {
                    $model = Modelx::get($channel['model_id']);
                    if ($model && isset($row['content'])) {
                        $values = array_intersect_key($row->getData(), array_flip($model->fields));
                        $values['id'] = $row['id'];
                        $values['content'] = $row['content'];

                        if (db($model['table'])->where('id', $row['id'])->find()) {
                            //更新
                            $row->isChange = db($model['table'])->update($values);
                        } else {
                            //添加
                            db($model['table'])->insert($values, TRUE);
                        }

                    }
                }
            }
            if (isset($row['tags'])) {
                $tags = array_unique(array_filter(explode(',', $row['tags'])));
                if ($tags) {
                    $tagslist = Tags::where('name', 'in', $tags)->where('type', $row['channel_id'])->select();
                    foreach ($tagslist as $k => $v) {
                        $archives = explode(',', $v['archives']);
                        if (!in_array($row['id'], $archives)) {
                            $archives[] = $row['id'];
                            $v->archives = implode(',', $archives);
                            $v->nums++;
                            $v->save();
                        }
                        $tags = array_diff($tags, [$v['name']]);
                    }
                    $list = [];
                    foreach ($tags as $k => $v) {
                        $list[] = ['name' => $v, 'archives' => $row['id'], 'nums' => 1, 'type' => $row['channel_id']];
                    }
                    if ($list) {
                        (new Tags())->saveAll($list);
                    }
                }
            }

            //检测敏感信息
            $is_sensitive = checkSensitive($row->getData());
            db('cms_archives')->where('id', $row->id)->update(['is_sensitive' => "$is_sensitive"]);

        });
    }

    public function getFlagList()
    {
//        return ['hot' => __('Hot'), 'new' => __('New'), 'recommend' => __('Recommend')];
        return ['hot' => __('Hot')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Status normal'), 'hidden' => __('Status hidden'), 'pulloff' => __('Status pulloff'), 'rejected' => __('Status rejected'), 'unreleased' => __('Status unreleased'),];
    }

    public function getFlagTextAttr($value, $data)
    {
        $value = $value ? $value : $data['flag'];
        $valueArr = $value ? explode(',', $value) : [];
        $list = $this->getFlagList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getPublishtimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['publishtime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPublishtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function channel()
    {
        return $this->belongsTo('Channel', 'channel_id', '', [], 'LEFT')->setEagerlyType(0);
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', '', [], 'LEFT')->setEagerlyType(0);
    }
}
