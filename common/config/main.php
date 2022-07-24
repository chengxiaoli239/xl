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
            #'class' => new \yii\redis\Connection(),
            'class' => 'yii\redis\Connection',
            'hostname' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'connectionTimeout'=>3
        ],

        'queue'  => [
            //Redis 队列方案
            'class'   => yii\queue\redis\Queue::className(),
            // 连接组件或它的配置
            'redis'   => 'redis',
            // Queue channel key
            'channel' => 'queue',
            'as log'=> new \yii\queue\LogBehavior(),
        ]
    ]
];
