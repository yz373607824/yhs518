<?php
namespace app\common\controller;
use addons\epay\library\Service;
use app\admin\model\Order as OrderModel;
use app\admin\model\Tips;
use app\common\library\Auth;
use think\Config;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Hook;
use think\Lang;
use think\Session;
use Yansongda\Pay\Pay;
/**
 * 前台控制器基类
 */
class Frontend extends Controller
{
    /**
     * 布局模板
     * @var string
     */
    protected $layout = '';
    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];
    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];
    /**
     * 权限Auth
     * @var Auth
     */
    protected $auth = null;
    public function _initialize()
    {
        //移除HTML标签
//        $this->request->filter('strip_tags');
        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());
        // 如果有使用模板布局
        if ($this->layout) {
            $this->view->engine->layout('layout/' . $this->layout);
        }
        $this->auth = Auth::instance();
        // token
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\Cookie::get('token')));
        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        //检测当前微信用户是否绑定微信，如果有绑定自动登录
        $this->auth->init($token);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin) && $modulename != 'admin') {
            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin()) {
//                $this->error(__('Please login first'), 'user/login');
                if ($modulename == 'mobile') {
                    if ($this->request->isAjax()) {
                        $this->error(__('请先登录'), url('/user/login'));
                    } else {
                        isWechat() ? $this->openidLogin('https://www.yhs518.com' . $this->request->url()) : $this->redirect(url('/mobile/user/login'));
                    }
                } else {
                    if ($this->request->isAjax()) {
                        $this->error(__('请先登录'), url('/user/login'));
                    } else {
                        $this->redirect(url('/user/login'));
                    }
                }
            } else {
                //判断在线状态
                if ($this->auth->online == 0) {
                    //让会员退出
                    $this->auth->logout();
                    if ($modulename == 'mobile') {
                        isWechat() ? $this->openidLogin('https://www.yhs518.com' . $this->request->url()) : $this->redirect(url('/mobile/user/login'));
                    } else {
                        $this->redirect(url('/user/login'));
                    }
                }
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    $this->error(__('You have no permission'));
                }
            }
        } else {
            // 如果有传递token才验证是否登录状态
            if ($token) {
                $this->auth->init($token);
            }
        }
        $this->view->assign('user', $this->auth->getUser());
        // 语言检测
        $lang = strip_tags($this->request->langset());
        $site = Config::get("site");
        $upload = \app\common\model\Config::upload();
        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);
        // 配置信息
        $config = [
            'site'           => array_intersect_key($site, array_flip(['name', 'cdnurl', 'version', 'timezone', 'languages'])),
            'upload'         => $upload,
            'modulename'     => $modulename,
            'controllername' => $controllername,
            'actionname'     => $actionname,
            'jsname'         => 'frontend/' . str_replace('.', '/', $controllername),
            'moduleurl'      => rtrim(url("/{$modulename}", '', false), '/'),
            'language'       => $lang
        ];
        $config = array_merge($config, Config::get("view_replace_str"));
        Config::set('upload', array_merge(Config::get('upload'), $upload));
        // 配置信息后
        Hook::listen("config_init", $config);
        // 加载当前控制器语言包
        $this->loadlang($controllername);
        $this->assign('seo_title', $site['seo_title']);
        $this->assign('seo_keywords', $site['seo_keywords']);
        $this->assign('seo_description', $site['seo_description']);
        $this->assign('site', $site);
        $this->assign('config', $config);
        $this->view->engine->config('taglib_pre_load', 'addons\cms\taglib\Cms');
    }
    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . $this->request->langset() . '/' . str_replace('.', '/', $name) . '.php');
    }
    /**
     * 渲染配置信息
     * @param mixed $name 键名或数组
     * @param mixed $value 值
     */
    protected function assignconfig($name, $value = '')
    {
        $this->view->config = array_merge($this->view->config ? $this->view->config : [], is_array($name) ? $name : [$name => $value]);
    }

    protected function openidLogin($wechat_redirect_url){
        $wechat_app_id = 'wx07216ea7473601e7';
        $wechat_app_secret = '143e1842dcabc139a6404436b52ea94c';

        $openid = Session::get('login_openid');
        if (!$openid) {
            if (!isset($_GET['code'])) {
                $redirect_url = urlencode($wechat_redirect_url);
                $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$wechat_app_id}&redirect_uri={$redirect_url}&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
                Header("Location: $url");
                exit();
            } else {
                //获取code码，以获取openid
                $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$wechat_app_id}&secret={$wechat_app_secret}&code={$_GET['code']}&grant_type=authorization_code";

                //初始化curl
                $ch = curl_init();
                //设置超时
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                //运行curl，结果以jason形式返回
                $res = curl_exec($ch);
                curl_close($ch);

                //取出openid
                $data = json_decode($res,true);

                if(isset($data['openid'])) {
                    $openid = $data['openid'];
                    Session::set('login_openid', $openid);
                }
            }
        }

        $user = \app\admin\model\User::get(['openid' => $openid]);
        if ($user) {
            //登录
            $this->auth->direct($user['id']);
            $expire = input('post.keeplogin') ? 30 * 86400 : 0;
            Cookie::set('uid', $user->id, $expire);
            Cookie::set('token', $this->auth->getToken(), $expire);
            $counter = intval(file_get_contents(ROOT_PATH . "counter.dat"));
            $counter++;
            $fp = fopen(ROOT_PATH . "counter.dat", "w");
            fwrite($fp, $counter);
            fclose($fp);
            $this->redirect($wechat_redirect_url);
        } else {
            $this->redirect(url('/mobile/user/login'));
        }
        exit();
    }


    /**
     * 创建一个新的提醒
     * @param mixed $user_id 提醒的用户
     * @param mixed $type 提醒类型
     */
    public function createTips($user_id, $type)
    {
        $data = ['user_id'=>$user_id, 'type'=>$type];
        $tips = Tips::get($data);

        //存在则增加信息数量，否则新增
        if ($tips) {
            $tips->setInc('num');
        } else {
            Tips::create($data);
        }
    }

    /**
     * 获取当前登录用户全部提示信息
     */
    protected function getTips()
    {
        $array = get_tips($this->auth->id);

        if (count($array) > 0) {
            $this->result($array, 1);
        } else {
            $this->error();
        }
    }

    /**
     * 根据提醒类型删除当前登录用户的提示信息
     * @param $type 提醒类型
     */
    protected function deleteTips($type)
    {
        $tips = Tips::get(['user_id'=>$this->auth->id, 'type'=>$type]);
        if ($tips) {
            $tips->delete();
        }
    }

    /**
     * 处理专家/配方/知识库的分类
     */
    protected function disposeCategory($name)
    {
        $industrial_cleaning_agent = config('site.' . $name . '_ica');
        $this->assign('industrial_cleaning_agent', explode('--', $industrial_cleaning_agent));
        $the_surface_film = config('site.' . $name . '_tsf');
        $this->assign('the_surface_film', explode('--', $the_surface_film));
        $post_processing = config('site.' . $name . '_pp');
        $this->assign('post_processing', explode('--', $post_processing));
    }

    /**
     * 发起支付
     */
    protected function _pay($config)
    {
        $trade_sn = $this->request->param('trade_sn', null);
        if (empty($trade_sn)) {
            $this->error(__('参数错误'), null, ['token'=>$this->request->token()]);
        }
        $order = OrderModel::get(['trade_sn'=>$trade_sn]);
        if (empty($order)) {
            $this->error(__('当前订单不存在'), null, ['token'=>$this->request->token()]);
        }
        if ($order['status'] == '1') {
            $this->success(__('当前订单已支付'), null, ['token'=>$this->request->token()]);
        }
        if ($order['status'] == '2' || time() > strtotime('+1 hour', $order['createtime'])) {
            $this->error(__('当前订单已过期'), null, ['token'=>$this->request->token()]);
        }

        //抢购活动(20200831更改为提交订单就减库存所以支付不用验证库存)
        //if ($order['type'] == 5) {
            //获取限购数量
            /*$limitNumber = db('cms_activity')->where('id',$order['goods_id'])->value('limit');
            if ($limitNumber > 0 ) {
                $orderNumber = OrderModel::where([
                    'user_id' => $order['user_id'],
                    'type' => $order['type'],
                    'goods_id' => $order['goods_id'],
                    'status' => 1
                ])
                    ->sum('goods_num');
                if ($orderNumber + $order['goods_num'] > $limitNumber) {
                    $this->error("购买数量超过限购数量！", null, ['token' => $this->request->token()]);
                }
            }*/
            //验证库存
            /*$activityFormData = db('cms_activity_form')->field('id,number,format')
                ->where([
                    'pay_type' => config('pay_type.unpaid'),
                    'order_id' => $order['id']
                ])
                ->find();
            $number = intval($activityFormData['number']) * intval($activityFormData['format']);
            $stock = db('cms_activity')->where('id', $order['goods_id'])->value('number');
            if ($number > $stock) {
                $this->error("库存不足！", null, ['token' => $this->request->token()]);
            }*/
        //}

        $url = '';
        switch ($order['type']) {
            case '1' :
                $url = url('/mobile/user/myAskquestion');
                break;
            case '2' :
                $url = url('/mobile/user/myReservation');
                break;
            case '3' :
                $url = url('/mobile/user/myArticle');
                break;
            case '4' :
                $url = url('/mobile/user/myKonwledge');
                break;
            case '5' :
                $url = url('/mobile/user/activity_order');
                break;
        }

        if ($config == 'alipay') {
            //创建支付对象
            $pay = Pay::alipay(Service::getConfig($config));
            //构建订单信息
            $order = [
                'out_trade_no' => $order['trade_sn'],   //订单号
                'total_amount' => $order['totalprice'], //单位元
                'subject'      => $order['title'],  //标题
            ];

            //跳转或输出
            if ($this->request->isMobile()) {
                return $pay->wap($order)->send();
            } else {
                return $pay->web($order)->send();
            }
        } elseif($config == 'wechat') {
            //创建支付对象
            $pay = Pay::wechat(Service::getConfig($config));
            //构建订单信息
            $order = [
                'out_trade_no' => $order['trade_sn'],   //订单号
                'body'         => $order['title'],  //标题
                'total_fee'    => $order['totalprice'] * 100, //单位分
                'return_url'   => url('/addons/epay/api/returnx/type/wechat?out_trade_no=' . $order['trade_sn'], '', false, true), //返回地址
            ];
            //跳转或输出
            if ($this->request->isMobile()) {
                if (isWechat()) {
                    $wechat_redirect_url = get_addon_config('epay')['wechat']['redirect_url'] . "?trade_sn={$trade_sn}";
                    $order['openid'] = $this->getOpenId($wechat_redirect_url);
                    $jsapi = $pay->mp($order);
                    $jsapi = json_decode($jsapi,TRUE);
                    echo <<<script
<script type="text/javascript">
    function onBridgeReady(){
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest', {
                "appId":"{$jsapi['appId']}",     //公众号名称，由商户传入     
                "timeStamp":"{$jsapi['timeStamp']}",         //时间戳，自1970年以来的秒数     
                "nonceStr":"{$jsapi['nonceStr']}", //随机串     
                "package":"{$jsapi['package']}",     
                "signType":"{$jsapi['signType']}",         //微信签名方式：     
                "paySign":"{$jsapi['paySign']}" //微信签名 
            },
            function(res){     
                if(res.err_msg == "get_brand_wcpay_request:ok" ) {
                    alert("支付成功！");
                    window.location.href = '{$url}';
                }
            }
        ); 
    }

    function callpay(){
        if (typeof window.WeixinJSBridge == "undefined"){
            if( document.addEventListener ){
                document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
            }else if (document.attachEvent){
                document.attachEvent('WeixinJSBridgeReady', onBridgeReady); 
                document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
            }
        }else{
            onBridgeReady();
        }
    }

    callpay();
</script>
script;
                    exit();
                } else {
                    return $pay->wap($order)->send();
                }
            } else {
                return $pay->web($order)->send();
            }
        } elseif($config == 'gold') {
            $this->goldAndPoints($order, 'site.gold_num', 'score', '3', '当前金豆不足，请使用其他支付方式');
        } elseif ($config == 'points') {
            $this->goldAndPoints($order, 'site.points_num', 'points', '4', '当前金币不足，请使用其他支付方式');
        } elseif ($config == 'offlinepay') {
            $this->offlinePay($order);
        }
    }

    /**
     * 获取Openid
     */
    protected function getOpenId($wechat_redirect_url)
    {
        $config = get_addon_config('epay');
        if (!isset($_GET['code'])) {
            $redirect_url = urlencode($wechat_redirect_url);
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$config['wechat']['app_id']}&redirect_uri={$redirect_url}&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$config['wechat']['app_id']}&secret={$config['wechat']['app_secret']}&code={$_GET['code']}&grant_type=authorization_code";

            //初始化curl
            $ch = curl_init();
            //设置超时
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //运行curl，结果以jason形式返回
            $res = curl_exec($ch);
            curl_close($ch);

            //取出openid
            $data = json_decode($res,true);
            $openid = $data['openid'];

            return $openid;
        }
    }

    /**
     * 金币支付和金豆支付
     * @param $order 订单model
     * @param $configName 配置变量名
     * @param $type 字段名称
     * @param $paytype 字符类型
     * @param $msg 提示信息
     */
    private function goldAndPoints($order, $configName, $type, $paytype, $msg)
    {
        if (!in_array($order['type'], [3, 4])) {
            $this->error('订单类型错误', null, ['token'=>$this->request->token()]);
        }

        $amount = bcmul($order['totalprice'], config($configName), 2);
        if ($this->auth->$type >= $amount) {
            Db::startTrans();
            //扣除当前用户的金币或者金豆
            $result = \app\admin\model\User::where('id', $this->auth->id)->setDec($type, $amount);
            if (!$result) {
                Db::rollback();
                $this->error('支付失败', null, ['token'=>$this->request->token()]);
            }

            //改变订单的状态
            $order['paytime'] = time();
            $order['confirm_finish'] = '1';
            $order['paytype'] = $paytype;
            $order['status'] = '1';
            $result = $order->save();
            if (!$result) {
                Db::rollback();
                $this->error('支付失败', null, ['token'=>$this->request->token()]);
            }

            //获取文章
            $table = substr_replace($order['goods_table'],"",0,5);
            $model = db($table);
            $archives = $model->where('id', $order['goods_id'])->find();
            //文章的付费量加一
            $result = $model->where('id', $archives['id'])->setInc('paynum', 1);
            if (!$result) {
                //回滚
                Db::rollback();
                $this->error('支付失败', null, ['token'=>$this->request->token()]);
            }

            //文章作者为空
            if (empty($archives['user_id'])) {
                Db::rollback();
                $this->error('支付失败', null, ['token'=>$this->request->token()]);
            }

            //增加文章作者的金豆
            $num = bcmul(bcmul($order['totalprice'], config('site.gold_num'), 2), (1 - config('site.service_charge')), 2);
            $result = \app\admin\model\User::where('id', $archives['user_id'])->setInc('score', $num);
            if (!$result) {
                Db::rollback();
            }
            //记录收入
            insert_bill(+$num, 'score', $archives['title'], $archives['user_id'], $order['type'], $order['trade_sn']);
            //记录支出
            insert_bill(-$amount, $type, $archives['title'], $this->auth->id, $order['type'], $order['trade_sn']);
            //提交事务
            Db::commit();

            if ($this->request->isMobile()) {
                $url = $order['type'] == 3 ? url('/mobile/user/myArticle') : url('/mobile/user/myKonwledge');
            } else {
                $url = $order['type'] == 3 ? url('/user/myArticle') : url('/user/myKonwledge');
            }
            $this->success('支付成功', $url);
        }else{
            if ($this->request->isMobile()) {
                $url = null;
            } else {
                $url = $order['type'] == 3 ? url('/user/myArticle') : url('/user/myKonwledge');
            }
            $this->error($msg, $url);
        }
    }

    /**
     * 消息推送
     */
    public static function sendTplMsg($openid, $title, $url, $data) {
        if($openid) {
            $params = [
                'touser' => $openid,
                'template_id' => '6kAruCCgL86qoZUxPyFeYGyWAjkiD4a3vxCiapx6EYY',
                'url'    => $url,
                'data'  => array(
                    'first' => array('value' => $title),
                    'keyword1' => array('value' => $data['type']),
                    'keyword2' => array('value' => $data['time']),
                    'remark' => array('value' => $data['title']),
                ),
            ];

            $access_token = self::getAccessToken();

            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token;

            $ch = curl_init();
            // 请求地址
            curl_setopt($ch, CURLOPT_URL, $url);
            // 请求参数类型
            $param = urldecode(json_encode($params));
            // 关闭https验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            // post提交
            if($param){
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            }
            // 返回的数据是否自动显示
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // 执行并接收响应结果
            curl_exec($ch);
            // 关闭curl
            curl_close($ch);
        }
    }

    private static function getAccessToken() {

        $data = json_decode( self::get_php_file(APP_PATH ."common/library/access_token.php"), true);

        if (empty($data) || $data['expire_time'] < time()) {
            $wechat = get_addon_config('epay')['wechat'];
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$wechat['app_id']}&secret={$wechat['app_secret']}";

            //初始化curl
            $ch = curl_init();
            //设置超时
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //运行curl，结果以jason形式返回
            $res = curl_exec($ch);
            curl_close($ch);

            //取出
            $data = json_decode($res,true);

            if ($data['access_token']) {
                $data['expire_time'] = time() + 7000;
                self::set_php_file(APP_PATH ."common/library/access_token.php", json_encode($data));
            }
        }

        return $data['access_token'];
    }

    private static function get_php_file($filename) {
        return trim(substr(file_get_contents($filename), 15));
    }

    private static function set_php_file($filename, $content) {
        $fp = fopen($filename, "w");
        fwrite($fp, "<?php exit();?>" . $content);
        fclose($fp);
    }

    /**
     * 线下支付
     * @param $order 订单model
     */
    private function offlinePay($order) {
        if ($order['type'] != '5') {
            $this->error('订单类型错误', null, ['token' => $this->request->token()]);
        }
        //不扣除库存 只修改支付类型为线下支付
        $orderModel = db('order');
        $orderResult = $orderModel->where('id', $order['id'])->update(['paytype' => 5]);
        $url = $order['type'] == 5 ? url('/user/activity_order') : url('/user/myKonwledge');
        if ($orderResult) {
            $this->success('提交成功', $url);
        }
        $this->error('提交失败',$url);
    }
}