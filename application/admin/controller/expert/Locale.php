<?php

namespace app\admin\controller\expert;

use app\admin\model\Order;
use app\common\controller\Backend;

/**
 * 专家现场服务管理
 *
 * @icon fa fa-circle-o
 */
class Locale extends Backend
{
    protected $searchFields = 'id,title,linkman,enterprise,mobile';
    
    /**
     * Locale模型对象
     * @var \app\admin\model\expert\Locale
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\expert\Locale;
        $this->view->assign("isConfirmList", $this->model->getIsConfirmList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['user','expert'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user','expert'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->getRelation('user')->visible(['no']);
                $row->getRelation('user')->visible(['nickname']);
				$row->getRelation('expert')->visible(['name']);
                $order = Order::get(['type'=>config('order_type.locale'), 'goods_id'=>$row['id']]);
                $row['status'] = $order['status'];
                $row['totalprice'] = $order['totalprice'];
                $row['paytime'] = $order['paytime'];
                $row['appraise'] = mb_substr($order['appraise'], 0, 10) . (mb_strlen($order['appraise']) > 10 ? '...' : '');
                $row['replyappraise'] = mb_substr($order['replyappraise'], 0, 10) . (mb_strlen($order['replyappraise']) > 10 ? '...' : '');
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
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
                        if ($name == 'Fields') {
                            $name = 'app\admin\validate\cms\\' . $name;
                        }
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }

                    //更新表单的专家信息
                    $flag = false;
                    $user_tips = null;
                    if ($row['expert_id'] != $params['expert_id']) {
                        $expert = \app\admin\model\user\Expert::get($params['expert_id']);
                        $user = \app\admin\model\User::get($expert['user_id']);

                        $params['expert_basic'] = serialize($user);
                        $params['expert_info'] = serialize($expert);

                        $user_tips = $user['id'];
                        $flag = true;
                    }

                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        //更新订单的shop_id
                        if ($flag) {
                            \app\admin\model\Order::update(['shop_id' => $params['expert_id']], ['type' => 2, 'goods_id' => $ids]);
                            $this->createTips($user_tips, 2);
                        }

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
        $order = Order::get(['type'=>config('order_type.locale'), 'goods_id'=>$row['id']]);
        $row['appraise'] = $order['appraise'];
        $row['replyappraise'] = $order['replyappraise'];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
