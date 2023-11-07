<?php
namespace backend\service\statics\statics_3d;
use backend\models\AgentUsersBalanceFlows;
use backend\models\statics\Static3dUserProfitsDay;
use backend\models\wechat\Bets;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\BaseService;
use common\models\wechat\WechatUser;
use common\service\thirdD\CommonBaseService;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;
use yii\helpers\Json;

class Statics3dUserDataService extends BaseService {

    /**
     * @param int $wechat_user_id
     * @param string $date
     * @param int|array $lottery_type 传0则计算所有
     * @return array
     */
    public static function calculateUserDayData(int $wechat_user_id=0, string $date='', $lottery_type=0): array
    {
        $data['day_detail'] = self::calculateUserDayDataDetail($wechat_user_id, $date, $lottery_type);
        $data['day_all'] = self::calculateUserDayDataAll($wechat_user_id, $date);

        return [0, $data, '处理成功'];
    }

    /**
     * @param int $wechat_user_id
     * @param string $date
     * @param int|array $lottery_type 传0则计算所有
     * @return array
     */
    public static function calculateUserDayDataDetail(int $wechat_user_id=0, string $date='', $lottery_type=0): array
    {
        try {

        $data = [];
        $lottery_types = is_array($lottery_type) ? $lottery_type : ($lottery_type?[$lottery_type] : [26, 27]);
        foreach ($lottery_types as $lottery_type){
            $WechatUser = WechatUser::findOne($wechat_user_id);
            $user_id = $WechatUser->user_id;
            // 报表计算：今日投分，中奖、盈利、上分、下分 => lt_static_3d_user_profits_day
            $baseWhere = [
                'AND',
                ['=', 'wechat_user_id', $wechat_user_id],
                ['=', 'user_id', $user_id],
                ['=', 'lottery_type', $lottery_type],
                ['between', 'created_at', strtotime($date.' 00:00:00'), strtotime($date.' 23:59:59')],
            ];
            $Bet = Bets::find()->where($baseWhere)->limit(1)->one();
            if(empty($Bet)){
                continue;
            }

            list($BetMoneys, $BonusMoneys, $ProfitsMoneys) = self::getBetAndProfitsAndBonus($baseWhere);
            $setData = [
                'user_id' => $user_id,
                'wechat_user_id' => $wechat_user_id,
                'bet_money' => $BetMoneys,
                'bonus' => $BonusMoneys,
                'profits' => $ProfitsMoneys
            ];
            $now_time = time();
            $where = ['date'=>$date, 'wechat_user_id'=>$wechat_user_id, 'lottery_type'=>$lottery_type];
            $Static3dUserProfitsDay = Static3dUserProfitsDay::findOne($where);
            if(empty($Static3dUserProfitsDay)){
                $Static3dUserProfitsDay = new Static3dUserProfitsDay();
                $setData = array_merge($setData, [
                    'date' => $date,
                    'wechat_user_id' => $wechat_user_id,
                    'lottery_type' => $lottery_type,
                    'wechat_user_name' => $WechatUser->userName,
                    'created_at' => $now_time,
                ]);

            }
            $setData['updated_at'] = $now_time;
            $data[] = $setData;
            $Static3dUserProfitsDay->setAttributes($setData, false);
            //p($Static3dUserProfitsDay->getAttributes());
            if(!$Static3dUserProfitsDay->save()){
                throw_info(Json::encode($Static3dUserProfitsDay->getErrors()));
            }
        }
        }catch (\Exception $e){
            return [10002, [], $e->getMessage()];
        }

        return [0, $data, '处理成功'];
    }


    /**
     * 汇总用户数据一天的汇总
     * @param int $wechat_user_id
     * @param string $date
     * @return array
     */
    public static function calculateUserDayDataAll(int $wechat_user_id=0, string $date=''): array
    {
        try {
            $data = [];
            $WechatUser = WechatUser::findOne($wechat_user_id);
            $user_id = $WechatUser->user_id;
            // 报表计算：今日投分，中奖、盈利、上分、下分 => lt_static_3d_user_profits_day
            $baseWhere = [
                'AND',
                ['=', 'wechat_user_id', $wechat_user_id],
                ['=', 'user_id', $user_id],
                ['between', 'created_at', strtotime($date.' 00:00:00'), strtotime($date.' 23:59:59')],
            ];
            $Bet = Bets::find()->where($baseWhere)->limit(1)->one();
            if(empty($Bet)){
                throw_info('没有下注记录，无需汇总');
            }

            list($BetMoneys, $BonusMoneys, $ProfitsMoneys) = self::getBetAndProfitsAndBonus($baseWhere);
            $flowsWhere = [
                'member_id' => $wechat_user_id,
                'status' => AgentUsersBalanceService::FLOW_CHECK_STATUS_PASS,
            ];
            # 上分
            $UpMoneys = AgentUsersBalanceFlows::find()->where($flowsWhere)
                ->andWhere(['type'=>WechatUserService::TYPE_BALANCE_UP])->groupBy('member_id')->scalar();
            # 下分
            $DownMoneys = AgentUsersBalanceFlows::find()->where($flowsWhere)
                ->andWhere(['type'=>WechatUserService::TYPE_BALANCE_DOWN])->groupBy('member_id')->scalar();

            $setData = [
                'user_id' => $user_id,
                'wechat_user_id' => $wechat_user_id,
                'bet_money' => $BetMoneys,
                'bonus' => $BonusMoneys,
                'profits' => $ProfitsMoneys,
                'up_money' => $UpMoneys,
                'down_money' => $DownMoneys,
            ];
            $now_time = time();
            $where = ['date'=>$date, 'wechat_user_id'=>$wechat_user_id];
            $Static3dUserProfitsDayAll = Static3dUserProfitsDay::findOne($where);
            if(empty($Static3dUserProfitsDayAll)){
                $Static3dUserProfitsDayAll = new Static3dUserProfitsDayAll();
                $setData = array_merge($setData, [
                    'date' => $date,
                    'wechat_user_id' => $wechat_user_id,
                    'wechat_user_name' => $WechatUser->userName,
                    'created_at' => $now_time,
                ]);

            }
            $setData['updated_at'] = $now_time;
            $data[] = $setData;
            $Static3dUserProfitsDayAll->setAttributes($setData, false);
            //p($Static3dUserProfitsDayAll->getAttributes());
            if(!$Static3dUserProfitsDayAll->save()){
                throw_info(Json::encode($Static3dUserProfitsDayAll->getErrors()));
            }
        }catch (\Exception $e){
            return [10001, [], $e->getMessage()];
        }

        return [0, $data, '处理成功'];
    }

    /**
     * 获取用户：下注金额、中奖金额、盈利金额
     * @param $baseWhere
     * @return array
     */
    public static function getBetAndProfitsAndBonus(array $baseWhere=[]): array
    {

        # 投分
        $BetsQuery = Bets::find()->where($baseWhere)->groupBy(['wechat_user_id']);
        $BetMoneys = $BetsQuery->select(['money'=>'SUM(bet_money)'])->scalar() ? : 0.00;

        # 中奖
        $BonusMoneys = $BetsQuery->select(['money'=>'SUM(bonus)'])->andWhere(['=', 'status', CommonBaseService::STATUS_LT_SUCCESS])->scalar() ? : 0.00;

        # 盈亏
        $BetsQuery = Bets::find()->where($baseWhere)->groupBy(['wechat_user_id']);
        $ProfitsMoneys = $BetsQuery->select(['money'=>'SUM(profits)'])
            ->andWhere(['IN', 'status', [CommonBaseService::STATUS_LT_SUCCESS, CommonBaseService::STATUS_LT_FAIL]])->scalar() ? : 0.00;

        return [$BetMoneys, $BonusMoneys, $ProfitsMoneys];
    }

}
