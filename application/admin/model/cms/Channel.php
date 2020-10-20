<?php

namespace app\admin\model\cms;

use app\admin\library\Auth;
use app\admin\model\AuthGroup;
use think\Db;
use think\Exception;
use think\Model;
use think\Request;

class Channel extends Model
{

    // 表名
    protected $name = 'cms_channel';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'url',
        'vote_end_time_text',
    ];

    // 新增/删除一级栏目的权限路由定义
    const PATH_ADD_TOP = 'cms/channel/addtop';
    const PATH_DEL_TOP = 'cms/channel/deltop';

    public function getUrlAttr($value, $data)
    {
        $diyname = $data['diyname'] ? $data['diyname'] : $data['id'];
//        return isset($data['type']) && isset($data['outlink']) && $data['type'] == 'link' ? $data['outlink'] : addon_url('cms/channel/index', [':id' => $data['id'], ':diyname' => $diyname]);
        return "/channel/".$diyname;
    }

    protected static function init()
    {
        /*self::afterInsert(function ($row) {
            //创建时自动添加权重值
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });*/
        $auth = new Auth();

        self::beforeInsert(function ($row) use ($auth) {
            if ($row->getData('type') == 'link') {
                $row->model_id = 0;
            }
            if ($row->getData('type') == 'page') {
                $row->model_id = $row->page_model_id;
            }
            // 检测管理员组是否有添加一级栏目权限
            $hasAddTopChannelAuth = Auth::instance()->check(self::PATH_ADD_TOP);
            if (!$auth->isSuperAdmin() && $row['parent_id'] == 0 && $hasAddTopChannelAuth == false) {
                throw new Exception(__('You have no permission'));
            }
        });
        self::beforeDelete(function ($row) use ($auth) {
            // 检测管理员组是否有删除一级栏目权限
            $hasDelTopChannelAuth = Auth::instance()->check(self::PATH_DEL_TOP);
            if (!$auth->isSuperAdmin() && $row['parent_id'] == 0 && $hasDelTopChannelAuth == false) {
                throw new Exception(__('You have no permission'));
            }
        });
        self::afterInsert(function ($row) use ($auth) {
            //创建时自动添加权重值 修改语言版本
            $lang = Request::instance()->langset();
            $pk   = $row->getPk();

            $archivesId = 0;
            if ($row->getData('type') == 'page') {
                // 新增一条单页栏目的单页信息
                Db::name('cms_archives')->insert([
                    'channel_id' => $row[$pk] ,
                    'model_id'   => $row['model_id'] ,
                    'title'      => $row['name'] ,
                    'status'     => 'normal' ,
                    'createtime' => time() ,
                    'updatetime' => time() ,
                    'lang'       => $lang ,
                ]);
                $archivesId = Db::name('cms_archives')->getLastInsID();
                $modelTable = Db::name('cms_model')->where('id' , $row['model_id'])->value('table');
                Db::name($modelTable)->insert([
                    'id' => $archivesId ,
                ]);
            }

            $row->getQuery()->where($pk , $row[$pk])->update(['weigh' => $row[$pk] , 'lang' => $lang , 'page_id' => $archivesId ,]);

            // 把新增的栏目放到网站管理员和当前用户组的权限里
            if (!$auth->isSuperAdmin()) {
                // 当前管理员组们
                $groupIds = $auth->getGroupIds();
                self::handleGroupChannelAuth($row[$pk] , $row['parent_id'] , $groupIds);
            }
        });
        self::afterDelete(function ($row) {
            //删除时，删除子节点，同时将所有相关文档移入回收站
            static $tree;
            if (!$tree) {
                $tree = \fast\Tree::instance();
                $tree->init(collection(Channel::order('weigh desc,id desc')->field('id,parent_id,name,type,diyname,status')->select())->toArray(), 'parent_id');
            }
            $childIds = $tree->getChildrenIds($row['id']);
            if ($childIds) {
                Channel::destroy(function ($query) use ($childIds) {
                    $query->where('id', 'in', $childIds);
                });
            }
            $childIds[] = $row['id'];
            db('cms_archives')->where('channel_id', 'in', $childIds)->update(['deletetime' => time()]);
        });
        self::afterWrite(function ($row) {
            $changed = $row->getChangedData();
            //隐藏时判断是否有子节点,有则隐藏
            if (isset($changed['status']) && $changed['status'] == 'hidden') {
                static $tree;
                if (!$tree) {
                    $tree = \fast\Tree::instance();
                    $tree->init(collection(Channel::order('weigh desc,id desc')->field('id,parent_id,name,type,diyname,status')->select())->toArray(), 'parent_id');
                }
                $childIds = $tree->getChildrenIds($row['id']);
                db('cms_channel')->where('id', 'in', $childIds)->update(['status' => 'hidden']);
            }
        });
    }

    public static function handleGroupChannelAuth($channelId , $parentId , $groupIds)
    {
        $auth = new Auth();
        // 循环管理员组
        foreach ($groupIds as $groupId) {
            $rules = AuthGroup::fetchChannelRulesById($groupId);
            $rules = explode(',' , $rules);
            if ($parentId == 0 && $auth->check(self::PATH_ADD_TOP)) {
                // 如果父级ID是0，则是一级栏目，判断这个组是否有一级栏目添加权限，有的话新增这个栏目权限
                $rules[] = $channelId;
                AuthGroup::saveChannelRulesById($groupId , implode(',' , $rules));
            } elseif ($parentId != 0 && in_array($parentId , $rules)) {
                // 如果父级ID在组的栏目权限里面，增加这个新增栏目权限
                $rules[] = $channelId;
                AuthGroup::saveChannelRulesById($groupId , implode(',' , $rules));
            }
        }
        // 不管条件给网站管理员组增加栏目权限
        $rules = AuthGroup::fetchChannelRulesById(Auth::WEB_ADMINISTRATOR_GROUPID);
        $rules .= ',' . $channelId;
        AuthGroup::saveChannelRulesById(Auth::WEB_ADMINISTRATOR_GROUPID , $rules);
        return true;
    }

    public static function getTypeList()
    {
        return ['channel' => __('Channel'), 'page' => __('Page'), 'list' => __('List'), 'link' => __('Link')];
    }

    public static function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getVoteEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['vote_end_time']) ? $data['vote_end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    protected function setVoteEndTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }
    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function model()
    {
        return $this->belongsTo('Modelx', 'model_id')->setEagerlyType(0);
    }

}
