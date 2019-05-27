<?php

return [
    'ajaxUrlRouteUser_key' => '/user/ajax.aspx', // 用户信息 route，例如：获取余额接口、
    'sscUrlRoute_key' => '/ssc/index.aspx', // 时时彩首页、登录请求路由
    'ajaxUrlRouteLot_key' => '/ssc/ajax.aspx',
    'ajaxUrlRouteLotDw_key' => '/ssc_qmode/ajax.aspx', // 定位接口
    'username' => 'gaozi2017',
    'password' => '0654321',
    'switchSsc'=>true,  // 投注开关

    //'sql_file' => '/usr/local/nginx/html/www.0898.com/docs/data/mine.sql',
    'sql_file' => '../../docs/data/mine.sql',

    'TZ_SWITCH_KEY' => 'TZ_SWITCH_STATUS', # 全局投注开关 - 真实
    'PLAN_SWITCH_KEY' => 'PLAN_SWITCH_STATUS', # 全局跑计划开关 - 真实
    'TZ_SWITCH_SIMULATE_KEY' => 'TZ_SWITCH_SIMULATE_KEY', # 全局投注开关 - 模拟
    'DATA_STATIC_KEY' => 'DATA_STATIC_KEY', # 数据统计基本key

    'TZ_LOCK_TIME' => 4 * 60 * 60,

];

