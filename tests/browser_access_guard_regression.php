<?php
define('YII_ENV_TEST', true);
// The installed Yii2 version emits PHP 8.4 deprecations before its web error handler is ready.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

use backend\components\BrowserAccessGuard;

$wechat = 'Mozilla/5.0 (iPhone) AppleWebKit/605.1.15 Mobile MicroMessenger/8.0.0';
$qqBrowser = 'Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36 MQQBrowser/13.0 Mobile Safari/537.36';
$qqApp = 'Mozilla/5.0 (iPhone) AppleWebKit/605.1.15 Mobile QQ/8.9.0';
$chrome = 'Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36';
$cases = [
    [$wechat, 'GET', 'text/html', '', 'site/login', false, true],
    [$qqBrowser, 'GET', 'text/html', '', 'forum/user/index.html', false, true],
    [$qqApp, 'GET', '', 'navigate', '', false, true],
    [$chrome, 'GET', 'text/html', 'navigate', 'forum/user/index.html', false, false],
    [$wechat, 'GET', 'application/json', '', 'forum/user/index.html', true, false],
    [$wechat, 'GET', 'application/json', '', 'forum/user/index.html', false, false],
    [$wechat, 'GET', 'text/html', 'navigate', 'api/client-auth/validate', false, false],
    [$wechat, 'POST', 'text/html', 'navigate', 'forum/user/index.html', false, false],
];
foreach ($cases as $case) {
    $expected = array_pop($case);
    if (BrowserAccessGuard::shouldBlock(...$case) !== $expected) {
        throw new RuntimeException('浏览器访问拦截回归失败：'.json_encode($case));
    }
}

// Exercise the actual bootstrap hook and HTML response without any production services.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/forum/user/index.html';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_ACCEPT'] = 'text/html';
$_SERVER['HTTP_USER_AGENT'] = $wechat;
$config = [
    'id' => 'browser-guard-test',
    'basePath' => dirname(__DIR__).'/backend',
    'bootstrap' => ['browserAccessGuard'],
    'components' => [
        'browserAccessGuard' => ['class' => BrowserAccessGuard::class],
    ],
];
$app = new yii\web\Application($config);
ob_start();
try {
    $app->trigger(yii\base\Application::EVENT_BEFORE_REQUEST);
    throw new RuntimeException('微信页面导航未被拦截');
} catch (yii\base\ExitException $expected) {
    $html = ob_get_clean();
}
if ($app->response->statusCode !== 403 || strpos($html, '请在系统浏览器中打开') === false
    || $app->response->headers->get('Cache-Control') !== 'private, no-store') {
    throw new RuntimeException('浏览器提示页响应异常');
}

$_SERVER['HTTP_USER_AGENT'] = $chrome;
$app = new yii\web\Application($config);
$app->trigger(yii\base\Application::EVENT_BEFORE_REQUEST);
if ($app->response->isSent) {
    throw new RuntimeException('普通浏览器被意外拦截');
}
echo "browser_access_guard_regression: OK\n";
