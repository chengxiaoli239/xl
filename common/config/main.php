<?php
$db_name = 'stock_datas'; # lottery_xl
return [
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
    'bootstrap' => ['log', 'queue', 'queue_fast', 'queue_open'],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname='.$db_name,
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'username' => $db_name,
            'tablePrefix' => 'lt_',
            'password' => 'dPmk3frLf8Teb6wm',
            'charset' => 'utf8mb4', // 使用 utf8mb4 编码，支持更广泛的字符集
            #'collation' => 'utf8mb4_unicode_ci', // 使用 utf8mb4_unicode_ci 校对规则
        ],
        'cache' => [
            //'class' => 'yii\caching\FileCache',
            /*
            'class' => '\yii\caching\MemCache',
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 60,
                ],
            ],
            'useMemcached' => true,
            */
            'class' => 'yii\redis\Cache',
            'keyPrefix' => 'xl_',
        ],
        'redis' => [
            #'class' => new \yii\redis\Connection(),
            'class' => '\yii\redis\Connection',
            'hostname' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'connectionTimeout'=>3
        ],
        'commonRedis' => [
            'class' => 'common\framework\Redis',
            'hostname' => '127.0.0.1',
            'port' => 6379,
            'database' => 1,
            'connectionTimeout'=>3
        ],

        'queue'  => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis',
            // Queue channel key
            'channel' => 'lottery:queue',
            'as log'=> \yii\queue\LogBehavior::class,
            'ttr' => 30,         // 30秒超时
            'attempts' => 2,     // 总共执行2次
        ],
        'queue_fast' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis connection component or its config
            'channel' => 'lottery:queue_fast', // Queue channel key
            'as log' => \yii\queue\LogBehavior::class,
            'ttr' => 30,         // 30秒超时
            'attempts' => 2,     // 总共执行2次
        ],
        'queue_open' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis connection component or its config
            'channel' => 'wechat:queue_open', // Queue channel key
            'as log' => \yii\queue\LogBehavior::class,
            //'as jobRetry' => 'yii\queue\RetryBehavior',
            'ttr' => 3600,
        ],
    ]
];
