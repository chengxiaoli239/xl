<?php

namespace backend\service;

use backend\models\BettingRecords;
use backend\models\ImportPlanCodes;
use backend\models\PlanAbRecord;
use backend\models\SscKjData;
use backend\models\UserSysPlans;
use common\tools\Tool_Common;

/**
 * State machine for plan type 13.
 *
 * A/B betting records are intentionally kept separate: A drives the strategy,
 * while B remains the real wager and payout source.
 */
class PlanType13Service extends BaseService
{
    const STATUS_WAIT = 1;
    const STATUS_BET = 2;

    public static function getSingles(UserSysPlans $plan): array
    {
        $raw = trim((string)$plan->singles);
        if ($raw === '') {
            return [(float)$plan->single];
        }

        $raw = str_replace(['，', ',', ';', '；'], '-', $raw);
        $parts = preg_split('/-+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $singles = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || !is_numeric($part) || (float)$part <= 0) {
                continue;
            }
            $singles[] = (float)$part;
        }

        return $singles ?: [(float)$plan->single];
    }

    public static function nextSingleKey(int $key, int $count): int
    {
        if ($count <= 1) {
            return 0;
        }
        return ($key + 1) % $count;
    }

    /**
     * Pure transition for a betting period. A hit continues immediately;
     * an A miss returns to the trigger wait state. Both advance the ladder.
     */
    public static function transitionAfterBet(array $state, bool $aHit, int $singleCount): array
    {
        $nextKey = self::nextSingleKey((int)($state['singles_key'] ?? 0), $singleCount);
        $state['singles_key'] = $nextKey;

        if ($aHit) {
            $state['A_x_B_y_status'] = self::STATUS_BET;
            $state['current_arise_A_times'] = max(1, (int)($state['arise_A_times'] ?? 1));
            $state['current_arise_B_times'] = max(0, (int)($state['arise_B_times'] ?? 0));
            $state['strategy_action'] = 'A命中，继续投B';
        } else {
            $state['A_x_B_y_status'] = self::STATUS_WAIT;
            $state['current_arise_A_times'] = 0;
            $state['current_arise_B_times'] = 0;
            $state['strategy_action'] = 'A未命中，等待条件';
        }

        return $state;
    }

    /**
     * Pure transition while waiting for A^x+B^y.
     * During the initial A phase, A has priority. Once A reaches x,
     * subsequent B matches may include A/B overlap so y remains usable.
     */
    public static function transitionWhileWaiting(array $state, bool $aHit, bool $bHit): array
    {
        $aTimes = max(0, (int)($state['current_arise_A_times'] ?? 0));
        $bTimes = max(0, (int)($state['current_arise_B_times'] ?? 0));
        $aNeed = max(1, (int)($state['arise_A_times'] ?? 1));
        $bNeed = max(0, (int)($state['arise_B_times'] ?? 0));

        if ($aTimes < $aNeed) {
            $aTimes = $aHit ? $aTimes + 1 : 0;
            $bTimes = 0;
        } elseif ($bNeed > 0) {
            if ($bHit) {
                $bTimes++;
            } elseif ($aHit) {
                $aTimes++;
                $bTimes = 0;
            } else {
                $aTimes = 0;
                $bTimes = 0;
            }
        }

        $state['current_arise_A_times'] = $aTimes;
        $state['current_arise_B_times'] = $bTimes;
        $state['A_x_B_y_status'] = ($aTimes >= $aNeed && $bTimes >= $bNeed)
            ? self::STATUS_BET
            : self::STATUS_WAIT;
        $state['strategy_action'] = $state['A_x_B_y_status'] === self::STATUS_BET
            ? '满足条件，下期投B'
            : '等待A^x+B^y';

        return $state;
    }

    public static function processPeriod(UserSysPlans $plan, string $qihao = ''): array
    {
        if ((int)$plan->plan_type !== 13 || (int)$plan->status !== 1) {
            return ['status' => 301, 'msg' => '不是激活的类型13计划'];
        }

        $kjData = self::getKjData($plan, $qihao);
        if (empty($kjData['qihao']) || empty($kjData['code_str'])) {
            return ['status' => 300, 'msg' => '找不到本期开奖数据'];
        }
        $qihao = (string)$kjData['qihao'];

        $hz = json_decode((string)$plan->hz_Arr, true);
        $hz = is_array($hz) ? $hz : [];
        self::normalizeState($hz);
        if (($hz['type13_last_qihao'] ?? '') === $qihao) {
            return ['status' => 200, 'msg' => '本期已处理', 'skipped' => true];
        }

        [$aCodes, $bCodes] = self::getGroups($plan->id);
        $aHit = OpKjService::opKjData4($aCodes, $kjData['code_str']) > 0;
        $bHit = OpKjService::opKjData4($bCodes, $kjData['code_str']) > 0;
        $before = $hz;
        $wasBetting = (int)$before['A_x_B_y_status'] === self::STATUS_BET;
        $singles = self::getSingles($plan);

        if ($wasBetting) {
            $hz = self::transitionAfterBet($hz, $aHit, count($singles));
        } else {
            $hz = self::transitionWhileWaiting($hz, $aHit, $bHit);
        }

        $hz['singles_key'] = (int)($hz['singles_key'] ?? 0);
        $hz['single_key'] = $hz['singles_key'];
        $hz['type13_last_qihao'] = $qihao;
        $hz['A_x_B_y_start_time'] = $hz['A_x_B_y_start_time'] ?? date('Y-m-d H:i:s');
        $single = $singles[$hz['singles_key']] ?? (float)$plan->single;

        UserSysPlans::updateAll([
            'single' => $single,
            'hz_Arr' => json_encode($hz, 320),
        ], ['id' => $plan->id]);

        self::saveDisplayRecord($plan, $kjData, $before, $hz, $wasBetting, $aHit, $bHit, $single);

        return [
            'status' => 200,
            'qihao' => $qihao,
            'a_hit' => (int)$aHit,
            'b_hit' => (int)$bHit,
            'is_bet' => $wasBetting ? 1 : 0,
            'single' => $single,
            'singles_key' => (int)$hz['singles_key'],
            'strategy_status' => (int)$hz['A_x_B_y_status'],
            'strategy_action' => $hz['strategy_action'],
        ];
    }

    public static function syncBetRecord(BettingRecords $betRecord): void
    {
        try {
            if (!PlanAbRecord::isTableAvailable()) {
                return;
            }
            PlanAbRecord::updateAll([
                'bet_record_id' => (int)$betRecord->id,
                'is_bet' => 1,
                'bet_codes' => (string)$betRecord->codes,
                'bet_status' => self::getBetStatus($betRecord),
                'updated_at' => time(),
            ], [
                'plan_id' => $betRecord->plan_id,
                'qihao' => (string)$betRecord->qihao,
            ]);
        } catch (\Throwable $e) {
            // A display record must never make the normal开奖结算 fail.
            Tool_Common::log('/plan/type13', 'ERR', '类型13投注结果同步失败', [
                'plan_id' => $betRecord->plan_id,
                'qihao' => $betRecord->qihao,
                'err_msg' => $e->getMessage(),
            ]);
        }
    }

    private static function normalizeState(array &$hz): void
    {
        $hz['arise_A_times'] = max(1, (int)($hz['arise_A_times'] ?? 1));
        $hz['arise_B_times'] = max(0, (int)($hz['arise_B_times'] ?? 0));
        $hz['current_arise_A_times'] = max(0, (int)($hz['current_arise_A_times'] ?? 0));
        $hz['current_arise_B_times'] = max(0, (int)($hz['current_arise_B_times'] ?? 0));
        $hz['singles_key'] = max(0, (int)($hz['singles_key'] ?? 0));
        $hz['A_x_B_y_status'] = in_array((int)($hz['A_x_B_y_status'] ?? self::STATUS_WAIT), [self::STATUS_WAIT, self::STATUS_BET], true)
            ? (int)$hz['A_x_B_y_status']
            : self::STATUS_WAIT;
    }

    private static function getGroups(int $planId): array
    {
        $rows = ImportPlanCodes::find()
            ->where(['plan_id' => $planId, 'status' => 1, 'plan_id_sort_key' => ['arise_A_codes', 'arise_B_codes']])
            ->indexBy('plan_id_sort_key')
            ->all();

        return [
            (string)($rows['arise_A_codes']->codes ?? ''),
            (string)($rows['arise_B_codes']->codes ?? ''),
        ];
    }

    private static function getKjData(UserSysPlans $plan, string $qihao = ''): array
    {
        $query = SscKjData::find()->where(['lottery_type' => $plan->lottery_type]);
        if ($qihao !== '') {
            $query->andWhere(['qihao' => $qihao]);
        }
        return (array)$query->orderBy(['id' => SORT_DESC])->asArray()->one();
    }

    private static function saveDisplayRecord(
        UserSysPlans $plan,
        array $kjData,
        array $before,
        array $after,
        bool $wasBetting,
        ?bool $aHit = null,
        ?bool $bHit = null,
        $single = null
    ): void {
        // The migration is optional during rollout; never block strategy processing if it is pending.
        if (!PlanAbRecord::isTableAvailable()) {
            return;
        }
        $qihao = (string)($kjData['qihao'] ?? '');
        if ($qihao === '') {
            return;
        }

        $betRecord = BettingRecords::find()
            ->where(['plan_id' => $plan->id, 'uid' => $plan->uid, 'qihao' => $qihao, 'lottery_type' => $plan->lottery_type])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $event = PlanAbRecord::findOne(['plan_id' => $plan->id, 'qihao' => $qihao]) ?: new PlanAbRecord();
        $event->setAttributes([
            'plan_id' => $plan->id,
            'uid' => $plan->uid,
            'lottery_type' => $plan->lottery_type,
            'qihao' => $qihao,
            'kj_codes' => (string)($kjData['code_str'] ?? ''),
            'a_hit' => $aHit === null ? (int)($event->a_hit ?? 0) : (int)$aHit,
            'b_hit' => $bHit === null ? $event->b_hit : (int)$bHit,
            'is_bet' => $betRecord ? 1 : 0,
            'bet_record_id' => $betRecord ? (int)$betRecord->id : (int)($event->bet_record_id ?? 0),
            'bet_codes' => $betRecord ? (string)$betRecord->codes : (string)($event->bet_codes ?? ''),
            'bet_status' => $betRecord ? self::getBetStatus($betRecord) : PlanAbRecord::BET_STATUS_NONE,
            'strategy_status_before' => (int)($before['A_x_B_y_status'] ?? self::STATUS_WAIT),
            'strategy_status_after' => (int)($after['A_x_B_y_status'] ?? self::STATUS_WAIT),
            'strategy_action' => (string)($after['strategy_action'] ?? ($wasBetting ? '下注状态' : '等待状态')),
            'single' => $betRecord ? (float)$betRecord->single : (float)($single ?? 0),
            'singles_key' => (int)($before['singles_key'] ?? 0),
            'updated_at' => time(),
        ], false);
        if (!$event->created_at) {
            $event->created_at = time();
        }
        if (!$event->save(false)) {
            Tool_Common::log('/plan/type13', 'ERR', '类型13策略记录保存失败', [
                'plan_id' => $plan->id,
                'qihao' => $qihao,
                'errors' => $event->getErrors(),
            ]);
        }
    }

    private static function getBetStatus(BettingRecords $record): int
    {
        if (empty($record->kj_codes)) {
            return PlanAbRecord::BET_STATUS_PENDING;
        }
        return (float)$record->bonus > 0 ? PlanAbRecord::BET_STATUS_WIN : PlanAbRecord::BET_STATUS_LOSE;
    }
}
