<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;
use think\Db;

/**
 * 自定义表单数据表
 *
 * @icon fa fa-circle-o
 */
class Diydata extends Backend
{

    /**
     * 自定义表单模型对象
     */
    protected $diyform = null;
    /**
     * 定义表单数据表模型
     * @var null
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $diyform_id = $this->request->param('diyform_id');
        $this->diyform = \app\admin\model\cms\Diyform::get($diyform_id);
        if (!$this->diyform) {
            $this->error('未找到对应自定义表单');
        }
        $this->model = \think\Db::name($this->diyform['table']);
        $this->assignconfig('diyform_id', $diyform_id);
    }

    /**
     * 查看
     */
    public function index()
    {
        $fieldsList = \app\admin\model\cms\Fields::where('diyform_id', $this->diyform['id'])->order('weigh', 'DESC')->select();
        $fields = [];
        foreach ($fieldsList as $index => $item) {
            $fields[] = ['field' => $item['name'], 'title' => $item['title'], 'type' => $item['type'], 'content' => $item['content_list']];
        }
        $this->assignconfig('fields', $fields);
        $diyformList = \app\admin\model\cms\Diyform::all();
        $this->view->assign('diyform', $this->diyform);
        $this->view->assign('diyformList', $diyformList);
        if ($this->request->isAjax()) {

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total                                       = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $comauth = new \app\common\library\Auth;
            $result = array("total" => $total, "rows" => $comauth->render($list));
            return json($result);
        }
        return $this->view->fetch();
//        return parent::index();
    }

    /**
     * 添加
     */
    public function add()
    {
        $this->assignFields();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    $result = $this->model->insert($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->where('id', $ids)->find();
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $diyformId = input('diyform_id');
            $params = $this->request->post("row/a");
            if ($params) {
                if ($diyformId == 5) {
                    $orderInfo = Db::name('order')->field('id,goods_id,paytype,status')->where('id',$params['order_id'])->find();
                    //抢购活动修改订单需关联订单表修改
                    Db::startTrans();
                    try{
                        //抢购活动线下支付库存修改(20200831改为下单就减库存)
                        /*if ($orderInfo['paytype'] == 5 && $params['pay_type'] != 3 && $params['pay_type'] != $orderInfo['status']) {
                            $number = intval($params['number']) * intval($params['format']);
                            if ($params['pay_type'] == 0) {
                                Db::name('cms_activity')->where('id', $orderInfo['goods_id'])->setInc('number',$number);
                            } elseif ($params['pay_type'] == 1) {
                                Db::name('cms_activity')->where('id', $orderInfo['goods_id'])->setDec('number',$number);
                            }
                        }*/
                        Db::name('order')->where('id',$params['order_id'])->update(['status' => $params['pay_type']]);
                        Db::name('cms_activity_form')->where('id', $ids)->update($params);
                        // 提交事务
                        Db::commit();
                        $this->success();
                    } catch (Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                } else {
                    try {
                        $result = $this->model->where('id', $ids)->update($params);
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
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $this->assignFields($ids);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            if (input('diyform_id') == 5) {
                //抢购活动删除数据
                $order_id = $this->model->where('id','in',$ids)->column('order_id');
                //判断是否是旧数据
                $a = 0;
                foreach ($order_id as $key => $value){
                    if ($value > 0) {
                        $a++;
                    }
                }
                $orderModel = db('order');
                $orderModel->startTrans();
                $this->model->startTrans();
                $orderCount = $orderModel->where('id', 'in', $order_id)->delete();
                $count = $this->model->where($pk, 'in', $ids)->delete();
                if ($orderCount == $a && $count){
                    $orderModel->commit();
                    $this->model->commit();
                    $this->success();
                } else {
                    $orderModel->rollback();
                    $this->model->rollback();
                    $this->error(__('No rows were deleted'));
                }
            }
            $count = $this->model->where($pk, 'in', $ids)->delete();
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    private function assignFields($diydata_id = 0)
    {
        $values = [];
        if ($diydata_id) {
            $values = db($this->diyform['table'])->where('id', $diydata_id)->find();
        }
        $fields = \app\admin\model\cms\Fields::where('diyform_id', $this->diyform['id'])
            ->order('weigh desc,id desc')
            ->select();
        foreach ($fields as $k => $v) {
            $v->value = isset($values[$v['name']]) ? $values[$v['name']] : '';
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
    }

}
