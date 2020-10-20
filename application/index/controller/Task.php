<?php

namespace app\index\controller;

use app\admin\model\Order;
use app\common\controller\Frontend;
use app\common\library\Enterprise;
use think\Db;

/**
 * 计划任务
 */
class Task extends Frontend
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    /**
     * 触发方法
     */
    public function index()
    {
        //罗列所有要执行的任务
        $this->checkEnterprise();
        $this->checkOrder();
        $this->checkPastOrder();
    }

    /**
     * 每天零时触发
     */
    public function zeroTrigger()
    {
        $this->logoutAllUser();
    }

    /**
     * 每周零时触发
     */
    public function weekTrigger() {
        $this->cleanLoginNum();
    }

    /**
     * 退出所有会员
     */
    private function logoutAllUser()
    {
        //会员下线
        db('user')->where('id', 'gt', 0)->update(['expire' => 0, 'online' => '0']);
    }

    /**
     * 将每天的登录人数清零
     */
    private function cleanLoginNum()
    {
        $fp = fopen(ROOT_PATH . "counter.dat","w");
        fwrite($fp, 0);
        fclose($fp);
    }

    /**
     * 检测所有企业的发布数量情况
     */
    private function checkEnterprise()
    {
        $do = new Enterprise();

        $enterprise = db('user_enterprise')->field('id,user_id,level,end_time,status')->select();

        foreach ($enterprise as $v) {
            //判断vip企业是否过期
            if ($v['level'] > 1 && $v['end_time'] < time()) {
                $do->vipExpire($v['id']);
                $v['level'] = 1;
            }

            //检测企业发布文章数量，超出的下架
            $do->checkLevel($v['user_id'], $v['level'], $v['status']);
        }
    }

    /**
     * 检测所用过期的订单，改变订单的状态
     */
    private function checkPastOrder()
    {
        $list = Order::where('status', 0)
            ->where('createtime', '<', strtotime("-1 hour"))
            ->select();

        foreach ($list as $item) {
            if ($item['type'] == 5) {
                db('cms_activity_form')->where('order_id',$item['id'])->update(['pay_type' => config('pay_type.expired')]);
            }
            $item->status = '2';
            $item->save();
        }
    }


    /**
     * 检测所有订单的状态，过期的自动确认结账
     */
    private function checkOrder()
    {
        $list = Order::where('status', 1)
            ->where('confirm_finish', 0)
            ->whereIn('type', '1,2')
            ->where('paytime', '<', strtotime("-7 day"))
            ->select();

        foreach ($list as $item) {
            Db::startTrans();

            $row['confirm_finish'] = '1';
            $result = $item->allowField(true)->save($row);
            if (!$result) {
                Db::rollback();
                $this->error(__('Processing error, please try again'));
            }

            if ($item['totalprice'] > 0) {
                $expertUserId = \app\admin\model\user\Expert::where('id', $item['shop_id'])->value('user_id');
                $num = bcmul(bcmul($item['totalprice'], config('site.gold_num'), 2), (1 - config('site.service_charge')), 2);
                $result = \app\admin\model\User::where('id', $expertUserId)->setInc('score', $num);
                if (!$result) {
                    Db::rollback();
                    $this->error(__('Processing error, please try again'));
                }
                //记录收入
                insert_bill(+$num, 'score', $item['title'], $expertUserId, $item['type'], $item['trade_sn']);
            }

            Db::commit();
        }
    }
}
