<?php

namespace backend\service;

use backend\models\PlanProfitStatGroup;
use backend\models\PlanProfitStatGroupPlan;
use backend\models\PlanStaticProfits;
use backend\models\UserSysPlans;
use common\models\AdminModel;
use Yii;
use yii\db\Query;

class PlanProfitStatGroupService
{
    public const MAX_PLANS_PER_GROUP = 20;

    /**
     * 超级管理员未选账号时无法确定分组归属用户，返回 null
     */
    public static function resolveStatOwnerUid(int $loginUid, ?string $accountUsername): ?int
    {
        if ($loginUid !== 1) {
            return $loginUid;
        }
        if ($accountUsername === null || $accountUsername === '') {
            return null;
        }
        $id = AdminModel::find()->select(['id'])->where(['username' => $accountUsername])->scalar();
        return $id ? (int) $id : null;
    }

    /**
     * @return PlanProfitStatGroup[]
     */
    public static function getGroups(int $ownerUid, int $lotteryType): array
    {
        return PlanProfitStatGroup::find()
            ->where(['uid' => $ownerUid, 'lottery_type' => $lotteryType])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    /**
     * plan_id => ['id' => groupId, 'name' => groupName]
     */
    public static function getPlanGroupMap(int $ownerUid, int $lotteryType, array $planIds): array
    {
        if ($planIds === []) {
            return [];
        }
        $rows = (new Query())
            ->from(['m' => PlanProfitStatGroupPlan::tableName()])
            ->innerJoin(['g' => PlanProfitStatGroup::tableName()], 'g.id = m.group_id')
            ->select(['m.plan_id', 'g.id AS group_id', 'g.name'])
            ->where(['m.plan_id' => $planIds, 'g.uid' => $ownerUid, 'g.lottery_type' => $lotteryType])
            ->all();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['plan_id']] = ['id' => (int) $row['group_id'], 'name' => $row['name']];
        }
        return $map;
    }

    /**
     * @return int[]
     */
    public static function getGroupPlanIds(int $groupId): array
    {
        return PlanProfitStatGroupPlan::find()
            ->select('plan_id')
            ->where(['group_id' => $groupId])
            ->column();
    }

    public static function sumCutProfitsForPlans(array $planIds): float
    {
        if ($planIds === []) {
            return 0.0;
        }
        $sum = (new Query())
            ->from(PlanStaticProfits::tableName())
            ->where(['plan_id' => $planIds])
            ->sum('cut_profits');
        return round((float) $sum, 2);
    }

    public static function normalizeIdsString(string $idsParam): string
    {
        $parts = array_filter(preg_split('/[\s#,，\r\n]+/', $idsParam, -1, PREG_SPLIT_NO_EMPTY));
        $parts = array_map('intval', $parts);
        $parts = array_filter($parts);
        sort($parts, SORT_NUMERIC);
        return implode(',', $parts);
    }

    public static function createGroup(int $ownerUid, int $lotteryType, string $name): PlanProfitStatGroup
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('分组名称不能为空');
        }
        $g = new PlanProfitStatGroup();
        $g->uid = $ownerUid;
        $g->lottery_type = $lotteryType;
        $g->name = $name;
        if (!$g->save()) {
            throw new \RuntimeException('保存失败: ' . json_encode($g->errors, JSON_UNESCAPED_UNICODE));
        }
        return $g;
    }

    public static function updateGroupName(int $groupId, int $ownerUid, string $name): void
    {
        $g = PlanProfitStatGroup::findOne(['id' => $groupId, 'uid' => $ownerUid]);
        if (!$g) {
            throw new \InvalidArgumentException('分组不存在');
        }
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('分组名称不能为空');
        }
        $g->name = $name;
        if (!$g->save()) {
            throw new \RuntimeException('保存失败');
        }
    }

    public static function deleteGroup(int $groupId, int $ownerUid): void
    {
        $g = PlanProfitStatGroup::findOne(['id' => $groupId, 'uid' => $ownerUid]);
        if (!$g) {
            throw new \InvalidArgumentException('分组不存在');
        }
        PlanProfitStatGroupPlan::deleteAll(['group_id' => $groupId]);
        $g->delete();
    }

    /**
     * 将选中计划加入分组（会先移出其它分组）；每组最多 self::MAX_PLANS_PER_GROUP 个
     *
     * @param int[] $planIds
     */
    public static function assignPlansToGroup(int $groupId, int $ownerUid, array $planIds): void
    {
        $planIds = array_values(array_unique(array_map('intval', array_filter($planIds))));
        if ($planIds === []) {
            throw new \InvalidArgumentException('请选择计划');
        }
        $group = PlanProfitStatGroup::findOne(['id' => $groupId, 'uid' => $ownerUid]);
        if (!$group) {
            throw new \InvalidArgumentException('分组不存在');
        }
        $plans = UserSysPlans::find()
            ->where(['id' => $planIds, 'uid' => $ownerUid, 'lottery_type' => $group->lottery_type])
            ->indexBy('id')
            ->all();
        if (count($plans) !== count($planIds)) {
            throw new \InvalidArgumentException('存在无效计划或非当前彩种/账号的计划');
        }

        $existingInGroup = PlanProfitStatGroupPlan::find()
            ->select('plan_id')
            ->where(['group_id' => $groupId])
            ->column();
        $existingInGroup = array_map('intval', $existingInGroup);

        $toAdd = array_values(array_diff($planIds, $existingInGroup));
        $afterCount = count($existingInGroup) + count($toAdd);
        if ($afterCount > self::MAX_PLANS_PER_GROUP) {
            throw new \InvalidArgumentException('每个分组最多 ' . self::MAX_PLANS_PER_GROUP . ' 个计划');
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            if ($toAdd !== []) {
                PlanProfitStatGroupPlan::deleteAll(['plan_id' => $toAdd]);
                foreach ($toAdd as $pid) {
                    $m = new PlanProfitStatGroupPlan();
                    $m->group_id = $groupId;
                    $m->plan_id = $pid;
                    if (!$m->save()) {
                        throw new \RuntimeException('关联保存失败');
                    }
                }
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    public static function removePlanFromGroup(int $planId, int $ownerUid): void
    {
        $row = (new Query())
            ->from(['m' => PlanProfitStatGroupPlan::tableName()])
            ->innerJoin(['g' => PlanProfitStatGroup::tableName()], 'g.id = m.group_id')
            ->select(['m.id'])
            ->where(['m.plan_id' => $planId, 'g.uid' => $ownerUid])
            ->one();
        if ($row) {
            PlanProfitStatGroupPlan::deleteAll(['plan_id' => $planId]);
        }
    }
}
