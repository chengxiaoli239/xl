<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'language' => 'zh-CN',
    'modules' => [
        'admin' => [
            'class' => 'izyue\admin\Module',
//            'layout' => 'left-menu',
            'layout' => '@app/views/layouts/main.php',
        ],
        'agent' => [
            'class' => 'backend\modules\agent\Module',
            'layout' => '@app/views/layouts/main.php',
        ],
        'forum' => [
            'class' => 'backend\modules\forum\Module',
            'layout' => '@app/views/layouts/main.php',
        ],
        'cron' => [
            'class' => 'backend\modules\cron\Module',
            'layout' => '@app/views/layouts/main.php',
        ],
        'test' => [
            'class' => 'backend\modules\test\Module',
            'layout' => '@app/views/layouts/main.php',
        ],
        'chat' => [
            'class' => 'backend\modules\chat\Module',
            'layout' => '@app/views/layouts/chat.php',
        ],
        /*
        'wx' => [
            'class' => 'backend\modules\wx\Module',
            'layout' => '@app/views/layouts/main.php',
        ],
        */
        'api' => [
            'class' => 'backend\modules\api\Module',
        ],
        //'commands' => [
        //    'class' => 'backend\modules\commands\Module',
        //],
        'kj' => [
            'class' => 'backend\modules\kj\Module',
        ],
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=120.77.157.40;dbname=lottery_xl',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'username' => 'lottery_xl',
            'tablePrefix' => 'lt_',
            'password' => 'dPmk3frLf8Teb6wm',
            'charset' => 'utf8',
        ],
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
        ],
        'request' => [
            'class' => 'common\web\Request',
            'enableCookieValidation' => true,
            'enableCsrfValidation' => false,
            'cookieValidationKey' => 'O1d232trde1x-M97_7QvwPo-5QGdkLMp#@#@',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'text/json' => 'yii\web\JsonParser',
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\AdminModel',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager', // or use 'yii\rbac\PhpManager'
        ],
        'i18n' => [
            'translations' => [
                'rbac-admin' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    //'basePath' => '/messages',
                    'fileMap' => [
                        'rbac-admin' => 'rbac-admin.php',
                    ],
                ],
                'common' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    //'basePath' => '/messages',
                    'fileMap' => [
                        'common' => 'common.php',
                    ],
                ],
                'login' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    //'basePath' => '/messages',
                    'fileMap' => [
                        'login' => 'login.php',
                    ],
                ],
                'signup' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    //'basePath' => '/messages',
                    'fileMap' => [
                        'admin' => 'sginup.php',
                    ],
                ],
                'admin' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    //'basePath' => '/messages',
                    'fileMap' => [
                        'admin' => 'admin.php',
                    ],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<modules:\w+>/<controller:\w+>/<action:\w+>.html' => '<modules>/<controller>/<action>',
                '<modules:\w+>/<controller:\w+>/<action:\w+>' => '<modules>/<controller>/<action>',
            ],
        ],
        /*
        */
    ],
    'as access' => [
        'class' => 'izyue\admin\components\AccessControl',
        'allowActions' => [
            'debug/*',
            'site/*',
            'gii/*',
            'cron/*',
            'test/*',
            'chat/*',
            'api/*',
            'kj/*',
            'wx-friends/*',
            '/agent/agent-users/get-user-info',
            #'forum/index/tz',
            #'admin/*',
            // The actions listed here will be allowed to everyone including guests.
            // So, 'admin/*' should not appear here in the production, of course.
            // But in the earlier stages of your development, you may probably want to
            // add a lot of actions here until you finally completed setting up rbac,
            // otherwise you may not even take a first step.
        ]
    ],
    'params' => $params,
];
