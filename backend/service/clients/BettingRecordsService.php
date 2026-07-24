<?php
namespace backend\service\clients;

use backend\models\BettingRecords;
use backend\models\PlanAbRecord;
use backend\models\TzSystemsUsers;
use backend\service\BetService;
use common\service\CommonService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class BettingRecordsService extends ClientsBaseService{
    public static $module_key = 'backend\models\BettingRecords';

    /**
     * @desc 用户游戏列表
     * @param $uid
     * @return array
     */
    public static function getLists($uid){
        $post = \Yii::$app->request->post();
        $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
        $isAdmin = $ADMIN_ACCESS_TOKEN == ($post['access_token'] ?? '');
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey($uid, $isAdmin ? 'admin' : 'user');
        $datas = $m->get($mkey);
        if(true OR empty($datas)){
            $where = ['AND', '1=1'];
            if(!$isAdmin){
                $where = array_merge($where, [['=', 'uid', $uid]]);
            }

            $datas = (self::$module_key)::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(50)->asArray()->all();
            $datas = self::mergePlanAbRecords($datas, $uid, $isAdmin);
            $m->set($mkey, $datas, 180);
        }

        return ['status'=>200, 'datas'=>$datas, 'msg'=>'操作成功'];
    }

    /**
     * Expose type13 A-signal records without changing the real betting table.
     */
    private static function mergePlanAbRecords(array $datas, $uid, bool $isAdmin): array
    {
        try {
            $query = PlanAbRecord::find()->orderBy(['id' => SORT_DESC])->limit(100);
            if (!$isAdmin) {
                $query->andWhere(['uid' => (int)$uid]);
            }
            $events = $query->asArray()->all();
            if (!$events) {
                return $datas;
            }

            $betRows = [];
            foreach ($datas as &$data) {
                $data['is_plan_ab'] = 0;
                $key = (int)($data['id'] ?? 0);
                if ($key > 0) {
                    $betRows[$key] = &$data;
                }
            }
            unset($data);

            foreach ($events as $event) {
                $betRecordId = (int)($event['bet_record_id'] ?? 0);
                if ($betRecordId > 0 && isset($betRows[$betRecordId])) {
                    $betRows[$betRecordId]['is_plan_ab'] = 1;
                    $betRows[$betRecordId]['record_type'] = 'bet';
                    $betRows[$betRecordId]['a_hit'] = (int)$event['a_hit'];
                    $betRows[$betRecordId]['b_hit'] = $event['b_hit'] === null ? null : (int)$event['b_hit'];
                    $betRows[$betRecordId]['bet_codes'] = (string)($event['bet_codes'] ?? '');
                    $betRows[$betRecordId]['bet_status'] = (int)$event['bet_status'];
                    $betRows[$betRecordId]['strategy_action'] = (string)$event['strategy_action'];
                    continue;
                }

                $datas[] = self::buildPlanAbRow($event);
            }

            usort($datas, static function (array $left, array $right): int {
                $leftTime = (int)($left['createtime'] ?? 0);
                $rightTime = (int)($right['createtime'] ?? 0);
                return $rightTime <=> $leftTime;
            });
            return array_slice($datas, 0, 50);
        } catch (\Throwable $e) {
            // The historical betting list remains available if the optional display table is unavailable.
            Tool_Common::log('/client/betting-records', 'ERR', '类型13记录合并失败', [
                'uid' => $uid,
                'err_msg' => $e->getMessage(),
            ]);
            return $datas;
        }
    }

    private static function buildPlanAbRow(array $event): array
    {
        $isBet = (int)($event['is_bet'] ?? 0) === 1;
        $betStatus = (int)($event['bet_status'] ?? PlanAbRecord::BET_STATUS_NONE);
        $createdAt = (int)($event['created_at'] ?? 0);
        return [
            'id' => -abs((int)$event['id']),
            'ab_record_id' => (int)$event['id'],
            'is_plan_ab' => 1,
            'record_type' => 'plan_ab',
            'qihao' => (string)$event['qihao'],
            'codes' => $isBet ? (string)($event['bet_codes'] ?? '') : '',
            'bet_codes' => (string)($event['bet_codes'] ?? ''),
            'betting_money' => 0,
            'bonus' => 0,
            'single' => (string)($event['single'] ?? 0),
            'profits' => 0,
            'kj_codes' => (string)($event['kj_codes'] ?? ''),
            'snid' => '',
            'playway_name' => '类型13 A/B策略',
            'create_time' => $createdAt ? date('Y-m-d H:i:s', $createdAt) : '',
            'createtime' => $createdAt,
            'status' => $isBet ? ($betStatus === PlanAbRecord::BET_STATUS_PENDING ? 0 : 1) : 0,
            'a_hit' => (int)$event['a_hit'],
            'b_hit' => $event['b_hit'] === null ? null : (int)$event['b_hit'],
            'bet_status' => $betStatus,
            'strategy_action' => (string)($event['strategy_action'] ?? ''),
        ];
    }

    /**
     * @desc 用户列表key
     * @return string
     */
    public static function buildUserKey($uid='', $scope='user'){
        $mkey = 'buildBettingRecordsKey_key_'.$scope.'_'.$uid;

        return $mkey;
    }

    /**
     * @desc 删除用户缓存信息
     */
    public static function delBettingRecordsData($uid=''){
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey($uid);

        $m->delete($mkey);
    }



}
