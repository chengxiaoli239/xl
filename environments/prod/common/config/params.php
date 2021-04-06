<?php

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'ssc_kj_time_start' => '03:00',
    'ssc_kj_time_end' => '07:15',
    'LOTTERY_TYPE_6_STOP_START_TIME' => '02:00', # 新疆截至投注开始时间
    'LOTTERY_TYPE_6_STOP_END_TIME' => '10:00', # 新疆截至投注结束时间
    'AIP_API_ID' => '11617584',    // 百度AI APP ID
    'AIP_API_KEY' => 'YzH6ouHBfApZpGBzcauRCGst',    // 百度AI APP KEY
    'AIP_SECRET_KEY' => 'st2syHNBIjapDAXnZIG77ndnqo6LwI1B',    // 百度AI APP SECRET KEY

    # 万维易源配置
    'SHOW_API_APPID' => '63733',
    'SHOW_API_SIGN' => '5a4a298be59c4825a477598b2bd6bccc',

    # 聚合接口配置
    'JUHE_KEY' => '4cf9ceff85b685abb8cb04abf9bb76cd',

    # 日志目录
    'LOG_PATH' => 'lottery_xl',

    # 基础数据缓存时间 2 分钟
    'BASE_DATA_CACHE_TIME' => 1200,

    # 聊天室配置
    'CHAT_DOMAIN' => 'http://154.83.17.96:6060', # 聊天室域名
    'ONLINE_DIR' => '/tmp/chat/', # 缓存目录
    'CHAT_PORT' => '9501', # web socket端口
    'CHAT_STORAGE' => 'file', # 缓存类型：文件
    'CHAT_ROOMS' => [ 'a'=>'唐', 'b'=>'伯', 'c'=>'虎', 'd'=>'点', 'e'=>'秋', 'f'=>'香' ], # , 'b =>'伯', 'c'=>'虎', 'd'=>'点', 'e'=>'秋', 'f'=>'香'

    # 可以切换正反买tz_type
    'can_change_buy_type' => [22, 25, 28, 34], # 四定单双、四定快选、系统快捷、导入
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

    'test_account' => ['aa07', 'aa02', 'as01'],

    'TZ_LOCK_TIME' => 4 * 60 * 60,

    # 导入号码投注类型
    'IMPORT_CODES_TYPES' => [ 19,27,34 ],

    # 现：二、三、四现
    'IS_XIAN' => [36, 17, 37],

    'NEED_PROXY_LOTTERYS' => [8, 10, 11 ], # 需要代理IP的彩种，后面修改判断站点ID

    # 不统计的彩种
    'NOT_STATIC_LOTTERYS' => [8, 9, 10, 11, 12, 13 ], # 台湾宾果，冰岛90s,3m,5m,10m
    # 统计数据彩种
    'STATIC_DATA_LOTTERYS' => [6], # 统计数据

    'GET_BASE_DATA_CACHE_TIME' => 30 * 60,

    # 站点域名，目前大部分站点登录之前robot7_session_id匹配获取
    'TZ_SITE_MIDDLE_DOMAINS' => ['cq779835', 'ww98877', 'ww33899', 'wx36625771', 'ww666733', 'cq779835' ,'ww793288', 'ww368755'],

    # IP代理 快代理配置
    'KUAI_POXY_API' => 'https://dps.kdlapi.com',
    'KUAI_POXY_ORDER_ID' => '949377904639969',
    'KUAI_USERNAME' => '379879537', # 通过密码使用私密代理
    'KUAI_PASSWORD' => '14wmcx7y', # 快代理密码

    'WX_IMG_URL_DOMAIN' => 'https://wx2.qq.com',
];

