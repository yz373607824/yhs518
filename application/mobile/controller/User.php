<?php

namespace app\mobile\controller;

use app\admin\model\cms\Collect;
use app\admin\model\expert\Online;
use app\admin\model\Order;
use app\admin\model\Order as OrderModel;
use app\admin\model\UserEnterprise;
use app\common\controller\Frontend;
use app\common\library\Sms as Smslib;
use app\common\library\Sms;
use think\Config;
use think\Cookie;
use think\Hook;
use think\Validate;
use think\Loader;
use app\admin\model\expert\Online as OnlineModel;
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
            $this->error(__('Please complete the engineer audit certification first'), url('/mobile/user/expertApplication'));
        } elseif (in_array($action, $this->personal_fun) && !$this->auth->verify_id) {
            $this->error(__('Please complete the certification of your personal identity card first'), url('/mobile/user/idAuthentication'));
        } elseif (in_array($action, $this->enterprise_fun) && !$this->auth->enterprise_id) {
            $this->error(__('Please apply for membership first'), '/mobile/archives/157.html');
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
     * 支付
     */
    public function pay() {
        $trade_sn = $this->request->param("trade_sn");

        $order = \app\admin\model\Order::get(['trade_sn' => $trade_sn]);

        if (empty($order)) {
            $this->error(__('Parameter error'), null);
        }

        if (in_array($order['type'], [1, 2])) {
            $this->view->assign('is_archives', false);
        } else {
            $this->view->assign('is_archives', true);
        }

        $this->view->assign('seo_title', '支付信息');
        $this->view->assign('seo_keywords', '支付信息');
        $this->view->assign('seo_description', '支付信息');
        $this->view->assign('trade_sn', $order['trade_sn']);
        $this->view->assign('url', url('/mobile/archives/175'));
        $this->view->assign('title', $order['title']);
        $this->view->assign('price', $order['totalprice']);
        $template = preg_replace('/\.html$/', '', 'pay.html');
        return $this->view->fetch('public/' . $template);
    }

    /**
     * 消息提醒
     */
    public function my_tips()
    {
        if ($this->request->isAjax()) {
            return parent::getTips();
        }
        $this->assign('seo_title', '消息提醒');
        return $this->view->fetch('my_tips');
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

        $this->assign('seo_title', __('User center'));
        return $this->view->fetch('member_center');
    }

    /**
     * 注册会员
     */
    public function register()
    {
        $tokenName = 'register_token';
        $url = $this->request->request('url');
        if ($this->auth->id)
            $this->redirect(url('/mobile/user/index'));
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
                $this->success(__('Sign up successful') . $synchtml, $url ? $url : url('/mobile/user/index'));
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
            $this->redirect(url('/mobile/user/index'));
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
                    $this->result(['url' => url('/mobile/user/index')], 1);
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
        $this->redirect(url('/mobile'));
    }

    /**
     * 个人信息
     */
    public function profile()
    {
        $this->view->assign('seo_title', __('Profile'));
        return $this->view->fetch('edit_personal_data');
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
     * 修改手机
     */
    public function change_mobile()
    {
        $this->view->assign('seo_title', __('Change mobile'));
        return $this->view->fetch('change_number');
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
            $address = $this->request->post('address');
            $token = $this->request->post($tokenName);

            $arr = explode(',', $address);
            $index = ['province', 'city', 'county'];
            for ($i = 0; $i < count($arr); $i++) {
                $row[$index[$i]] = $arr[$i];
            }

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

                $this->success(__('Authentication tips'), url('/mobile/user/index'));
            } else {
                //新增
                $row['user_id'] = $this->auth->id;
                $result = model('app\admin\model\user\Expert')->allowField(true)->save($row);

                if ($result) {
                    $this->success(__('Authentication tips'), url('/mobile/user/index'));
                } else {
                    $this->error(__('Failure to submit'), null, ['token' => $this->request->token($tokenName)]);
                }
            }
        }

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
                $this->success(__('Submitted successfully'), url('/mobile/user/index'));
            } else {
                $this->error(__('Failure to submit'), null, ['token' => $this->request->token($tokenName)]);
            }
        }

        $this->view->assign('verify', $verify);
        $this->view->assign('seo_title', '身份认证');
        return $this->view->fetch();
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

        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $time = date('Y-m-d', $item['createtime']);
                $file = !empty($item['file']) ? "<a href=\"{$item['file']}\" class=\"download\" target=\"_blank\">下载附件</a>" : '';
                $button = '';
                if ($item['is_reply'] == 1) {
                    $button .= "<a href=\"". url('/mobile/user/replyService', ['id' => $item['id']]) ."\" class=\"btn\">查看回复</a>";
                } elseif ($item['is_rollback'] == 0 || $item['is_commit'] == 1) {
                    $button .= "<a href=\"" . url('/mobile/user/replyService', ['id' => $item['id']]) ."\" class=\"btn\">回复留言</a>";
                }

                if ($item.is_reply == 0 && $item.is_commit == 0 && $item.is_rollback == 0) {
                    $button .= "<a href=\"" . url('/mobile/user/rollbackService', ['id' => $item['id']]) . "\" class=\"btn\">驳回重写</a>";
                } elseif ($item['is_reply'] == 0 && $item['is_commit'] == 0 && $item['is_rollback'] == 1) {
                    $button .= "<a href=\"javascript:;\" class=\"btn\">已驳回</a>";
                }

                if (empty($item.appraise) && $item.confirm_finish == 1) {
                    $button .= "<a href=\"javascript:;\" class=\"btn\">待评价</a>";
                } elseif (empty($item['replyappraise']) && $item['confirm_finish'] == 1) {
                    $button .= "<a href=\"" . url('/mobile/user/replyAppraise', ['id' => $item['orderid']]) ."\" class=\"btn\">查看并评价</a>";
                } elseif (!empty($item['replyappraise']) && $item['confirm_finish'] == 1) {
                    $button .= "<a href=\"" . url('/mobile/user/replyAppraise', ['id' => $item['orderid']]) ."\" class=\"btn\">查看评价</a>";
                }

                $html .= <<<HTML
                <li class="list">
                    <div class="hd">
                        <div class="top">
                            <div class="title">{$item['title']}</div>
                            <div class="top-btn">
                                <span class="date">时间：{$time}</span>
                                {$file}
                            </div>
                        </div>
                        <p class="center">{$item['question_description']}</p>
                    </div>
                    <div class="bd"><span class="word">提问者：{$item['user']}</span></div>
                    <div class="ft">
                        {$button}
                    </div>
                </li>
HTML;
            }
            $this->result($html);
        }

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

        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $time = date('Y-m-d', $item['createtime']);
                $file = !empty($item.file) ? "<a href='{$item['file']}' class='download' target='_blank'>下载附件</a>" : '';
                if(empty($item.expert_id)) {
                    $button = "<a href=\"javascript:;\" onclick=\"confirmServer({$item['id']})\" class=\"btn\">抢单</a>";
                } elseif ($item.expert_id == $this->auth->expert_id) {
                    $button = "<a href=\"". url('/mobile/user/onlineService') ."\" class=\"btn\">已抢单</a>";
                } else {
                    $button = "<a href=\"javascript:;\" class=\"btn\">已抢单</a>";
                }
                $html .= <<<HTML
                <li class="list">
                    <div class="hd">
                        <div class="top">
                            <div class="title">{$item['title']}<span style="color: #db3733;">(￥{$item['totalprice']})</span></div>
                            <div class="top-btn">
                                <span class="date">时间：{$time}</span>
                                {$file}
                            </div>
                        </div>
                        <p class="center">{$item['question_description']}</p>
                    </div>
                    <div class="ft">
                        {$button}
                    </div>
                </li>
HTML;
            }
            $this->result($html);
        }

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

        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $time = date('Y-m-d', $item['createtime']);
                $file = !empty($item['file']) ? "<a href=\"{$item['file']}\" class=\"download\" target=\"_blank\">下载附件</a>" : '';
                $ytime = date('Y-m-d H:i:s', $item['reservation_time']);
                $button = '';
                if ($item['is_confirm'] == '1') {
                    $button .= '<a href="javascript:;" class="btn">已确认</a>';
                } else {
                    $button .= "<a href=\"javascript:;\" onclick=\"confirmServer({$item['id']})\" class=\"btn\">待确定</a>";
                }
                if (empty($item['appraise'])) {
                    $button .= '<a href="javascript:;" class="btn">待评价</a>';
                } elseif (empty($item['replyappraise'])) {
                    $button .= '<a href="'. url('/mobile/user/replyAppraise', ['id' => $item['orderid']]) . '" class="btn">查看并评价</a>';
                } else {
                    $button .= '<a href="' . url('/mobile/user/replyAppraise', ['id' => $item['orderid']]) . '" class="btn">查看评价</a>';
                }

                $html .= <<<HTML
                <li class="list">
                    <div class="hd">
                        <div class="top">
                            <div class="title">{$item['title']}</div>
                            <div class="top-btn">
                                <span class="date">时间：{$time}</span>
                                {$file}
                            </div>
                        </div>
                        <p class="center">{$item['question_description']}</p>
                    </div>
                    <div class="bd"><span class="word">提问者：{$item['user']}</span></div>
                    <div class="bd"><span class="word">姓名：{$item['linkman']}</span></div>
                    <div class="bd"><span class="word">手机：{$item['mobile']}</span></div>
                    <div class="bd"><span class="word">公司：{$item['enterprise']}</span></div>
                    <div class="bd"><span class="word">预约服务时间：{$ytime}</span></div>
                    <div class="bd"><span class="word">地址：{$item['reservation_address']}</span></div>
                    <div class="ft">
                        {$button}
                    </div>
                </li>
HTML;
            }
        }

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
        if ($this->request->isPost()) {
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

        $id = $this->request->param('id');
        $online = Online::get($id);
        if ($online['is_rollback'] == 1 && $online['is_commit'] == 0) {
            $this->error(__('状态异常，不能回复'), null, ['token' => $this->request->token()]);
        }
        $this->assign('online', $online);
        $this->assign('seo_title', empty($online['replycontent']) ? '回复留言' : '查看留言');

        return $this->view->fetch('reply');

    }

    /**
     * 专家驳回在线服务
     */
    public function rollbackService()
    {
        if ($this->request->isPost()) {
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

        $id = $this->request->param('id');
        $online = Online::get($id);
        if ($online['is_reply'] != 0 || $online['is_commit'] != 0 || $online['is_rollback'] != 0) {
            $this->error(__('状态异常，不能驳回'), null, ['token' => $this->request->token()]);
        }
        $this->assign('online', $online);
        $this->assign('seo_title', '驳回重写');

        return $this->view->fetch('rollback');
    }

    /**
     * 专家回复评价
     */
    public function replyAppraise()
    {
        if ($this->request->isPost()) {
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

        $id = $this->request->param('id');
        $order = Order::get($id);
        if (!$order || $order['confirm_finish'] == 0 || empty($order['appraise'])) {
            $this->error(__('状态异常，不能回复'), null, ['token' => $this->request->token()]);
        }
        $this->assign('order', $order);
        $this->assign('flag', 'expert');
        $this->assign('seo_title', '回复评价');

        return $this->view->fetch('comment');

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

        //异步传输数据
        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $time = date('Y-m-d H:i', $item['ordercreatetime']);
                $url = url('/mobile/archives/' . $item['id']);

                $pay = '';
                if ($item['paystatus'] == 0 && time() < strtotime('+1 hour', $item['ordercreatetime'])) {
                    $pay = '<a href="' . url('/mobile/user/pay', ['trade_sn' => $item['ordertrade_sn']]) .'" style="width: 150px;" class="btn">剩余' . ceil((strtotime('+1 hour', $item['ordercreatetime']) - time())/60) .'分钟，请尽快支付</a>';
                } elseif ($item['paystatus'] == 2 || ($item['paystatus'] == 0 && time() > strtotime('+1 hour', $item['ordercreatetime']))) {
                    $pay = '<a href="javascript:;" class="btn" style="background-color: #909090;">已过期</a>';
                }
                $html .= <<<HTML
                <li>
                    <div class="hd">
                        <a href="{$url}"><h3 class="title">{$item['title']}</h3></a>
                        <p class="info">{$item['description']}</p>
                    </div>
                    <div class="ft">
                        <p class="time">{$time}</p>
                        <a href="javascript:;" onclick="cancel({$item['orderid']});" class="btn">删除</a>
                        {$pay}
                    </div>
                </li>
HTML;
            }
            $this->result($html);
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

        //异步传输数据
        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $time = date('Y-m-d H:i', $item['ordercreatetime']);
                $url = url('/mobile/archives/' . $item['id']);

                $pay = '';
                if ($item['paystatus'] == 0 && time() < strtotime('+1 hour', $item['ordercreatetime'])) {
                    $pay = '<a href="' . url('/mobile/user/pay', ['trade_sn' => $item['ordertrade_sn']]) .'" style="width: 150px;" class="btn">剩余' . ceil((strtotime('+1 hour', $item['ordercreatetime']) - time())/60) .'分钟，请尽快支付</a>';
                } elseif ($item['paystatus'] == 2 || ($item['paystatus'] == 0 && time() > strtotime('+1 hour', $item['ordercreatetime']))) {
                    $pay = '<a href="javascript:;" class="btn" style="background-color: #909090;">已过期</a>';
                }
                $html .= <<<HTML
                <li>
                    <div class="hd">
                        <a href="{$url}"><h3 class="title">{$item['title']}</h3></a>
                        <p class="info">{$item['description']}</p>
                    </div>
                    <div class="ft">
                        <p class="time">{$time}</p>
                        <a href="javascript:;" onclick="cancel({$item['orderid']});" class="btn">删除</a>
                        {$pay}
                    </div>
                </li>
HTML;
            }
            $this->result($html);
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

        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $file = !empty($item.online_file) ? " <a href=\"{$item['online_file']}\" class=\"icon-box\"><div class=\"img\"><img src=\"/assets/static_mobile/images/download_icon.png\" alt=\"\"></div></a>" : '';
                $expert = unserialize($item['online_expert_info']);
                $url = url('/mobile/expert/detail', ['id' => $expert['id']]);
                if (empty($item['shop_id'])) {
                    $span = '<span class="name">等待专家接单</span>';
                } else {
                    $span = "<span class=\"name\">{$expert['nickname']}</span><a href=\"{$url}\" class=\"again\">再次咨询</a>";
                }
                $button = '';
                if ($item['confirm_finish'] == 1 && empty($item['appraise'])) {
                    $button .= "<a href=\"" . url('/mobile/user/appraise', ['id' => $item['id']]) ."\" class=\"btn\">我要评价</a>";
                } elseif ($item['confirm_finish'] == 1 && !empty($item['appraise'])) {
                    $button .= "<a href=\"". url('/mobile/user/appraise', ['id' => $item['id']]) ."\" class=\"btn\">查看评价</a>";
                }

                if ($item['status'] == 1 && $item['confirm_finish'] == 0 && $item['online_is_reply'] == 1) {
                    $button .= "<a href=\"javascript:confirmServer({$item['id']});\" style=\"width: 100px;\" class=\"btn\">确定完成服务</a>";
                } elseif ($item['status'] == 1 && $item['confirm_finish'] == 1 && $item['online_is_reply'] == 1) {
                    $button .= "<a href=\"javascript:;\" style=\"width: 100px;\" class=\"btn\">已确认完成服务</a>";
                }

                if ($item['status'] == 1 && $item['online_is_reply'] == 1) {
                    $button .= "<a href=\"". url('/mobile/user/viewReply', ['id' => $item['online_id']]) ."\" class=\"btn\">查看回复</a>";
                } elseif ($item['status'] == 1 && ($item['online_is_rollback'] != 1 || $item['online_is_commit'] == 1)) {
                    $button .= "<a href=\"javascript:;\" class=\"btn\">待回复</a>";
                }

                if ($item.online_is_rollback == 1 && $item.online_is_commit == 0) {
                    $button .= "<a href=\"". url('/mobile/user/resumitOnline', ['id' => $item['online_id']]) ."\" class=\"btn\">重写提交</a>";
                }

                if ($item.status == 0 && time() < strtotime('+1 hour', $item.createtime)) {
                    $button .= "<a href=\"" . url('/mobile/user/pay', ['trade_sn' => $item['trade_sn']]) . "\" style=\"width: 150px;\" class=\"btn\">剩余" . ceil((strtotime('+1 hour', $item['createtime']) - time())/60) ."分钟，请尽快支付</a>";
                }

                if ($item.status == 2 || ($item.status == 0 && time() > strtotime('+1 hour', $item.createtime))) {
                    $button .= "<a href=\"javascript:;\" class=\"btn\">已过期</a>";
                }

                $html .= <<<HTML
                <li class="list">
                    <div class="p icon-btn">
                        {$file}
                        <a href="javascript:;" onclick="cancel({$item['id']});" class="icon-box">
                            <div class="img"><img src="/assets/static_mobile/images/delete_icon.png" alt=""></div>
                        </a>
                    </div>
                    <div class="p"><span class="title">专家姓名：</span>
                        {$span}
                    </div>
                    <div class="p"><span class="title">标题：</span><span class="name">{$item['online_title']}</span></div>
                    <div class="p">
                        <span class="title">问题描述：</span>
                        <p class="info">{$item['online_question_description']}</p>
                    </div>
                    <div class="btn-box">
                        {$button}
                        <a href="https://wpa.qq.com/msgrd?v=3&uin=779088800&site=qq&menu=yes" class="btn">联系客服</a>
                    </div>
                </li>
HTML;
            }
            $this->result($html);
        }

        $this->assign('list', $list);
        $this->assign('seo_title', '我的咨询');
        return $this->view->fetch();
    }

    /**
     * 查看回复
     */
    public function viewReply() {
        $id = $this->request->param('id');
        $online = Online::get($id);
        $this->assign('online', $online);
        $this->assign('seo_title', '查看回复');
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

        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $file = !empty($item['locale_file']) ? "<a href=\"{$item['locale_file']}\" class=\"icon-box\"><div class=\"img\"><img src=\"/assets/static_mobile/images/download_icon.png\" alt=\"\"></div></a>" : '';
                $expert = unserialize($item['locale_expert_info']);
                $url = url('/mobile/expert/detail', ['id' => $expert['id']]);
                $time = date('Y-m-d', $item['locale_reservation_time']);
                $button = '';
                if ($item['confirm_finish'] == 1 && empty($item['appraise'])) {
                    $button .= '<a href="'. url('/mobile/user/appraise', ['id' => $item['id']]) .'" class="btn">我要评价</a>';
                } elseif ($item['confirm_finish'] == 1 && !empty($item['appraise'])) {
                    $button .= '<a href="'.url('/mobile/user/appraise', ['id' => $item['id']]).'" class="btn">查看评价</a>';
                }
                if ($item['locale_is_confirm'] == 1 && $item['confirm_finish'] == 0) {
                    $button .= '<a href="javascript:confirmServer({$item.id});" style="width: 120px;" class="btn">确定完成此次服务</a>';
                } elseif ($item['locale_is_confirm'] == 1 && $item['confirm_finish'] == 1) {
                    $button .=  '<a href="javascript:;" style="width: 120px;" class="btn">已确认完成此次服务</a>';
                }
                if ($item['status'] == 1 && $item['locale_is_confirm'] == 0) {
                    $button .= '<a href="javascript:;" class="btn">待专家服务</a>';
                }
                if ($item['status'] == 0 && time() < strtotime('+1 hour', $item['createtime'])) {
                    $button .= '<a href="' . url('/mobile/user/pay', ['trade_sn' => $item['trade_sn']]) . '" style="width: 150px;" class="btn">剩余' . ceil((strtotime('+1 hour', $item['createtime']) - time())/60) .'分钟，请尽快支付</a>';
                }
                if ($item['status'] == 2 || ($item['status'] == 0 && time() > strtotime('+1 hour', $item['createtime']))) {
                    $button .= '<a href="javascript:;" class="btn">已过期</a>';
                }

                $html .= <<<HTML
                <li class="list">
                    <div class="p icon-btn">
                        {$file}
                        <a href="javascript:;" onclick="cancel({$item['id']});" class="icon-box">
                            <div class="img"><img src="/assets/static_mobile/images/delete_icon.png" alt=""></div>
                        </a>
                    </div>
                    <div class="p"><span class="title">专家姓名：</span>
                        <span class="name">{$expert['nickname']}</span>
                        <a href="{$url}" class="again">再次咨询</a>
                    </div>
                    <div class="p"><span class="title">预约时间：</span><span class="name">{$time}</span></div>
                    <div class="p"><span class="title">预约地点：</span><span class="name">{$item['locale_reservation_address']}</span></div>
                    <div class="p"><span class="title">标题：</span><span class="name">{$item['locale_title']}</span></div>
                    <div class="p">
                        <span class="title">问题描述：</span>
                        <p class="info">{$item['locale_question_description']}</p>
                    </div>
                    <div class="p">
                        <span class="ft-txt">姓 名：{$item['locale_linkman']}</span>
                        <span class="ft-txt">手 机：{$item['locale_mobile']}</span>
                        <span class="ft-txt">公 司：{$item['locale_enterprise']}</span>
                    </div>
                    <div class="btn-box">
                        {$button}
                        <a href="https://wpa.qq.com/msgrd?v=3&uin=779088800&site=qq&menu=yes" class="btn">联系客服</a>
                    </div>
                </li>
HTML;
            }
            $this->result($html);
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
        if ($this->request->isPost()) {
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

        $id = $this->request->param('id');
        $online = Online::get($id);

        if (!$online || $online['is_rollback'] == 0 || $online['is_commit'] == 1) {
            $this->error(__('状态异常，不能提交'), null, ['token' => $this->request->token()]);
        }

        $this->assign('online', $online);
        $this->assign('seo_title', '重复提交');

        return $this->view->fetch('rewrite');
    }

    /**
     * 用户评价
     */
    public function appraise()
    {
        if ($this->request->isPost()) {
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

        $id = $this->request->param('id');
        $order = Order::get($id);
        if (!$order || $order['confirm_finish'] == 0) {
            $this->error(__('订单没有完成，暂时不能评价'), null, ['token' => $this->request->token()]);
        }
        $this->assign('order', $order);
        $this->assign('flag', 'customer');
        $this->assign('seo_title', '我要评价');

        return $this->view->fetch('comment');
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

        //异步传输数据
        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $time = date('Y-m-d H:i', $item['createtime']);
                $url = url('/mobile/archives/' . $item['aid']);
                $html .= <<<HTML
                <li class="list">
                    <a href="{$url}"><div class="title">{$item['title']}</div></a>
                    <div class="msg">
                        <div class="date">{$time}</div>
                        <a href="javascript:cancel({$item['id']});" class="btn">取消收藏</a>
                    </div>
                </li>
HTML;
            }
            $this->result($html);
        }

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
            $this->error(__('Parameter error'), null);
        }

        $order = OrderModel::get($id);
        if (empty($order)) {
            $this->error(__('The current order does not exist'), null);
        }

        //防止越权操作
        if ($order['user_id'] != $this->auth->id) {
            $this->error(__('Unauthorized operation, attention please'), null);
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
                $this->error(__('Delete failed'), null);
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
                    $this->error(__('Delete failed'), null);
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

        //异步传输数据
        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $time = date('Y-m-d H:i', $item['createtime']);
                $url = url('/mobile/user/takeCash/', ['id' => $item['id']]);
                $status = $item['status'] == '1' ? '已处理' : '处理中';
                $html .= <<<HTML
                <li class="list">
                <div class="p">
                    <div class="tt">提现金额：</div>
                    <div class="info">¥ <span class="red">{$item['amount']}</span></div>
                </div>
                <div class="p">
                    <div class="tt">提交时间：</div>
                    <div class="info">{$time}</div>
                </div>
                <div class="p">
                    <div class="tt">当前状态：{$status}</div>
                    <div class="info"></div>
                </div>
                <div class="btn-box">
                    <a href="{$url}" class="more-btn">查看详细>></a>
                </div>
            </li>
HTML;
            }
            $this->result($html);
        }

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
//        if ($day < 10 || $day > 15) {
//            $this->error(__('Withdrawals are only available from the 10th to 15th of each month'));
//        }

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

        $this->success(__('Submitted successfully'), url('/mobile/user/takeCashList'));
    }

    /**
     * 我的活动订单
     */
    public function activity_order()
    {
        $this->deleteTips(7);
        $list = OrderModel::alias('a')
            ->where('a.user_id', $this->auth->id)
            ->where('a.type', '1')
            ->where('a.is_delete', 0)
            ->join('cms_activity_form' . ' n', 'a.id=n.order_id', 'LEFT')
            ->field('a.*')
            ->field(true, false, config('database.prefix') . 'cms_activity_form', 'n', 'activity_form')
            ->order('n.updatetime', 'desc')
            ->paginate(config('page_size.expert'));

        foreach ($list as $item) {
            //判断订单是否过期
            if (time() > strtotime('+1 hour', $item['createtime']) && $item['status'] == '0' && $item['paytype'] != 5) {
                $orderModel = db('order');
                $activityFormModel = db('cms_activity_form');

                $orderModel->startTrans();
                $activityFormModel->startTrans();

                $updateOrderStatus = $orderModel->where('id', $item['id'])->update(['status' => '2']);
                $updateActivityFormStatus = $activityFormModel->where('order_id', $item['id'])->update(['pay_type' => config('pay_type.expired')]);
                if ($updateOrderStatus == 0 || $updateActivityFormStatus == 0) {
                    $orderModel->rollback();
                    $activityFormModel->rollback();
                    return false;
                }
                $orderModel->commit();
                $activityFormModel->commit();
                $item['status'] = '2';
            }
        }

        //异步传输数据
        if ($this->request->isAjax()) {
            $html = '';
            foreach ($list as $item) {
                $time = date('Y-m-d H:i', $item['createtime']);
                $url = url('/mobile/archives/' . $item['id']);

                $pay = '';
                if ($item['paytype'] != 5){
                    if ($item['status'] == 0 && time() < strtotime('+1 hour', $item['createtime'])) {
                        $pay = '<a href="' . url('/mobile/user/pay', ['trade_sn' => $item['trade_sn']]) .'" style="width: 150px;" class="btn">剩余' . ceil((strtotime('+1 hour', $item['createtime']) - time())/60) .'分钟，请尽快支付</a>';
                    } elseif ($item['status'] == 2 || ($item['status'] == 0 && time() > strtotime('+1 hour', $item['createtime']))) {
                        $pay = '<a href="javascript:;" class="btn" style="background-color: #909090;">已过期</a>';
                    }
                }
                $html .= <<<HTML
                <li>
                    <div class="hd">
                        <a href="{$url}"><h3 class="title">{$item['title']}</h3></a>
                        <p class="info">{$item['description']}</p>
                    </div>
                    <div class="ft">
                        <p class="time">{$time}</p>
                        <a href="javascript:;" onclick="cancel({$item['activity_formid']});" class="btn">删除</a>
                        {$pay}
                    </div>
                </li>
HTML;
            }
            $this->result($html);
        }

        $this->assign('list', $list);
        $this->assign('seo_title', '我的活动');
        return $this->view->fetch('message_list');
    }
}
