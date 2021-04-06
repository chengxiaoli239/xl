<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            //'cookieValidationKey' => '09Nk8m9n-1LxlLj-PjvgW1W6mQYEK4Rp',
            'cookieValidationKey' => '5e0f9cf8442bc1bfe753e324cdecb5e471daf539',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname=lottery_xl',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'username' => 'lottery_xl',
            'tablePrefix' => 'lt_',
            'password' => 'dPmk3frLf8Teb6wm',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
    ],
];

if (!YII_ENV_TEST) {
    // configuration adjustments for 'dev' environment
    //$config['bootstrap'][] = 'debug';
    //$config['modules']['debug'] = [
    //    'class' => 'yii\debug\Module',
    //];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'model' => [
                'class' => 'izyue\admin\generators\model\Generator',
                'templates' => [
                    'default' => '@izyue/admin/generators/model/default',
                ]
            ],
            'crud' => [
                'class' => 'izyue\admin\generators\crud\Generator',
                'templates' => [
                    'default' => '@izyue/admin/generators/crud/default',
                ]
            ],
            'controller' => [
                'class' => 'izyue\admin\generators\controller\Generator',
                'templates' => [
                    'default' => '@izyue/admin/generators/controller/default',
                ]
            ],
            'form' => [
                'class' => 'izyue\admin\generators\form\Generator',
                'templates' => [
                    'default' => '@izyue/admin/generators/form/default',
                ]
            ],
        ],
        'allowedIPs' => [
            '127.0.0.1',
            '*.*.*.*',
        ],
    ];
}

return $config;
