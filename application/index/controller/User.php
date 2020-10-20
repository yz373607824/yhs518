<?php

namespace app\index\controller;

use app\admin\model\cms\Collect;
use app\admin\model\cms\Fields;
use app\admin\model\cms\Modelx;
use app\admin\model\Order;
use app\admin\model\Order as OrderModel;
use app\admin\model\user\Verify;
use app\admin\model\UserEnterprise;
use app\common\controller\Frontend;
use app\common\library\Sms as Smslib;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Cookie;
use think\Hook;
use think\Validate;
use think\Loader;
use app\admin\model\expert\Online as OnlineModel;
use app\admin\model\cms\Channel;
use app\admin\model\user\Bill;
use think\Db;
use think\Lang;

/**
 * 会员中心
 */
class User extends Frontend
{
    //栏目id
    const HONOR_TID = 77; //荣誉资质
    const ACTIVITY_TID = 72; //抢购活动
    const RECRUIT_TID = 71; //人才招聘
    const SUPPLY_TID = 69; //供应信息
    const PURCHASE_TID = 67; //求购信息
    const FORMULA_TID = 70; //配方
    const KNOWLEDGE_TID = 74; //知识库
    const JOB_WANTED_TID = 96; //求职

    protected $layout = '';
    protected $noNeedLogin = ['login', 'register', 'forgetpwd', 'mobilelogin', 'index_login'];
    protected $noNeedRight = ['*'];

    /**
     * 会员角色权限验证。注意：方法名有大写的统一转为小写
     * @var array
     */
    //专家
    private $expert_fun = ['onlineservice', 'grabonlineservice', 'localeservice', 'experdgraborder',
        'confirm', 'confirmservice', 'replyservice', 'rollbackservice', 'replyappraise'];
    //企业
    private $enterprise_fun = ['purchase', 'release_purchase', 'release_supply', 'supply', 'release_recruit', 'recruit', 'honor', 'release_honor', 'activity', 'release_activity', 'enterprise_order', 'leave_message', 'enterprise_order_del', 'enterprise_contact'];
    //个人
    private $personal_fun = ['takecashlist', 'takecash', 'getamount', 'applytaskcash'];

    public function _initialize()
    {
        parent::_initialize();
        $auth = $this->auth;

        if (!Config::get('uwebadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

        $ucenter = get_addon_info('ucenter');
        if ($ucenter && $ucenter['state']) {
            include ADDON_PATH . 'ucenter' . DS . 'uc.php';
        }

        //监听注册登录注销的事件
        Hook::add('user_login_successed', function ($user) use ($auth) {
            $expire = input('post.keeplogin') ? 30 * 86400 : 0;
            Cookie::set('uid', $user->id, $expire);
            Cookie::set('token', $auth->getToken(), $expire);
        });
        Hook::add('user_register_successed', function ($user) use ($auth) {
            Cookie::set('uid', $user->id);
            Cookie::set('token', $auth->getToken());
        });
        Hook::add('user_delete_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
        Hook::add('user_logout_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });

        //角色权限验证
        $action = $this->request->action();
        if (in_array($action, $this->expert_fun) && !$this->auth->expert_id) {
            $this->error(__('Please complete the engineer audit certification first'), url('/user/expertApplication'));
        } elseif (in_array($action, $this->personal_fun) && !$this->auth->verify_id) {
            $this->error(__('Please complete the certification of your personal identity card first'), url('/user/idAuthentication'));
        } elseif (in_array($action, $this->enterprise_fun) && !$this->auth->enterprise_id) {
            $this->error(__('Please apply for membership first'), url('user/enterprise'));
        } elseif (in_array($action, $this->expert_fun)) {
            //判断专家服务期限是否过期
            $flag = session($this->auth->id . '_expert_time');
            if ($flag === NULL) {
                $expert = \app\admin\model\user\Expert::get($this->auth->expert_id);
                if ($expert['deadline_starttime'] < time() && $expert['deadline_endtime'] > time()) {
                    session($this->auth->id . '_expert_time', false);
                } else {
                    session($this->auth->id . '_expert_time', true);
                    $this->error(__('The period of expert service has expired'));
                }
            } elseif ($flag) {
                $this->error(__('The period of expert service has expired'));
            }
        }
    }

    /**
     * 获取用户的收入支出明细列表
     */
    public function getBillList() {

        $list = Bill::where('user_id', $this->auth->id)
        ->order('createtime', 'desc')
        ->paginate(10, false, ['type' => '\\addons\\cms\\library\\Bootstrap']);
        
        $this->view->assign('list', $list);
        $this->view->assign('seo_title', '收入支出明细');
        return $this->view->fetch('bill_list');
    }

    /**
     * 如何成为工程师
     */
    public function engineer_page()
    {
        $archives = \app\admin\model\cms\Archives::get(266);
        $model = db('cms_addonpage')->where('id', $archives['id'])->find();

        $this->view->assign('title', $archives['title']);
        $this->view->assign('content', htmlspecialchars_decode($model['content']));

        return $this->view->fetch();
    }

    /**
     * 重发
     */
    public function repeat()
    {
        $id = input('id/d');
        $info = model('admin/cms/archives')->get(['user_id' => $this->auth->id, 'id' => $id]);
        if (!$info) {
            $this->error('参数错误');
        }
        if ($info->status != 'normal') {
            $this->error('未审核的不能重发');
        }
        $info->updatetime = time();

        $info->save();

        $this->success('刷新成功');
    }

    /**
     * 申请成为优质供应商
     */
    public function apply_level()
    {
        $level = input('level');
        $enterprise = \app\admin\model\UserEnterprise::get(['user_id' => $this->auth->id]);

        if (!$enterprise) {
            $this->error(__('Please apply for membership first'));
        }

        Lang::load(APP_PATH . 'admin/lang/zh-cn/user/enterprise.php');
        if ($enterprise->level == $level) {
            $this->error('您已经是'. Lang::get('Level ' . $enterprise['level']) .'会员');
        }

        if ($enterprise && $level) {
            $enterprise->apply_level = $level;
            $enterprise->save();
        }

        $this->success('提交成功');
    }

    /**
     * 我的活动订单
     */
    public function activity_order()
    {
        $list = db('cms_activity_form')->alias('a')
            ->where(['a.user_id' => $this->auth->id])
            ->join('order n','a.order_id = n.id and n.is_delete = 0','left')
            ->join('cms_activity m','n.goods_id = m.id','left')
            ->field('n.status as paystatus,n.id as orderid,n.trade_sn as ordertrade_sn,n.title as ordertitle,n.totalprice as ordertotalprice,n.createtime as ordercreatetime,n.goods_id as goodsid,n.paytype as orderpaytype')
            ->field('a.*')
            ->field('m.is_offline,m.bank_info')
            ->order('a.createtime', 'DESC')
            ->paginate(config('page_size.leave_message'),false);

        foreach ($list as $item) {
            //判断订单是否过期
            if (time() > strtotime('+1 hour', $item['ordercreatetime']) && $item['paystatus'] == '0' && $item['orderpaytype'] != 5) {
                $orderModel = db('order');
                $activityFormModel = db('cms_activity_form');

                $orderModel->startTrans();
                $activityFormModel->startTrans();

                $updateOrderStatus = $orderModel->where('id', $item['orderid'])->update(['status' => '2']);
                $updateActivityFormStatus = $activityFormModel->where('id', $item['id'])->update(['pay_type' => config('pay_type.expired')]);
                if ($updateOrderStatus == 0 || $updateActivityFormStatus == 0) {
                    $orderModel->rollback();
                    $activityFormModel->rollback();
                    return false;
                }
                $orderModel->commit();
                $activityFormModel->commit();
                $item['paystatus'] = '2';
            }
        }

        $this->assign('list', $list);
        $this->assign('seo_title', '我的配方');
        return $this->view->fetch('message_list');
    }

    /**
     * 我的活动订单删除
     */
    public function activity_order_del()
    {
        $id = $this->request->param('id',null);
        if (empty($id)) {
            $this->error(__('Parameter error'), null, ['token' => $this->request->param('__token__')]);
        }

        //活动订单表
        $activity_order = db('cms_activity_form')->where(['user_id' => $this->auth->id, 'id' => $id])->find();
        if (empty($activity_order)) {
            $this->error(__('The current activity order does not exist'), null, ['token' => $this->request->param('__token__')]);
        }

        //订单表
        $order = OrderModel::get($activity_order['order_id']);
        if (empty($order)) {
            //旧数据(没有订单表数据)
            $res = db('cms_activity_form')->where(['user_id' => $this->auth->id, 'id' => $id])->delete();
            if ($res) {
                $this->success('删除成功');
            }
            $this->error('删除失败');
        }
        //防止越权操作
        if ($order['user_id'] != $this->auth->id) {
            $this->error(__('Unauthorized operation, attention please'), null, ['token' => $this->request->param('__token__')]);
        }

        //检查订单状态
        if ($order['status'] == '1') {
            //更改删除标识
            $order->is_delete = 1;
            $order->save();
        } else {
            //开启事务
            $order->startTrans();

            $result = $order->delete();
            if (!$result) {
                $order->rollback();
                $this->error(__('Delete failed'), null, ['token' => $this->request->param('__token__')]);
            }

            //关联删除
            $activityFormModel = db('cms_activity_form');
            $activityFormModel->startTrans();
            $result = $activityFormModel->where(['user_id' => $this->auth->id, 'id' => $id])->delete();
            if (!$result) {
                //回滚
                $activityFormModel->rollback();
                $order->rollback();
                $this->error(__('Delete failed'), null, ['token' => $this->request->param('__token__')]);
            }
            //提交事务
            $activityFormModel->commit();
            $order->commit();
        }

        $this->result(null, 1, __('Delete the success'));
    }

    /**
     * 企业活动订单
     */
    public function enterprise_order()
    {
        $list = db('cms_activity_form')->where(['enterprise_id' => $this->auth->enterprise_id])
            ->order('createtime', 'DESC')
            ->paginate(config('page_size.leave_message'), false);

        $this->view->assign('list', $list);
        $this->assign('seo_title', '收到的活动订单');

        //删除提醒
        $this->deleteTips('7');

        return $this->view->fetch('message_list');
    }

    /**
     * 企业活动订单删除
     */
    public function enterprise_order_del()
    {
        $id = input('id');

        $res = db('cms_activity_form')->where(['enterprise_id' => $this->auth->enterprise_id, 'id' => $id])->delete();

        if ($res) {

            $this->success('删除成功');
        }

        $this->error('删除失败');
    }

    /**
     * 收到的留言
     */
    public function leave_message()
    {
        $list = db('cms_enterprise_message')->where(['enterprise_id' => $this->auth->enterprise_id])
            ->order('createtime', 'DESC')
            ->paginate(config('page_size.leave_message'), false);

        $this->view->assign('list', $list);
        $this->assign('seo_title', '收到的留言');

        //删除提醒
        $this->deleteTips('4');

        return $this->view->fetch();
    }

    /**
     * 平台群发
     */
    public function message()
    {
        $list = db('message_record')->alias('a')
            ->field('b.id,b.title,b.createtime')
            ->join('message b', 'a.message_id = b.id', 'left')
            ->where(['a.user_id' => $this->auth->id])
            ->order('b.createtime', 'DESC')
            ->paginate(config('page_size.message'), false);

        $this->view->assign('list', $list);
        $this->assign('seo_title', '平台群发');

        //删除提醒
        $this->deleteTips('3');

        return $this->view->fetch();
    }

    /**
     * 平台群发删除
     */
    public function message_del()
    {
        $id = input('id');

        $res = db('message_record')->where(['user_id' => $this->auth->id, 'message_id' => $id])->delete();

        if ($res) {

            $this->success('删除成功');
        }

        $this->error('删除失败');
    }

    /**
     * 留言删除
     */
    public function leave_del()
    {
        $id = input('id');

        $res = db('cms_enterprise_message')->where(['enterprise_id' => $this->auth->enterprise_id, 'id' => $id])->delete();

        if ($res) {

            $this->success('删除成功');
        }

        $this->error('删除失败');
    }

    /**
     * 平台群发详情
     */
    public function message_detail()
    {
        $id = input('id');
        $read = db('message_record')->alias('a')
            ->join('message b', 'a.message_id = b.id', 'LEFT')
            ->where(['a.message_id' => $id, 'user_id' => $this->auth->id])
            ->find();

        if (!$read) {
            $this->error(__('You have no permission'));
        }

        $this->assign('read', $read);
        $this->assign('seo_title', $read['title'] . '-平台群发');
        return $this->view->fetch();
    }

    /**
     * 消息提醒
     */
    public function getTips()
    {
        return parent::getTips();
    }

    /**
     * 我要求职列表
     */
    public function job_wanted()
    {
        $this->assign('action', 'job_wanted');
        $this->assign('seo_title', '我的求职');
        return $this->releaseList(self::JOB_WANTED_TID);
    }

    /**
     * 发布求职
     */
    public function release_job_wanted()
    {
        $this->assign('seo_title', '发布求职');
        $this->assign('returnUrl', url('user/job_wanted'));
        return $this->release(self::JOB_WANTED_TID, 'ReleaseJobWanted', 'release_job_wanted');
    }

    /**
     * 资质荣誉列表
     */
    public function honor()
    {
        $this->assign('action', 'honor');
        $this->assign('seo_title', '荣誉资质');
        return $this->releaseList(self::HONOR_TID);
    }

    /**
     * 发布资质荣誉
     */
    public function release_honor()
    {
        //企业等级
        $level = getEnterprise($this->auth->id, 'level');
        //可发布数量
        $num = config("enterprise.level_{$level}");

        $this->assign('seo_title', '发布荣誉资质');
        return $this->release(self::HONOR_TID, 'ReleaseHonor', 'release_honor', $num['honor']);
    }

    /**
     * 抢购活动
     * @return string
     */
    public function activity()
    {
        $this->assign('action', 'activity');
        $this->assign('seo_title', '抢购活动');
        return $this->releaseList(self::ACTIVITY_TID);
    }

    /**
     * 发布抢购活动
     */
    public function release_activity()
    {
        $this->assign('seo_title', '发布抢购活动');
        $this->assign('returnUrl', url('user/activity'));
        return $this->release(self::ACTIVITY_TID, 'ReleaseActivity', 'release_scare_buying_table');
    }


    /**
     * 人才招聘列表
     */
    public function recruit()
    {
        $this->assign('action', 'recruit');
        $this->assign('seo_title', '人才招聘');
        return $this->releaseList(self::RECRUIT_TID);
    }

    /**
     * 发布人才招聘
     */
    public function release_recruit()
    {
        //企业等级
        $level = getEnterprise($this->auth->id, 'level');
        //可发布数量
        $num = config("enterprise.level_{$level}");

        $this->assign('seo_title', '发布人才招聘');
        $this->assign('returnUrl', url('user/recruit'));
        return $this->release(self::RECRUIT_TID, 'ReleaseRecruit', 'release_recruit', $num['recruit']);
    }


    /**
     * 供应信息列表
     */
    public function supply()
    {
        $this->assign('action', 'supply');
        $this->assign('seo_title', '供应信息');
        return $this->releaseList(self::SUPPLY_TID, 'release_supply_list');
    }

    /**
     * 发布供应信息
     */
    public function release_supply()
    {
        //企业等级
        $level = getEnterprise($this->auth->id, 'level');
        //可发布数量
        $num = config("enterprise.level_{$level}");

        $this->assign('seo_title', '发布供应信息');
        $this->assign('level', $level);
        $this->assign('returnUrl', url('user/supply'));
        return $this->release(self::SUPPLY_TID, 'ReleaseSupply', 'release_supply_msg', $num['supply']);
    }

    /**
     * 求购列表
     */
    public function purchase()
    {
        $this->assign('action', 'purchase');
        $this->assign('seo_title', '求购列表');
        return $this->releaseList(self::PURCHASE_TID);
    }

    /**
     * 发布求购
     */
    public function release_purchase()
    {
        //企业信息
        $info = getEnterprise($this->auth->id);
        //可发布数量
        $num = config("enterprise.level_{$info['level']}");

        $this->assign('seo_title', '发布求购');
        $this->assign('returnUrl', url('user/purchase'));
        $this->assign('info', $info);
        return $this->release(self::PURCHASE_TID, 'ReleasePurchase', 'release_for_buying', $num['purchase']);
    }

    /**
     * 投稿配方列表
     */
    public function formula()
    {
        $this->assign('action', 'formula');
        $this->assign('seo_title', '配方列表');
        $this->assign('title', '已发布配方');
        return $this->releaseList(self::FORMULA_TID);
    }

    /**
     * 发布配方
     */
    public function release_formula()
    {
        //获取配方类别列表
        $files = Fields::get(174);
        $content = explode("\n", $files['content']);
        $typeList = [];
        foreach ($content as $v) {
            $item = explode("|", trim($v));
            $typeList[] = ['title' => $item[0], 'value' => $item[1]];
        }

        $this->disposeCategory('formula');
        $this->assign('typeList', $typeList);
        $this->assign('seo_title', '发布配方');
        $this->assign('returnUrl', url('user/formula'));
        return $this->release(self::FORMULA_TID, 'ContributeRecipe', 'contribute_recipe');
    }

    /**
     * 行业知识库列表
     */
    public function knowledge()
    {
        $this->assign('action', 'knowledge');
        $this->assign('seo_title', '知识库列表');
        $this->assign('title', '已发布知识');
        return $this->releaseList(self::KNOWLEDGE_TID);
    }

    /**
     * 发布知识库
     */
    public function release_knowledge()
    {
        //获取知识库类别列表
        $files = Fields::get(199);
        $content = explode("\n", $files['content']);
        $typeList = [];
        foreach ($content as $v) {
            $item = explode("|", trim($v));
            $typeList[] = ['title' => $item[0], 'value' => $item[1]];
        }

        $this->disposeCategory('konwledge');
        $this->assign('typeList', $typeList);
        $this->assign('seo_title', '发布知识库');
        $this->assign('returnUrl', url('user/knowledge'));
        return $this->release(self::KNOWLEDGE_TID, 'ContributeKnowledge', 'contribute_knowledge');
    }

    /**
     * 通用发布列表
     * @param $channel_id int 栏目id
     * @return string
     */
    private function releaseList($channel_id, $tpl = '')
    {
        $list = db('cms_archives')
            ->field('id,title,createtime,status,status,updatetime,views')
            ->where(['user_id' => $this->auth->id, 'channel_id' => $channel_id, 'deletetime' => null])
            ->order('updatetime', 'DESC')
            ->paginate(config('page_size.release'), false);

        $channel = Channel::get($channel_id)->toArray();

        $this->view->assign('list', $list);
        $this->view->assign('channel', $channel);
        return $this->view->fetch($tpl ?: 'release_list');
    }

    /**
     * 通用删除发布信息
     */
    public function release_del()
    {
        $id = input('id/d');

        $info = db('cms_archives')->alias('a')
            ->field('a.id,a.user_id,a.channel_id,b.table,a.status')
            ->join('cms_model b', 'a.model_id = b.id', 'LEFT')
            ->where(['user_id' => $this->auth->id, 'a.id' => $id])
            ->find();

        if ($info && in_array($info['channel_id'], [self::FORMULA_TID, self::KNOWLEDGE_TID])) {
            if ($info['status'] == 'normal') {
                $this->error(__('Audited, cannot be deleted'));
            }

            $order = Order::get(['goods_table' => 'uweb_cms_archives', 'goods_id' => $info['id'], 'shop_id' => $info['user_id']]);
            if ($order) {
                $this->error(__('Cannot delete, a user has placed an order to buy'));
            }
        }

        $res = db('cms_archives')->where(['user_id' => $this->auth->id, 'id' => $id])->delete();

        if ($res) {

            db('cms_channel')->where('id', $info['channel_id'])->where('items', '>', 0)->setDec('items');

            db($info['table'])->where(['id' => $id])->delete();

            $this->success('删除成功');
        }

        $this->error('删除失败');

    }

    /**
     * 通用发布/修改信息
     * @param $channel_id int 栏目id
     * @param $validateFile string 字段验证文件
     * @param $tpl string 发布页面模板
     * @param $tpl string 允许发布的数量
     * @return string
     */
    private function release($channel_id, $validateFile, $tpl, $num = '*')
    {
        $channel = Channel::get($channel_id)->toArray();
        $model = Modelx::get($channel['model_id'])->toArray();

        //token名称
        $tokenName = $this->request->action() . '_token';

        //条件
        $where = [
            'user_id' => $this->auth->id,
            'channel_id' => $channel_id,
            'deletetime' => null,
        ];

        $id = input('id/d');
        $read = db('cms_archives')->alias('a')
            ->join($model['table'] . ' b', 'a.id = b.id', 'LEFT')
            ->where('a.id', $id)
            ->where($where)
            ->find();

        //图册处理
        if (isset($read['images']) && $read['images']) {
            $read['images'] = explode(',', $read['images']);
        }

        //判断是否超过允许发布数量
        if ($num !== '*' && (!$read || $read['status'] == 'pulloff')) {

            $count = db('cms_archives')->where($where)->where('status', 'neq', 'pulloff')->count();

            if ($count >= $num) {
                //荣誉提示
                if ($channel_id == self::HONOR_TID) {
                    $this->error(__('Over the number of releases honor'), null, ['token' => $this->request->token($tokenName)]);
                }

                $this->error(__('Over the number of releases'), null, ['token' => $this->request->token($tokenName)]);
            }
        }

        if ($this->request->isPost()) {

            $token = $this->request->post($tokenName);
            $captcha = $this->request->post('imgcaptcha');

            $rule = [
                $tokenName => 'token:' . $tokenName,
            ];
            $msg = [];
            $data = [
                $tokenName => $token
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token($tokenName)]);
            }

            //验证图形验证码
            $result = captcha_check($captcha);
            if (!$result) {
                $this->error("图形验证码错误！", null, ['token' => $this->request->token($tokenName)]);
            }

            //已审核，不能修改
            if ($read && in_array($read['channel_id'], [self::FORMULA_TID, self::KNOWLEDGE_TID])) {
                if ($read['status'] == 'normal') {
                    $this->error(__('It has been reviewed and cannot be modified'), null, ['token' => $this->request->token($tokenName)]);
                }
            }

            $row = $this->request->post("row/a");
            //内容
            $row['content'] = input('post.content', '');

            //图册
            if (isset($row['images']) && is_array($row['images'])) {
                $row['images'] = implode(',', $row['images']);
            }

            //采购量
            $number = input('number/d');
            if ($number === 0) {
                //不限
                $row['number'] = 0;
            }

            //标签
            if (isset($row['tags'])) {
                //将中文逗号和空格统一转换为英文逗号
                $row['tags'] = str_replace(['，', ' '], ',', $row['tags']);
            }

            //初始化信息
            if (!$read) {
                //栏目id
                $row['channel_id'] = $channel_id;
                //会员id
                $row['user_id'] = $this->auth->id;
                $row['publishtime'] = time();
                $row['status'] = 'hidden';
            }

            //字段验证
            $validate = Loader::validate($validateFile);
            if (!$validate->check($row)) {
                $this->error($validate->getError(), null, ['token' => $this->request->token($tokenName)]);
            }

            $model = new \app\admin\model\cms\Archives;

            try {

                //判断是否投稿配方和行业知识库，如果是设置文章来源
                if (in_array($channel_id, [self::FORMULA_TID, self::KNOWLEDGE_TID])) {
                    if ($row['price'] == 0) {
                        $row['source'] = $this->auth->nickname;
                    } else {
                        $row['source'] = config('site.formula_konwledge_source');
                    }
                }

                if ($read) {
                    $row = array_merge($read, $row);

                    //修改
                    $result = $model->allowField(true)->save($row, ['id' => $read['id']]);

                    if ($result > 0 || $model->isChange || $row['status'] == 'pulloff') {
                        //修改且有影响行数或者是重新上架，状态改为审核中
                        $model->save(['status' => 'hidden'], ['id' => $read['id']]);
                    }

                } else {
                    //新增
                    $result = $model->allowField(true)->save($row);
                }

                if ($result !== false) {
                    $returnUrl = input('returnUrl');
                    $msg = '提交成功';

                    //如果是知识库和配发发布，判断是否认证个人认证信息，没有跳转认证页面
                    if (in_array($channel_id, [self::FORMULA_TID, self::KNOWLEDGE_TID])) {
                        if (empty($this->auth->verify_id)) {
                            $verify = Verify::get(['user_id' => $this->auth->id]);
                            if (empty($verify)) {
                                $msg = '提交成功，还没有认证个人身份，请先认证';
                                $returnUrl = url('/user/idAuthentication');
                            }
                        }
                    }

                    $this->success($msg, $returnUrl ?: null);
                } else {
                    $this->error($model->getError(), null, ['token' => $this->request->token($tokenName)]);
                }
            } catch (\think\exception\PDOException $e) {
                $this->error($e->getMessage(), null, ['token' => $this->request->token($tokenName)]);
            } catch (\think\Exception $e) {
                $this->error($e->getMessage(), null, ['token' => $this->request->token($tokenName)]);
            }

            $this->error('参数错误', null, ['token' => $this->request->token($tokenName)]);
        }

        $this->view->assign('read', $read);
        $this->view->assign('channel', $channel);
        return $this->view->fetch($tpl);

    }

    /**
     * 企业联系信息
     * @return string
     * @throws \think\Exception
     */
    public function enterprise_contact()
    {
        $tokenName = 'enterprise_contact_token';
        $enterprise = model('admin/UserEnterprise')->get(['user_id' => $this->auth->id]);
        if ($this->request->isPost()) {
            $token = $this->request->post($tokenName);
            $captcha = $this->request->post('imgcaptcha');

            $rule = [
                $tokenName => 'token:' . $tokenName,
            ];
            $msg = [];
            $data = [
                $tokenName => $token
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token($tokenName)]);
            }

            //验证图形验证码
            $result = captcha_check($captcha);
            if (!$result) {
                $this->error("图形验证码错误！", null, ['token' => $this->request->token($tokenName)]);
            }

            $enterprise->contact_text = input('post.contact_text');
            $enterprise->coordinate = str_replace('，', ',', input('post.coordinate'));
            $enterprise->save();

            $this->success('提交成功');
        }
        $this->view->assign('enterprise', $enterprise);
        return $this->view->fetch();
    }

    /**
     * 企业信息
     */
    public function enterprise()
    {
        //删除提醒
        $this->deleteTips('11');

        $tokenName = 'enterprise_token';

        $read = db('user_enterprise')->where('user_id', $this->auth->id)->find();
        //审核状态
        Lang::load(APP_PATH . 'admin/lang/zh-cn/user/enterprise.php');
        $this->view->assign('status', Lang::get('Status ' . $read['status']));

        if ($this->request->isPost()) {

            $token = $this->request->post($tokenName);
            $captcha = $this->request->post('imgcaptcha');

            $rule = [
                $tokenName => 'token:' . $tokenName,
            ];
            $msg = [];
            $data = [
                $tokenName => $token
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token($tokenName)]);
            }

            //验证图形验证码
            $result = captcha_check($captcha);
            if (!$result) {
                $this->error("图形验证码错误！", null, ['token' => $this->request->token($tokenName)]);
            }

            $row = input('post.row/a');
            $row['introduce'] = input('post.introduce');
            //日期转换
            if (isset($row['establish_time'])) {
                $row['establish_time'] = strtotime($row['establish_time']);
            }
            $row['user_id'] = $this->auth->id;

            //字段验证
            $validate = Loader::validate('UserEnterprise');
            if (!$validate->check($row)) {
                $this->error($validate->getError(), null, ['token' => $this->request->token($tokenName)]);
            }

            if ($read) {
                $row = array_merge($read, $row);
                //修改
                $res = model('admin/UserEnterprise')->allowField(true)->save($row, ['id' => $read['id']]);

                if ($read['status'] != 0 && $res > 0) {
                    //有影响行数且不是首次提交，状态改为审核中
                    model('admin/UserEnterprise')->save(['status' => 1], ['id' => $read['id']]);
                }

            } else {
                //新增
                $res = model('admin/UserEnterprise')->allowField(true)->save($row);
            }

            if ($res !== false) {

                $this->success(__('Authentication tips'), null, ['token' => $this->request->token($tokenName)]);
            }

            $this->error('提交失败，请联系管理员', url('user/index'), ['token' => $this->request->token($tokenName)]);
        }

        $this->assign('seo_title', '企业信息');
        $this->view->assign('read', $read);
        $this->view->assign('enterprise', config('enterprise'));
        return $this->view->fetch('enterprise_message');
    }

    /**
     * 会员中心
     */
    public function index()
    {
        //我的文章
        $articleNum = OrderModel::where('type', '3')->where('user_id', $this->auth->id)->where('is_delete', 0)->count();
        //我的咨询
        $onlineNum = OrderModel::where('type', '1')->where('user_id', $this->auth->id)->where('is_delete', 0)->count();
        //我的求购
        $purchaseNum = db('cms_archives')->where(['user_id' => $this->auth->id, 'channel_id' => self::PURCHASE_TID, 'deletetime' => null])->count();
        //我的供应
        $supplyNum = db('cms_archives')->where(['user_id' => $this->auth->id, 'channel_id' => self::SUPPLY_TID, 'deletetime' => null])->count();

        $expert = \app\admin\model\user\Expert::get($this->auth->expert_id);
        if ($expert) {
            Lang::load(APP_PATH . 'admin/lang/zh-cn/user/expert.php');
            $this->view->assign('expert_level', Lang::get('Level ' . $expert['level']));
            $this->view->assign('expert_time', date('Y.m.d', $expert['deadline_starttime']) . '-' . date('Y.m.d', $expert['deadline_endtime']));
        }
        $enterprise = UserEnterprise::get($this->auth->enterprise_id);
        if ($enterprise) {
            Lang::load(APP_PATH . 'admin/lang/zh-cn/user/enterprise.php');
            $this->view->assign('enterprise_level', Lang::get('Level ' . $enterprise['level']));
            if ($enterprise['level'] > 1) {
                $this->view->assign('enterprise_time', date('Y.m.d', $enterprise['start_time']) . '-' . date('Y.m.d', $enterprise['end_time']));
            }
        }

        $this->view->assign('articleNum', $articleNum);
        $this->view->assign('onlineNum', $onlineNum);
        $this->view->assign('purchaseNum', $purchaseNum);
        $this->view->assign('supplyNum', $supplyNum);

        $this->assign('seo_title', __('User center'));
        return $this->view->fetch();
    }

    /**
     * 注册会员
     */
    public function register()
    {
        $tokenName = 'register_token';
        $url = $this->request->request('url');
        if ($this->auth->id)
            $this->redirect(url('/user/index'));
//            $this->success(__('You\'ve logged in, do not login again'), $url);
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $mobile = $this->request->post('mobile', '');
            $code = $this->request->post('code');
            $captcha = $this->request->post('captcha');
            $token = $this->request->post($tokenName);
            $rule = [
                'username' => 'require|length:6,30',
                'password' => 'require|length:6,30',
                'mobile' => 'regex:/^1\d{10}$/',
                'captcha' => 'require',
                $tokenName => 'token:' . $tokenName,
            ];

            $msg = [
                'username.require' => 'Username can not be empty',
                'username.length' => 'Username must be 6 to 30 characters',
                'password.require' => 'Password can not be empty',
                'password.length' => 'Password must be 6 to 30 characters',
                'captcha.require' => 'Captcha can not be empty',
                'mobile' => 'Mobile is incorrect',
            ];
            $data = [
                'username' => $username,
                'password' => $password,
                'mobile' => $mobile,
                'captcha' => $captcha,
                $tokenName => $token,
            ];

            //验证图形验证码
            $result = captcha_check($captcha);
            if (!$result) {
                $this->error(__('Graphic captcha error'), null, ['token' => $this->request->token($tokenName)]);
            }

            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token($tokenName)]);
            }


            //手机验证码的验证
            $ret = Smslib::check($mobile, $code, 'register');
            if (!$ret) {
                $this->error(__('Cell phone verification code error'), null, ['token' => $this->request->token($tokenName)]);
            }
            if ($this->auth->register($username, $password, '', $mobile)) {

                $synchtml = '';
                ////////////////同步到Ucenter////////////////
                if (defined('UC_STATUS') && UC_STATUS) {
                    $uc = new \addons\ucenter\library\client\Client();
                    $synchtml = $uc->uc_user_synregister($this->auth->id, $password);
                }
                $this->success(__('Sign up successful') . $synchtml, $url ? $url : url('user/index'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token($tokenName)]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (!$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
        $this->assign('seo_title', __('Register'));
        return $this->view->fetch();
    }

    /**
     * 会员登录
     */
    public function login()
    {
        $tokenName = 'login_token';
        $url = $this->request->request('url');
        $backurl = $this->request->param('backurl', null);
        if ($this->auth->id)
            $this->redirect(url('/user/index'));
//            $this->success(__('You\'ve logged in, do not login again'), $url);
        if ($this->request->isPost()) {
            $account = $this->request->post('account');
            $password = $this->request->post('password');
            $keeplogin = (int)$this->request->post('keeplogin');
            $captcha = $this->request->post('captcha');
            $token = $this->request->post($tokenName);
            $rule = [
                'account' => 'require|length:6,30',
                'password' => 'require|length:6,30',
                'captcha' => 'require',
                $tokenName => 'token:' . $tokenName,
            ];

            $msg = [
                'account.require' => 'Account can not be empty',
                'account.length' => 'Account must be 6 to 30 characters',
                'password.require' => 'Password can not be empty',
                'password.length' => 'Password must be 6 to 30 characters',
                'captcha.require' => 'Captcha can not be empty',
            ];
            $data = [
                'account' => $account,
                'password' => $password,
                'captcha' => $captcha,
                $tokenName => $token,
            ];

            //验证图形验证码
            $result = captcha_check($captcha);
            if (!$result) {
                $this->error(__('Graphic captcha error'), null, ['token' => $this->request->token($tokenName)]);
            }

            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token($tokenName)]);
                return FALSE;
            }

            if ($this->auth->login($account, $password)) {
                $synchtml = '';
                ////////////////同步到Ucenter////////////////
                if (defined('UC_STATUS') && UC_STATUS) {
                    $uc = new \addons\ucenter\library\client\Client();
                    $synchtml = $uc->uc_user_synlogin($this->auth->id);
                }
//                $this->success(__('Logged in successful') . $synchtml, $url ? $url : url('user/index'), '', 0);
                //登录人数加一
                $counter = intval(file_get_contents(ROOT_PATH . "counter.dat"));
                $counter++;
                $fp = fopen(ROOT_PATH . "counter.dat", "w");
                fwrite($fp, $counter);
                fclose($fp);
                if ($backurl) {
                    $this->result(['url' => $backurl], 1);
                } else {
                    $this->result(['url' => url('/user/index')], 1);
                }
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token($tokenName)]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (!$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
        $this->view->assign('backurl', $backurl);
        $this->assign('seo_title', __('Login'));
        return $this->view->fetch();
    }

    /**
     * 会员首页登录
     */
    public function index_login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        $tokenName = 'index_login_token';
        $token = $this->request->post($tokenName);
        $rule = [
            'account' => 'require|length:6,30',
            'password' => 'require|length:6,30',
            $tokenName => 'token:' . $tokenName,
        ];

        $msg = [
            'account.require' => 'Account can not be empty',
            'account.length' => 'Account must be 6 to 30 characters',
            'password.require' => 'Password can not be empty',
            'password.length' => 'Password must be 6 to 30 characters',
        ];
        $data = [
            'account' => $account,
            'password' => $password,
            $tokenName => $token,
        ];
        $validate = new Validate($rule, $msg);
        $result = $validate->check($data);
        if (!$result) {
            $this->error(__($validate->getError()), url('/user/login'), ['token' => $this->request->token($tokenName)]);
            return FALSE;
        }

        if ($this->auth->login($account, $password)) {
            $synchtml = '';
            ////////////////同步到Ucenter////////////////
            if (defined('UC_STATUS') && UC_STATUS) {
                $uc = new \addons\ucenter\library\client\Client();
                $synchtml = $uc->uc_user_synlogin($this->auth->id);
            }
//            $this->success(__('Logged in successful') . $synchtml, url('user/index'));
            //登录人数加一
            $counter = intval(file_get_contents(ROOT_PATH . "counter.dat"));
            $counter++;
            $fp = fopen(ROOT_PATH . "counter.dat", "w");
            fwrite($fp, $counter);
            fclose($fp);
            $this->redirect(url('user/index'));
        } else {
            $this->error($this->auth->getError(), url('/user/login'), ['token' => $this->request->token($tokenName)]);
        }
    }

    /**
     * 手机验证码登录
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);

        if ($user) {
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }

        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $this->redirect(url('user/index'));
//            $this->success(__('Logged in successful'), url('/user/index'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注销登录
     */
    function logout()
    {
        //注销本站
        $this->auth->logout();
        $synchtml = '';
        ////////////////同步到Ucenter////////////////
        if (defined('UC_STATUS') && UC_STATUS) {
            $uc = new \addons\ucenter\library\client\Client();
            $synchtml = $uc->uc_user_synlogout();
        }
        //清除专家服务时间信息
        session($this->auth->id . '_expert_time', null);
//        $this->success(__('Logout successful') . $synchtml, url('/user/login'));
        $this->redirect(url('/'));
    }

    /**
     * 个人信息
     */
    public function profile()
    {
        $this->view->assign('seo_title', __('Profile'));
        return $this->view->fetch('personal_data');
    }

    /**
     * 忘记密码
     */
    public function forgetpwd()
    {
        $this->view->assign('seo_title', __('Forget password'));
        return $this->view->fetch();
    }

    /**
     * 修改密码
     */
    public function changepwd()
    {
        if ($this->request->isPost()) {
            $oldpassword = $this->request->post("oldpassword");
            $newpassword = $this->request->post("newpassword");
            $renewpassword = $this->request->post("renewpassword");
            $token = $this->request->post('__token__');
            $rule = [
                'oldpassword' => 'require|length:6,30',
                'newpassword' => 'require|length:6,30',
                'renewpassword' => 'require|length:6,30|confirm:newpassword',
                '__token__' => 'token',
            ];

            $msg = [
            ];
            $data = [
                'oldpassword' => $oldpassword,
                'newpassword' => $newpassword,
                'renewpassword' => $renewpassword,
                '__token__' => $token,
            ];
            $field = [
                'oldpassword' => __('Old password'),
                'newpassword' => __('New password'),
                'renewpassword' => __('Renew password')
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return FALSE;
            }

            $ret = $this->auth->changepwd($newpassword, $oldpassword);
            if ($ret) {
                $synchtml = '';
                ////////////////同步到Ucenter////////////////
                if (defined('UC_STATUS') && UC_STATUS) {
                    $uc = new \addons\ucenter\library\client\Client();
                    $synchtml = $uc->uc_user_synlogout();
                }
                $this->success(__('Reset password successful') . $synchtml, url('user/login'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        $this->view->assign('seo_title', __('Change password'));
        return $this->view->fetch();
    }

    /**
     * 专家认证
     */
    public function expertApplication()
    {
        $tokenName = 'expert_application_token';
        $this->deleteTips(10);
        $expert = model('app\admin\model\user\Expert');
        $expert = $expert::get(['user_id' => $this->auth->id]);

        if ($this->request->isPost()) {
            $row = $this->request->post('row/a');
            $captcha = $this->request->post('img_captcha');
            $token = $this->request->post($tokenName);

            $expertValidate = Loader::validate('app\admin\validate\user\Expert');
            $data = $row;
            $data[$tokenName] = $token;

            //图形验证码验证
            $result = captcha_check($captcha);
            if (!$result) {
                $this->error(__('The graphic verification code is not correct'), null, ['token' => $this->request->token($tokenName)]);
            }

            $result = $expertValidate->check($data);
            if (!$result) {
                $this->error(__($expertValidate->getError()), null, ['token' => $this->request->token($tokenName)]);
            }

            //判断数据库是否存在该条数据，存在则不添加
            if ($expert) {
                $row = array_merge($expert->toArray(), $row);
                //修改
                $result = model('app\admin\model\user\Expert')->allowField(true)->save($row, ['id' => $expert['id']]);

                if ($expert['status'] > 0 && $result > 0) {
                    //有影响行数且不是首次提交，状态改为审核中
                    model('app\admin\model\user\Expert')->save(['status' => 0], ['id' => $expert['id']]);
                    \app\admin\model\User::update(['expert_id' => null], ['id' => $this->auth->id]);
                }

                $this->success(__('Authentication tips'), url('/user/index'));
            } else {
                //新增
                $row['user_id'] = $this->auth->id;
                $result = model('app\admin\model\user\Expert')->allowField(true)->save($row);

                if ($result) {
                    $this->success(__('Authentication tips'), url('/user/index'));
                } else {
                    $this->error(__('Failure to submit'), null, ['token' => $this->request->token($tokenName)]);
                }
            }
        }

        $relationAdeptList = array();
        $list = \app\admin\model\cms\Tags::where('type', 0)->where('status', 1)->field('name')->select();
        foreach ($list as $val) {
            array_push($relationAdeptList, $val['name']);
        }
        $this->view->assign('relationAdeptList', $relationAdeptList);

        $this->disposeCategory('expert');
        $this->view->assign('expert', $expert);
        $this->view->assign('seo_title', '专家认证');
        return $this->view->fetch();
    }

    /**
     * 个人身份认证
     */
    public function idAuthentication()
    {
        $tokenName = 'idAuthentication_token';
        $this->deleteTips(9);
        $verify = \app\admin\model\user\Verify::get(['user_id' => $this->auth->id]);

        if ($this->request->isPost()) {
            $row = $this->request->post('row/a');
            $token = $this->request->post($tokenName);

            if ($verify) {
                //验证图片是否修改了
                $row['idcardfrontimage'] = $row['idcardfrontimage'] == $verify['idcardfrontimage'] ? $row['idcardfrontimage'] : $this->_base64ImageContent($row['idcardfrontimage'], '/uploads/personVerify');
                $row['idcardversoimage'] = $row['idcardversoimage'] == $verify['idcardversoimage'] ? $row['idcardversoimage'] : $this->_base64ImageContent($row['idcardversoimage'], '/uploads/personVerify');
                $row['idcardhandimage'] = $row['idcardhandimage'] == $verify['idcardhandimage'] ? $row['idcardhandimage'] : $this->_base64ImageContent($row['idcardhandimage'], '/uploads/personVerify');
            } else {
                //将base64转为图片文件
                $row['idcardfrontimage'] = $this->_base64ImageContent($row['idcardfrontimage'], '/uploads/personVerify');
                $row['idcardversoimage'] = $this->_base64ImageContent($row['idcardversoimage'], '/uploads/personVerify');
                $row['idcardhandimage'] = $this->_base64ImageContent($row['idcardhandimage'], '/uploads/personVerify');
            }

            $rule = [
                'name' => 'require',
                'idcard' => 'require',
                'idcardfrontimage' => 'require',
                'idcardversoimage' => 'require',
                'idcardhandimage' => 'require',
                $tokenName => 'token:' . $tokenName,
            ];

            $msg = [
                'name.require' => '姓名不能为空',
                'idcard.require' => '身份证号码不能为空',
                'idcardfrontimage.require' => '身份证正面照片不能为空，并且大小不能超过2M',
                'idcardversoimage.require' => '身份证反面照片学历不能为空，并且大小不能超过2M',
                'idcardhandimage.require' => '身份证手持照片不能为空，并且大小不能超过2M',
            ];

            $data = $row;
            $data[$tokenName] = $token;

            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token($tokenName)]);
            }

            if ($verify) {
                $user = model('app\admin\model\User');
                $user->where('id', '=', $verify['user_id'])->update(['verify_id' => null]);
                $row['status'] = '0';
                $result = $verify->save($row, ['user_id' => $this->auth->id]);
            } else {
                $verify = model('app\admin\model\user\Verify');
                $row['user_id'] = $this->auth->id;
                $result = $verify->allowField(true)->save($row);
            }
            if ($result) {
                $this->success(__('Submitted successfully'), url('/user/index'));
            } else {
                $this->error(__('Failure to submit'), null, ['token' => $this->request->token($tokenName)]);
            }
        }

        $this->view->assign('verify', $verify);
        $this->view->assign('seo_title', '身份认证');
        return $this->view->fetch();
    }

    /**
     * [将Base64图片转换为本地图片并保存]
     * @param $base64_image_content [要保存的Base64]
     * @param $path [要保存的路径]
     * @return bool|string
     */
    private function _base64ImageContent($base64_image_content, $path)
    {
        //判断base64是否为空
        if (empty($base64_image_content)) {
            return null;
        }

        //判断图片大小是否超过2M
        $img_len = strlen($base64_image_content);
        $size = ($img_len - ($img_len / 8) * 2);
        if (number_format(($size / 1024), 2) > 2100) {
            return null;
        }

        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $path = $path . "/" . date('Ymd', time()) . "/";
            $basePutUrl = ROOT_PATH . 'public' . $path;
            if (!file_exists($basePutUrl)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($basePutUrl, 0777, true);
            }

            $ping_url = date('Ymd', time()) . '_' . rand(1000000, 9999999) . ".{$type}";
            $local_file_url = $basePutUrl . $ping_url;

            if (file_put_contents($local_file_url, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                //返回文件路径
                return $path . $ping_url;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * 我的在线服务
     */
    public function onlineService()
    {
        $this->deleteTips(1);
        $list = OrderModel::alias('a')
            ->where('a.status', '1')
            ->where('a.type', '1')
            ->where('a.is_delete', 0)
            ->where('a.shop_id', $this->auth->expert_id)
            ->join('expert_online' . ' n', 'a.goods_id=n.id', 'LEFT')
            ->join('user' . ' u', 'n.user_id=u.id', 'LEFT')
            ->field('a.appraise,a.star,a.appraisetime,a.id as orderid,a.replytime,a.replyappraise,a.confirm_finish')
            ->field(true, false, config('database.prefix') . 'expert_online', 'n')
            ->field('nickname as user', false, config('database.prefix') . 'user', 'u')
            ->order('n.updatetime', 'desc')
            ->paginate(config('page_size.online_service'));

        $this->assign('list', $list);

        $this->assign('seo_title', '我的在线服务');
        return $this->view->fetch();
    }

    /**
     * 在线服务抢单
     */
    public function grabOnlineService()
    {
        $this->deleteTips(8);
        $expert = \app\admin\model\user\Expert::get($this->auth->expert_id);
        $list = OrderModel::alias('a')
            ->where('a.status', '1')
            ->where('a.type', '1')
            ->where('a.is_delete', 0)
            ->join('expert_online' . ' n', 'a.goods_id=n.id', 'LEFT')
            ->where('n.is_flag', '1')
            ->where('n.level', $expert['level'])
            ->field('a.totalprice')
            ->field(true, false, config('database.prefix') . 'expert_online', 'n')
            ->order('n.updatetime', 'desc')
            ->paginate(config('page_size.grade_locale_service'));

        $this->assign('list', $list);
        $this->assign('seo_title', '在线服务抢单');
        return $this->view->fetch();
    }

    /**
     * 我的现场服务
     */
    public function localeService()
    {
        $this->deleteTips(2);
        $list = OrderModel::alias('a')
            ->where('a.status', '1')
            ->where('a.type', '2')
            ->where('a.is_delete', 0)
            ->where('a.shop_id', $this->auth->expert_id)
            ->join('expert_locale' . ' n', 'a.goods_id=n.id', 'LEFT')
            ->join('user' . ' u', 'n.user_id=u.id', 'LEFT')
            ->field('a.appraise,a.star,a.appraisetime,a.id as orderid,a.replytime,a.replyappraise,a.confirm_finish')
            ->field(true, false, config('database.prefix') . 'expert_locale', 'n')
            ->field('nickname as user', false, config('database.prefix') . 'user', 'u')
            ->order('n.updatetime', 'desc')
            ->paginate(config('page_size.locale_service'));

        $this->assign('list', $list);
        $this->assign('seo_title', '我的现场服务');
        return $this->view->fetch();
    }

    /**
     * 专家抢单
     */
    public function experdGrabOrder()
    {
        $id = $this->request->post('id');
        $token = $this->request->post('__token__');

        $rule = [
            'id' => 'require',
            '__token__' => 'token',
        ];

        $msg = [
            'id.require' => '参数错误',
        ];

        $validate = new Validate($rule, $msg);
        $result = $validate->check(['id' => $id, '__token__' => $token]);
        if (!$result) {
            $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
        }

        $online = OnlineModel::get($id);
        if (empty($online)) {
            $this->error(__('The current service sheet does not exist'), null, ['token' => $this->request->token()]);
        }

        $fp = fopen(ROOT_PATH . 'lock.txt', 'w+');

        if (flock($fp, LOCK_EX)) {
            //抢单操作
            $expert = \app\admin\model\user\Expert::get($this->auth->expert_id);
            $order = OrderModel::get(['user_id' => $online['user_id'], 'type' => '1', 'goods_id' => $online['id'], 'status' => '1']);

            //判断该专家是否有等级抢这单
            if ($expert['level'] != $online['level']) {
                $this->error(__('Grab a single failure'), null, ['token' => $this->request->token()]);
            }

            //判断该订单是否已接单
            if (!isset($order) || !empty($order['shop_id'])) {
                $this->error(__('I\'m sorry, this order has been snatched by other experts, come on next time!'));
            }

            $online->startTrans();  //开启事务
            $order->startTrans();

            $online->expert_id = $this->auth->expert_id;
            //序列化专家的基本信息和认证信息
            $online->expert_basic = serialize(\app\admin\model\User::get($expert['user_id']));
            $online->expert_info = serialize($expert);

            $result = $online->save();
            if (!$result) {
                $online->rollback();
                $this->error(__('Grab a single failure'), null, ['token' => $this->request->token()]);
            }

            //订单表保存商家ID
            $order->shop_id = $this->auth->expert_id;
            $result = $order->save();
            if (!$result) {
                $order->rollback();
                $online->rollback();
                $this->error(__('Grab a single failure'), null, ['token' => $this->request->token()]);
            }

            $online->commit();
            $order->commit();

            flock($fp, LOCK_UN);
            $this->createTips($online->user_id, 1);
            $this->result(null, 1, __('Grab a single success'));
        } else {
            $this->error(__('The service is busy, please try again later'));
        }

        fclose($fp);
    }

    /**
     * 确认前的验证
     */
    private function confirm($model, $field)
    {
        $id = $this->request->post('id');
        $token = $this->request->post('__token__');

        $rule = [
            'id' => 'require',
            '__token__' => 'token',
        ];

        $msg = [
            'id.require' => '参数错误',
        ];

        $validate = new Validate($rule, $msg);
        $result = $validate->check(['id' => $id, '__token__' => $token]);
        if (!$result) {
            $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
        }

        $model = $model::get($id);
        if (empty($model)) {
            $this->error(__('The current service sheet does not exist'), null, ['token' => $this->request->token()]);
        }

        $id = $this->auth->expert_id;
        if ($field == 'user_id') {
            $id = $this->auth->id;
        }

        //验证是否当前专家，或者当前用户
        if ($model[$field] != $id) {
            $this->error(__('You do not have permission to operate'), null, ['token' => $this->request->token()]);
        }

        return $model;
    }

    /**
     * 专家确认现场服务
     */
    public function confirmService()
    {
        $locale = $this->confirm(model('app\admin\model\expert\Locale'), 'expert_id');
        $locale->is_confirm = '1';
        $result = $locale->save();

        if ($result) {
            $user = \app\admin\model\User::get($locale->user_id);
            $data = ['type' => '现场服务', 'time' => date('Y-m-d H:i', time()), 'title' => $locale['title']];
            Frontend::sendTplMsg($user['openid'], '您好，您的现场服务专家已确认，请查看！', url('/mobile/user/myReservation', '', true, true), $data);
            $this->createTips($locale->user_id, 6);
            $this->result(null, 1, __('Determine the success'));
        } else {
            $this->error(__('Confirm the failure'), null, ['token' => $this->request->token()]);
        }
    }

    /**
     * 专家回复在线服务留言
     */
    public function replyService()
    {
        $replycontent = $this->request->post('replycontent', null);
        if (empty($replycontent)) {
            $this->error(__('Reply content cannot be empty'), null, ['token' => $this->request->token()]);
        }

        $online = $this->confirm(model('app\admin\model\expert\Online'), 'expert_id');
        $online->replycontent = $replycontent;
        $online->is_reply = '1';
        $result = $online->save();

        if ($result) {
            //发送微信通知
            $user = \app\admin\model\User::get($online->user_id);
            $data = ['type' => '在线提问', 'time' => date('Y-m-d H:i', time()), 'title' => $online['title']];
            Frontend::sendTplMsg($user['openid'], '您好，您的问题专家已回复，请查看！', url('/mobile/user/myAskquestion', '', true, true), $data);
            $this->createTips($online->user_id, 5);
            $this->result(null, 1, __('Reply to success'));
        } else {
            $this->error(__('Respond to failure'), null, ['token' => $this->request->token()]);
        }
    }

    /**
     * 专家驳回在线服务
     */
    public function rollbackService()
    {
        $rollbackcontent = $this->request->post('rollbackcontent', null);
        if (empty($rollbackcontent)) {
            $this->error(__('The grounds for rejection cannot be empty'), null, ['token' => $this->request->token()]);
        }

        $online = $this->confirm(model('app\admin\model\expert\Online'), 'expert_id');

        if ($online['is_rollback'] == '1') {
            $this->error(__('If the rejection fails, it can only be rejected once'), null, ['token' => $this->request->token()]);
        }

        $online->rollbackcontent = $rollbackcontent;
        $online->is_rollback = '1';
        $result = $online->save();

        if ($result) {
            //发送微信通知
            $user = \app\admin\model\User::get($online->user_id);
            $data = ['type' => '在线提问', 'time' => date('Y-m-d H:i', time()), 'title' => $online['title']];
            Frontend::sendTplMsg($user['openid'], '您好，您的问题被专家驳回，请查看！', url('/mobile/user/myAskquestion', '', true, true), $data);
            $this->createTips($online->user_id, 5);
            $this->result(null, 1, __('Dismiss the success'));
        } else {
            $this->error(__('Dismiss the failure'), null, ['token' => $this->request->token()]);
        }
    }

    /**
     * 专家回复评价
     */
    public function replyAppraise()
    {
        $replyappraise = $this->request->post('replyappraise', null);
        if (empty($replyappraise)) {
            $this->error(__('Reply content cannot be empty'), null, ['token' => $this->request->token()]);
        }

        $order = $this->confirm(model('app\admin\model\Order'), 'shop_id');

        //判断订单是否已回复
        if (empty($order['replyappraise'])) {
            $order['replyappraise'] = $replyappraise;
            $order['replytime'] = time();
            $result = $order->save();
            if ($result) {
                $this->result(null, 1, __('Reply to success'));
            } else {
                $this->error(__('Respond to failure'), null, ['token' => $this->request->token()]);
            }
        } else {
            $this->error(__('Cannot reply repeatedly'), null, ['token' => $this->request->token()]);
        }
    }

    /**
     * 我的配方
     */
    public function myArticle()
    {
        $list = OrderModel::alias('a')
            ->where('a.user_id', $this->auth->id)
            ->where('a.type', '3')
            ->where('a.is_delete', 0)
            ->join('cms_archives' . ' n', 'a.goods_id=n.id', 'LEFT')
            ->where('n.status', 'normal')
            ->field('a.status as paystatus,a.id as orderid,a.trade_sn as ordertrade_sn,a.title as ordertitle,a.totalprice as ordertotalprice,a.createtime as ordercreatetime')
            ->field(true, false, config('database.prefix') . 'cms_archives', 'n')
            ->order('a.createtime', 'desc')
            ->paginate(config('page_size.my_article'));

        foreach ($list as $item) {
            //判断订单是否过期
            if (time() > strtotime('+1 hour', $item['ordercreatetime']) && $item['paystatus'] == '0') {
                db('order')->where('id', $item['orderid'])->update(['status' => '2']);
                $item['paystatus'] = '2';
            }
        }

        $this->assign('list', $list);
        $this->assign('seo_title', '我的配方');
        return $this->view->fetch();
    }

    /**
     * 我的知识
     */
    public function myKonwledge()
    {
        $list = OrderModel::alias('a')
            ->where('a.user_id', $this->auth->id)
            ->where('a.type', '4')
            ->where('a.is_delete', 0)
            ->join('cms_archives' . ' n', 'a.goods_id=n.id', 'LEFT')
            ->where('n.status', 'normal')
            ->field('a.status as paystatus,a.id as orderid,a.trade_sn as ordertrade_sn,a.title as ordertitle,a.totalprice as ordertotalprice,a.createtime as ordercreatetime')
            ->field(true, false, config('database.prefix') . 'cms_archives', 'n')
            ->order('a.createtime', 'desc')
            ->paginate(config('page_size.my_article'));

        foreach ($list as $item) {
            //判断订单是否过期
            if (time() > strtotime('+1 hour', $item['ordercreatetime']) && $item['paystatus'] == '0') {
                db('order')->where('id', $item['orderid'])->update(['status' => '2']);
                $item['paystatus'] = '2';
            }
        }

        $this->assign('list', $list);
        $this->assign('seo_title', '我的知识');
        return $this->view->fetch();
    }

    /**
     * 我的咨询
     */
    public function myAskquestion()
    {
        $this->deleteTips(5);
        $list = OrderModel::alias('a')
            ->where('a.user_id', $this->auth->id)
            ->where('a.type', '1')
            ->where('a.is_delete', 0)
            ->join('expert_online' . ' n', 'a.goods_id=n.id', 'LEFT')
            ->field('a.*')
            ->field(true, false, config('database.prefix') . 'expert_online', 'n', 'online_')
            ->order('n.updatetime', 'desc')
            ->paginate(config('page_size.my_askquestion'));

        foreach ($list as $item) {
            //判断订单是否过期
            if (time() > strtotime('+1 hour', $item['createtime']) && $item['status'] == '0') {
                db('order')->where('id', $item['id'])->update(['status' => '2']);
                $item['status'] = '2';
            }
        }

        $this->assign('list', $list);
        $this->assign('seo_title', '我的咨询');
        return $this->view->fetch();
    }

    /**
     * 我的服务
     */
    public function myReservation()
    {
        $this->deleteTips(6);
        $list = OrderModel::alias('a')
            ->where('a.user_id', $this->auth->id)
            ->where('a.type', '2')
            ->where('a.is_delete', 0)
            ->join('expert_locale' . ' n', 'a.goods_id=n.id', 'LEFT')
            ->field('a.*')
            ->field(true, false, config('database.prefix') . 'expert_locale', 'n', 'locale_')
            ->order('n.updatetime', 'desc')
            ->paginate(config('page_size.my_reservation'));

        foreach ($list as $item) {
            //判断订单是否过期
            if (time() > strtotime('+1 hour', $item['createtime']) && $item['status'] == '0') {
                db('order')->where('id', $item['id'])->update(['status' => '2']);
                $item['status'] = '2';
            }
        }

        $this->assign('list', $list);
        $this->assign('seo_title', '我的服务');
        return $this->view->fetch();
    }

    /**
     * 用户重写提交在线服务
     */
    public function resumitOnline()
    {
        $question_description = $this->request->post('question_description', null);
        $file = $this->request->post('file', null);

        if (empty($question_description)) {
            $this->error(__('The problem description cannot be empty'), null, ['token' => $this->request->token()]);
        }

        $online = $this->confirm(model('app\admin\model\expert\Online'), 'user_id');

        if ($online['is_commit'] == '1') {
            $this->error(__('It has been resubmitted'), null, ['token' => $this->request->token()]);
        }

        $online->question_description = $question_description;
        if (!empty($file)) {
            $online->file = $file;
        }
        $online->is_commit = '1';
        $result = $online->save();

        if ($result) {
            $user = unserialize($online->expert_basic);
            $data = ['type' => '在线服务', 'time' => date('Y-m-d H:i', time()), 'title' => $online['title']];
            Frontend::sendTplMsg($user['openid'], '您好，用户的问题已重新提交，请查看！', url('/mobile/user/onlineService', '', true, true), $data);
            $this->createTips($user['id'], '1');
            $this->result(null, 1, __('Submitted successfully'));
        } else {
            $this->error(__('Failure to submit'), null, ['token' => $this->request->token()]);
        }
    }

    /**
     * 用户评价
     */
    public function appraise()
    {
        $appraise = $this->request->post('appraise', null);
        $star = $this->request->post('star', null);

        if (empty($appraise)) {
            $this->error(__('Appraise content cannot be empty'), null, ['token' => $this->request->token()]);
        }
        if ($star != null && $star < 0) {
            $this->error(__('Star rating cannot be empty'), null, ['token' => $this->request->token()]);
        }

        $order = $this->confirm(model('app\admin\model\Order'), 'user_id');

        //判断订单是否已回复
        if (empty($order['appraise'])) {
            //星级评价3星以上（包括3星）订单类型是在线提问和现场服务则增加好评
            $expert = \app\admin\model\user\Expert::get($order['shop_id']);
            if ($star >= 3 && in_array($order['type'], [1, 2])) {
                $expert->likes = $expert->likes + 1;
                $expert->save();
            }

            $order['star'] = $star > 5 ? 5 : $star;
            $order['appraise'] = $appraise;
            $order['appraisetime'] = time();
            $result = $order->save();
            if ($result) {
                $this->createTips($expert['user_id'], $order['type']);
                $this->result(null, 1, __('Appraise to success'));
            } else {
                $this->error(__('Appraise to failure'), null, ['token' => $this->request->token()]);
            }
        } else {
            $this->error(__('Cannot appraise repeatedly'), null, ['token' => $this->request->token()]);
        }
    }

    /**
     * 用户确定结束服务，将订单的金额，转为金豆转给专家
     */
    public function confirm_finish()
    {
        $order = $this->confirm(model('app\admin\model\Order'), 'user_id');

        if ($order['confirm_finish'] == '1') {
            $this->error(__('Please do not repeat the confirmation'), null, ['token' => $this->request->token()]);
        }

        // 判断订单类型
        if (!in_array($order['type'], [1, 2])) {
            $this->error(__('Failure to submit'), null, ['token' => $this->request->token()]);
        }

        Db::startTrans();

        $expertUserId = \app\admin\model\user\Expert::where('id', $order['shop_id'])->value('user_id');

        $row['confirm_finish'] = '1';
        $result = $order->allowField(true)->save($row);

        if (!$result) {
            Db::rollback();
            $this->error(__('Failure to submit'), null, ['token' => $this->request->token()]);
        }

        //判断订单的金额是否大于0，才给商家增加金豆
        if ($order['totalprice'] > 0) {
            $num = bcmul(bcmul($order['totalprice'], config('site.gold_num'), 2), (1 - config('site.service_charge')), 2);
            $result = \app\admin\model\User::where('id', $expertUserId)->setInc('score', $num);
            if (!$result) {
                Db::rollback();
                $this->error(__('Failure to submit'), null, ['token' => $this->request->token()]);
            }
            //记录收入
            insert_bill(+$num, 'score', $order['title'], $expertUserId, $order['type'], $order['trade_sn']);
        }

        Db::commit();

        $this->result(null, 1, __('Submitted successfully'));
    }

    /**
     * 文章收藏列表
     */
    public function myCollect()
    {
        //取消收藏
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $collect = Collect::get(['id' => $id, 'user_id' => $this->auth->id]);
            if ($collect) {
                $collect->delete();
                $this->result(null, 1, __('Cancel the success'));
            } else {
                $this->error(__('Cancel the failure'));
            }
        }

        $list = Collect::alias('a')
            ->where('a.user_id', $this->auth->id)
            ->join('cms_archives' . ' n', 'a.archives_id=n.id', 'LEFT')
            ->field('a.*')
            ->field('title,id as aid', false, config('database.prefix') . 'cms_archives', 'n')
            ->order('a.createtime', 'desc')
            ->limit(config('page_size.my_collect'))
            ->paginate();

        $this->assign('list', $list);
        $this->assign('seo_title', '我的收藏');
        return $this->view->fetch();
    }

    /**
     * 文章和知识订单删除
     */
    public function deleteAK()
    {
        $this->_deleteOrder(false);
    }

    /**
     * 咨询和服务订单删除
     */
    public function deleteOL()
    {
        $this->_deleteOrder(true);
    }

    /**
     * 通用删除订单
     * @param $flag 是否关联删除
     * @throws \think\exception\DbException
     */
    private function _deleteOrder($flag)
    {
        $id = $this->request->param('id', null);
        if (empty($id)) {
            $this->error(__('Parameter error'), null, ['token' => $this->request->param('__token__')]);
        }

        $order = OrderModel::get($id);
        if (empty($order)) {
            $this->error(__('The current order does not exist'), null, ['token' => $this->request->param('__token__')]);
        }

        //防止越权操作
        if ($order['user_id'] != $this->auth->id) {
            $this->error(__('Unauthorized operation, attention please'), null, ['token' => $this->request->param('__token__')]);
        }

        //检查订单状态
        if ($order['status'] == '1') {
            //更改删除标识
            $order->is_delete = 1;
            $order->save();
        } else {
            //开启事务
            $order->startTrans();
            $result = $order->delete();
            if (!$result) {
                $order->rollback();
                $this->error(__('Delete failed'), null, ['token' => $this->request->param('__token__')]);
            }

            //关联删除
            if ($flag) {
                $table = substr_replace($order['goods_table'], "", 0, 5);
                $model = db($table);
                //开启事务
                $model->startTrans();
                $result = $model->where('id', $order['goods_id'])->delete();
                if (!$result) {
                    //回滚
                    $model->rollback();
                    $order->rollback();
                    $this->error(__('Delete failed'), null, ['token' => $this->request->param('__token__')]);
                }
                //提交事务
                $model->commit();
            }
            //提交事务
            $order->commit();
        }

        $this->result(null, 1, __('Delete the success'));
    }

    /**
     * 微信支付
     */
    public function wechatPay()
    {
        $this->_pay('wechat');
    }

    /**
     * 支付宝支付
     */
    public function aliPay()
    {
        $this->_pay('alipay');
    }

    /**
     * 金豆支付
     */
    public function goldPay()
    {
        $this->_pay('gold');
    }

    /**
     * 金币支付
     */
    public function pointsPay()
    {
        $this->_pay('points');
    }

    /**
     * 线下支付 add20200629
     */
    public function Offlinepay()
    {
        $this->_pay('offlinepay');
    }

    /**
     * 提现列表
     */
    public function takeCashList()
    {
        $model = model('app\admin\model\user\Takecash');
        $list = $model->where('user_id', $this->auth->id)
            ->order('createtime', 'desc')
            ->paginate(config('page_size.take_cash_list'));
        $this->assign('list', $list);
        $this->assign('seo_title', '我要提现');
        return $this->fetch();
    }

    /**
     * 提现详细
     */
    public function takeCash()
    {
        $id = $this->request->param('id');
        $model = model('app\admin\model\user\Takecash');
        $read = $model::get($id);

        $this->assign('read', $read);
        $this->assign('bank_info', json_decode($this->auth->bank_info, true));
        $this->assign('seo_title', '我要提现');
        return $this->fetch();
    }

    /**
     * 获取可用余额
     */
    public function getAmount()
    {
        $number = $this->request->param('number');

        if (empty($number)) {
            $this->error(__('Parameter error'));
        }

        $verify = \app\admin\model\user\Verify::get(['user_id' => $this->auth->id]);
        $idcard = substr($verify['idcard'], -6);

        if ($number != $idcard) {
            $this->error(__('The last six digits of the id card are incorrect'));
        }

        $amount = $this->auth->score / config('site.gold_num');

        $this->result(['gold' => $this->auth->score, 'amount' => $amount], 1);
    }

    /**
     * 提交提现申请
     */
    public function applyTaskCash()
    {
        $tokenName = 'apply_task_cash_token';
        $day = date('d', time());
        if ($day < 10 || $day > 15) {
            $this->error(__('Withdrawals are only available from the 10th to 15th of each month'));
        }

        $row = $this->request->post('row/a');
        $captcha = $this->request->post('captcha');
        $token = $this->request->post($tokenName);

        $validate = Loader::validate('app\admin\validate\user\TakeCash');
        $data = $row;
        $data[$tokenName] = $token;

        //图形验证码验证
        $result = captcha_check($captcha);
        if (!$result) {
            $this->error(__('Captcha is incorrect'), null, ['token' => $this->request->token($tokenName)]);
        }

        $result = $validate->check($data);
        if (!$result) {
            $this->error(__($validate->getError()), null, ['token' => $this->request->token($tokenName)]);
        }

        //验证提现金额
        if ($row['amount'] < config('site.min_money')) {
            $this->error('提现金额不能低于' . config('site.min_money') . '元', null, ['token' => $this->request->token($tokenName)]);
        }
        if ($row['amount'] > 5000) {
            $this->error(__('The withdrawal amount shall not be more than 5000'), null, ['token' => $this->request->token($tokenName)]);
        }
        $withdrawalAmount = $this->auth->score / config('site.gold_num');
        if ($row['amount'] > $withdrawalAmount) {
            $this->error(__('The withdrawal amount exceeds the available amount'), null, ['token' => $this->request->token($tokenName)]);
        }

        $model = model('app\admin\model\user\Takecash');
        $userModel = \app\admin\model\User::get($this->auth->id);

        $model->startTrans();
        $userModel->startTrans();

        $row['user_id'] = $this->auth->id;
        $result = $model->allowField(true)->save($row);

        if (!$result) {
            $model->rollback();
            $this->error(__('Failure to submit'), null, ['token' => $this->request->token($tokenName)]);
        }

        $userModel['score'] = $userModel['score'] - ($row['amount'] * config('site.gold_num'));
        $userModel['bank_info'] = json_encode($row);
        $result = $userModel->save();

        if (!$result) {
            $model->rollback();
            $userModel->rollback();
            $this->error(__('Failure to submit'), null, ['token' => $this->request->token($tokenName)]);
        }

        $model->commit();
        $userModel->commit();

        $this->success(__('Submitted successfully'), url('/user/takeCashList'));
    }
}
