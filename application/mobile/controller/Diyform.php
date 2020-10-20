<?php

namespace app\mobile\controller;

use addons\cms\model\Diyform as DiyformModel;
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
                    $this->error("验证码不能为空！", null, ['token' => $this->request->token()]);
                }

                $result = captcha_check($captcha);
                if( !$result ){
                    $this->error("验证码错误！", null, ['token' => $this->request->token()]);
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

            try {
                \think\Db::name($diyform['table'])->insert($row);
            } catch (Exception $e) {
                $this->error("发生错误:" . $e->getMessage());
            }

            //企业留言提醒
            if ($diyform['id'] == 3) {
                $user_id = db('user_enterprise')->where('id', $row['enterprise_id'])->value('user_id');
                $this->createTips($user_id, '4');
            }
            //抢购活动提醒&库存更新
            if ($diyform['id'] == 5) {
                db('cms_activity')->where('id', input('activity_id'))->setDec('number', $number);

                $user_id = db('user_enterprise')->where('id', $row['enterprise_id'])->value('user_id');
                $this->createTips($user_id, '7');
            }

            $this->success($diyform['successtips'] ? $diyform['successtips'] : '提交成功！', $diyform['redirecturl'] ? url($diyform['redirecturl']) : null);
        }
        return;

    }

}