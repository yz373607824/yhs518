<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;

/**
 * 标签表
 *
 * @icon tags
 */
class Tags extends Backend
{
    protected $noNeedRight = ['select'];

    /**
     * Tags模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Tags;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("hotSearchList", $this->model->getHotSearchList());
//        $this->view->assign("typeList", config('tags_type'));
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个方法
     * 因此在当前控制器中可不用编写增删改查的代码,如果需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {
                if ($row->type == 0) {
                    $row->type = '专家认证';
                } else {
                    $channel = db('cms_channel')->where('id', $row->type)->field('name')->find();
                    $row->type = $channel['name'];
                }
            }

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function selectpage()
    {
        $response = parent::selectpage();
        $word = (array)$this->request->request("q_word/a");
        if (array_filter($word)) {
            $result = $response->getData();
            foreach ($word as $k => $v) {
                array_unshift($result['list'], ['id' => $v, 'name' => $v]);
                $result['total']++;
            }

//            foreach ($result as $k => $v) {
//                if ($k)
//            }

            $response->data($result);
        }
        return $response;
    }

    public function select()
    {
        $channel = \app\admin\model\cms\Channel::field('id,name')->select();

        $list = [];
        foreach ($channel as $item) {
            $list[$item['id']] = $item['name'];
        }

        $list[0] = '专家认证';

        return json($list);
    }

}
