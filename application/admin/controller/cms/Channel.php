<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;
use app\admin\model\cms\Channel as ChannelModel;
use fast\Tree;

/**
 * 栏目表
 *
 * @icon fa fa-circle-o
 */
class Channel extends Backend
{

    protected $channelList = [];
    protected $modelList = [];
    protected $multiFields = ['weigh', 'status'];

    /**
     * Channel模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['check_element_available'];

    public function _initialize()
    {
        parent::_initialize();
        $this->request->filter(['strip_tags']);
        $this->model = new \app\admin\model\cms\Channel;

        // 栏目列表权限控制
        $channelRuleIds = $this->auth->getChannelRuleIds();
        $whereChannelIds = $this->auth->isSuperAdmin() ? '' : ['id' => ['in', $channelRuleIds]];

        $tree = Tree::instance();
        $tree->init(collection($this->model->where($whereChannelIds)->order('weigh asc,id asc')->select())->toArray(), 'parent_id');
        $this->channelList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $this->modelList = \app\admin\model\cms\Modelx::order('id asc')->select();

        $this->view->assign("modelList", $this->modelList);
        $this->view->assign("channelList", $this->channelList);
        $this->view->assign("typeList", ChannelModel::getTypeList());
        $this->view->assign("statusList", ChannelModel::getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {

        if ($this->request->isAjax()) {
            $search = $this->request->request("search");
            $model_id = $this->request->request("model_id");
            //构造父类select列表选项数据
            $list = [];
            if ($search) {
                foreach ($this->channelList as $k => $v) {
                    if (stripos($v['name'], $search) !== false || stripos($v['nickname'], $search) !== false) {
                        $list[] = $v;
                    }
                }
            } else {
                $list = $this->channelList;
            }
            foreach ($list as $index => $item) {
                if ($model_id && $model_id != $item['model_id']) {
                    unset($list[$index]);
                }
            }
            $list = array_values($list);
            $modelNameArr = [];
            foreach ($this->modelList as $k => $v) {
                $modelNameArr[$v['id']] = $v['name'];
            }
            foreach ($list as $k => &$v) {
                $v['model_name'] = $v['model_id'] && isset($modelNameArr[$v['model_id']]) ? $modelNameArr[$v['model_id']] : __('None');
            }
            $total = count($list);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $nameArr = array_filter(explode("\n", str_replace("\r\n", "\n", $params['name'])));
                    if (count($nameArr) > 1) {
                        foreach ($nameArr as $index => $item) {
                            $itemArr = array_filter(explode('|', $item));
                            $params['name'] = $itemArr[0];
                            $params['diyname'] = isset($itemArr[1]) ? $itemArr[1] : '';
                            $result = $this->model->allowField(true)->isUpdate(false)->data($params)->save();
                        }
                    } else {
                        $result = $this->model->allowField(true)->save($params);
                        //如果是单页新增一条文章
                        if ($result !== false && $this->model->type == 'page') {
                            $row = [
                                'channel_id'=>$this->model->id,
                                'model_id'=>$this->model->model_id,
                                'title'=>$params['name'],
                                'content'=>$params['name'],
                                'views'=>0,
                                'comments'=>0,
                                'likes'=>0,
                                'dislikes'=>0,
                                'status'=>'normal',
                                'publishtime'=>time(),
                            ];
                            $model = new \app\admin\model\cms\Archives;
                            $model->allowField(true)->save($row);
                        }
                    }
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        // 是否存在新增一级栏目权限
        $hasAddTopChannelAuth = $this->auth->check(\app\admin\model\cms\Channel::PATH_ADD_TOP);
        $this->view->assign("addTop", $hasAddTopChannelAuth ? 1 : 0);
        return $this->view->fetch();
    }

    /**
     * 编辑
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
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        // 是否存在新增一级栏目权限
        $hasAddTopChannelAuth = $this->auth->check(\app\admin\model\cms\Channel::PATH_ADD_TOP);
        $this->view->assign("addTop", $hasAddTopChannelAuth ? 1 : 0);
        $this->view->assign("row", $row);

        // 获取当前栏目的一级子栏目
        $this->view->assign('child', $this->getChildChannelList($ids));

        return $this->view->fetch();
    }

    protected function getChildChannelList($tid)
    {
        return db('cms_channel')->field('name,id')
            ->where('parent_id', $tid)
            ->where('status', 'normal')
            ->select();
    }

    /**
     * Selectpage搜索
     *
     * @internal
     */
    public function selectpage()
    {
        return parent::selectpage();
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
        if ($name == 'diyname') {
            if ($id) {
                $this->model->where('id', '<>', $id);
            }
            $exist = $this->model->where($name, $value)->find();
            if ($exist) {
                $this->error(__('The data already exist'));
            } else {
                $this->success();
            }
        } else if ($name == 'name') {
            $nameArr = array_filter(explode("\n", str_replace("\r\n", "\n", $value)));
            if (count($nameArr) > 1) {
                foreach ($nameArr as $index => $item) {
                    $itemArr = array_filter(explode('|', $item));
                    if (!isset($itemArr[1])) {
                        $this->error('格式:分类名称|自定义名称');
                    }
                    $exist = \app\admin\model\cms\Channel::getByDiyname($itemArr[1]);
                    if ($exist) {
                        $this->error('自定义名称[' . $itemArr[1] . ']已经存在');
                    }
                }
                $this->success();
            } else {
                $this->success();
            }
        }
    }

    public function select(){
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                //设置过滤方法
                $this->request->filter(['strip_tags', 'htmlspecialchars']);

                //搜索关键词,客户端输入以空格分开,这里接收为数组
                $word = (array)$this->request->request("q_word/a");
                //当前页
                $page = $this->request->request("pageNumber");
                //分页大小
                $pagesize = $this->request->request("pageSize");
                //搜索条件
                $andor = $this->request->request("andOr", "and", "strtoupper");
                //排序方式
                $orderby = (array)$this->request->request("orderBy/a");
                //显示的字段
                $field = $this->request->request("showField");
                //主键
                $primarykey = $this->request->request("keyField");
                //主键值
                $primaryvalue = $this->request->request("keyValue");
                //搜索字段
                $searchfield = (array)$this->request->request("searchField/a");
                //自定义搜索条件
                $custom = (array)$this->request->request("custom/a");
                $order = [];
                foreach ($orderby as $k => $v) {
                    $order[$v[0]] = $v[1];
                }
                $field = $field ? $field : 'name';

                //如果有primaryvalue,说明当前是初始化传值
                if ($primaryvalue !== null) {
                    $where = [$primarykey => ['in', $primaryvalue]];
                    if ($custom && is_array($custom)) {
                        foreach ($custom as $k => $v) {
                            $where[$k] = ['=', $v];
                        }
                    }
                } else {
                    $where = function ($query) use ($word, $andor, $field, $searchfield, $custom) {
                        $logic = $andor == 'AND' ? '&' : '|';
                        $searchfield = is_array($searchfield) ? implode($logic, $searchfield) : $searchfield;
                        foreach ($word as $k => $v) {
                            $query->where(str_replace(',', $logic, $searchfield), "like", "%{$v}%");
                        }
                        if ($custom && is_array($custom)) {
                            foreach ($custom as $k => $v) {
                                $query->where($k, '=', $v);
                            }
                        }
                    };
                }

                $adminIds = $this->getDataLimitAdminIds();
                if (is_array($adminIds)) {
                    $this->model->where($this->dataLimitField, 'in', $adminIds);
                }
                $list = [];
                $total = $this->model->where($where)->count();
                if ($total > 0) {
                    if (is_array($adminIds)) {
                        $this->model->where($this->dataLimitField, 'in', $adminIds);
                    }
                    $datalist = $this->model->where($where)
                        ->order($order)
                        ->page($page, $pagesize)
                        ->field($this->selectpageFields)
                        ->select();
                    foreach ($datalist as $index => $item) {
                        unset($item['password'], $item['salt']);
                        $list[] = [
                            $primarykey => isset($item[$primarykey]) ? $item[$primarykey] : '',
                            $field      => isset($item[$field]) ? $item[$field] : ''
                        ];
                    }
                }

                if (count($list) < 10 || ($total % $pagesize == 0 && $page == $total / $pagesize)) {
                    $list[] = ['id' => 0, 'name' => '专家认证'];
                }

                return json(['list' => $list, 'total' => $total]);
            }
        }
    }

}
