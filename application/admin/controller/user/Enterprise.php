<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 企业信息
 *
 * @icon fa fa-circle-o
 */
class Enterprise extends Backend
{

    protected $searchFields = 'id,company,license';
    
    /**
     * UserEnterprise模型对象
     * @var \app\admin\model\UserEnterprise
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\UserEnterprise;
        $this->view->assign("levelList", $this->model->getLevelList());
        $this->view->assign("applyLevelList", $this->model->getApplyLevelList());
        $this->view->assign("isAptitudeList", $this->model->getIsAptitudeList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("isExcellentList", $this->model->getIsExcellentList());
        $this->view->assign("isActiveList", $this->model->getIsActiveList());
        $this->view->assign("isIndexRecruitList", $this->model->getIsIndexRecruitList());
        $this->view->assign("enterprise", config('enterprise'));
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
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','level','apply_level','company','is_excellent','status','weigh','is_sensitive','end_time','license', 'code', 'createtime']);
                $row->visible(['user']);
				$row->getRelation('user')->visible(['username']);
                $row->getRelation('user')->visible(['nickname']);
				$row->getRelation('user')->visible(['no']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}