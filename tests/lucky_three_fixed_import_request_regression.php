<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

use backend\service\Lucky5\Lucky5Service;
use backend\service\Lucky5\LuckyBaseService;

function sameThreeFixed($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.' expected='.var_export($expected, true).' actual='.var_export($actual, true));
    }
}

// 合成号码仅校验服务器生成的本地下注任务参数，不连接盘口。
$codes = ['224X', '324X'];
sameThreeFixed(102, Lucky5Service::getWayForPlan(19, 2), '导入三定应使用盘口三定投注方式');
sameThreeFixed(30, LuckyBaseService::getNumType(19, 2, $codes), '导入三定号码类别');
$condition = json_decode(Lucky5Service::getOperationCondition(19, 2), true);
sameThreeFixed(30, $condition['numberType'], '三定操作条件的号码类别');
sameThreeFixed([], $condition['remainValueRanges'], '三定操作条件不沿用四定合分范围');
sameThreeFixed([0, 0, 0, 0], $condition['symbolPositions'], '三定操作条件保留四个位置');
sameThreeFixed(0, $condition['remainMatchFilterThree'], '三定操作条件保留三数匹配开关');
sameThreeFixed([], $condition['remainMatchNumbersThree'], '三定操作条件保留三数匹配列表');
sameThreeFixed('[三定位]', Lucky5Service::getBetLogForPlan(19, 2), '三定记录说明');
sameThreeFixed(1, substr_count($codes[0], 'X'), '三定号码形态');

foreach ([[19, 3, 108, 40], [20, 3, 102, 40], [30, 1, 102, 20], [29, 2, 102, 30]] as $case) {
    sameThreeFixed($case[2], Lucky5Service::getWayForPlan($case[0], $case[1]), '其它玩法方式不变');
    sameThreeFixed($case[3], LuckyBaseService::getNumType($case[0], $case[1], $codes), '其它玩法号码类别不变');
}
$fourCondition = json_decode(Lucky5Service::getOperationCondition(19, 3), true);
sameThreeFixed(40, $fourCondition['numberType'], '导入四定仍使用原操作条件');
sameThreeFixed([30, 35], $fourCondition['remainValueRanges'], '导入四定原合分范围不变');

echo "lucky_three_fixed_import_request_regression: OK\n";
