<?php
if($_SERVER['SERVER_PORT'] == '8090')@ini_set('session.name', 'PHPSESSID_BACKEND');# 一般不需要不提交SVN，只是为了在一个浏览器登录两个端口站点session不冲突问题
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

defined('DEFAULT_LOTTERY_TYPE') or define('DEFAULT_LOTTERY_TYPE',5); # 默认为重庆时时彩

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');
require(__DIR__ . '/../../common/common.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../../common/config/main-local.php'),
    require(__DIR__ . '/../config/main.php'),
    require(__DIR__ . '/../config/main-local.php')
);

$application = new yii\web\Application($config);
$application->run();
