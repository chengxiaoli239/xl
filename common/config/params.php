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
    'can_change_buy_type' => [22, 25, 28], # 四定单双、四定快选、系统快捷

];
