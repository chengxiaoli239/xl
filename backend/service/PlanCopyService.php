<?php

namespace backend\service;

use backend\models\ImportPlanCodes;
use backend\models\TzSystemsAuth;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use common\models\AdminModel;
use RuntimeException;
use Throwable;
use Yii;

class PlanCopyService
{
    private const RUNTIME_DEFAULTS = [
        'current_miss' => 0,
        'history_max_miss' => 0,
        'singles_key' => 0,
        'single_key' => 0,
        'turn_key' => 0,
        'betStatus' => 0,
        'areaBetStatus' => 0,
        'current_area_profits' => 0,
        'area_arise_qishus' => 0,
        'area_msg' => '',
        'is_init' => 1,
        'A_x_B_y_status' => 0,
        'A_x_B_y_start_time' => '',
        'current_arise_A_times' => 0,
        'current_arise_B_times' => 0,
        'current_yl_desc' => '',
        'start_bet_yl_nums' => 0,
        'type13_last_qihao' => '',
        'current_kj_qihao' => '',
        'has_bet_nums' => 0,
    ];

    public static function copyPlan(int $sourcePlanId, int $targetUid): UserSysPlans
    {
        if ($sourcePlanId <= 0 || $targetUid <= 0) {
            throw new RuntimeException('源计划和目标账号不能为空');
        }

        $source = UserSysPlans::findOne($sourcePlanId);
        if (!$source) {
            throw new RuntimeException('源计划不存在');
        }
        if ((int)$source->uid === $targetUid) {
            throw new RuntimeException('目标账号不能与源计划账号相同');
        }

        $target = AdminModel::findOne([
            'id' => $targetUid,
            'status' => AdminModel::STATUS_ACTIVE,
        ]);
        if (!$target) {
            throw new RuntimeException('目标账号不存在或已停用');
        }

        $requiresEnabledSite = (int)$source->is_test === 0 && (int)$source->is_batch_simulate === 0;
        $targetSites = self::getTargetSites((string)$source->tz_sites, $targetUid, $requiresEnabledSite);
        if ($targetSites === '') {
            throw new RuntimeException('目标账号没有可用盘口，请先配置盘口账号或授权站点');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $now = time();
            $copy = new UserSysPlans();
            $copy->setAttributes(self::preparePlanAttributes(
                $source->getAttributes(),
                $targetUid,
                (string)$target->username,
                $targetSites,
                $now
            ), false);
            if (!$copy->save()) {
                throw new RuntimeException('计划复制失败：' . implode('；', $copy->getErrorSummary(true)));
            }

            $sourceCodes = ImportPlanCodes::find()
                ->where(['plan_id' => $sourcePlanId])
                ->orderBy(['id' => SORT_ASC])
                ->all();
            foreach ($sourceCodes as $sourceCode) {
                $codeCopy = new ImportPlanCodes();
                $codeCopy->setAttributes(self::prepareImportCodeAttributes(
                    $sourceCode->getAttributes(),
                    $targetUid,
                    (int)$copy->id,
                    $now
                ), false);
                if (!$codeCopy->save()) {
                    throw new RuntimeException('导入号码复制失败：' . implode('；', $codeCopy->getErrorSummary(true)));
                }
            }

            $transaction->commit();
            return $copy;
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function preparePlanAttributes(
        array $source,
        int $targetUid,
        string $targetAccount,
        string $targetSites,
        int $now
    ): array {
        unset($source['id']);
        $source['uid'] = $targetUid;
        $source['account'] = $targetAccount;
        $source['status'] = 0;
        $source['is_parent'] = 0;
        $source['children_plan_id'] = '';
        $source['tz_sites'] = $targetSites;
        $source['current_profits'] = 0;
        $source['created_at'] = $now;
        $source['updated_at'] = $now;
        $source['update_time'] = date('Y-m-d H:i:s', $now);

        if (isset($source['hz_Arr']) && is_string($source['hz_Arr'])) {
            $decoded = json_decode($source['hz_Arr'], true);
            if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) {
                $source['hz_Arr'] = json_encode(
                    self::resetRuntimeState($decoded),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }
        }

        return $source;
    }

    public static function prepareImportCodeAttributes(
        array $source,
        int $targetUid,
        int $targetPlanId,
        int $now
    ): array {
        unset($source['id']);
        $source['uid'] = $targetUid;
        $source['plan_id'] = $targetPlanId;
        $source['created_at'] = $now;
        $source['updated_at'] = $now;
        $source['update_time'] = date('Y-m-d H:i:s', $now);

        return $source;
    }

    public static function resolveTargetSites(
        string $sourceSites,
        array $enabledSites,
        array $authorizedSites,
        bool $requiresEnabledSite = false
    ): string
    {
        $source = self::normalizeSiteIds(explode(',', $sourceSites));
        $enabled = self::normalizeSiteIds($enabledSites);
        $authorized = self::normalizeSiteIds($authorizedSites);

        $matching = array_values(array_intersect($source, $enabled));
        if ($matching) {
            return implode(',', $matching);
        }
        if ($enabled) {
            return (string)$enabled[0];
        }
        if ($requiresEnabledSite) {
            return '';
        }

        return implode(',', $authorized);
    }

    private static function getTargetSites(string $sourceSites, int $targetUid, bool $requiresEnabledSite): string
    {
        $enabledSites = TzSystemsUsers::find()
            ->select(['tz_system_id'])
            ->where(['uid' => $targetUid, 'status' => 1])
            ->andWhere(['>', 'tz_system_id', 0])
            ->orderBy(['id' => SORT_ASC])
            ->column();
        $auth = TzSystemsAuth::findOne(['uid' => $targetUid]);
        $authorizedSites = $auth
            ? explode(',', (string)$auth->tz_systems_ids)
            : [];

        return self::resolveTargetSites($sourceSites, $enabledSites, $authorizedSites, $requiresEnabledSite);
    }

    private static function normalizeSiteIds(array $siteIds): array
    {
        $normalized = [];
        foreach ($siteIds as $siteId) {
            $siteId = (int)$siteId;
            if ($siteId > 0 && !in_array($siteId, $normalized, true)) {
                $normalized[] = $siteId;
            }
        }

        return $normalized;
    }

    private static function resetRuntimeState(array $data): array
    {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, self::RUNTIME_DEFAULTS)) {
                $data[$key] = self::RUNTIME_DEFAULTS[$key];
            } elseif (is_array($value)) {
                $data[$key] = self::resetRuntimeState($value);
            }
        }

        return $data;
    }
}
