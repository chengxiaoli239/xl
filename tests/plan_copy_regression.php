<?php

require dirname(__DIR__) . '/backend/service/PlanCopyService.php';

use backend\service\PlanCopyService;

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true));
    }
}

$source = [
    'id' => 22079,
    'uid' => 6,
    'account' => 'aa06',
    'status' => 1,
    'is_parent' => 1,
    'children_plan_id' => '22080',
    'tz_sites' => '2,3',
    'single' => '2.5',
    'singles' => '1,2,4',
    'current_profits' => '-88.5',
    'created_at' => 100,
    'updated_at' => 200,
    'update_time' => '2026-01-01 00:00:00',
    'hz_Arr' => json_encode([
        'codes' => '123',
        'current_miss' => 46,
        'history_max_miss' => 80,
        'singles_key' => 3,
        'betStatus' => 1,
        'areaBetStatus' => 1,
        'current_area_profits' => -10,
        'area_msg' => 'runtime',
        'A_x_B_y_start_time' => '2026-09-20 10:00:00',
        'filters' => ['filter_type' => 2],
    ], JSON_UNESCAPED_UNICODE),
];

$copy = PlanCopyService::preparePlanAttributes($source, 66, 'aa66', '7,8', 1700000000);
assertSameValue(null, $copy['id'] ?? null, '复制计划不能保留源计划ID');
assertSameValue(66, $copy['uid'], '复制计划应替换目标UID');
assertSameValue('aa66', $copy['account'], '复制计划应替换目标账号');
assertSameValue(0, $copy['status'], '复制计划默认关闭');
assertSameValue(0, $copy['is_parent'], '复制计划不能继承父计划关系');
assertSameValue('', $copy['children_plan_id'], '复制计划不能继承子计划关系');
assertSameValue('7,8', $copy['tz_sites'], '复制计划应使用目标账号可用站点');
assertSameValue('2.5', $copy['single'], '计划倍数应保留');
assertSameValue('1,2,4', $copy['singles'], '倍投梯度应保留');
assertSameValue(0, $copy['current_profits'], '复制计划不能继承当前盈利');
assertSameValue(1700000000, $copy['created_at'], '复制计划应使用新的创建时间');
assertSameValue(1700000000, $copy['updated_at'], '复制计划应使用新的更新时间');
assertSameValue('2023-11-14 22:13:20', $copy['update_time'], '更新时间应与时间戳一致');

$hz = json_decode($copy['hz_Arr'], true);
assertSameValue('123', $hz['codes'], '计划配置应保留');
assertSameValue(0, $hz['current_miss'], '当前遗漏运行值应重置');
assertSameValue(0, $hz['history_max_miss'], '历史最大遗漏运行值应重置');
assertSameValue(0, $hz['singles_key'], '倍投运行索引应重置');
assertSameValue(0, $hz['betStatus'], '投注运行状态应重置');
assertSameValue(0, $hz['areaBetStatus'], '区间投注运行状态应重置');
assertSameValue(0, $hz['current_area_profits'], '区间运行盈利应重置');
assertSameValue('', $hz['area_msg'], '区间运行描述应重置');
assertSameValue('', $hz['A_x_B_y_start_time'], 'AB计划运行起点应重置');
assertSameValue(['filter_type' => 2], $hz['filters'], '过滤配置应保留');

$rawCopy = PlanCopyService::preparePlanAttributes(['hz_Arr' => 'plain-config'], 66, 'aa66', '7', 1700000000);
assertSameValue('plain-config', $rawCopy['hz_Arr'], '非JSON扩展配置应原样保留');

$import = PlanCopyService::prepareImportCodeAttributes([
    'id' => 9,
    'uid' => 6,
    'plan_id' => 22079,
    'plan_id_sort_key' => '0',
    'codes' => '1,2,3@4,5,6',
    'status' => 1,
], 66, 22100, 1700000000);
assertSameValue(null, $import['id'] ?? null, '复制导入号码不能保留源ID');
assertSameValue(66, $import['uid'], '导入号码应替换目标UID');
assertSameValue(22100, $import['plan_id'], '导入号码应关联新计划');
assertSameValue('0', $import['plan_id_sort_key'], '导入号码顺序应保留');
assertSameValue('1,2,3@4,5,6', $import['codes'], '导入号码内容应保留');
assertSameValue(1, $import['status'], '导入号码状态应保留');

assertSameValue('2,3', PlanCopyService::resolveTargetSites('1,2,3', [2, 3], [2, 4]), '应优先使用源站点与目标可用站点交集');
assertSameValue('2,4', PlanCopyService::resolveTargetSites('1,3', [], [2, 4]), '无启用站点时应使用授权站点');
assertSameValue('', PlanCopyService::resolveTargetSites('1,3', [], [2, 4], true), '真实计划没有启用盘口时应拒绝复制');

echo "plan_copy_regression: OK\n";
