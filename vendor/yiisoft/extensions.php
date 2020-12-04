<?php

$vendorDir = dirname(__DIR__);

return [
    'yiisoft/yii2-swiftmailer' => [
        'name' => 'yiisoft/yii2-swiftmailer',
        'version' => '2.1.1.0',
        'alias' => [
            '@yii/swiftmailer' => $vendorDir . '/yiisoft/yii2-swiftmailer/src',
        ],
    ],
    'izyue/yii2-admin' => [
        'name' => 'izyue/yii2-admin',
        'version' => '1.0.3.0',
        'alias' => [
            '@izyue/admin' => $vendorDir . '/izyue/yii2-admin',
        ],
    ],
    'yiidoc/yii2-redactor' => [
        'name' => 'yiidoc/yii2-redactor',
        'version' => '2.0.1.0',
        'alias' => [
            '@yii/redactor' => '/',
        ],
    ],
    'yiisoft/yii2-imagine' => [
        'name' => 'yiisoft/yii2-imagine',
        'version' => '2.1.1.0',
        'alias' => [
            '@yii/imagine' => $vendorDir . '/yiisoft/yii2-imagine/src',
        ],
    ],
    'yiisoft/yii2-codeception' => [
        'name' => 'yiisoft/yii2-codeception',
        'version' => '2.0.6.0',
        'alias' => [
            '@yii/codeception' => $vendorDir . '/yiisoft/yii2-codeception',
        ],
    ],
    'yiisoft/yii2-bootstrap' => [
        'name' => 'yiisoft/yii2-bootstrap',
        'version' => '2.0.8.0',
        'alias' => [
            '@yii/bootstrap' => $vendorDir . '/yiisoft/yii2-bootstrap/src',
        ],
    ],
    'yiisoft/yii2-debug' => [
        'name' => 'yiisoft/yii2-debug',
        'version' => '2.0.13.0',
        'alias' => [
            '@yii/debug' => $vendorDir . '/yiisoft/yii2-debug',
        ],
    ],
    'yiisoft/yii2-gii' => [
        'name' => 'yiisoft/yii2-gii',
        'version' => '2.0.7.0',
        'alias' => [
            '@yii/gii' => $vendorDir . '/yiisoft/yii2-gii/src',
        ],
    ],
    'yiisoft/yii2-faker' => [
        'name' => 'yiisoft/yii2-faker',
        'version' => '2.0.4.0',
        'alias' => [
            '@yii/faker' => $vendorDir . '/yiisoft/yii2-faker',
        ],
    ],
    'bubifengyun/yii2-echarts' => [
        'name' => 'bubifengyun/yii2-echarts',
        'version' => '0.0.7.0',
        'alias' => [
            '@bubifengyun/echarts' => $vendorDir . '/bubifengyun/yii2-echarts',
        ],
    ],
    'daixianceng/yii2-echarts' => [
        'name' => 'daixianceng/yii2-echarts',
        'version' => '1.1.0.0',
        'alias' => [
            '@daixianceng/echarts' => $vendorDir . '/daixianceng/yii2-echarts/src',
        ],
    ],
    'yiisoft/yii2-redis/src' => [
        'name' => 'yiisoft/yii2-redis/src',
        'version' => '2.2.0.0',
        'alias' => [
            '@yii/redis' => $vendorDir . '/yiisoft/yii2-redis/src',
        ],
    ],
];
