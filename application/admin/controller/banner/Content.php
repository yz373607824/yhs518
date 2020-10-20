<?php

namespace app\admin\controller\banner;

use app\admin\validate\Order;
use app\common\controller\Backend;

/**
 * 广告内容管理
 *
 * @icon fa fa-circle-o
 */
class Content extends Backend
{
    protected $searchFields = 'id,name';
    
    /**
     * Content模型对象
     * @var \app\admin\model\banner\Content
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\banner\Content;
        $this->view->assign("is_timeList", $this->model->getIsTimeList());
        $this->manageList = \app\admin\model\banner\Manage::order('id asc')->select();
        $this->view->assign("manageList", $this->manageList);
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
                    ->with(['manage'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['manage'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                $row->getRelation('manage')->visible(['name']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 获取广告位的尺寸
     */
    public function getSize($param)
    {
        if (is_numeric($param)) {
            $manage = \app\admin\model\banner\Manage::get($param);
        } else {
            $manage = \app\admin\model\banner\Manage::get(['name'=>$param]);
        }

        if ($manage) {
            $data['pic'] = '(推荐尺寸:' . $manage['picwidth'] . 'x' . $manage['picheight'] . ')';
            $data['picimg'] = '(推荐尺寸:' . $manage['picimgwidth'] . 'x' . $manage['picimgheight'] . ')';
            $this->result($data, 1);
        } else {
            $this->result(null, 0);
        }

    }
}
