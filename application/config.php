<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Env;

return [
    //必须绑定会员的模型id
    'must_user_mid' => ['6', '7', '8', '10', '11', '12', '13', '15', '16'],

    //投票间隔
    'vote_interval' => 30 * 60,

    //企业认证
    'enterprise' => [
        //企业类型
        'enterprise_type' => [
            1 => '生产型企业',
            2 => '贸易型企业',
            3 => '综合型企业',
        ],
        //商家类别
        'business_type' => [
            1 => '品牌商标持有企业',
            2 => '高新技术企业',
            3 => '持有品牌授权为品牌商授权认可的经销商',
            4 => '均不是',
        ],
        //企业三证
        'certificate_type' => [
            1 => '类型1',
            2 => '类型2',
            3 => '类型3',
        ],
        //经营类别
        'supplier_type' => [
            '表面活性剂',
            '金属表面处理剂',
            '无机助剂',
            '无机盐',
            '无机碱',
            '低泡表面活性剂',
            '无泡表面活性剂',
            '精细化工原料',
            '金属加工助剂',
            '金属工艺液',
            '其他精细化学品',
            '电镀添加剂',
            '工业清洗剂',
            '缓蚀剂',
            '切削液',
            '涂料',
            '生产设备',
            '搅拌设备',
            '实验仪器',
            '硅烷剂',
            '陶化剂',
            '包装制品',
            '食品添加剂',
            '化工中间体',
        ],
        //每个等级对应发布文章的数量
        'level_1' => [
            //供应
            'supply' => 8,
            //采购
            'purchase' => 4,
            //招聘
            'recruit' => 1,
            //荣誉资质
            'honor' => 0,
        ],
        'level_2' => [
            'supply' => '*',
            'purchase' => '*',
            'recruit' => 5,
            'honor' => '*',
        ],
        'level_3' => [
            'supply' => '*',
            'purchase' => '*',
            'recruit' => 10,
            'honor' => '*',
        ],
        'level_4' => [
            'supply' => '*',
            'purchase' => '*',
            'recruit' => 20,
            'honor' => '*',
        ],
    ],

    //文章图片推荐尺寸
    'image_size' => [
        '70' => '200x180',
        '74' => '200x180',
        '73' => '348x210',
        '47' => '169x152',
        '49' => '169x152',
        '77' => '230x165',
        '72' => '230x260',
        '69' => '300x214',
        '128' => '150x140',
        '133' => '180x180'
    ],

    //提示跳转链接(添加新类型后必须在数据库对应表字段增加对应的选项)
    'tips_type' => [
        //在线服务
        1 => [
            'text' => '在线服务',
            'url' => '/user/onlineService.html',
        ],
        //现场服务
        2 => [
            'text' => '现场服务',
            'url' => '/user/localeService.html',
        ],
        //平台群发
        3 => [
            'text' => '平台群发',
            'url' => '/user/message.html',
        ],
        //企业留言
        4 => [
            'text' => '企业留言',
            'url' => '/user/leave_message.html',
        ],
        //我的咨询
        5 => [
            'text' => '我的咨询',
            'url' => '/user/myAskquestion.html',
        ],
        //我的服务
        6 => [
            'text' => '我的服务',
            'url' => '/user/myReservation.html',
        ],
        //企业活动订单
        7 => [
            'text' => '我的活动订单',
            'url' => '/user/enterprise_order.html',
        ],
        //在线抢单
        8 => [
            'text' => '在线抢单',
            'url' => '/user/grabOnlineService.html',
        ],
        //个人信息审核
        9 => [
            'text' => '个人信息审核',
            'url' => '/user/idAuthentication.html',
        ],
        //专家信息审核
        10 => [
            'text' => '专家信息审核',
            'url' => '/user/expertApplication.html',
        ],
        //企业信息审核
        11 => [
            'text' => '企业信息审核',
            'url' => '/user/enterprise.html',
        ],
    ],

    //分页大小
    'page_size' => [
        'expert' => '10',
        //评论
        'appraise' => '6',
        //在线服务
        'online_service' => '6',
        //现场服务
        'locale_service' => '6',
        //在线服务
        'grade_locale_service' => '6',
        //在线抢单
        'grade_locale_service_list' => '6',
        //我的文章
        'my_article' => '6',
        //我的知识
        'my_konwledge' => '6',
        //我的咨询
        'my_askquestion' => '8',
        //我的现场
        'my_reservation' => '8',
        //我的收藏
        'my_collect' => '10',
        //联盟企业
        'enterprise' => '15',
        //会员中心发布列表
        'release' => '10',
        //企业供应产品
        'supply' => '12',
        //企业求购产品
        'purchase' => '8',
        //企业人才招聘
        'recruit' => '10',
        //提现
        'take_cash_list' => '10',
        //站内信
        'message' => '10',
        //收到的留言
        'leave_message' => '10',
    ],

    //订单类型
    'order_type' => [
        'online' => 1,
        'locale' => 2,
        'formula' => 3,
        'konwledge' => 4,
        'activity' => 5,
    ],

    //支付状态
    'pay_type' => [
        'unpaid'        => 0,
        'Paid'          => 1,
        'expired'       => 2,
    ],

    //RSA公钥和私钥
    'RSA' => [
        'public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrmTxS3EB0vSTH12i8QkV6sMs4A/1ZytumQ2MHo8fa1mC/XJQf/DvXiccskNWhQdEoyPVstvIii7Rlw22GrYQFGOBr6cNo1cta4h0LBZyT6KZgvzF/nOYwbsbs9zPK22rs3hHwUVsyrl0II1VT4TmieWHw1IIjthTYa5c9HLF06QIDAQAB',
        'private_key' => 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKuZPFLcQHS9JMfXaLxCRXqwyzgD/VnK26ZDYwejx9rWYL9clB/8O9eJxyyQ1aFB0SjI9Wy28iKLtGXDbYathAUY4Gvpw2jVy1riHQsFnJPopmC/MX+c5jBuxuz3M8rbauzeEfBRWzKuXQgjVVPhOaJ5YfDUgiO2FNhrlz0csXTpAgMBAAECgYA6RMh1IpFIksmgioboFmDdbpczKDepe/bmGE/SUk0VBGLJ2Df8PHxdjk1x1qSUI3NQtBySk/TYwjO5sojIiLchLWd0+B06kAKqaaGhc9rxKDMaIT42JXiWqdyb7laHDEP6vDSojoCVsFi+dsT4ShnInuD0sSbgd8ve38v+K3JIuQJBAN2O7KZEbhgv7uj+gz7l9UDEdATKzD1LnGgDREuEbb+jNj1xaNCHH8c75oXOLHQS6bojmyDYqy0KuvyS34ZJp0sCQQDGRiCYKOzUghAl+4wPu70ao5JaLnp1IUiDDphqr0dbkSbsSAkFFUYVOw0Ndsil1wRLdCFcAokjgVpiH4OrtHAbAkEAvMaqnIBxzeoJhjxVV6JX9Xdt4ydoHlHuUjF2X2HWoRJPhMq8o/B+AyPmptukxMHYo+DyrGnwb9BUwh/ilGjtQQJAbZUCnLY5tnWv8R4m2ec768Yts/PuMGBV0EE30fbP7Ha72WkyuwF3+3Hok+FroeTFdeJYMZ8hJmNujb63KiYOswJAUwFA0IFLT4gUSK0uTwwx10b7pZqhRuN9IvZYEAC3mER1aP8bWglV6Ao/6uCRBFDF4xJqrnz2FAhhvq5AabwgkQ==',
    ],

    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    // 应用命名空间
    'app_namespace'          => 'app',
    // 应用调试模式
    'app_debug'              => Env::get('app.debug', true),
    // 应用Trace
    'app_trace'              => Env::get('app.trace', false),
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [
        THINK_PATH . 'helper' . EXT,
        APP_PATH . 'logic' . EXT
    ],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => 'removeXSS,htmlspecialchars',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,
    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------
    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => true,
    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------
    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,
    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------
    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
        'tpl_cache'    => true,
    ],
    // 视图输出字符串内容替换,留空则会自动进行计算
    'view_replace_str'       => [
        '__PUBLIC__' => '',
        '__ROOT__'   => '',
        '__CDN__'    => '',
    ],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => APP_PATH . 'common' . DS . 'view' . DS . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => APP_PATH . 'common' . DS . 'view' . DS . 'tpl' . DS . 'dispatch_jump.tpl',
    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------
    // 异常页面的模板文件
    'exception_tmpl'         => APP_PATH . 'common' . DS . 'view' . DS . 'tpl' . DS . 'think_exception.tpl',
    // 错误显示信息,非调试模式有效
    'error_message'          => '你所浏览的页面暂时无法访问',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',
    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------
    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'        => 'File' ,
        // 日志保存目录
        'path'        => LOG_PATH ,
        // 日志记录级别
        'level'       => [] ,
        // error日志单独记录
        'apart_level' => ['error'] ,
    ] ,
    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],
    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------
    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],
    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------
    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'think',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],
    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],
    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],
    //验证码配置
    'captcha'                => [
        // 验证码字符集合
        'codeSet'  => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
        // 验证码字体大小(px)
        'fontSize' => 18,
        // 是否画混淆曲线
        'useCurve' => false,
        //使用中文验证码
        'useZh'    => false,
        //是否使用背景图片
        'useImgBg' => false,
        //是否添加杂点
        'useNoise' => false,
        // 验证码图片高度
        'imageH'   => 50,
        // 验证码图片宽度
        'imageW'   => 130,
        // 验证码位数
        'length'   => 4,
        // 验证成功后是否重置
        'reset'    => true
    ],
    // +----------------------------------------------------------------------
    // | Token设置
    // +----------------------------------------------------------------------
    'token'                  => [
        // 驱动方式
        'type'     => 'Mysql',
        // 缓存前缀
        'key'      => 'i3d6o32wo8fvs1fvdpwens',
        // 加密方式
        'hashalgo' => 'ripemd160',
        // 缓存有效期 0表示永久缓存
        'expire'   => 0,
    ],
    //Uwebadmin配置
    'uwebadmin'              => [
        //是否开启前台会员中心
        'usercenter'          => true,
        //登录验证码
        'login_captcha'       => true,
        //登录失败超过10则1天后重试
        'login_failure_retry' => true,
        //是否同一账号同一时间只能在一个地方登录
        'login_unique'        => false,
        //登录页默认背景图
        'login_background'    => "/assets/img/loginbg.jpg",
        //是否启用多级菜单导航
        'multiplenav'         => false,
        //自动检测更新
        'checkupdate'         => false,
        //版本号
        'version'             => '1.0.0.20180911_beta',
        //API接口地址
        'api_url'             => 'https://api.fastadmin.net',
    ],
];
