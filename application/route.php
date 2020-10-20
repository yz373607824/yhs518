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

return [
    //别名配置,别名只能是映射到控制器且访问时必须加上请求的方法
    '__alias__'   => [
    ],
    //变量规则
    '__pattern__' => [
    ],

    'search' => 'index/Index/search',      //首页路由
//    'task/:action' => 'index/Task/:action',      //定时任务路由
    'channel/:action' => 'index/Channel/index?diyname=:action&id=:action', //栏目
    'archives/:action' => 'index/Archives/index?diyname=:action&id=:action',   //文章
    'handleArchives/:action' => 'index/Archives/:action',    //操作文章
    'page/:action' => 'index/Page/index?diyname=:action&id=:action',    //单页
    'ajax/:action' => 'index/Ajax/:action',   //异步请求
    'diyform' => 'index/Diyform/post',   //自定义表单
    'user/:action' => 'Index/User/:action',     //会员路由
    'expert/:action' => 'Index/Expert/:action', //专家路由

    //手机
    'mobile/channel/:action' => 'mobile/Channel/index?diyname=:action&id=:action', //栏目
    'mobile/archives/:action' => 'mobile/Archives/index?diyname=:action&id=:action',   //文章
    'mobile/handleArchives/:action' => 'mobile/Archives/:action',    //操作文章
    'mobile/diyform' => 'index/Diyform/post',   //自定义表单
    'mobile/connect/:action' => '/addons/third/index/connect?platform=:action',     //微信授权
    'mobile/callback/:action' => '/addons/third/index/callback?platform=:action',     //微信授权回调
    '/grab'     => 'api/Grab/getGrab',     //抓取数据
//        域名绑定到模块
//        '__domain__'  => [
//            'admin' => 'admin',
//            'api'   => 'api',
//        ],
];
