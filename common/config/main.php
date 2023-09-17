<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'bootstrap' => ['log', 'queue', 'queue_fast', 'queue_open'],
    'components' => [
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

        'queue'  => [
            //Redis 队列方案
            'class' => \yii\queue\redis\Queue::class,
            // 连接组件或它的配置
            'redis' => 'redis',
            // Queue channel key
            'channel' => 'lottery:queue',
            'as log'=> \yii\queue\LogBehavior::class,
        ],
        'queue_fast' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis connection component or its config
            'channel' => 'lottery:queue_fast', // Queue channel key
            'as log' => \yii\queue\LogBehavior::class,
            'ttr' => 3600,
        ],
        'queue_open' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis connection component or its config
            'channel' => 'wechat:queue_open', // Queue channel key
            'as log' => \yii\queue\LogBehavior::class,
            'ttr' => 3600,
        ],
    ]
];
