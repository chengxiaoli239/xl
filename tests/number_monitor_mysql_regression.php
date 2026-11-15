<?php

// Run only against an isolated, disposable database named monitor_test.
$dsn = getenv('NUMBER_MONITOR_TEST_DSN') ?: '';
if (!preg_match('/(?:^|;)dbname=monitor_test(?:;|$)/', $dsn)) {
    fwrite(STDERR, "Set NUMBER_MONITOR_TEST_DSN to an isolated monitor_test database.\n");
    exit(2);
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__) . '/common/config/bootstrap.php';
require dirname(__DIR__) . '/console/migrations/m261115_000001_create_number_monitor_tables.php';
require dirname(__DIR__) . '/console/migrations/m261115_000002_add_occurrence_count_to_number_monitor_patterns.php';

use backend\models\NumberMonitorFile;
use backend\models\NumberMonitorPattern;
use backend\models\NumberMonitorScheme;
use backend\service\NumberMonitorService;
use common\service\numberMonitor\NumberSchemeParser;
use yii\db\Query;

function checkMonitor($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$app = new yii\console\Application([
    'id' => 'number-monitor-mysql-test',
    'basePath' => dirname(__DIR__) . '/console',
    'runtimePath' => sys_get_temp_dir(),
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => $dsn,
            'username' => getenv('NUMBER_MONITOR_TEST_USER') ?: 'root',
            'password' => getenv('NUMBER_MONITOR_TEST_PASSWORD') ?: '',
            'tablePrefix' => 'lt_',
            'charset' => 'utf8mb4',
        ],
    ],
]);
$db = $app->db;
$db->createCommand()->createTable('{{%ssc_kj_data}}', [
    'id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
    'lottery_type' => 'SMALLINT NOT NULL',
    'qihao' => 'VARCHAR(24) NOT NULL',
    'index_id' => 'BIGINT NOT NULL',
    'kj_code' => 'VARCHAR(8) NOT NULL',
    'code_4n_str' => 'VARCHAR(24) NOT NULL',
    'date' => 'DATE NOT NULL',
])->execute();
$migration = new m261115_000001_create_number_monitor_tables();
$migration->safeUp();
$migration = new m261115_000002_add_occurrence_count_to_number_monitor_patterns();
$migration->safeUp();

function addMonitorDraw($db, $qihao, $code)
{
    $db->createCommand()->insert('{{%ssc_kj_data}}', [
        'lottery_type' => 8,
        'qihao' => $qihao,
        'index_id' => (int)$qihao,
        'kj_code' => $code,
        'code_4n_str' => implode(',', str_split(substr($code, 0, 4))),
        'date' => '2026-11-15',
    ])->execute();
}

function monitorStreaks($fileId)
{
    $rows = NumberMonitorScheme::find()->select(['current_status', 'streak_count'])
        ->where(['file_id' => $fileId])->orderBy(['line_no' => SORT_ASC])->asArray()->all();
    return array_map(function ($row) {
        return ['current_status' => (int)$row['current_status'], 'streak_count' => (int)$row['streak_count']];
    }, $rows);
}

addMonitorDraw($db, '1001', '99999');
$schemes = NumberSchemeParser::parse("1234 4567 8923\n\n1,3,5,6 0,3,6,1 9,1,4,5\n\n12XX 23XX 89XX\n\n234X 890X 234X 892X 235X");
$created = NumberMonitorService::createFile(123, 8, 'sample.txt', $schemes);
$fileId = $created['id'];
checkMonitor($created['scheme_count'] === 4 && $created['pattern_count'] === 14, 'import counts are incorrect');
checkMonitor((int)NumberMonitorPattern::find()->count() === 13, 'patterns were duplicated during import');
checkMonitor((int)NumberMonitorPattern::find()->where(['pattern' => '234X'])->sum('occurrence_count') === 2,
    'duplicate pattern occurrence was not preserved');
$schemeId = (int)NumberMonitorScheme::find()->select('id')->where(['file_id' => $fileId, 'line_no' => 4])->scalar();
$schemePage = NumberMonitorService::schemePatterns(123, $schemeId, 1);
checkMonitor($schemePage['pattern_count'] === 5 && count($schemePage['patterns']) === 4
    && $schemePage['patterns'][0]['pattern'] === '234X'
    && (int)$schemePage['patterns'][0]['occurrence_count'] === 2
    && $schemePage['has_more'] === false, 'scheme detail lost order or duplicate counts');
try {
    NumberMonitorService::schemePatterns(456, $schemeId, 1);
    throw new RuntimeException('another account was able to read scheme patterns');
} catch (InvalidArgumentException $expected) {
}
checkMonitor(NumberMonitorService::dashboardData(456, 8)['files'] === [], 'files leaked across accounts');
try {
    NumberMonitorService::setFileStatus(456, $fileId, false);
    throw new RuntimeException('another account was able to pause the file');
} catch (InvalidArgumentException $expected) {
}
try {
    NumberMonitorService::deleteFile(456, $fileId);
    throw new RuntimeException('another account was able to clear the file');
} catch (InvalidArgumentException $expected) {
}

addMonitorDraw($db, '1002', '12345');
checkMonitor(NumberMonitorService::processPendingDraws(8) === 1, 'first draw not processed');
checkMonitor(monitorStreaks($fileId) === [
    ['current_status' => 1, 'streak_count' => 1],
    ['current_status' => 2, 'streak_count' => 1],
    ['current_status' => 1, 'streak_count' => 1],
    ['current_status' => 2, 'streak_count' => 1],
], 'first draw matching is incorrect');

addMonitorDraw($db, '1003', '12346');
checkMonitor(NumberMonitorService::processPendingDraws(8) === 1, 'second draw not processed');
checkMonitor((int)monitorStreaks($fileId)[0]['streak_count'] === 2, 'consecutive hits were not counted');
addMonitorDraw($db, '1004', '98765');
checkMonitor(NumberMonitorService::processPendingDraws(8) === 1, 'third draw not processed');
checkMonitor((int)monitorStreaks($fileId)[0]['streak_count'] === 1
    && (int)monitorStreaks($fileId)[1]['streak_count'] === 3, 'miss streak transition is incorrect');

NumberMonitorService::setFileStatus(123, $fileId, false);
$paused = monitorStreaks($fileId);
addMonitorDraw($db, '1005', '12345');
checkMonitor(monitorStreaks($fileId) === $paused, 'paused file changed during draw processing');
NumberMonitorService::setFileStatus(123, $fileId, true);
checkMonitor((int)monitorStreaks($fileId)[0]['current_status'] === 0, 'resume did not reset streak');
checkMonitor(NumberMonitorService::dashboardData(123, 8)['processed_draw_id']
    === NumberMonitorService::dashboardData(123, 8)['latest_draw_id'], 'resume replayed paused draws');
addMonitorDraw($db, '1006', '12345');
NumberMonitorService::processPendingDraws(8);
checkMonitor((int)monitorStreaks($fileId)[0]['streak_count'] === 1, 'resumed file did not count next draw');
checkMonitor(NumberMonitorService::processPendingDraws(8) === 0, 'draw processed twice');

NumberMonitorService::deleteFile(123, $fileId);
checkMonitor((int)NumberMonitorFile::find()->count() === 0
    && (int)NumberMonitorScheme::find()->count() === 0
    && (int)NumberMonitorPattern::find()->count() === 0, 'clear left monitor rows behind');
addMonitorDraw($db, '1007', '99999');

$bulkSchemes = [];
for ($i = 0; $i < 10; $i++) {
    $patterns = [];
    for ($number = 0; $number < 10; $number++) {
        $patterns[] = str_pad((string)$number, 4, '0', STR_PAD_LEFT);
    }
    $bulkSchemes[] = ['patterns' => $patterns];
}
for ($file = 0; $file < 100; $file++) {
    NumberMonitorService::createFile(123, 8, 'bulk-' . $file . '.txt', $bulkSchemes);
}
checkMonitor(NumberMonitorService::dashboardData(123, 8)['processed_draw_id']
    === NumberMonitorService::dashboardData(123, 8)['latest_draw_id'], 'new file replayed draws from inactive period');
addMonitorDraw($db, '1008', '00000');
$startedAt = microtime(true);
checkMonitor(NumberMonitorService::processPendingDraws(8) === 1, 'bulk draw not processed');
$elapsedMs = round((microtime(true) - $startedAt) * 1000);
checkMonitor((int)NumberMonitorScheme::find()->where(['current_status' => 1, 'streak_count' => 1])->count() === 1000,
    'bulk monitor did not update all 1000 schemes');

$allFourDigitPatterns = [];
for ($number = 0; $number < 10000; $number++) {
    $allFourDigitPatterns[] = str_pad((string)$number, 4, '0', STR_PAD_LEFT);
}
$largeFileSchemes = array_fill(0, 3, ['patterns' => $allFourDigitPatterns]);
$largeFile = NumberMonitorService::createFile(124, 8, 'large.txt', $largeFileSchemes);
$largeSchemeId = (int)NumberMonitorScheme::find()->select('id')
    ->where(['file_id' => $largeFile['id'], 'line_no' => 1])->scalar();
$firstPage = NumberMonitorService::schemePatterns(124, $largeSchemeId, 1);
$lastPage = NumberMonitorService::schemePatterns(124, $largeSchemeId, 25);
checkMonitor(count($firstPage['patterns']) === 400 && $firstPage['has_more']
    && count($lastPage['patterns']) === 400 && !$lastPage['has_more'],
    'large scheme detail pagination is incorrect');
$storedLargePatterns = (new Query())->from(NumberMonitorPattern::tableName() . ' p')
    ->innerJoin(NumberMonitorScheme::tableName() . ' s', 's.id = p.scheme_id')
    ->where(['s.file_id' => $largeFile['id']])->count();
checkMonitor($largeFile['pattern_count'] === 30000 && (int)$storedLargePatterns === 30000,
    'large multi-scheme import exceeded the former file pattern limit');

$repeatedScheme = ['patterns' => array_fill(0, 10000, '1234')];
$additionalFile = NumberMonitorService::createFile(124, 8, 'additional.txt', array_fill(0, 15, $repeatedScheme));
checkMonitor($additionalFile['scheme_count'] === 15 && $additionalFile['pattern_count'] === 150000,
    'account import exceeded the former 100000 pattern limit');
checkMonitor((int)NumberMonitorScheme::find()->where(['uid' => 124])->sum('pattern_count') === 180000,
    'account pattern total did not preserve duplicate occurrences');

echo "number_monitor_mysql_regression: OK (1000 schemes, 10000 active patterns, duplicate counts and 180000 imported patterns, draw {$elapsedMs} ms)\n";
