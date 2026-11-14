<?php

error_reporting(E_ERROR | E_PARSE);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__) . '/common/config/bootstrap.php';

$common = require dirname(__DIR__) . '/common/config/main.php';
$consoleConfig = file_get_contents(dirname(__DIR__) . '/console/config/main.php');
if (strpos($consoleConfig, "'bootstrap' => ['log', 'queue', 'queue_monitor']") === false) {
    throw new RuntimeException('monitor queue is not bootstrapped for console workers');
}
if (in_array('queue_monitor', $common['bootstrap'], true)) {
    throw new RuntimeException('monitor queue should not bootstrap for web requests');
}

$app = new yii\console\Application([
    'id' => 'number-monitor-queue-test',
    'basePath' => dirname(__DIR__) . '/console',
    'runtimePath' => sys_get_temp_dir(),
    'bootstrap' => ['queue_monitor'],
    'components' => [
        'redis' => $common['components']['redis'],
        'queue_monitor' => $common['components']['queue_monitor'],
    ],
]);
if (!isset($app->controllerMap['queue-monitor'])) {
    throw new RuntimeException('queue-monitor/listen command is unavailable');
}

echo "number_monitor_queue_regression: OK\n";
