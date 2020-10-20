<?php

namespace app\admin\controller\cms;

use app\admin\model\cms\Channel;
use app\admin\model\user\Expert;
use app\admin\model\UserEnterprise;
use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\db\Query;
use think\Lang;

/**
 * 内容表
 *
 * @icon fa fa-circle-o
 */
class Archives extends Backend
{
    protected $searchFields = 'id,title';

    /**
     * Archives模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['get_channel_fields', 'check_element_available'];
    protected $channelIds = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Archives;

        // 栏目权限控制
        $channelRuleIds = $this->auth->getChannelRuleIds();
        $whereChannelIds = $this->auth->isSuperAdmin() ? '' : ['id' => ['in', $channelRuleIds]];

        $channelList = [];
        $disabledIds = [];
        $all = collection(Channel::where($whereChannelIds)->order("weigh asc,id asc")->select())->toArray();

        //允许的栏目
        $this->channelIds = $channelRuleIds;
        $parentChannelIds = Channel::where('id' , 'in' , $this->channelIds)->column('parent_id');
        foreach ($all as $k => $v) {
            $state = ['opened' => true];
            if ($v['type'] != 'list') {
                $disabledIds[] = $v['id'];
            }
            if ($v['type'] == 'link') {
                $state['checkbox_disabled'] = true;
            }
            $channelList[] = [
                'id'     => $v['id'],
                'parent' => $v['parent_id'] ? $v['parent_id'] : '#',
                'text'   => __($v['name']),
                'type'   => $v['type'],
                'state'  => $state
            ];
        }
        $tree = Tree::instance()->init($all, 'parent_id');
        $channelOptions = $tree->getTree(0, "<option value=@id @selected @disabled>@spacer@name</option>", '', $disabledIds);
        $this->view->assign('channelOptions', $channelOptions);
        $this->assignconfig('channelList', $channelList);

        $this->view->assign("flagList", $this->model->getFlagList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->relationSearch = TRUE;
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['user','channel'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            if (!$this->auth->isSuperAdmin()) {
                $this->model->where('channel_id' , 'in' , $this->channelIds);
            }
            $list = $this->model
                ->with(['user','channel'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {
                $row['title'] = str_replace("&#39;","'",html_entity_decode($row['title']));
                $row->getRelation('user')->visible(['nickname']);
                $row->getRelation('channel')->visible(['name']);
            }

            $list = collection($list)->toArray();
            array_walk($list, function (&$v) {
                //去掉目录
                $v['url'] = '/archives/' . $v['id'];
                return $v;
            });

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        $modelList = \app\admin\model\cms\Modelx::all();
        $this->view->assign('modelList', $modelList);
        return $this->view->fetch();
    }

    /**
     * 副表内容
     */
    public function content($model_id = null)
    {
        $model = \app\admin\model\cms\Modelx::get($model_id);
        if (!$model) {
            $this->error('未找到对应模型');
        }
        $fieldsList = \app\admin\model\cms\Fields::where('model_id', $model['id'])->where('type', '<>', 'text')->select();

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $fields = [];
            foreach ($fieldsList as $index => $item) {
                $fields[] = "addon." . $item['name'];
            }
            $table = $this->model->getTable();
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = 'main.id';
            $total = Db::table($table)
                ->alias('main')
                ->join('cms_channel channel', 'channel.id=main.channel_id', 'LEFT')
                ->join($model['table'] . ' addon', 'addon.id=main.id', 'LEFT')
                ->field('main.id,main.channel_id,main.title,channel.name as channel_name,addon.id as aid' . ($fields ? ',' . implode(',', $fields) : ''))
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = Db::table($table)
                ->alias('main')
                ->join('cms_channel channel', 'channel.id=main.channel_id', 'LEFT')
                ->join($model['table'] . ' addon', 'addon.id=main.id', 'LEFT')
                ->field('main.id,main.channel_id,main.title,channel.name as channel_name,addon.id as aid' . ($fields ? ',' . implode(',', $fields) : ''))
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $fields = [];
        foreach ($fieldsList as $index => $item) {
            $fields[] = ['field' => $item['name'], 'title' => $item['title'], 'type' => $item['type'], 'content' => $item['content_list']];
        }
        $this->assignconfig('fields', $fields);
        $this->view->assign('fieldsList', $fieldsList);
        $this->view->assign('model', $model);
        $this->assignconfig('model_id', $model_id);
        $modelList = \app\admin\model\cms\Modelx::all();
        $this->view->assign('modelList', $modelList);
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
//            $this->request->filter('');
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false) {
                        if ($params['channel_id'] == 133) {
                            $res = $this->updateEncyclopediasKeyword($params,$this->model->id);
                            if ($res['code'] == 0){
                                Db::rollback();//回滚
                                $this->error($res['message']);
                            }
                        }
                        Db::commit();
                        $this->success();
                    } else {
                        Db::rollback();//回滚
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    Db::rollback();//回滚
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    Db::rollback();//回滚
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     *
     * @param mixed $ids
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $row = $this->model->get($ids);
            $titleOld = $row['title'];
            if (!$row)
                $this->error(__('No Results were found'));
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                if (!in_array($row[$this->dataLimitField], $adminIds)) {
                    $this->error(__('You have no permission'));
                }
            }
            if ($this->request->isPost()) {
//                $this->request->filter('');
                $params = $this->request->post("row/a");
                if ($params) {
                    Db::startTrans();
                    try {
                        //是否采用模型验证
                        if ($this->modelValidate) {
                            $name = basename(str_replace('\\', '/', get_class($this->model)));
                            if ($name == 'Fields') {
                                $name = 'app\admin\validate\cms\\' . $name;
                            }
                            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                            $row->validate($validate);
                        }
                        $result = $row->allowField(true)->save($params);
                        if ($result !== false) {
                            if ($row['channel_id'] == 133 && $titleOld != $params['title']) {
                                $res = $this->updateEncyclopediasKeyword($params,$row['id'],$titleOld);
                                if ($res['code'] == 0){
                                    Db::rollback();//回滚
                                    $this->error($res['message']);
                                }
                            }
                            Db::commit();
                            $this->success();
                        } else {
                            Db::rollback();//回滚
                            $this->error($row->getError());
                        }
                    } catch (\think\exception\PDOException $e) {
                        Db::rollback();//回滚
                        $this->error($e->getMessage());
                    } catch (\think\Exception $e) {
                        Db::rollback();//回滚
                        $this->error($e->getMessage());
                    }
                }
                $this->error(__('Parameter %s can not be empty', ''));
            }
            $this->view->assign("row", $row);
            return $this->view->fetch();
//            return parent::edit($ids);
        }
        $channel = Channel::get($row['channel_id']);
        if (!$channel) {
            $this->error(__('No specified channel found'));
        }
        $model = \app\admin\model\cms\Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error(__('No specified model found'));
        }
        $addon = db($model['table'])->where('id', $row['id'])->find();
        if ($addon) {
            $row = array_merge($row->toArray(), $addon);
        }

        $all = collection(Channel::order("weigh desc,id desc")->select())->toArray();
        foreach ($all as $k => $v) {
            if ($v['type'] != 'list' || $v['model_id'] != $channel['model_id']) {
                $disabledIds[] = $v['id'];
            }
        }

        //获取专家等级和企业等级
        $expert = Expert::get(['user_id' => $row['user_id']]);
        if ($expert) {
            Lang::load(APP_PATH . 'admin/lang/zh-cn/user/expert.php');
            $this->view->assign('expert_level', Lang::get('Level ' . $expert['level']));
        } else {
            $this->view->assign('expert_level', '无');
        }
        $enterprise = UserEnterprise::get(['user_id' => $row['user_id']]);
        if ($enterprise) {
            Lang::load(APP_PATH . 'admin/lang/zh-cn/user/enterprise.php');
            $this->view->assign('enterprise_level', Lang::get('Level ' . $enterprise['level']));
        } else {
            $this->view->assign('enterprise_level', '无');
        }

        $tree = Tree::instance()->init($all, 'parent_id');
        $channelOptions = $tree->getTree(0, "<option value=@id @selected @disabled>@spacer@name</option>", $row['channel_id'], $disabledIds);
        $this->view->assign('channelOptions', $channelOptions);
        $this->view->assign("row", $row);
        $this->view->assign("channel_type", $channel['type']);
        return $this->view->fetch();
    }

    /**
     * 删除
     * @param mixed $ids
     */
    public function del($ids = "")
    {
        $archives = \app\admin\model\cms\Archives::get($ids);
        $channel = Channel::get($archives['channel_id']);
        if ($channel['type'] == 'page') {
            $this->error('单页文章不能删除');
        }

        \app\admin\model\cms\Archives::event('after_delete', function ($row) {
            Channel::where('id', $row['channel_id'])->where('items', '>', 0)->setDec('items');
        });
        return parent::del($ids);
    }

    /**
     * 还原
     * @param mixed $ids
     */
    public function restore($ids = "")
    {
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        if ($ids) {
            $this->model->where($pk, 'in', $ids);
        }
        $archivesChannelIds = $this->model->onlyTrashed()->column('id,channel_id');
        $archivesChannelIds = array_filter($archivesChannelIds);
        $this->model->where('id', 'in', array_keys($archivesChannelIds));
        $count = $this->model->restore('1=1');
        if ($count) {
            $channelNums = array_count_values($archivesChannelIds);
            foreach ($channelNums as $k => $v) {
                Channel::where('id', $k)->setInc('items', $v);
            }
            $this->success();
        }
        $this->error(__('No rows were updated'));

    }

    /**
     * 移动
     */
    public function move($ids = "")
    {
        if ($ids) {
            $channel_id = $this->request->post('channel_id');
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $this->model->where($pk, 'in', $ids);
            $channel = Channel::get($channel_id);
            if ($channel && $channel['type'] === 'list') {
                $channelNums = \app\admin\model\cms\Archives::
                with('channel')
                    ->where('archives.' . $pk, 'in', $ids)
                    ->where('channel_id', '<>', $channel['id'])
                    ->field('channel_id,COUNT(*) AS nums')
                    ->group('channel_id')
                    ->select();
                $result = $this->model
                    ->where('model_id', '=', $channel['model_id'])
                    ->where('channel_id', '<>', $channel['id'])
                    ->update(['channel_id' => $channel_id]);
                if ($result) {
                    $count = 0;
                    foreach ($channelNums as $k => $v) {
                        if ($v['channel']) {
                            Channel::where('id', $v['channel_id'])->where('items', '>', 0)->setDec('items', min($v['channel']['items'], $v['nums']));
                        }
                        $count += $v['nums'];
                    }
                    Channel::where('id', $channel_id)->setInc('items', $count);
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            } else {
                $this->error(__('No rows were updated'));
            }
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
    }

    /**
     * 获取栏目列表
     * @internal
     */
    public function get_channel_fields()
    {
        $this->view->engine->layout(false);
        $channel_id = $this->request->post('channel_id');
        $archives_id = $this->request->post('archives_id');
        $channel = Channel::get($channel_id, 'model');

        if ($channel && $channel['type'] === 'list') {

            $values = [];
            if ($archives_id) {
                $values = db($channel['model']['table'])->where('id', $archives_id)->find();
            }

            $fields = \app\admin\model\cms\Fields::where(['model_id' => $channel['model_id'], 'status' => 'normal'])
                ->order('weigh desc,id desc')
                ->select();
            foreach ($fields as $k => $v) {
                //优先取编辑的值,再次取默认值
                $v->value = isset($values[$v['name']]) ? $values[$v['name']] : (is_null($v['defaultvalue']) ? '' : $v['defaultvalue']);
                $v->rule = str_replace(',', '; ', $v->rule);
                if (in_array($v->type, ['checkbox', 'lists', 'images'])) {
                    $checked = '';
                    if ($v['minimum'] && $v['maximum'])
                        $checked = "{$v['minimum']}~{$v['maximum']}";
                    else if ($v['minimum'])
                        $checked = "{$v['minimum']}~";
                    else if ($v['maximum'])
                        $checked = "~{$v['maximum']}";
                    if ($checked)
                        $v->rule .= (';checked(' . $checked . ')');
                }
                if (in_array($v->type, ['checkbox', 'radio']) && stripos($v->rule, 'required') !== false) {
                    $v->rule = str_replace('required', 'checked', $v->rule);
                }
                if (in_array($v->type, ['selects'])) {
                    $v->extend .= (' ' . 'data-max-options="' . $v['maximum'] . '"');
                }
            }

            $this->view->assign('fields', $fields);
            $this->view->assign('values', $values);
            //获取推荐尺寸
            $text = config("image_size." . $channel_id);

            $mustUser = in_array($channel['model_id'], config('must_user_mid')) ? 1 : 0;

            $this->success('', null, ['mustUser' => $mustUser, 'html' => $this->view->fetch('fields'), 'text'=>$text != null ? $text : '']);
        } else {
            $this->error(__('Please select channel'));
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 检测元素是否可用
     * @internal
     */
    public function check_element_available()
    {
        $id = $this->request->request('id');
        $name = $this->request->request('name');
        $value = $this->request->request('value');
        $name = substr($name, 4, -1);
        if (!$name) {
            $this->error(__('Parameter %s can not be empty', 'name'));
        }
        if ($id) {
            $this->model->where('id', '<>', $id);
        }
        $exist = $this->model->where($name, $value)->find();
        if ($exist) {
            $this->error(__('The data already exist'));
        } else {
            $this->success();
        }
    }

    /**
     * 重发(更新修改时间)
     */
    public function repeat()
    {
        $ids = input('ids/a');
        db('cms_archives')->where('id', 'in', $ids)->update(['updatetime' => time()]);
        $this->success('成功');

    }

    /**
     *
     */
    /**
     * 更新化工字典关键字
     * @param $params
     * @param string $id
     * @param string $titleOld
     * @return array
     */
    private function updateEncyclopediasKeyword($params,$id = '', $titleOld = '')
    {
        try {
            //取出标题中的中文作为化工字典关键字
            $regularC = '/[\x{4e00}-\x{9fa5}]/u';//匹配中文正则
            //修改标题名称
            preg_match_all($regularC, $params['title'], $chineseParams);
            $chineseParamsArray = $chineseParams[0];

            //编辑操作
            if ($titleOld != '') {
                //原有标题名称
                preg_match_all($regularC, $titleOld, $chineseRow);
                $chineseRowArray = $chineseRow[0];
                //去除相同的标题关键字
                foreach ($chineseRowArray as $key => $item) {
                    foreach ($chineseParamsArray as $k => $i) {
                        if ($item == $i) {
                            unset($chineseRowArray[$key]);
                            unset($chineseParamsArray[$k]);
                        }
                    }
                }

                //更新已存在的化工字典关键字(去除旧标题关键字中当前id)
                $chineseRowString = implode($chineseRowArray, ',');
                $keywordExistedData = db('cms_encyclopedias_keyword')->where('name', 'in', $chineseRowString)->column('name,id,encyclopedias_id');
                if ($keywordExistedData) {
                    foreach ($keywordExistedData as $datum) {
                        $encyclopediasIDArray = explode(',', $datum['encyclopedias_id']);
                        foreach ($encyclopediasIDArray as $key => $value) {
                            if ($value == $id) unset($encyclopediasIDArray[$key]);
                        }
                        $encyclopediasIDNew = implode($encyclopediasIDArray, ',');
                        db('cms_encyclopedias_keyword')->where('id', $datum['id'])->update(['encyclopedias_id' => $encyclopediasIDNew]);
                    }
                }
            }


            //新增化工字典关键字(新增新标题关键字中当前id)
            $chineseParamsString = implode($chineseParamsArray, ',');
            $keywordExistedParamsData = db('cms_encyclopedias_keyword')->where('name', 'in', $chineseParamsString)->column('name,id,encyclopedias_id');
            if ($chineseParamsArray) {
                if ($keywordExistedParamsData) {
                    //存在关键字,更新关键字数据
                    foreach ($chineseParamsArray as $key => $value) {
                        if (isset($keywordExistedParamsData[$value])) {
                            $updateData = [
                                'id' => $keywordExistedParamsData[$value]['id'],
                                'encyclopedias_id' => $keywordExistedParamsData[$value]['encyclopedias_id'] == '' ? $id : $keywordExistedParamsData[$value]['encyclopedias_id'] . ',' . $id,
                            ];
                            db('cms_encyclopedias_keyword')->update($updateData);
                            unset($chineseParamsArray[$key]);
                        }
                    }
                }
                $addData = [];
                foreach ($chineseParamsArray as $item) {
                    $addData[] = [
                        'name' => $item,
                        'encyclopedias_id' => $id,
                        'createtime' => time(),
                        'updatetime' => time(),
                    ];
                }
                db('cms_encyclopedias_keyword')->insertAll($addData);
            }
            return ['code' => 1];
        } catch (\think\exception\PDOException $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
        } catch (\think\Exception $e) {
            return ['code' => 0, 'message' => $e->getMessage()];
        }
    }

}
