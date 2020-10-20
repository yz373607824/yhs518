<?php
/**
 * 自定义处理系统逻辑函数文件
 */

use think\Lang;

if (!function_exists('insert_bill')) {
    /**
     * 记录账单信息
     * @param mixed $num 数量
     * @param mixed $type 类型
     * @param mixed $detail 明细
     * @param mixed $userId 用户ID
     * @param mixed $orderType 订单类型
     * @param mixed $orderNo 订单号
     */
    function insert_bill($num, $type, $detail, $userId, $orderType, $orderNo) {
         //创建模型对象
         $bill = model('\app\admin\model\user\Bill');
         //组装数据
         $data = ['num' => $num, 'type' => $type, 'detail' => $detail, 'user_id' => $userId, 'order_type' => $orderType, 'order_no' => $orderNo];
         //保存数据
         $bill->save($data);
    }
}

if (!function_exists('createTips')) {
    /**
     * 创建一个新的提醒
     */
    function createTips($user_id, $type)
    {
        $data = ['user_id'=>$user_id, 'type'=>$type];
        $tips = \app\admin\model\Tips::get($data);

        //存在则增加信息数量，否则新增
        if ($tips) {
            $tips->setInc('num');
        } else {
            \app\admin\model\Tips::create($data);
        }
    }
}

if (!function_exists('get_msg_num')) {
    /**
     * 获取会员消息总数
     */
    function get_msg_num($userId)
    {
        if (!$userId) {
            return 0;
        }

        $array = get_tips($userId);

        return count($array);
    }
}

if (!function_exists('get_tips')) {
    /**
     * 获取当前登录用户全部提示信息
     */
    function get_tips($userId)
    {
        $list = \app\admin\model\Tips::all(['user_id' => $userId]);

        //判断当前用户是否是专家
        if ($userId) {
            //判断专家服务时间是否过期
            $flag = session($userId . '_expert_time');
            if ($flag === NULL) {
                $expert = \app\admin\model\user\Expert::get(['user_id' => $userId]);
                if ($expert['deadline_starttime'] < time() && $expert['deadline_endtime'] > time() && $expert['status'] == 1) {
                    session($userId . '_expert_time', false);
                    $flag = false;
                } else {
                    session($userId . '_expert_time', true);
                    $flag = true;
                }
            }
        } else {
            $flag = true;
        }

        $array = [];
        foreach ($list as $tips) {
            if ($flag && in_array($tips['type'], [1, 2, 8])) {
                continue;
            }

            $array[] = [
                'url' => (in_array($tips['type'], [1, 2, 8]) && isMobile() ? '/mobile' : '' ) . config('tips_type.' . $tips['type'])['url'],
                'msg' => config('tips_type.' . $tips['type'])['text'] . '有' . $tips['num'] . '条新的消息，请查收',
            ];
        }

        return $array;
    }
}

if (!function_exists('isMobile')) {
    function isMobile() {
        if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('isWechat')) {
    /**
     * 判断是否是微信浏览器浏览
     * @return boolean
     */
    function isWechat()
    {

        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger')) {
            // 微信浏览器浏览
            return true;
        }
        return false;
    }
}

if (!function_exists('timediff')) {

    /**
     * 计算时间戳与当前时间相差的日时分秒
     */
    function timediff($end_time)
    {
        if ($end_time < time()) {
            return false;
        }

        $starttime = time();

        $endtime = $end_time;


        //计算天数

        $timediff = $endtime - $starttime;

        $days = intval($timediff / 86400);

        //计算小时数

        $remain = $timediff % 86400;

        $hours = intval($remain / 3600);

        //计算分钟数

        $remain = $remain % 3600;

        $mins = intval($remain / 60);

        //计算秒数

        $secs = $remain % 60;

        $res = array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);

        return $res;

    }
}


if (!function_exists('checkSensitive')) {
    /**
     * 检测敏感词
     * @param $array 数组或字符串
     * @return bool
     */
    function checkSensitive($array)
    {
        //敏感词
        $sensitives = explode('|', config('site.sensitive'));

        if (!is_array($array)) {
            $array = [$array];
        }

        //遍历检测
        foreach ($array as $str) {
            foreach ($sensitives as $sensitive) {
                if (strpos($str, $sensitive) !== false) {
                    return 1;
                }
            }
        }

        return 0;
    }
}

if (!function_exists('getTags')) {
    /**
     * 获取标签
     */
    function getTags($type, $max = 10)
    {
        return db('cms_tags')->where(['type' => $type, 'status' => '1'])->limit(0, $max)->select();
    }
}

if (!function_exists('get_prev_next')) {
    /**
     * 文章上下页
     * @param $archivesId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function get_prev_next($archivesId)
    {
        $archives = db('cms_archives')->where('id', $archivesId)->find();
        $where = ['status' => 'normal', 'deletetime' => null, 'channel_id' => $archives['channel_id']];

        $prev = db('cms_archives')->field('id,title')->where($where)->where(['createtime' => ['gt', $archives['createtime']]])->order('createtime', 'ASC')->find();
        $next = db('cms_archives')->field('id,title')->where($where)->where(['createtime' => ['lt', $archives['createtime']]])->order('createtime', 'DESC')->find();
        return [$prev, $next];
    }
}

if (!function_exists('getLink')) {
    /**
     * 获取栏目地址
     * @param $channel_id
     */
    function getLink($channel_id, $diyname = '')
    {
        if (!$diyname) {
            $diyname = db('cms_channel')->where('id', $channel_id)->value('diyname');
        }

        return (request()->module() == 'mobile' ? '/mobile' : '') . '/channel/' . $diyname;
    }
}

if (!function_exists('getFields')) {
    /**
     * 获取字段值
     * @param $fieldId
     */
    function getFields($fieldId)
    {
        $files = app\admin\model\cms\Fields::get($fieldId);
        $content = explode("\n", $files['content']);
        $list = [];
        foreach ($content as $v) {
            $item = explode("|", trim($v));
            $list[] = ['value' => $item[0], 'title' => isset($item[1]) ? $item[1] : $item[0]];
        }

        return $list;
    }
}

if (!function_exists('indexRecruit')) {
    /**
     * 首页企业招聘信息
     */
    function indexRecruit($max = 12)
    {
        $list = db('user_enterprise')->alias('a')
            ->field('a.id,a.company,a.product,a.supplier_type,a.logo,a.city,a.area,COUNT(c.number) sum')
            ->join('cms_archives b', 'a.user_id = b.user_id', 'LEFT')
            ->join('cms_recruit c', 'b.id = c.id', 'LEFT')
            ->where(['a.status' => ['gt', '0'], 'is_index_recruit' => '1', 'b.model_id' => '12', 'b.status' => 'normal', 'b.deletetime' => null])
            ->group('a.id')
            ->order('a.weigh', 'DESC')
            ->limit($max)
            ->select();

        return $list;
    }
}

if (!function_exists('get_nickname')) {
    /**
     * 根据用户ID获取用户昵称
     */
    function get_nickname($id)
    {
        $userinfo = \app\admin\model\User::get($id);

        if ($userinfo) {
            return $userinfo->nickname;
        } else {
            return '';
        }
    }
}

if (!function_exists('get_expert')) {
    /**
     * 根据推荐位获取专家
     */
    function get_expert($max = 10, $sort = 0, $recommendField = null)
    {
        $sorts = ['weigh', 'id'];

        $list = \app\admin\model\user\Expert::where('status', 1)
            ->where('deadline_starttime', '<', time())
            ->where('deadline_endtime', '>', time())
            ->where($recommendField, 1)
            ->order($sorts[$sort], 'desc')
            ->limit($max)
            ->select();

        Lang::load(APP_PATH . 'admin/lang/zh-cn/user/expert.php');

        foreach ($list as $item) {
            $item['level'] = Lang::get('Level ' . $item['level']);
        }

        return $list;
    }
}


if (!function_exists('enterpriseActivity')) {
    /**
     * 优质供应商活动
     * 查询符合条件的最新一条信息
     */
    function enterpriseActivity()
    {
        $where = [
            'a.model_id' => 13,
            'a.status' => 'normal',
            'a.deletetime' => null,
            'b.start_time' => ['lt', date('Y-m-d H:i:s')],
            'b.end_time' => ['gt', date('Y-m-d H:i:s')],
        ];

        $info = db('cms_archives')->alias('a')
            ->join('cms_activity b', 'a.id = b.id', 'LEFT')
            ->where($where)
            ->order('createtime', 'DESC')
            ->find();

        return $info;
    }
}

if (!function_exists('getEnterpriseList')) {
    /**
     * 获取企业列表
     * @param $max 查询数量
     * @param $recommendField 推荐字段
     * @param $order 排序字段
     * @return mixed
     */
    function getEnterpriseList($max, $recommendField = '', $order = 'b.online DESC,b.loginnum DESC,a.weigh DESC', $level = '')
    {
        $where['a.status'] = ['gt', '0'];

        if ($recommendField) {
            $where[$recommendField] = '1';
        }

        if ($level) {
            $where['a.level'] = ['IN', $level];
        }

        $list = db('user_enterprise')->alias('a')->join('user b', 'a.user_id = b.id', 'LEFT')->field('a.id,a.logo,a.company,a.product,a.supplier_type')->where($where)->order($order)->limit($max)->select();

        return $list;
    }
}

if (!function_exists('get_banner')) {

    /**
     * 获取广告位下的广告
     * @param $type_id
     * @param int $max
     * @return mixed
     */
    function get_banner($manage_id, $max = 10)
    {
        $list = db('banner_content')
            ->where(['manage_id' => $manage_id, 'status' => 'normal'])
            ->where("is_time = '0' OR endtime > " . time())
            ->order('weigh', 'DESC')
            ->limit($max)
            ->select();
        return $list;
    }
}

if (!function_exists('getEnterprise')) {
    /**
     * 获取企业信息
     * @param $user_id
     * @param string $field
     * @return mixed
     */
    function getEnterprise($user_id, $field = '*')
    {
        $res = db('user_enterprise')->field($field)->where('user_id', $user_id)->find();

        //查询企业等级
        if (isset($res['level'])) {
            Lang::load(APP_PATH . 'admin/lang/zh-cn/user/enterprise.php');
            $res['level_text'] = Lang::get('Level ' . $res['level']);
        }

        return $field == '*' ? $res : $res[$field];
    }
}

if (!function_exists('get_list')) {

    /**
     * 获取文章列表
     * @param int $mid 模型id
     * @param int $channelId 栏目id
     * @param int $max 数量
     * @param int $sort 排序方式，0：排序号、1：发布时间、2：id、3：浏览次数
     * @param string $recommendField 推荐字段
     * @return bool
     */
    function get_list($mid, $channelId, $max = 10, $sort = 0, $recommendField = null)
    {
        $sorts = ['weigh', 'publishtime', 'id','views'];
        $mid = intval($mid);
        $model = db('cms_model')->field('table')->where('id', $mid)->find();
        $channelId = intval($channelId);
        if (empty($channelId) || empty($mid)) return false;

        $where = ['a.model_id' => $mid, 'a.channel_id' => $channelId, 'a.deletetime' => null, 'a.status' => 'normal'];

        //推荐字段
        if ($recommendField) {
            $where['b.' . $recommendField] = '1';
        }
        $list = db('cms_archives')->alias('a')
            ->join($model['table'] . ' b', 'a.id = b.id', 'LEFT')
            ->where($where)
            ->limit($max)
            ->order($sorts[$sort], 'DESC')
            ->select();

        return $list;
    }
}

if (!function_exists('get_agreement')) {

    /**
     * 获取指定协议内容
     * @param int $mid 文章ID
     */
    function get_agreement($id)
    {
        $agreement = db('cms_addonagreement')->where('id', $id)->find();

        return html_entity_decode($agreement['content']);
    }

}

//这个过滤不会过滤样式
if (!function_exists('removeXSS')) {

    function removeXSS($data)
    {

        $data = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $data);

        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);

        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);

        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);

        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);

        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);

        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);

        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do {
            $old_data = $data;

            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);

        } while ($old_data !== $data);

        return $data;

    }
}

//获取化工字典关联供应商
if (!function_exists('getAssociatedSupplier')) {
    /**
     * @param $name string 中文名称
     * @param $enname string 英文名称
     * @return false|PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    function getAssociatedSupplier($name, $enname)
    {
        //distinct(去除重复)
        $data = db('cms_archives')->field('distinct(user_id)')
            ->where('model_id', 10)
            ->where('status','normal');
        if ($name != '') {
            $data->where('title', 'like', '%' . $name . '%');
        }
        if ($enname != '' && $name != '') {
            $data->whereOr('title', 'like', '%' . $enname . '%');
        } elseif ($enname != '' && $name == '') {
            $data->where('title', 'like', '%' . $enname . '%');
        }
        $data = $data->limit(0,20)->select();
        $enterpriseIds = array_column($data, 'user_id');
        //企业信息
        $result = db('user_enterprise')->where('status' , '2')->where('user_id', 'in', $enterpriseIds)->select();
        return $result;
    }
}

//获取热搜标签
if (!function_exists('getHotSearchTags')) {
    /**
     * 获取热搜标签
     */
    function getHotSearchTags($type, $max = 10)
    {
        return db('cms_tags')->where(['type' => $type, 'status' => '1','hot_search' => '1'])->order('weigh desc')->limit(0, $max)->select();
    }
}