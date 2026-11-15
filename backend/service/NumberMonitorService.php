<?php

namespace backend\service;

use backend\models\NumberMonitorCursor;
use backend\models\NumberMonitorFile;
use backend\models\NumberMonitorPattern;
use backend\models\NumberMonitorScheme;
use backend\models\SscKjData;
use common\service\lottery\CommonLotteryService;
use common\service\numberMonitor\NumberSchemeParser;
use Yii;
use yii\db\Expression;
use yii\db\Query;

class NumberMonitorService
{
    const MAX_FILES_PER_USER = 100;
    const MAX_SCHEMES_PER_USER = 2000;
    const MAX_PATTERNS_PER_USER = 2000000;
    const MAX_ACTIVE_SCHEMES_PER_LOTTERY = 10000;
    const PROCESS_BATCH_SIZE = 1;
    const UPDATE_BATCH_SIZE = 400;
    const SUPPORTED_LOTTERY_TYPES = [
        CommonLotteryService::LOTTERY_TYPE_LUCKY5,
        CommonLotteryService::LOTTERY_TYPE_AOZHOU5,
    ];

    public static function supportedLotteryType($lotteryType): bool
    {
        return in_array((int)$lotteryType, self::SUPPORTED_LOTTERY_TYPES, true);
    }

    public static function lotteryTypeOptions(): array
    {
        return array_intersect_key(CommonLotteryService::LOTTERY_TYPE_OPTIONS, array_flip(self::SUPPORTED_LOTTERY_TYPES));
    }

    public static function createFile(int $uid, int $lotteryType, string $name, array $schemes): array
    {
        if ($uid <= 0 || !self::supportedLotteryType($lotteryType)) {
            throw new \InvalidArgumentException('彩种参数无效');
        }
        if (!$schemes || count($schemes) > NumberSchemeParser::MAX_SCHEMES_PER_FILE) {
            throw new \InvalidArgumentException('文件方案数量无效');
        }
        $name = trim(basename(str_replace('\\', '/', $name)));
        if ($name === '' || strlen($name) > 180) {
            $name = '号码方案.txt';
        }

        $totalPatterns = 0;
        foreach ($schemes as $index => $scheme) {
            if (empty($scheme['patterns']) || !is_array($scheme['patterns'])
                || count($scheme['patterns']) > NumberSchemeParser::MAX_PATTERNS_PER_SCHEME) {
                throw new \InvalidArgumentException('文件中包含无效方案');
            }
            $fixedCount = null;
            $normalizedPatterns = [];
            $patternCounts = [];
            $samplePatterns = array_slice($scheme['patterns'], 0, 8);
            foreach ($scheme['patterns'] as $pattern) {
                if (!is_string($pattern) || !preg_match('/^[0-9X]{4}$/', strtoupper($pattern))) {
                    throw new \InvalidArgumentException('文件中包含无效号码');
                }
                $pattern = strtoupper($pattern);
                $patternFixedCount = strlen(str_replace('X', '', $pattern));
                if ($patternFixedCount < 2) {
                    throw new \InvalidArgumentException('仅支持二定、三定和四定号码');
                }
                if ($fixedCount !== null && $fixedCount !== $patternFixedCount) {
                    throw new \InvalidArgumentException('每套方案中的号码需要使用相同定数');
                }
                $fixedCount = $patternFixedCount;
                if (!isset($patternCounts[$pattern])) {
                    $normalizedPatterns[] = $pattern;
                    $patternCounts[$pattern] = 0;
                }
                $patternCounts[$pattern]++;
            }
            $patternEntryCount = count($scheme['patterns']);
            $schemes[$index]['patterns'] = $normalizedPatterns;
            $schemes[$index]['pattern_counts'] = $patternCounts;
            $schemes[$index]['sample_patterns'] = $samplePatterns;
            $schemes[$index]['pattern_count'] = $patternEntryCount;
            $totalPatterns += $patternEntryCount;
        }
        if ($totalPatterns > NumberSchemeParser::MAX_PATTERNS_PER_FILE) {
            throw new \InvalidArgumentException('单个文件不能超过 150,000 组号码');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $latestDrawId = self::latestDrawId($lotteryType);
            $cursor = self::lockOrCreateCursor($lotteryType, $latestDrawId);
            if (!self::hasActiveFile($lotteryType) && (int)$cursor->last_draw_id < $latestDrawId) {
                $cursor->last_draw_id = $latestDrawId;
                $cursor->updated_at = time();
                $cursor->save(false);
            }

            $fileCount = (int)NumberMonitorFile::find()->where(['uid' => $uid])->count();
            $schemeCount = (int)(new Query())->from(NumberMonitorScheme::tableName() . ' s')
                ->innerJoin(NumberMonitorFile::tableName() . ' f', 'f.id = s.file_id')
                ->where(['f.uid' => $uid])->count();
            $patternCount = (int)NumberMonitorScheme::find()->where(['uid' => $uid])->sum('pattern_count');
            if ($fileCount >= self::MAX_FILES_PER_USER) {
                throw new \InvalidArgumentException('账号最多支持 100 个监控文件');
            }
            if ($schemeCount + count($schemes) > self::MAX_SCHEMES_PER_USER) {
                throw new \InvalidArgumentException('账号最多支持 2,000 套监控方案');
            }
            if ($patternCount + $totalPatterns > self::MAX_PATTERNS_PER_USER) {
                throw new \InvalidArgumentException('账号监控号码累计不能超过 2,000,000 组');
            }
            $activeSchemeCount = (int)(new Query())->from(NumberMonitorScheme::tableName() . ' s')
                ->innerJoin(NumberMonitorFile::tableName() . ' f', 'f.id = s.file_id')
                ->where(['f.lottery_type' => $lotteryType, 'f.status' => 1])->count();
            if ($activeSchemeCount + count($schemes) > self::MAX_ACTIVE_SCHEMES_PER_LOTTERY) {
                throw new \InvalidArgumentException('该彩种的监控方案数量已达上限');
            }

            $now = time();
            $file = new NumberMonitorFile();
            $file->setAttributes([
                'uid' => $uid,
                'lottery_type' => $lotteryType,
                'name' => $name,
                'status' => 1,
                'scheme_count' => count($schemes),
                'created_at' => $now,
                'updated_at' => $now,
            ], false);
            if (!$file->save(false)) {
                throw new \RuntimeException('无法保存监控文件');
            }

            $patternRows = [];
            // Bound each SQL statement; repeated numbers have already been aggregated per scheme.
            $insertPatternBatch = function () use (&$patternRows) {
                if (!$patternRows) {
                    return;
                }
                Yii::$app->db->createCommand()->batchInsert(
                    NumberMonitorPattern::tableName(),
                    ['scheme_id', 'pattern', 'occurrence_count'],
                    $patternRows
                )->execute();
                $patternRows = [];
            };
            foreach ($schemes as $index => $scheme) {
                $firstPattern = reset($scheme['patterns']);
                $fixedCount = strlen(str_replace('X', '', $firstPattern));
                $model = new NumberMonitorScheme();
                $model->setAttributes([
                    'file_id' => $file->id,
                    'uid' => $uid,
                    'lottery_type' => $lotteryType,
                    'line_no' => $index + 1,
                    'title' => '方案 ' . ($index + 1) . ' · ' . $fixedCount . ' 定',
                    'sample' => implode(' ', $scheme['sample_patterns']) . ($scheme['pattern_count'] > 8 ? ' …' : ''),
                    'fixed_count' => $fixedCount,
                    'pattern_count' => $scheme['pattern_count'],
                    'started_draw_id' => $latestDrawId,
                    'last_draw_id' => $latestDrawId,
                    'current_status' => NumberMonitorScheme::STATUS_NONE,
                    'streak_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], false);
                if (!$model->save(false)) {
                    throw new \RuntimeException('无法保存监控方案');
                }
                foreach ($scheme['patterns'] as $pattern) {
                    $patternRows[] = [$model->id, strtoupper($pattern), $scheme['pattern_counts'][$pattern]];
                    if (count($patternRows) >= 500) {
                        $insertPatternBatch();
                    }
                }
            }
            $insertPatternBatch();

            $transaction->commit();
            // A draw can arrive while the first file is being imported, before its queue hook sees it.
            try {
                if (self::hasPendingDraws($lotteryType)) {
                    push_queue_monitor(\common\service\jobs\number_monitor\ProcessNumberMonitorDrawJob::class, [
                        'lotteryType' => $lotteryType,
                        'business_id' => 'import-' . $file->id,
                    ]);
                }
            } catch (\Throwable $queueException) {
                Yii::warning('号码监控导入后补发队列失败：' . $queueException->getMessage(), 'number-monitor');
            }
            return ['id' => (int)$file->id, 'scheme_count' => count($schemes), 'pattern_count' => $totalPatterns];
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public static function setFileStatus(int $uid, int $fileId, bool $active): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $file = NumberMonitorFile::find()->where(['id' => $fileId, 'uid' => $uid])->one();
            if (!$file) {
                throw new \InvalidArgumentException('监控文件不存在');
            }
            $latestDrawId = self::latestDrawId((int)$file->lottery_type);
            $cursor = self::lockOrCreateCursor((int)$file->lottery_type, $latestDrawId);
            if ($active && (int)$file->status === 0) {
                if (!self::hasActiveFile((int)$file->lottery_type) && (int)$cursor->last_draw_id < $latestDrawId) {
                    $cursor->last_draw_id = $latestDrawId;
                    $cursor->updated_at = time();
                    $cursor->save(false);
                }
                NumberMonitorScheme::updateAll(['started_draw_id' => $latestDrawId, 'last_draw_id' => $latestDrawId,
                    'last_qihao' => null, 'current_status' => NumberMonitorScheme::STATUS_NONE,
                    'streak_count' => 0, 'updated_at' => time()], ['file_id' => $file->id]);
            }
            $file->status = $active ? 1 : 0;
            $file->updated_at = time();
            $file->save(false);
            $transaction->commit();
            return true;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public static function deleteFile(int $uid, int $fileId): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $file = NumberMonitorFile::findOne(['id' => $fileId, 'uid' => $uid]);
            if (!$file) {
                throw new \InvalidArgumentException('监控文件不存在');
            }
            $schemeIds = NumberMonitorScheme::find()->select('id')->where(['file_id' => $fileId])->column();
            if ($schemeIds) {
                NumberMonitorPattern::deleteAll(['scheme_id' => $schemeIds]);
                NumberMonitorScheme::deleteAll(['id' => $schemeIds]);
            }
            $file->delete();
            $transaction->commit();
            return true;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public static function dashboardData(int $uid, int $lotteryType): array
    {
        $files = NumberMonitorFile::find()->select(['id', 'name', 'status', 'scheme_count', 'lottery_type'])
            ->where(['uid' => $uid, 'lottery_type' => $lotteryType])
            ->orderBy(['id' => SORT_ASC])->asArray()->all();
        $fileIds = array_column($files, 'id');
        $schemes = $fileIds ? NumberMonitorScheme::find()
            ->select(['id', 'file_id', 'line_no', 'title', 'fixed_count', 'pattern_count', 'sample',
                'last_qihao', 'current_status', 'streak_count', 'updated_at'])
            ->where(['file_id' => $fileIds])
            ->orderBy(['file_id' => SORT_ASC, 'line_no' => SORT_ASC])->asArray()->all() : [];
        $schemesByFile = [];
        foreach ($schemes as $scheme) {
            $schemesByFile[$scheme['file_id']][] = $scheme;
        }
        foreach ($files as &$file) {
            $file['schemes'] = $schemesByFile[$file['id']] ?? [];
        }
        unset($file);

        $draws = SscKjData::find()->select(['id', 'index_id', 'qihao', 'kj_code', 'code_4n_str', 'date'])
            ->where(['lottery_type' => $lotteryType])->orderBy(['id' => SORT_DESC])->limit(30)->asArray()->all();
        $cursor = NumberMonitorCursor::find()->where(['lottery_type' => $lotteryType])->asArray()->one();
        return [
            'files' => $files,
            'draws' => $draws,
            'processed_draw_id' => (int)($cursor['last_draw_id'] ?? 0),
            'latest_draw_id' => (int)($draws[0]['id'] ?? 0),
            'updated_at' => (int)($cursor['updated_at'] ?? 0),
            'server_now' => time(),
        ];
    }

    public static function schemePatterns(int $uid, int $schemeId, int $page): array
    {
        if ($uid <= 0 || $schemeId <= 0 || $page < 1 || $page > 50) {
            throw new \InvalidArgumentException('监控方案或页码无效');
        }
        $scheme = (new Query())->from(NumberMonitorScheme::tableName() . ' s')
            ->innerJoin(NumberMonitorFile::tableName() . ' f', 'f.id = s.file_id')
            ->select(['s.id', 's.title', 's.pattern_count'])
            ->where(['s.id' => $schemeId, 'f.uid' => $uid])->one();
        if (!$scheme) {
            throw new \InvalidArgumentException('监控方案不存在');
        }

        $pageSize = 400;
        $patterns = NumberMonitorPattern::find()->select(['pattern', 'occurrence_count'])
            ->where(['scheme_id' => $schemeId])->orderBy(['id' => SORT_ASC])
            ->offset(($page - 1) * $pageSize)->limit($pageSize + 1)->asArray()->all();
        $hasMore = count($patterns) > $pageSize;
        if ($hasMore) {
            array_pop($patterns);
        }
        return [
            'id' => (int)$scheme['id'],
            'title' => $scheme['title'],
            'pattern_count' => (int)$scheme['pattern_count'],
            'page' => $page,
            'has_more' => $hasMore,
            'patterns' => $patterns,
        ];
    }

    public static function schemeHistory(int $uid, int $schemeId, int $beforeDrawId = 0): array
    {
        if ($uid <= 0 || $schemeId <= 0 || $beforeDrawId < 0) {
            throw new \InvalidArgumentException('监控方案或历史游标无效');
        }
        $scheme = (new Query())->from(NumberMonitorScheme::tableName() . ' s')
            ->innerJoin(NumberMonitorFile::tableName() . ' f', 'f.id = s.file_id')
            ->select(['s.id', 's.title', 's.lottery_type', 's.started_draw_id', 's.last_draw_id'])
            ->where(['s.id' => $schemeId, 'f.uid' => $uid])->one();
        if (!$scheme) {
            throw new \InvalidArgumentException('监控方案不存在');
        }
        if ((int)$scheme['last_draw_id'] <= (int)$scheme['started_draw_id']) {
            return ['title' => (string)$scheme['title'], 'has_more' => false,
                'next_before_id' => 0, 'records' => []];
        }

        $drawQuery = SscKjData::find()->select(['id', 'qihao', 'code_4n_str'])
            ->where(['lottery_type' => (int)$scheme['lottery_type']])
            ->andWhere(['>', 'id', (int)$scheme['started_draw_id']])
            ->andWhere(['<=', 'id', (int)$scheme['last_draw_id']]);
        if ($beforeDrawId > 0) {
            $drawQuery->andWhere(['<', 'id', $beforeDrawId]);
        }
        $draws = $drawQuery->orderBy(['id' => SORT_DESC])->limit(51)->asArray()->all();
        $hasMore = count($draws) > 50;
        if ($hasMore) {
            array_pop($draws);
        }

        $candidatePatterns = [];
        foreach ($draws as $draw) {
            $code = preg_replace('/\D/', '', (string)$draw['code_4n_str']);
            if (preg_match('/^[0-9]{4}$/', $code)) {
                foreach (self::candidatePatterns($code) as $pattern) {
                    $candidatePatterns[$pattern] = true;
                }
            }
        }
        $matchedPatterns = $candidatePatterns ? NumberMonitorPattern::find()
            ->select('pattern')
            ->where(['scheme_id' => $schemeId, 'pattern' => array_keys($candidatePatterns)])
            ->column() : [];
        $matchedLookup = array_fill_keys($matchedPatterns, true);
        $records = [];
        foreach ($draws as $draw) {
            $code = preg_replace('/\D/', '', (string)$draw['code_4n_str']);
            $validCode = preg_match('/^[0-9]{4}$/', $code) === 1;
            $hit = false;
            if ($validCode) {
                foreach (self::candidatePatterns($code) as $pattern) {
                    if (isset($matchedLookup[$pattern])) {
                        $hit = true;
                        break;
                    }
                }
            }
            $records[] = [
                'id' => (int)$draw['id'],
                'qihao' => (string)$draw['qihao'],
                'code' => $validCode ? $code : '',
                'status' => $validCode ? ($hit ? NumberMonitorScheme::STATUS_HIT : NumberMonitorScheme::STATUS_MISS) : 0,
            ];
        }

        return [
            'title' => (string)$scheme['title'],
            'has_more' => $hasMore,
            'next_before_id' => $records ? (int)end($records)['id'] : 0,
            'records' => $records,
        ];
    }

    public static function processPendingDraws(int $lotteryType): int
    {
        if (!self::supportedLotteryType($lotteryType)) {
            return 0;
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $latestDrawId = self::latestDrawId($lotteryType);
            $cursor = self::lockOrCreateCursor($lotteryType, $latestDrawId);
            if (!self::hasActiveFile($lotteryType)) {
                $cursor->last_draw_id = $latestDrawId;
                $cursor->updated_at = time();
                $cursor->save(false);
                $transaction->commit();
                return 0;
            }
            $draws = SscKjData::find()->where(['lottery_type' => $lotteryType])
                ->andWhere(['>', 'id', $cursor->last_draw_id])->orderBy(['id' => SORT_ASC])
                ->limit(self::PROCESS_BATCH_SIZE)->all();
            foreach ($draws as $draw) {
                self::processDraw($lotteryType, $draw);
                $cursor->last_draw_id = $draw->id;
                $cursor->updated_at = time();
                $cursor->save(false);
            }
            $transaction->commit();
            return count($draws);
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public static function hasActiveMonitoringFiles(int $lotteryType): bool
    {
        return self::supportedLotteryType($lotteryType) && self::hasActiveFile($lotteryType);
    }

    public static function latestDrawIdForLottery(int $lotteryType): int
    {
        return self::supportedLotteryType($lotteryType) ? self::latestDrawId($lotteryType) : 0;
    }

    public static function processedDrawIdForLottery(int $lotteryType): int
    {
        $cursor = NumberMonitorCursor::find()->select('last_draw_id')
            ->where(['lottery_type' => $lotteryType])->scalar();
        return $cursor === false ? 0 : (int)$cursor;
    }

    public static function hasPendingDraws(int $lotteryType): bool
    {
        $cursor = NumberMonitorCursor::find()->where(['lottery_type' => $lotteryType])->one();
        if (!$cursor) {
            return false;
        }
        return SscKjData::find()->where(['lottery_type' => $lotteryType])
            ->andWhere(['>', 'id', $cursor->last_draw_id])->exists();
    }

    private static function processDraw(int $lotteryType, SscKjData $draw): void
    {
        $drawCode = preg_replace('/\D/', '', (string)$draw->code_4n_str);
        if (!preg_match('/^[0-9]{4}$/', $drawCode)) {
            return;
        }

        $candidatePatterns = self::candidatePatterns($drawCode);
        $hitSchemeIds = (new Query())->select('p.scheme_id')
            ->distinct()
            ->from(NumberMonitorPattern::tableName() . ' p')
            ->innerJoin(NumberMonitorScheme::tableName() . ' s', 's.id = p.scheme_id')
            ->innerJoin(NumberMonitorFile::tableName() . ' f', 'f.id = s.file_id')
            ->where(['f.lottery_type' => $lotteryType, 'f.status' => 1, 'p.pattern' => $candidatePatterns])
            ->andWhere(['<', 's.started_draw_id', $draw->id])
            ->column();
        $hitLookup = array_fill_keys(array_map('intval', $hitSchemeIds), true);

        $schemes = NumberMonitorScheme::find()->alias('s')
            ->innerJoin(NumberMonitorFile::tableName() . ' f', 'f.id = s.file_id')
            ->select('s.*')
            ->where(['f.lottery_type' => $lotteryType, 'f.status' => 1])
            ->andWhere(['<', 's.started_draw_id', $draw->id])
            ->asArray()->all();
        $updates = [];
        foreach ($schemes as $scheme) {
            $id = (int)$scheme['id'];
            list($status, $streak) = NumberSchemeParser::advance(
                (int)$scheme['current_status'],
                (int)$scheme['streak_count'],
                isset($hitLookup[$id])
            );
            $updates[] = [$id, $status, $streak, (int)$draw->id, (string)$draw->qihao, time()];
        }

        foreach (array_chunk($updates, self::UPDATE_BATCH_SIZE) as $chunk) {
            $ids = array_column($chunk, 0);
            $statusCase = self::caseExpression($chunk, 0, 1);
            $streakCase = self::caseExpression($chunk, 0, 2);
            $drawIdCase = self::caseExpression($chunk, 0, 3);
            $qihaoCase = self::caseExpression($chunk, 0, 4, true);
            $updatedCase = self::caseExpression($chunk, 0, 5);
            NumberMonitorScheme::updateAll([
                'current_status' => new Expression($statusCase),
                'streak_count' => new Expression($streakCase),
                'last_draw_id' => new Expression($drawIdCase),
                'last_qihao' => new Expression($qihaoCase),
                'updated_at' => new Expression($updatedCase),
            ], ['id' => $ids]);
        }
    }

    private static function caseExpression(array $updates, int $keyIndex, int $valueIndex, bool $quoteString = false): string
    {
        $parts = ['CASE id'];
        foreach ($updates as $update) {
            $id = (int)$update[$keyIndex];
            $value = $quoteString ? Yii::$app->db->quoteValue((string)$update[$valueIndex]) : (string)(int)$update[$valueIndex];
            $parts[] = 'WHEN ' . $id . ' THEN ' . $value;
        }
        $parts[] = 'END';
        return implode(' ', $parts);
    }

    private static function candidatePatterns(string $draw): array
    {
        $patterns = [];
        for ($mask = 1; $mask < 16; $mask++) {
            if (substr_count(decbin($mask), '1') < 2) {
                continue;
            }
            $pattern = '';
            for ($position = 0; $position < 4; $position++) {
                $pattern .= ($mask & (1 << $position)) ? $draw[$position] : 'X';
            }
            $patterns[] = $pattern;
        }
        return $patterns;
    }

    private static function latestDrawId(int $lotteryType): int
    {
        return (int)SscKjData::find()->where(['lottery_type' => $lotteryType])->max('id');
    }

    private static function hasActiveFile(int $lotteryType): bool
    {
        return NumberMonitorFile::find()->where(['lottery_type' => $lotteryType, 'status' => 1])->exists();
    }

    private static function lockOrCreateCursor(int $lotteryType, int $initialDrawId): NumberMonitorCursor
    {
        Yii::$app->db->createCommand(
            'INSERT INTO {{%number_monitor_cursors}} (lottery_type, last_draw_id, updated_at) '
            . 'VALUES (:lotteryType, :drawId, :updatedAt) ON DUPLICATE KEY UPDATE id = id',
            [':lotteryType' => $lotteryType, ':drawId' => $initialDrawId, ':updatedAt' => time()]
        )->execute();
        // The upsert holds the cursor row lock until the caller commits.
        $cursor = NumberMonitorCursor::find()->where(['lottery_type' => $lotteryType])->one();
        if (!$cursor) {
            throw new \RuntimeException('无法锁定号码监控开奖游标');
        }
        return $cursor;
    }
}
