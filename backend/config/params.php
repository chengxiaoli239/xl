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
    'ALL_DS' => '1112,1121,1211,2111,1222,2122,2212,2221,1122,1212,1221,2112,2121,2211,1111,2222',

    'test_account' => ['aa07', 'aa03'],

    'TZ_LOCK_TIME' => 4 * 60 * 60,

    # 导入号码投注类型
    'IMPORT_CODES_TYPES' => [ 19,27,34 ],

    'GET_BASE_DATA_CACHE_TIME' => 30 * 60,

    # 站点域名，目前大部分站点登录之前robot7_session_id匹配获取
    'TZ_SITE_MIDDLE_DOMAINS' => ['cq779835', 'ww98877', 'ww33899', 'wx36625771', 'ww666733', 'cq779835' ,'ww793288', 'ww368755'],

    # IP代理 快代理配置
    'KUAI_POXY_API' => 'https://dps.kdlapi.com',
    'KUAI_POXY_ORDER_ID' => '938684913491492',
    'KUAI_USERNAME' => '379879537', # 通过密码使用私密代理
    'KUAI_PASSWORD' => '14wmcx7y', # 快代理密码

];

