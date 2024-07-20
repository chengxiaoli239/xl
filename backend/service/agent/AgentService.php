<?php
namespace backend\service\agent;

use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\models\wechat\Bets;
use backend\service\BaseService;
use backend\service\UserService;
use common\service\cache\CacheKeyService;
use common\service\open\ActionBaseService;
use common\service\open\aozhou5\ActionService;
use common\service\thirdD\CommonBaseService;
use common\tools\Timer;
use common\tools\Tool_Common;

class AgentService extends BaseService {

    /**
     * 汇总
     * @param $userId
     * @return array
     */
    public static function getCalcMoney($userId, $isSiteInfo=1): array
    {
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$userId]);
        if($isSiteInfo){
            $mKeyStaticsInfoKey = CacheKeyService::getSiteReportDataKey($TzSystemsUsers->uid); # 客户端推送数据
            if(!$siteStaticsInfos = commonRedis()->get($mKeyStaticsInfoKey)){
                $siteStaticsInfos = (new ActionService($TzSystemsUsers))->getSiteStaticsInfo();
                $mKey = CacheKeyService::getIsClientNeedLoginKey($TzSystemsUsers->uid);
                if(!empty($siteStaticsInfos['error'])){
                    commonRedis()->setex($mKey, 120, UserService::USER_CLIENT_LOGIN_NEED);
                }else{
                    commonRedis()->setex($mKey, 120, UserService::USER_CLIENT_LOGIN_NO);
                }
            }
            $thisWeekBetMoney = 0.00; # 本周下单金额
            $thisWeekProfits = 0.00; # 本周实际盈亏
            $lastWeekBetMoney = 0.00; # 上周下单金额
            $lastWeekProfits = 0.00; # 上周实际盈亏
            list($startOfWeek, $endOfWeek) = Timer::thisWeekTime();
            $startThisWeekDate = date('Y-m-d', $startOfWeek);
            foreach ($siteStaticsInfos as $k=>$siteStaticsInfo){
                Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '单日数据', ['siteStaticsInfo'=>$siteStaticsInfo]);
                list($date, $bs, $betMoney, $profits, $backWater, $realProfits) = $siteStaticsInfo;
                if($date == date('Y-m-d')){ # ($k+1) == count($siteStaticsInfos) OR
                    $todayProfits = $realProfits;
                    $todayBetMoney = $betMoney;
                }
                if($date<$startThisWeekDate){
                    # 上周
                    $lastWeekBetMoney += $betMoney;
                    $lastWeekProfits += $realProfits;
                }else{
                    # 本周
                    $thisWeekBetMoney += $betMoney;
                    $thisWeekProfits += $realProfits;
                }
            }
            $data = [
                $TzSystemsUsers->balance?:0.00, # 盘口余额
                $todayProfits, # 今日盈亏
                $todayBetMoney, # 有效金额，暂时计算今日，有待确认
                $thisWeekBetMoney, # 本周下单金额
                $thisWeekProfits, # 本周实际盈亏
                $lastWeekBetMoney, # 上周下单金额
                $lastWeekProfits, # 上周实际盈亏

            ];
            #p(['siteStaticsInfos'=>$siteStaticsInfos]);
        }else{
            // 今日时间
            list($startOfDay, $endOfDay) = Timer::todayTime();
            // 本周时间，获取本周的第一天（星期一）的时间戳、 获取本周的最后一天（星期日）的时间戳, 加上一天的秒数，然后减去1秒，以获取当天的最后一秒
            list($startOfWeek, $endOfWeek) = Timer::thisWeekTime();
            // 上周时间
            list($startOfLastWeek, $endOfLastWeek) = Timer::lastWeekTime();


            # 盘口余额、今日盈亏、有效金额、本周下单金额、本周实际盈亏、上周下单金额、上周实际盈亏
            $data = [
                $TzSystemsUsers->balance?:0.00, # 盘口余额
                self::getZoneProfits($TzSystemsUsers->account, $startOfDay, $endOfDay), # 今日盈亏
                self::getZoneBetMoney($TzSystemsUsers->account, $startOfDay, $endOfDay), # 有效金额，暂时计算今日，有待确认
                self::getZoneBetMoney($TzSystemsUsers->account, $startOfWeek, $endOfWeek), # 本周下单金额
                self::getZoneProfits($TzSystemsUsers->account, $startOfWeek, $endOfWeek), # 本周实际盈亏
                self::getZoneBetMoney($TzSystemsUsers->account, $startOfLastWeek, $endOfLastWeek), # 上周下单金额
                self::getZoneProfits($TzSystemsUsers->account, $startOfLastWeek, $endOfLastWeek), # 上周实际盈亏
            ];
        }

        return $data;
    }

    /**
     * 计算区间利润
     * @param $account - 盘口账号
     * @param $startTime
     * @param $endTime
     * @param float|int $plusTime 往前推后4个小时
     * @return false|float|string
     */
    private static function getZoneProfits($account, $startTime, $endTime, $plusTime=4*3600)
    {
        $profits = Bets::find()->select(['profitAndLoss'=>'SUM(profits)'])->where([
            'AND',
            ['=', 'site_account', $account],
            ['>=', 'created_at', $startTime+$plusTime],
            ['<=', 'created_at', $endTime+$plusTime],
            ['=', 'push_status', BetsBackend::PUSH_STATUS_SUCCESS],
            ['IN', 'status', [BetsBackend::STATUS_SUCCESS, BetsBackend::STATUS_FAIL]],
        ])->scalar();

        return $profits?:0.00;
    }

    /**
     * 有效金额
     * @param $account - 盘口账号
     * @param $startTime
     * @param $endTime
     * @param float|int $plusTime 往后推移4个小时
     * @return false|float|string
     */
    public static function getZoneBetMoney($account, $startTime, $endTime, $plusTime=4*3600)
    {
        $money = Bets::find()->select(['money'=>'SUM(bet_money)'])->where([
            'AND',
            ['=', 'site_account', $account],
            ['>=', 'created_at', $startTime+$plusTime],
            ['<=', 'created_at', $endTime+$plusTime],
            ['!=', 'status', CommonBaseService::STATUS_LT_CANCEL],
            ['=', 'push_status', BetsBackend::PUSH_STATUS_SUCCESS],
        ])->scalar();

        return $money?:0.00;

    }
}
