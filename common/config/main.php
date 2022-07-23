<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            //'class' => 'yii\caching\FileCache',
            'class' => 'yii\caching\MemCache',
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 60,
                ],
            ],
            'useMemcached' => true,
            'keyPrefix' => 'xl_',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
            'connectionTimeout'=>3
        ],

        'queue'  => [
            //Redis 队列方案
            'class'   => new \yii\queue\redis\Queue(),
            // 连接组件或它的配置
            'redis'   => 'redis',
            // Queue channel key
            'channel' => 'queue',
            'as log'=> new \yii\queue\LogBehavior(),
        ]
    ]
];
