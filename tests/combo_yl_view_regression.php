<?php

// 无账号、无数据库的实际Yii视图渲染，覆盖新增及已有统计页。
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

new yii\web\Application([
    'id' => 'omission-view-test',
    'basePath' => dirname(__DIR__).'/backend',
    'components' => [
        'request' => [
            'cookieValidationKey' => 'synthetic-view-test',
            'scriptFile' => __FILE__,
            'scriptUrl' => '/index.php',
            'hostInfo' => 'http://localhost',
            'url' => '/ssc-static-yl/index',
        ],
        'urlManager' => ['enablePrettyUrl' => true, 'showScriptName' => false],
        'assetManager' => ['basePath' => __DIR__, 'baseUrl' => '/assets', 'bundles' => [
            'yii\web\JqueryAsset' => false,
            'yii\web\YiiAsset' => false,
            'yii\grid\GridViewAsset' => false,
        ]],
    ],
]);

$controller = new yii\web\Controller('ssc-static-yl', Yii::$app);
Yii::$app->controller = $controller;
$cases = [
    [backend\service\statics\yl\ThreeComboYlService::class, 11, '三字复式', 120],
    [backend\service\statics\yl\FiveComboYlService::class, 12, '五字复式', 252],
    [backend\service\statics\yl\FiveAllReverseYlService::class, 13, '五字全倒', 252],
    [backend\service\statics\yl\ThreeDistinctYlService::class, 14, '三字不重复', 120],
    [backend\service\statics\yl\ThreeBrothersYlService::class, 15, '三兄弟', 10],
    [backend\service\statics\yl\FourSameYlService::class, 16, '四重', 10],
    [backend\service\statics\yl\TwoComboYlService::class, 17, '二字复式', 45],
    [backend\service\statics\yl\ThreeSameYlService::class, 18, '三重', 10],
];
foreach ($cases as [$service, $codeType, $label, $count]) {
    $html = $controller->view->renderFile(dirname(__DIR__).'/backend/modules/forum/views/ssc-static-yl/three_combo.php', [
        'lottery_types' => [],
        'lottery_type' => 8,
        'code_type' => $codeType,
        'combo_type' => $service::TYPE,
        'periods' => 2000,
        'codeFilter' => '',
        'dataProvider' => new yii\data\ArrayDataProvider([
            'allModels' => $service::calculate([
                ['qihao' => 'test-1', 'code1' => 0, 'code2' => 1, 'code3' => 2, 'code4' => 3],
            ]),
            'pagination' => false,
            'sort' => false,
        ]),
    ], $controller);
    if (strpos($html, $label.'遗漏') === false
        || substr_count($html, 'data-key=') !== $count
        || strpos($html, '>四重</a>') === false
        || strpos($html, '>二字复式</a>') === false
        || strpos($html, 'SscStaticYl%5Btype%5D=13') === false
        || strpos($html, '≥2000') === false) {
        throw new RuntimeException($label.' view render failed');
    }
}
echo "combo_yl_view_regression: OK (8 tabs)\n";
