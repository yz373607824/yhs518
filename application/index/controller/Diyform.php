<?php

namespace app\index\controller;

use addons\cms\model\Diyform as DiyformModel;
use fast\Random;
use think\Exception;
use app\common\controller\Frontend;
use think\Validate;

/**
 * 自定义表单控制器
 */
class Diyform extends Frontend
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 提交
     */
    public function post()
    {
        $this->request->filter('strip_tags');
        if ($this->request->isPost()) {
            $diyname = $this->request->post('__diyname__');
            //token名称
            $tokenName = $diyname . '_token';
            $token = $this->request->post($tokenName);
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
            $diyform = DiyformModel::getByDiyname($diyname);
            if (!$diyform || $diyform['status'] != 'normal') {
                $this->error(__('表单未找到'));
            }
            if ($diyform['needlogin'] && !$this->auth->id) {
                $backurl = $this->request->post('backurl');
                $this->error(__('请登录后再操作'), url('/index/user/login') . '?backurl=' . $backurl);
            }

            $row = $this->request->post('row/a');

            //验证字段
            $fields = DiyformModel::getDiyformFields($diyform['id']);
            foreach ($fields as $index => $field) {
                if ($field['isrequire'] && (!isset($row[$field['name']]) || $row[$field['name']] == '')) {
                    $this->error("{$field['title']}不能为空！", null, ['token' => $this->request->token($tokenName)]);
                }else if( strpos($field['rule'], "mobile") !== false ){
                    //验证手机
                    if( (!isset($row[$field['name']]) || $row[$field['name']] == '') ){
                        $this->error("{$field['title']}不能为空！", null, ['token' => $this->request->token('')]);
                    }else if(!preg_match("/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/", $row[$field['name']]) ){
                        $this->error("{$field['title']}格式错误！", null, ['token' => $this->request->token($tokenName)]);
                    }
                }else if( strpos($field['rule'], "email") !== false ){
                    //验证邮箱
                    if( (!isset($row[$field['name']]) || $row[$field['name']] == '') ){
                        $this->error("{$field['title']}不能为空！", null, ['token' => $this->request->token($tokenName)]);
                    }else if(!preg_match('/^[a-z0-9]+([._-][a-z0-9]+)*@([0-9a-z]+\.[a-z]{2,14}(\.[a-z]{2})?)$/i', $row[$field['name']]) ){
                        $this->error("{$field['title']}格式错误！", null, ['token' => $this->request->token($tokenName)]);
                    }
                }
            }

            //如果有验证码，判断验证码
            $captcha = $this->request->post('captcha');
            if( isset($captcha) ){
                if( $captcha == '' ){
                    $this->error("验证码不能为空！", null, ['token' => $this->request->token($tokenName)]);
                }

                $result = captcha_check($captcha);
                if( !$result ){
                    $this->error("验证码错误！", null, ['token' => $this->request->token($tokenName)]);
                }
            }


            $row['user_id'] = $this->auth->id;
            $row['createtime'] = time();
            $row['updatetime'] = time();
            foreach ($row as $index => &$value) {
                if (is_array($value) && isset($value['field'])) {
                    $value = json_encode(\app\common\model\Config::getArrayData($value), JSON_UNESCAPED_UNICODE);
                } else {
                    $value = is_array($value) ? implode(',', $value) : $value;
                }
            }

            //抢购活动验证库存
            if ($diyform['id'] == 5) {
                $number = intval($row['number']) * intval($row['format']);
                $stock = db('cms_activity')->where('id', input('activity_id'))->value('number');
                if ($number > $stock) {
                    $this->error("库存不足！", null, ['token' => $this->request->token()]);
                }
            }



            //企业留言提醒
            if ($diyform['id'] == 3) {
                try {
                    \think\Db::name($diyform['table'])->insert($row);
                } catch (Exception $e) {
                    $this->error("发生错误:" . $e->getMessage());
                }
                $user_id = db('user_enterprise')->where('id', $row['enterprise_id'])->value('user_id');
                $this->createTips($user_id, '4');
            }
            //抢购活动提醒&库存更新(20200831改为下单就减库存)
            if ($diyform['id'] == 5) {
                $activity_id = input('activity_id');
                $data = $this->_saveAndOrder($activity_id, $row, config('order_type.activity'),
                    '抢购活动商品费用', 'uweb_cms_activity',$diyform['table']);
                if ($data === false) {
                    $this->error(__('Place the order failed'), null, ['token' => $this->request->token($tokenName)]);
                }
                $this->result($data, 1, __('Place an order successfully'));
            }

            $this->success($diyform['successtips'] ? $diyform['successtips'] : '提交成功！', $diyform['redirecturl'] ? url($diyform['redirecturl']) : null);
        }
        return;

    }

    /**
     * 保存表单并下单
     * @param $activity_id 表单模型
     * @param $row 表单数据
     * @param $orderType 订单类型
     * @param $title 标题
     * @param $table 关联商品表
     * @return array|bool 返回值
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @comment 提交订单并减库存(20200831改为下单就减库存)
     */
    private function _saveAndOrder($activity_id, $row, $orderType, $title, $table,$diyformTable)
    {
        $user_id = db('user_enterprise')->where('id', $row['enterprise_id'])->value('user_id');
        $orderModel = model('app\admin\model\Order');
        $activityFormModel = db($diyformTable);
        //20200831改为下单就减库存
        $activityModel = db('cms_activity');

        //开启事务
        $activityFormModel->startTrans();
        $orderModel->startTrans();
        //20200831改为下单就减库存
        $activityModel->startTrans();

        //价格
        $price = 0;
        //验证金额
        $currentPrice = config('site.article_pay_num');
        if ($row['total'] < $currentPrice) {
            return false;
        }
        $price = $row['total'];

        //订单内容填充
        $order['user_id'] = $this->auth->id;   //用户ID
        if (!empty($user_id)) {
            $order['shop_id'] = $user_id;   //商家ID
        }
        $order['trade_sn'] = date('YmdHis') . Random::numeric();   //订单编号
        $order['type'] = $orderType;   //订单类型
        $order['title'] = $title;   //标题
        $order['goods_table'] = $table;   //关联的商品表
        $order['goods_id'] = $activity_id;    //关联商品表的ID
        $order['goods_price'] = $price;    //商家价格
        $order['totalprice'] = $price;     //订单总价格
        /*$order['goods_price'] = 0.01;    //商家价格
        $order['totalprice'] = 0.01;     //订单总价格*/

        //消息提醒
        //$this->createTips($user_id, '7');

        $result = $orderModel->save($order);
        $row['order_id'] = $orderModel->getLastInsID();
        $row['order_num'] = $order['trade_sn'];
        $row['pay_type'] = config('pay_type.unpaid');
        $resultt = $activityFormModel->insert($row);
        //20200831改为下单就减库存
        $number = intval($row['number']) * intval($row['format']);
        $activityModel->where('id', $order['goods_id'])->setDec('number', $number);
        if ($result === false || $resultt == 0 || !$activityModel) {
            //事务回滚
            $activityFormModel->rollback();
            $orderModel->rollback();
            $activityModel->rollback();
            return false;
        }

        //事务提交
        $activityFormModel->commit();
        $orderModel->commit();
        $activityModel->commit();

        return ['trade_sn'=>$order['trade_sn'], 'title'=>$order['title'], 'price'=>$order['totalprice']];
    }

}