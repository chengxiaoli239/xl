<?php

namespace backend\service\statics\yl;

use backend\models\SscKjData;
use backend\models\SscStaticYl;
use yii\helpers\Json;

class ThreeComboYlService
{
    public const TYPE = 6;
    public const ROW_VAL = 'three_combo_fs';
    public const DEFAULT_PERIODS = 500;
    public const MAX_PERIODS = 10000;

    public static function normalizePeriods($periods): int
    {
        $periods = (int)$periods;
        if ($periods <= 0) {
            return self::DEFAULT_PERIODS;
        }

        return min($periods, self::MAX_PERIODS);
    }

    public static function getCombinations(): array
    {
        $codes = [];
        for ($first = 0; $first <= 7; $first++) {
            for ($second = $first + 1; $second <= 8; $second++) {
                for ($third = $second + 1; $third <= 9; $third++) {
                    $codes[] = (string)$first.$second.$third;
                }
            }
        }

        return $codes;
    }

    public static function normalizeCodeFilter(string $value): array
    {
        $codes = [];
        foreach (preg_split('/[\s,，]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) as $code) {
            if (!preg_match('/^\d{3}$/', $code)) {
                continue;
            }
            $digits = str_split($code);
            $digits = array_values(array_unique($digits));
            sort($digits, SORT_NUMERIC);
            if (count($digits) === 3) {
                $codes[] = implode('', $digits);
            }
        }

        return array_values(array_unique($codes, SORT_STRING));
    }

    public static function isHit(string $combination, array $drawCodes): bool
    {
        // 复式号码表示允许出现的数字集合，不要求三个数字在本期都至少出现一次。
        if (count($drawCodes) < 4) {
            return false;
        }
        $allowed = array_fill_keys(str_split($combination), true);
        foreach (array_slice($drawCodes, 0, 4) as $drawCode) {
            $drawCode = (string)$drawCode;
            if (!isset($allowed[$drawCode])) {
                return false;
            }
        }

        return true;
    }

    public static function calculate(array $draws, ?array $onlyCodes = null): array
    {
        $combinations = $onlyCodes ?: self::getCombinations();
        $hits = array_fill_keys($combinations, []);
        $scanned = 0;

        foreach ($draws as $position => $draw) {
            $drawCodes = [$draw['code1'], $draw['code2'], $draw['code3'], $draw['code4']];
            foreach ($combinations as $combination) {
                if (self::isHit($combination, $drawCodes)) {
                    $hits[$combination][] = [
                        'position' => (int)$position,
                        'qihao' => (string)$draw['qihao'],
                    ];
                }
            }
            $scanned++;
        }

        $statistics = [];
        foreach ($combinations as $combination) {
            $comboHits = $hits[$combination];
            $currentMiss = isset($comboHits[0]) ? $comboHits[0]['position'] : $scanned;
            $lastMiss = 0;
            $lastRange = '';
            $maxMiss = 0;
            $maxRange = '';
            $missRecords = [];

            for ($index = 1, $count = count($comboHits); $index < $count; $index++) {
                $miss = $comboHits[$index]['position'] - $comboHits[$index - 1]['position'] - 1;
                $missRecords[] = $miss;
                $range = $comboHits[$index - 1]['qihao'].'-'.$comboHits[$index]['qihao'];
                if ($index === 1) {
                    $lastMiss = $miss;
                    $lastRange = $range;
                }
                if ($miss >= $maxMiss) {
                    $maxMiss = $miss;
                    $maxRange = $range;
                }
            }

            $statistics[$combination] = [
                'code' => $combination,
                'current_miss' => $currentMiss,
                'last_time_miss' => $lastMiss,
                'last_time_miss_range' => $lastRange,
                'max_miss' => $maxMiss,
                'max_range' => $maxRange,
                'history_max_miss' => max($currentMiss, $maxMiss),
                'hit_count' => count($comboHits),
                'yl_records' => implode('-', array_slice($missRecords, 0, 30)),
                'is_window_limit' => empty($comboHits) && $scanned > 0,
            ];
        }

        return array_values($statistics);
    }

    public static function getStatistics(int $lotteryType, $periods, string $codeFilter = ''): array
    {
        $periods = self::normalizePeriods($periods);
        $onlyCodes = self::normalizeCodeFilter($codeFilter);
        $lastIndexId = (int)SscKjData::find()
            ->where(['lottery_type' => $lotteryType])
            ->max('index_id');

        if ($periods === self::DEFAULT_PERIODS && empty($onlyCodes)) {
            $row = self::findSummaryRow($lotteryType);
            if ($row && (int)$row->stat_last_index_id === $lastIndexId) {
                $statistics = Json::decode($row->yl_records ?: '[]');
                if (is_array($statistics)) {
                    return self::buildResult($statistics, $periods, $lastIndexId, false);
                }
            }
        }

        $cacheKey = 'three_combo_yl_'.$lotteryType.'_'.$lastIndexId.'_'.$periods.'_'.md5(implode(',', $onlyCodes));
        $cache = \Yii::$app->cache;
        $statistics = $cache->get($cacheKey);
        if (!is_array($statistics)) {
            $statistics = self::calculate(self::fetchDraws($lotteryType, $periods), $onlyCodes ?: null);
            $cache->set($cacheKey, $statistics, 300);
        }

        return self::buildResult($statistics, $periods, $lastIndexId, true);
    }

    public static function refreshSummary(int $lotteryType, int $periods = self::DEFAULT_PERIODS): array
    {
        $periods = self::normalizePeriods($periods);
        $draws = self::fetchDraws($lotteryType, $periods);
        $statistics = self::calculate($draws);
        $lastIndexId = empty($draws) ? 0 : (int)$draws[0]['index_id'];
        $row = self::findSummaryRow($lotteryType) ?: new SscStaticYl();
        $isNew = $row->isNewRecord;

        $row->val = self::ROW_VAL;
        $row->codes = implode(',', self::getCombinations());
        $row->yl_records = Json::encode($statistics);
        $row->stat_last_index_id = $lastIndexId;
        $row->static_nums = $periods;
        $row->count = count($statistics);
        $row->lottery_type = $lotteryType;
        $row->type = self::TYPE;
        $row->status = 1;
        $row->update_time = date('Y-m-d H:i:s');
        $row->updated_at = time();
        if ($isNew) {
            $row->created_at = time();
        }
        if (!$row->save()) {
            throw new \RuntimeException(Json::encode($row->getErrors()));
        }

        return self::buildResult($statistics, $periods, $lastIndexId, false);
    }

    private static function fetchDraws(int $lotteryType, int $periods): array
    {
        return SscKjData::find()
            ->select(['index_id', 'qihao', 'code1', 'code2', 'code3', 'code4'])
            ->where(['lottery_type' => $lotteryType])
            ->orderBy(['index_id' => SORT_DESC])
            ->limit($periods)
            ->asArray()
            ->all();
    }

    private static function findSummaryRow(int $lotteryType): ?SscStaticYl
    {
        return SscStaticYl::findOne([
            'lottery_type' => $lotteryType,
            'type' => self::TYPE,
            'val' => self::ROW_VAL,
        ]);
    }

    private static function buildResult(array $statistics, int $periods, int $lastIndexId, bool $calculated): array
    {
        return [
            'statistics' => $statistics,
            'periods' => $periods,
            'last_index_id' => $lastIndexId,
            'calculated' => $calculated,
        ];
    }
}
