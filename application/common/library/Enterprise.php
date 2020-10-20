<?php

namespace app\common\library;

class Enterprise
{
    const SUPPLY_MID = 10;  //供应模型id
    const PURCHASE_MID = 8;  //采购模型id
    const RECRUIT_MID = 12;  //招聘模型id
    const HONOR_MID = 16;  //荣誉模型id

    /**
     * 检测企业等级情况
     * @param $user_id
     * @param $level
     */
    public function checkLevel($user_id, $level, $status)
    {
        $cofig = config('enterprise.level_' . $level);

        //状态为首次提交或者拒绝通过时全部下架
        $all = $status < 1 ? true : false;

        //检测供应信息
        $this->checkRelease(self::SUPPLY_MID, $user_id, $cofig['supply'], $all);
        //检测采购信息
        $this->checkRelease(self::PURCHASE_MID, $user_id, $cofig['purchase'], $all);
        //检测招聘信息
        $this->checkRelease(self::RECRUIT_MID, $user_id, $cofig['recruit'], $all);
        //检测资质荣誉
        $this->checkRelease(self::HONOR_MID, $user_id, $cofig['honor'], $all);
    }

    /**
     * 检测企业发布数量
     * @param $mid 模型id
     * @param $user_id 会员id
     * @param $num 允许发布数量
     * @param $all 是否全部下架
     */
    private function checkRelease($mid, $user_id, $num, $all)
    {
        //全部下架
        if ($all === true) {
            $num = 0;
        }
        if ($num !== '*') {
            $where = [
                'model_id' => $mid,
                'user_id' => $user_id,
                'status' => ['neq', 'pulloff'],
                'deletetime' => null
            ];
            $count = db('cms_archives')->where($where)->count();

            //超过数量限制的设置为下架
            if ($count > $num) {
                $ids = db('cms_archives')->where($where)->order('publishtime DESC')->limit($num, $count)->column('id');
                db('cms_archives')->where('id', 'IN', implode(',', $ids))->update(['status' => 'pulloff']);
            }
        }
    }

    /**
     * vip企业到期
     * @param $id
     */
    public function vipExpire($id)
    {
        db('user_enterprise')->where('id', $id)->update(['level' => '1']);
    }
}
