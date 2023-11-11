<?php
$db_name = 'stock_datas'; # lottery_xl
return [
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
            'collation' => 'utf8mb4_unicode_ci', // 使用 utf8mb4_unicode_ci 校对规则
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
