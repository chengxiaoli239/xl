<?php

// Render only the monitor view with synthetic data; no production account or database access.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__) . '/common/config/bootstrap.php';

$app = new yii\web\Application([
    'id' => 'number-monitor-view-test',
    'basePath' => dirname(__DIR__) . '/backend',
    'runtimePath' => sys_get_temp_dir(),
    'components' => [
        'request' => [
            'class' => yii\web\Request::class,
            'scriptUrl' => '/index.php',
            'hostInfo' => 'http://localhost',
            'cookieValidationKey' => 'monitor-view-test',
        ],
        'urlManager' => ['enablePrettyUrl' => true, 'showScriptName' => false],
    ],
]);

function checkView(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function renderMonitor(bool $isSuperAdmin, int $ownerUid): string
{
    $dashboard = [
        'owners' => [
            ['uid' => 2, 'username' => 'member <test>', 'scheme_count' => 3, 'file_count' => 1],
        ],
        'files' => [],
        'draws' => [],
        'server_now' => 0,
    ];
    return Yii::$app->view->renderFile(
        dirname(__DIR__) . '/backend/modules/forum/views/number-monitor/index.php',
        [
            'lotteryType' => 8,
            'lotteryTypes' => [8 => '幸运五'],
            'dashboard' => $dashboard,
            'isSuperAdmin' => $isSuperAdmin,
            'ownerUid' => $ownerUid,
            'ownUid' => $isSuperAdmin ? 1 : 2,
            'csrfToken' => 'test',
        ]
    );
}

$overview = renderMonitor(true, 0);
checkView(strpos($overview, 'id="nm-owner"') !== false
    && strpos($overview, 'data-owner-uid="0"') !== false
    && strpos($overview, 'aria-label="导入号码方案" hidden') !== false,
    'admin overview selector or read-only state is missing');
checkView(strpos($overview, 'member &lt;test&gt;') !== false
    && strpos($overview, 'member <test>') === false,
    'account name was not escaped');
$otherOwner = renderMonitor(true, 2);
checkView(strpos($otherOwner, 'data-owner-uid="2"') !== false
    && strpos($otherOwner, 'aria-label="导入号码方案" hidden') !== false,
    'admin account view unexpectedly allows importing');
$own = renderMonitor(true, 1);
checkView(strpos($own, 'aria-label="导入号码方案" hidden') === false,
    'admin own view cannot import');
$member = renderMonitor(false, 2);
checkView(strpos($member, 'id="nm-owner"') === false
    && strpos($member, 'data-admin-view="0"') !== false
    && strpos($member, 'aria-label="导入号码方案" hidden') === false,
    'ordinary member view changed');

echo "number_monitor_admin_view_regression: OK\n";
