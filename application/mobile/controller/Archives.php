<?php

namespace app\mobile\controller;

use addons\cms\model\Archives as ArchivesModel;
use addons\cms\model\Channel;
use addons\cms\model\Modelx;
use app\admin\model\cms\Collect;
use app\admin\model\Order;
use fast\Random;
use think\Config;
use app\common\controller\Frontend;
use think\Validate;

/**
 * 文档控制器
 */
class Archives extends Frontend
{
    protected $noNeedLogin = ['index', 'formula', 'konwledge'];
    protected $noNeedRight = '*';
    protected $layout = '';
    const SUPPLY_CID = 69; //供应栏目id
    const PURCHASE_CID = 67; //求购栏目id
    const ACTIVITY_CID = 72; //抢购活动栏目id

    const FORMULA_MID = 11;     //投稿配方
    const KONWLEDGE_MID = 15;   //行业知识库
    const FORMULA_TYPE = 3;   //投稿配方支付类型
    const KONWLEDGE_TYPE = 4;   //行业知识库支付类型

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $action = $this->request->post("action");
        if ($action && $this->request->isPost()) {
            return $this->$action();
        }
        $diyname = $this->request->param('diyname');
        if ($diyname && !is_numeric($diyname)) {
            $archives = ArchivesModel::getByDiyname($diyname);
        } else {
            $id = $diyname ? $diyname : $this->request->param('id', '');
            $archives = ArchivesModel::get($id, ['channel']);
        }
        if (!$archives || $archives['status'] != 'normal' || $archives['deletetime']) {
            $this->error(__('No specified article found'), url('/mobile'));
        }
        $channel = Channel::get($archives['channel_id']);
        if (!$channel) {
            $this->error(__('No specified channel found'));
        }
        $model = Modelx::get($channel['model_id'], [], true);
        if (!$model) {
            $this->error(__('No specified model found'));
        }

        $addon = db($model['table'])->where('id', $archives['id'])->find();
        if ($addon) {
            if ($model->fields) {
                $fieldsContentList = $model->getFieldsContentList($model->id);
                //附加列表字段
                array_walk($fieldsContentList, function ($content, $field) use (&$addon) {
                    $addon[$field . '_text'] = isset($content[$addon[$field]]) ? $content[$addon[$field]] : $addon[$field];
                });
            }
            $archives->setData($addon);
        } else {
            $this->error(__('No specified addon article found'));
        }

        //查询订单
        $order = null;
        switch ($model['id']) {
            case self::FORMULA_MID :
                $order = Order::get(['type'=>self::FORMULA_TYPE, 'user_id'=>$this->auth->id, 'goods_id'=>$archives['id'], 'status' => '1']);
                break;
            case self::KONWLEDGE_MID :
                $order = Order::get(['type'=>self::KONWLEDGE_TYPE, 'user_id'=>$this->auth->id, 'goods_id'=>$archives['id'], 'status' => '1']);
                break;
        }

        //企业信息
        if (in_array($archives['channel_id'], [self::SUPPLY_CID, self::PURCHASE_CID, self::ACTIVITY_CID])) {
            $enterprise = db('user_enterprise')->alias('a')
                ->field('a.*,b.expire,b.online')
                ->join('user b', 'a.user_id = b.id', 'LEFT')
                ->where('user_id', $archives['user_id'])->find();
            $this->assign('enterprise', $enterprise);
        }

        //内容转码
        if (isset($archives['content'])) {
            $archives['content'] = htmlspecialchars_decode($archives['content']);
        }

        //判断是否有收藏
        $collectStatus = false;
        if ($this->auth->id) {
            $collect = Collect::get(['archives_id'=>$archives->id, 'user_id'=>$this->auth->id]);
            $collectStatus = $collect ? true : false;
        }

        //如果是配方和知识库需要回显点赞
        if (in_array($model['id'], [self::FORMULA_MID, self::KONWLEDGE_MID])) {
            //回显点赞
            $userIds = explode(',', $archives['user_ids']);
            if ($this->auth->id && in_array($this->auth->id, $userIds)) {
                $archives['like'] = true;
            } else {
                $archives['like'] = false;
            }
            $archives['paynum'] = $archives['paynum'] + config('site.article_pay_num');
        }
        
        $this->view->assign("collectStatus", $collectStatus);

        $this->view->assign("__ORDER__", $order);

        $archives->setInc("views", 1);
        $this->view->assign("__ARCHIVES__", $archives);
        $this->view->assign("__CHANNEL__", $channel);
        $this->view->assign('seo_title', $archives['title']);
        $this->view->assign('seo_keywords', $archives['keywords']);
        $this->view->assign('seo_description', $archives['description']);
        $template = preg_replace('/\.html$/', '', $channel['mobile_showtpl']);
        return $this->view->fetch('cms/' . $template);
    }

    /**
     * 赞与踩
     */
    public function vote()
    {
        $id = (int)$this->request->post("id");
        $type = trim($this->request->post("type", ""));
        if (!$id || !$type) {
            $this->error(__('Operation failed'));
        }
        $archives = ArchivesModel::get($id);
        if (!$archives || ($archives['user_id'] != $this->auth->id && $archives['status'] != 'normal') || $archives['deletetime']) {
            $this->error(__('No specified article found'));
        }
        $archives->where('id', $id)->setInc($type === 'like' ? 'likes' : 'dislikes', 1);
        $archives = ArchivesModel::get($id);
        $this->success(__('Operation completed'), null, ['likes' => $archives->likes, 'dislikes' => $archives->dislikes, 'likeratio' => $archives->likeratio]);
    }

    /**
     * 文章收藏
     */
    public function collect()
    {
        $id = $this->request->param('id');

        $archives = ArchivesModel::get($id);
        if ($archives) {
            $collect = Collect::get(['archives_id'=>$id, 'user_id'=>$this->auth->id]);
            if ($collect) {
                $this->result(['url'=>url('/mobile/user/myCollect')], 1, __('Has been collected, can go to my collection view'));
            } else {
                model('\app\admin\model\cms\Collect')->save(['archives_id'=>$id, 'user_id'=>$this->auth->id]);
            }
            $this->result(['url'=>url('/mobile/user/myCollect')], 1, __('Collection of success'));
        } else {
            $this->error(__('The current article does not exist'));
        }
    }

    /**
     * 配方需求
     */
    public function formula_demand() {
        $this->view->assign('seo_title', '配方需求');
        $this->view->assign('seo_keywords', '配方需求');
        $this->view->assign('seo_description', '配方需求');
        $template = preg_replace('/\.html$/', '', 'formula_demand.html');
        return $this->view->fetch('cms/' . $template);
    }

    /**
     * 知识库需求
     */
    public function konwledge_demand() {
        $this->view->assign('seo_title', '知识库需求');
        $this->view->assign('seo_keywords', '知识库需求');
        $this->view->assign('seo_description', '知识库需求');
        $template = preg_replace('/\.html$/', '', 'konwledge_demand.html');
        return $this->view->fetch('cms/' . $template);
    }

    /**
     * 配方的下单
     */
    public function formula()
    {
        $data = $this->_createOrder(config('order_type.formula'));
        $this->redirect(url('/mobile/user/pay', ['trade_sn' => $data['trade_sn']]));
    }

    /**
     * 知识的下单
     */
    public function konwledge()
    {
        $data = $this->_createOrder(config('order_type.konwledge'));
        $this->redirect(url('/mobile/user/pay', ['trade_sn' => $data['trade_sn']]));
    }

    /**
     * 创建订单
     * @param $orderType 订单类型
     */
    private function _createOrder($orderType)
    {
        if (!$this->auth->id) {
            $this->error(__('请登录后再操作'), url('/mobile/user/login'));
        }

        $id = (int)$this->request->param("id");

        if (empty($id)) {
            $this->error(__('Parameter error'), null);
        }

        $archives = ArchivesModel::get($id);
        if (!$archives || $archives['status'] != 'normal' || $archives['deletetime']) {
            $this->error(__('No specified article found'), null);
        }
        $channel = Channel::get($archives['channel_id']);
        if (!$channel) {
            $this->error(__('No specified channel found'), null);
        }
        $model = Modelx::get($channel['model_id'], [], true);
        if (!$model) {
            $this->error(__('No specified model found'), null);
        }
        $addon = db($model['table'])->where('id', $archives['id'])->find();
        if (!$addon) {
            $this->error(__('No specified addon article found'), null);
        }

        $orderModel = model('app\admin\model\Order');

        //订单内容填充
        $order['user_id'] = $this->auth->id;   //用户ID
        $order['shop_id'] = $archives['user_id'];   //商家ID，发布文章的用户ID
        $order['trade_sn'] = date('YmdHis') . Random::numeric();   //订单编号
        $order['type'] = $orderType;   //订单类型
        $order['title'] = $archives['title'];   //标题
        $order['goods_table'] = 'uweb_cms_archives';   //关联的商品表
        $order['goods_id'] = $archives['id'];    //关联商品表的ID
        $order['goods_price'] = $addon['price'];    //商家价格
        $order['totalprice'] = $addon['price'];     //订单总价格

        $result = $orderModel->save($order);
        if ($result) {
           return $order;
        } else {
            $this->error(__('Place the order failed'));
        }
    }
}
