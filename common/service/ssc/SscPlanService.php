<?php
namespace common\service\ssc;

use backend\models\UserSysPlans;
use backend\models\ImportPlanCodes;
use common\models\AdminModel;
use common\models\base\BaseModel;
use common\service\CommonService;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class SscPlanService extends CommonService
{
    /**
     * 复制计划任务
     * @param int $userId 目标用户ID
     * @param int $planId 被复制的计划ID
     * @return array
     */
    public static function copyOnePlan($userId, $planId)
    {
        try {
            // 1. 获取原计划
            $plan = UserSysPlans::findOne($planId);
            if (!$plan) {
                throw_info('计划不存在');
            }
            
            if ($plan->status == BaseModel::STATUS_DELETED) {
                throw_info('计划已经删除不能复制');
            }

            // 2. 获取目标用户信息
            $targetUser = AdminModel::findOne($userId);
            if (!$targetUser) {
                throw_info('目标用户不存在');
            }
            $singles = explode('-', trim($plan->singles));

            // 3. 复制计划
            $newPlan = new UserSysPlans();
            $newPlan->setAttributes($plan->attributes);

            $hzArr = Json::decode($plan->hz_Arr, 1);
            $hzArr['singles_key'] = 0;
            // 修改需要变动的字段
            $newPlan->hz_Arr = Json::encode($hzArr);
            $newPlan->id = null; // 让数据库自动生成新ID
            $newPlan->uid = $userId; // 目标用户ID
            $newPlan->account = $targetUser->username; // 目标用户账号名称
            $newPlan->singles = implode('-', $singles);
            $newPlan->current_profits = 0; // 当前盈利重置为0
            $newPlan->created_at = time(); // 更新创建时间
            $newPlan->updated_at = time(); // 更新修改时间
            $newPlan->update_time = date('Y-m-d H:i:s'); // 更新修改时间

            if (empty($singles)) $singles = [$plan->single];
            $newPlan->single = $singles[0];
            $newPlan->status = 0;


            // 4. 保存新计划
            if (!$newPlan->save()) {
                throw_info('复制计划失败：' . json_encode($newPlan->getErrors()));
            }

            $newPlanId = $newPlan->id;

            // 5. 复制导入号码（如果存在）
            $importCodes = ImportPlanCodes::find()->where(['plan_id' => $planId])->all();
            $copiedCodesCount = 0;
            
            if (!empty($importCodes)) {
                foreach ($importCodes as $importCode) {
                    $newImportCode = new ImportPlanCodes();
                    $newImportCode->setAttributes($importCode->attributes);
                    
                    // 修改需要变动的字段
                    $newImportCode->id = null; // 让数据库自动生成新ID
                    $newImportCode->uid = $userId; // 目标用户ID
                    $newImportCode->plan_id = $newPlanId; // 新计划ID
                    $newImportCode->created_at = time(); // 更新创建时间
                    $newImportCode->updated_at = time(); // 更新修改时间
                    $newImportCode->update_time = date('Y-m-d H:i:s'); // 更新修改时间

                    if ($newImportCode->save()) {
                        $copiedCodesCount++;
                    } else {
                        // 记录错误但不中断整个复制过程
                        \Yii::error('复制导入号码失败：' . json_encode($newImportCode->getErrors()));
                    }
                }
            }

            return [
                'status' => 200,
                'msg' => '复制计划成功',
                'data' => [
                    'original_plan_id' => $planId,
                    'new_plan_id' => $newPlanId,
                    'target_user_id' => $userId,
                    'target_user_account' => $targetUser->username,
                    'copied_import_codes_count' => $copiedCodesCount,
                    'total_import_codes_count' => count($importCodes)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status' => 500,
                'msg' => '复制计划失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 批量复制计划任务
     * @param int $userId 目标用户ID
     * @param array $planIds 被复制的计划ID数组
     * @return array
     */
    public static function copyMultiplePlans($userId, $planIds)
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($planIds as $planId) {
            $result = self::copyOnePlan($userId, $planId);
            $results[] = [
                'plan_id' => $planId,
                'result' => $result
            ];

            if ($result['status'] == 200) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'status' => ($failCount == 0) ? 200 : 206, // 206表示部分成功
            'msg' => "批量复制完成：成功{$successCount}个，失败{$failCount}个",
            'data' => [
                'total_count' => count($planIds),
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'results' => $results
            ]
        ];
    }

    /**
     * 验证计划是否可以复制
     * @param int $planId 计划ID
     * @return array
     */
    public static function validatePlanForCopy($planId)
    {
        try {
            $plan = UserSysPlans::findOne($planId);
            if (!$plan) {
                return [
                    'status' => 404,
                    'msg' => '计划不存在',
                    'can_copy' => false
                ];
            }
            
            if ($plan->status == BaseModel::STATUS_DELETED) {
                return [
                    'status' => 400,
                    'msg' => '计划已删除，不能复制',
                    'can_copy' => false
                ];
            }

            return [
                'status' => 200,
                'msg' => '计划可以复制',
                'can_copy' => true,
                'data' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->desc ?: "计划{$plan->id}",
                    'account' => $plan->account,
                    'playway' => $plan->playway,
                    'plan_type' => $plan->plan_type
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status' => 500,
                'msg' => '验证失败：' . $e->getMessage(),
                'can_copy' => false
            ];
        }
    }
}
