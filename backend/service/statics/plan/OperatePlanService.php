<?php
namespace backend\service\statics\plan;

use backend\models\BettingRecords;
use backend\models\PlanStaticProfits;
use backend\models\UserSysPlans;
use backend\service\BaseService;
use backend\service\HN0898Service;
use backend\service\SscDataService;
use common\service\lottery\LotteryTypeService;
use common\tools\RedisLock;
use common\tools\Tool_Common;

class OperatePlanService extends BaseService
{
    public static function initPlanPerDate($lotteryType=DEFAULT_LOTTERY_TYPE, $isAuto=1): bool
    {
        try {
            $nowHI = date('H:i:s', time() + 300);
            $lotteryTypeData = LotteryTypeService::getLotteryTypeData();
            $openingTime = $lotteryTypeData[$lotteryType]['opening_time'];
            $closingTime = $lotteryTypeData[$lotteryType]['closing_time'];
            # 勾选每天初始化，倍数、遗漏、 05:00:00 < 当前 < 08:00:00
            if ($isAuto == 2 or ($closingTime < $nowHI && $nowHI < $openingTime)) {
                $currentKjQiHao = HN0898Service::getCurrentQihao($lotteryType);
                # 翻倍计划初始化
                $where = [
                    'AND',
                    ['=', 'is_init_perdate', UserSysPlans::IS_INIT_PERDATE_Y],
                    ['=', 'status', 1],
                    ['=', 'is_batch_simulate', 0],
                    ['=', 'lottery_type', $lotteryType]
                ];
                $UserSysPlans = UserSysPlans::find()->where($where);
                foreach ($UserSysPlans->each(10) as $UserSysPlan) {
                    if (empty($UserSysPlan->singles)) continue; // 目前暂时只处理翻倍计划
                    $beforeSingle = $UserSysPlan->single;
                    $codes_hz = json_decode($UserSysPlan->hz_Arr, true);
                    $beforeCodesHz = $codes_hz;
                    $beforeSingleKey = $codes_hz['singles_key'];
                    $beforeCurrentMiss = $codes_hz['current_miss'];

                    $singles = explode('-', trim($UserSysPlan->singles));
                    if (empty($singles)) $singles = [$UserSysPlan->single];
                    $codes_hz['current_miss'] = 0;
                    $codes_hz['singles_key'] = 0;
                    $codes_hz['turn_key'] = 0;
                    $codes_hz['has_bet_nums'] = 0;
                    $codes_hz['betStatus'] = SscDataService::PLAN_BET_STATUS_INIT;

                    # 盈利归零
                    PlanStaticProfits::updateAll([
                        'cut_profits' => 0,
                        'current_qihao' => $currentKjQiHao,
                        'uid' => $UserSysPlan->uid,
                    ], ['plan_id' => $UserSysPlan->id]);

                    $single = $singles[0] ?? $beforeSingle;
                    $whereUpdate = ['id' => $UserSysPlan->id]; # 更新条件
                    $updateData = [
                        'single' => $single,
                        'hz_Arr' => json_encode($codes_hz, 320),
                    ];

                    $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
                    Tool_Common::log('/data/' . __FUNCTION__ . '_init', 'INFO', '每天翻倍计划初始化', [
                        'plan_id' => $UserSysPlan->id,
                        'beforeSingle' => $beforeSingle,
                        'afterSingle' => $single,
                        'beforeSingleKey' => $beforeSingleKey,
                        'beforeCurrentMiss' => $beforeCurrentMiss,
                        'beforeCodesHz' => $beforeCodesHz,
                        'afterCodesHz' => $codes_hz,
                        'rst' => $rst,
                    ]);
                }
                return true;
            }
        }catch (\Exception $e){
            Tool_Common::log('/plan/'.__FUNCTION__.'_err', 'ERR', '计划处理异常999', ['lottery_type'=>$lotteryType, 'err_msg'=>$e->getMessage()]);
            return $e->getMessage();
        }

        return 'lottery_type:'.$lotteryType.'计划初始化处理完成';
    }

    /**
     * 中则投、中则波推倍投
     * @param object $UserSysPlans
     * @return array
     */
    public static function operatePlans_6(object $UserSysPlans, $current_kj_qihao): array
    {
        try {
            if(!in_array($UserSysPlans->plan_type, [SscDataService::PLAN_TYPE_SINGLES_BET_WIN, SscDataService::PLAN_TYPE_BT_SINGLES_BET])){
                throw_info('非中则投类型6、10,plan_type:'.$UserSysPlans->plan_type.'不处理:plan_id:'.$UserSysPlans->id);
            }
            $lottery_type = $UserSysPlans->lottery_type;
            $RedisLock = new RedisLock();
            $Rkey = __FUNCTION__.'_redis_op_plan_6_10_'.$lottery_type.'_'.$UserSysPlans->id;
            \Yii::$app->redis->expire($Rkey, 120);
            if(!$RedisLock->lock($Rkey, 10)){
                throw_info('并发处理失败');
            }
            //$current_miss = ($codes_hz['is_init'] == 1) ? 0 : $codes_hz['current_miss'] + 1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
            $flag = SscDataService::isZjBefore($UserSysPlans->id, $recordDatas);
            $codes_hz = json_decode($UserSysPlans->hz_Arr, true);
            if(isset($codes_hz['filters'])){
                $codes_hz['filters']['current_kj_qihao'] = $current_kj_qihao;
            }
            $before_codes_hz = $codes_hz;
            $singles = explode('-', trim($UserSysPlans->singles));
            if(!is_array($codes_hz)) {
                throw_info('下注规则异常'); # 部分投注方式 hz_Arr 不是json 防止错误，
            }
            $single = $UserSysPlans->single;
            $singles_count = count($singles);
            if(!empty($UserSysPlans->singles)){
                $has_bet_nums = 0;
                if($flag == '-1'){
                    $next_single_key = 0;
                    $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                }else if($flag){
                    # 中则投的倍投
                    if(!isset($codes_hz['betStatus']) OR in_array($codes_hz['betStatus'], [SscDataService::PLAN_BET_STATUS_INIT, SscDataService::PLAN_BET_STATUS_WAIT])){
                        $next_single_key = 0;
                        $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                    }else{
                        if($codes_hz['betStatus'] == 1){
                            #$has_bet_nums = $codes_hz['singles_key'] + 1;
                            $has_bet_nums = $codes_hz['singles_key'];
                        }else{
                            $has_bet_nums = 0;
                        }
                        if($has_bet_nums >= $singles_count){
                            $next_single_key = 0;
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                            $has_bet_nums = 0;
                        }else{
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                            OperatePlanService::getPlanNextSingle($UserSysPlans->id, $codes_hz['singles_key'], $next_single_key, $lottery_type);
                        }
                    }
                }else{
                    $has_bet_nums = 0;
                    $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                    if(!isset($codes_hz['betStatus']) OR in_array($codes_hz['betStatus'], [SscDataService::PLAN_BET_STATUS_INIT, SscDataService::PLAN_BET_STATUS_WAIT])){
                        # 继续等待：betStatus=2
                        $next_single_key = 0;
                        $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                    }else{
                        # 上期是下注状态：回第一个倍数接着投 betStatus=1、next_single_key=0 或者 进入等待状态 betStatus=2
                        #$afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;  # 有待确认
                        $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                        $next_single_key = 0;

                        #$afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;  #
                    }

                }
                $single = $singles[$next_single_key];
                #$next_single_key = $codes_hz['singles_key']; # 倍数索引
                $codes_hz['singles_key'] = $next_single_key;
                $codes_hz['has_bet_nums'] = $has_bet_nums; # 已经下注的期数
            }else{
                # 中则投，无倍投
                if($flag == 1){ # plan_type:8、9 遗漏xx期投、遗漏x期倍投
                    $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                }else{
                    $afterBetStatus = SscDataService::PLAN_BET_STATUS_INIT;
                }
            }

            $codes_hz['betStatus'] = $afterBetStatus;
            $updateData = ['hz_Arr'=>json_encode($codes_hz, 320), 'single'=>$single];
            $logArr = ['plan_id'=>$UserSysPlans->id, 'isZjBefore'=>$flag, 'recordDatas'=>$recordDatas, 'before_codes_hz' => $before_codes_hz, 'after_code_hz'=>$codes_hz, 'single'=>$single, 'lottery_type'=>$lottery_type];
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '中则投倍投', $logArr);
            $whereUpdate = ['id'=>$UserSysPlans->id]; # 更新条件
            $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $logArr['plan_type_6'][$UserSysPlans->id]['rst'] = $rst;
        }catch (\Exception $e){
            Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '处理计划异常:', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
            return [10001, [], $e->getMessage()];
        }

        return [0, $logArr, '处理成功_plan_type:6'];
    }

    /**
     * 中则倍投
     * @param object $UserSysPlans
     * @return array
     */
    public static function operatePlans_15(object $UserSysPlans, $current_kj_qihao){
        try {
            if($UserSysPlans->plan_type != SscDataService::PLAN_TYPE_SINGLES_BET_2){
                throw_info('非中则倍投类型15,plan_type:'.$UserSysPlans->plan_type.'不处理');
            }
            $lottery_type = $UserSysPlans->lottery_type;
            #$current_kj_qihao = HN0898Service::getCurrentQihao($lottery_type);
            $RedisLock = new RedisLock();
            $Rkey = __FUNCTION__.'_redis_op_plan_15_'.$lottery_type.'_'.$UserSysPlans->id;
            \Yii::$app->redis->expire($Rkey, 120);
            if(!$RedisLock->lock($Rkey, 30)){
                throw_info('并发处理失败');
            }
            //$current_miss = ($codes_hz['is_init'] == 1) ? 0 : $codes_hz['current_miss'] + 1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
            $flag = SscDataService::isZjBefore($UserSysPlans->id, $recordDatas);
            $codes_hz = json_decode($UserSysPlans->hz_Arr, true);
            if(isset($codes_hz['filters'])){
                $codes_hz['filters']['current_kj_qihao'] = $current_kj_qihao;
            }
            $before_codes_hz = $codes_hz;
            $singles = explode('-', trim($UserSysPlans->singles));
            if(!is_array($codes_hz)) {
                throw_info('下注规则异常'); # 部分投注方式 hz_Arr 不是json 防止错误，
            }
            $single = $UserSysPlans->single;
            $singles_count = count($singles);
            $has_bet_nums = (int)$codes_hz['has_bet_nums'];
            if(!empty($UserSysPlans->singles)){
                switch ((int)$codes_hz['betStatus']){
                    case SscDataService::PLAN_BET_STATUS_INIT:
                        # 初始状态
                        if($flag){
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                            $has_bet_nums = 1;
                        }else{
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                            $has_bet_nums = 1;
                        }
                        break;
                    case SscDataService::PLAN_BET_STATUS_BETTING:
                        # 正在下注状态
                        if($flag){
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                            $has_bet_nums = 1;
                        }else{
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                            $has_bet_nums = $codes_hz['has_bet_nums'];
                        }
                        break;
                    case SscDataService::PLAN_BET_STATUS_WAIT:
                        # 等待状态
                        if($flag){
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                            $has_bet_nums = $codes_hz['has_bet_nums'] + 1;
                        }else{
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                            $has_bet_nums = $codes_hz['has_bet_nums'];
                        }
                        break;
                }

                if($has_bet_nums>$singles_count){
                    $has_bet_nums = 1;
                }
                $next_single_key = $has_bet_nums - 1;
                $single = $singles[$next_single_key];
                $codes_hz['singles_key'] = $next_single_key;
                $codes_hz['has_bet_nums'] = $has_bet_nums;
            }else{
                # 中则投，无倍投
                if($flag == 1){ # plan_type:8、9 遗漏xx期投、遗漏x期倍投
                    $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                }else{
                    $afterBetStatus = SscDataService::PLAN_BET_STATUS_INIT;
                }
            }

            $codes_hz['betStatus'] = $afterBetStatus;
            $updateData = ['hz_Arr'=>json_encode($codes_hz, 320), 'single'=>$single];
            $logArr = ['plan_id'=>$UserSysPlans->id, 'isZjBefore'=>$flag, 'recordDatas'=>$recordDatas, 'before_codes_hz' => $before_codes_hz, 'after_code_hz'=>$codes_hz, 'single'=>$single, 'lottery_type'=>$lottery_type];
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '中则倍投', $logArr);
            $whereUpdate = ['id'=>$UserSysPlans->id]; # 更新条件
            $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $logArr['plan_type_15'][$UserSysPlans->id]['rst'] = $rst;
        }catch (\Exception $e){
            Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '处理计划异常:', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
            return [10001, [], $e->getMessage()];
        }

        return [0, $logArr, '处理成功_plan_type:6'];
    }

    /**
     * 遗漏中则倍投
     * @param object $UserSysPlans
     * @return array
     */
    public static function operatePlans_17(object $UserSysPlans, $current_kj_qihao){
        try {
            if($UserSysPlans->plan_type != SscDataService::PLAN_TYPE_YL_ZZ_SINGLES_BET){
                throw_info('非遗漏中则倍/投类型17,plan_type:'.$UserSysPlans->plan_type.'不处理');
            }

            $lottery_type = $UserSysPlans->lottery_type;
            $RedisLock = new RedisLock();
            $Rkey = __FUNCTION__.'_redis_op_plan_17_'.$lottery_type.'_'.$UserSysPlans->id;
            \Yii::$app->redis->expire($Rkey, 120);
            if(!$RedisLock->lock($Rkey, 30)){
                throw_info('并发处理失败');
            }
            //$current_miss = ($codes_hz['is_init'] == 1) ? 0 : $codes_hz['current_miss'] + 1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
            $flag = SscDataService::isZjBefore($UserSysPlans->id, $recordDatas);
            $codes_hz = json_decode($UserSysPlans->hz_Arr, true);
            if(isset($codes_hz['filters'])){
                $codes_hz['filters']['current_kj_qihao'] = $current_kj_qihao;
            }
            $befor_codes_hz = $codes_hz;
            if(!is_array($codes_hz)) {
                throw_info('下注规则异常'); # 部分投注方式 hz_Arr 不是json 防止错误，
            }
            $singles = explode('-', trim($UserSysPlans->singles));
            if(empty($singles) OR count($singles)<2) {
                $singles = [$UserSysPlans->single, $UserSysPlans->single];
            }
            $singles_count = count($singles);

            switch ((int)$codes_hz['betStatus']){
                case SscDataService::PLAN_BET_STATUS_INIT:
                    # 初始状态
                    if($flag){
                        $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                        $has_bet_nums = 0;
                        $current_miss = 0; #
                    }else{
                        $current_miss = (int)$codes_hz['current_miss'] + 1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
                        if($current_miss>=$codes_hz['bet_while_miss']){
                            $has_bet_nums = 1;
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                        }else{
                            $has_bet_nums = 0;
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                        }
                    }
                    break;
                case SscDataService::PLAN_BET_STATUS_BETTING:
                    # 正在下注状态
                    if($flag){
                        $current_miss = 0;
                        if($codes_hz['has_bet_nums']>=$singles_count){
                            $has_bet_nums = 1;
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                        }else{
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                            $has_bet_nums = $codes_hz['has_bet_nums'] + 1;
                        }
                    }else{
                        # 有疑问？？？？？ 投还是不投
                        $current_miss = $codes_hz['current_miss'] + 1;
                        if($current_miss>=$codes_hz['bet_while_miss']){
                            $current_miss = 0;
                            $has_bet_nums = 0;
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                        }else{
                            $has_bet_nums = 0;
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                        }
                    }
                    break;
                case SscDataService::PLAN_BET_STATUS_WAIT:
                    # 等待状态
                    if($flag){
                        $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                        $current_miss = 0; #
                        $has_bet_nums = 0;
                    }else{
                        $current_miss = (int)$codes_hz['current_miss'] + 1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
                        if($current_miss>=$codes_hz['bet_while_miss']){
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_BETTING;
                            $has_bet_nums = (int)$codes_hz['has_bet_nums'] + 1;
                        }else{
                            $has_bet_nums = 0;
                            $afterBetStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                        }
                    }
                    break;
            }
            if($has_bet_nums>$singles_count){
                $has_bet_nums = 1;
            }
            $next_single_key = $has_bet_nums - 1;
            if($has_bet_nums==0 OR $has_bet_nums>$singles_count){
                $next_single_key = 0;
            }
            $single = $singles[$next_single_key];
            $codes_hz['singles_key'] = $next_single_key;
            $codes_hz['betStatus'] = $afterBetStatus;
            $codes_hz['has_bet_nums'] = $has_bet_nums;
            $codes_hz['current_miss'] = $current_miss;

            $updateData = ['hz_Arr'=>json_encode($codes_hz, 320), 'single'=>$single];
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '遗漏中则投|倍投', ['plan_id'=>$UserSysPlans->id, 'isZjBefore'=>$flag, 'recordDatas'=>$recordDatas, 'before_codes_hz' => $befor_codes_hz, 'after_code_hz'=>$codes_hz, 'single'=>$single, 'lottery_type'=>$lottery_type]);
            $whereUpdate = ['id'=>$UserSysPlans->id]; # 更新条件
            $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $logArr['6_8'][$UserSysPlans->id]['rst'] = $rst;
        }catch (\Exception $e){
            Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '处理计划异常:', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
        }

        return [];
    }

    /**
     * 遗漏投
     * @param object $UserSysPlans
     * @return array
     */
    public static function operatePlans_8(object $UserSysPlans, $current_kj_qihao){
        try {
            if($UserSysPlans->plan_type != SscDataService::PLAN_TYPE_YL_BET){
                throw_info('非中则投类型8,plan_type:'.$UserSysPlans->plan_type.'不处理');
            }

            $lottery_type = $UserSysPlans->lottery_type;
            $RedisLock = new RedisLock();
            $Rkey = __FUNCTION__.'_redis_op_plan_8_'.$lottery_type.'_'.$UserSysPlans->id;
            if(!$RedisLock->lock($Rkey, 30)){
                throw_info('并发处理失败');
            }
            \Yii::$app->redis->expire($Rkey, 120);

            //$current_miss = ($codes_hz['is_init'] == 1) ? 0 : $codes_hz['current_miss'] + 1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
            $flag = SscDataService::isZjBefore($UserSysPlans->id, $recordDatas);
            $codes_hz = json_decode($UserSysPlans->hz_Arr, true);
            if(isset($codes_hz['filters'])){
                $codes_hz['filters']['current_kj_qihao'] = $current_kj_qihao;
            }
            $befor_codes_hz = $codes_hz;
            if(!is_array($codes_hz)) {
                throw_info('下注规则异常'); # 部分投注方式 hz_Arr 不是json 防止错误，
            }
            if($flag == 1 OR (in_array($UserSysPlans->plan_type, [8]) && $codes_hz['current_miss']>=$codes_hz['bet_while_miss'])){ # plan_type:8、9 遗漏xx期投、遗漏xx期投
                $betStatus = 1;
            }else{
                $betStatus = 0;
            }
            if(in_array($flag, [1, -1])){
                $current_miss = 0;
            }else{
                $current_miss = $codes_hz['current_miss'] + 1;
            }
            $codes_hz['current_miss'] = $current_miss;
            $single = $UserSysPlans->single;

            $codes_hz['betStatus'] = $betStatus;
            $updateData = ['hz_Arr'=>json_encode($codes_hz, 320), 'single'=>$single];
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '中则投倍投', ['plan_id'=>$UserSysPlans->id, 'isZjBefore'=>$flag, 'recordDatas'=>$recordDatas, 'before_codes_hz' => $befor_codes_hz, 'after_code_hz'=>$codes_hz, 'single'=>$single, 'lottery_type'=>$lottery_type]);
            $whereUpdate = ['id'=>$UserSysPlans->id]; # 更新条件
            $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $logArr['6_8'][$UserSysPlans->id]['rst'] = $rst;
        }catch (\Exception $e){
            Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '处理计划异常:', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
        }

        return [];
    }

    /**
     * @desc A出x次B出y次投B、A出x次B出y次投B_2 计划处理
     * @param int $lottery_type
     * @return bool
     */
    public static function opProfitsPlans12_13(int $lottery_type = DEFAULT_LOTTERY_TYPE): bool
    {
        $RedisLock = new RedisLock();
        $Rkey = __FUNCTION__.'_redis_'.$lottery_type;
        \Yii::$app->redis->expire($Rkey, 120);
        if(!$RedisLock->lock($Rkey, 30)){
            Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', 'A出x次B出y次投B-处理锁错误', ['lottery_type'=>$lottery_type, 'err_msg'=>'获取锁失败']);
            return false;
        }

        try {
            # plan_type:12 A出x次B出y次投B
            $where = ['AND', ['IN', 'plan_type', UserSysPlans::$A_x_arise_B_y_arise_bet_B_types], ['=', 'is_batch_simulate', 0], ['=', 'status', 1], ['=', 'lottery_type', $lottery_type]];
            if($UserSysPlans = UserSysPlans::find()->where($where)->all()){
                foreach ($UserSysPlans as $UserSysPlan){
                    $plan_type = $UserSysPlan->plan_type;
                    $hzArr = json_decode($UserSysPlan->hz_Arr, true);
                    $zj_group = SscDataService::getNewZjGroupByPlanId($UserSysPlan->id, $hzArr['A_x_B_y_start_time'], $qihao, $zjResult);
                    $hzArr_update_before = $hzArr;
                    $A_x_B_y_status = $hzArr['A_x_B_y_status']; # 状态：0初始1等待中2正在投
                    //$A_x_B_y_status = $hzArr[];

                    if(!$hzArr['A_x_B_y_start_time']){
                        $hzArr['A_x_B_y_start_time'] = date('Y-m-d H:i:s'); # 计划最新条件起始时间
                    }
                    if($hzArr['A_x_B_y_status'] == 0){
                        $hzArr['A_x_B_y_status'] = 1;
                    }

                    #$hzArr["current_arise_A_times"]; # A上奖次
                    #$hzArr["current_arise_B_times"]; # B上奖次
                    #$hzArr['arise_A_times']; # 设置A条件
                    #$hzArr['arise_B_times']; # 设置B条件

                    $singles = explode('-', trim($UserSysPlan->singles));
                    if(empty($singles)) $singles = [$UserSysPlan->single];
                    $single = $UserSysPlan->single;
                    if($zj_group == 'arise_A_codes'){
                        # 上 A
                        SscDataService::operateZjGroupA($A_x_B_y_status, $plan_type, $hzArr);
                        if($plan_type == 12){
                            if(in_array($A_x_B_y_status, [0, 1])){
                                $next_single_key = 0;
                                $single = $singles[$next_single_key];
                            }elseif($A_x_B_y_status == 2){
                                $single = self::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
                            }
                        }elseif($plan_type == 13){
                            $next_single_key = $hzArr['singles_key'];
                        }
                    }else{
                        # 上 B
                        SscDataService::operateZjGroupB($A_x_B_y_status, $plan_type, $hzArr);
                        if($plan_type == 13){
                            if($A_x_B_y_status == 2 && $hzArr['A_x_B_y_status'] == 2 && ($hzArr['current_arise_B_times'] == $hzArr['arise_B_times'])){
                                $single = self::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
                            }elseif ($A_x_B_y_status == 1 && $hzArr['A_x_B_y_status'] == 2 && ($hzArr['current_arise_B_times'] == $hzArr['arise_B_times'])){
                                # 启动下注
                                $count_singles = count($singles);
                                $next_single_key = $hzArr['start_bet_yl_nums']%$count_singles;
                                //}elseif ($A_x_B_y_status == 2 && $hzArr['A_x_B_y_status'] == 1){
                            }elseif ($hzArr['A_x_B_y_status'] == 1){
                                # 不中等待下次满足
                                $next_single_key = $hzArr['singles_key'];
                            }else{
                                $next_single_key = 0;
                            }
                            $single = $singles[$next_single_key];
                        }else{
                            $next_single_key = 0;
                            $single = $singles[$next_single_key];
                        }
                    }
                    $hzArr['singles_key'] = (int)$next_single_key;
                    $hzArr_update_after = $hzArr;
                    Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'INFO', '计划更新前后11', ['plan_id'=>$UserSysPlan->id, 'zj_group'=>$zj_group, 'qihao'=>$qihao, 'zjResult'=>$zjResult, 'hzArr_update_before'=>$hzArr_update_before, 'hzArr_update_after'=>$hzArr_update_after, 'next_single_key'=>$next_single_key, 'single'=>$single, 'singles'=>$UserSysPlan->singles]);

                    $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
                    $updateData = ['single'=>$single, 'hz_Arr'=>json_encode($hzArr, 320)];
                    $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
                    $logArr['plan_8'][$UserSysPlan->id]['updateData'] = $updateData;
                    $logArr['plan_8'][$UserSysPlan->id]['rst'] = $rst;
                }
            }
        }catch (\Exception $exception){
            Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type.'_'.$UserSysPlan->id, 'ERR', 'A出x次B出y次投B-处理错误', ['plan_id'=>$UserSysPlans->id, 'err_msg'=>$exception->getMessage()]);
            return false;
        }

        return true;
    }

    /**
     * @desc 14区间遗漏投 计划处理
     * @param int $lottery_type
     * @return bool
     */
    public static function opProfitsPlans14(int $lottery_type = DEFAULT_LOTTERY_TYPE): bool
    {
        $RedisLock = new RedisLock();
        $Rkey = __FUNCTION__.'_redis_'.$lottery_type;
        \Yii::$app->redis->expire($Rkey, 120);
        if(!$RedisLock->lock($Rkey, 10)){
            Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '区间遗漏投-处理锁错误', ['lottery_type'=>$lottery_type, 'err_msg'=>'获取锁失败']);
            return false;
        }

        try {
            # plan_type:14区间遗漏投
            $where = ['AND', ['=', 'plan_type', 14], ['=', 'status', 1], ['=', 'is_batch_simulate', 0], ['=', 'lottery_type', $lottery_type]];
            if($UserSysPlans = UserSysPlans::find()->where($where)->all()){
                foreach ($UserSysPlans as $UserSysPlan){
                    $plan_type = $UserSysPlan->plan_type;
                    $hzArr = json_decode($UserSysPlan->hz_Arr, true);
                    $hzArr_update_before = $hzArr;

                    $singles = explode('-', trim($UserSysPlan->singles));
                    if(empty($singles)) $singles = [$UserSysPlan->single];
                    $single = $UserSysPlan->single;

                    $areaBetStatus = isset($hzArr['areaBetStatus']) ? (int)$hzArr['areaBetStatus'] : 0; # 0监控中1下注中
                    $area_all_qishus = $hzArr['area_all_qishus']; # 区间统计期数
                    $area_yl_qishus = $hzArr['area_yl_qishus']; # 区间遗漏期数
                    $area_profits = $hzArr['area_profits']; # 区间止盈
                    $area_loss = $hzArr['area_loss']; # 区间止损

                    $logArr = ['plan_id'=>$UserSysPlan->id, 'areaBetStatus'=>$areaBetStatus, 'hzArr_update_before'=>$hzArr_update_before, 'single'=>$single];
                    # 2 # 监控中状态统计
                    if($areaBetStatus == 0){
                        $area_bet_type = $hzArr['area_bet_type'] ? (int)$hzArr['area_bet_type'] : 1; # 下注起算类型：1用户下注记录统计 2:最近开奖统计
                        $area_arise_qishus = SscDataService::get_area_arise_qishus($UserSysPlan, $area_all_qishus, $hzArr['start_qihao'], $area_bet_type); # 指定期数上了多少期
                        $bmsg = '不符合条件0【'.$area_arise_qishus.'<=('.$area_all_qishus.'-'.$area_yl_qishus.')】';
                        if($area_arise_qishus <= ($area_all_qishus-$area_yl_qishus)){ # 上奖期数 = 统计期数 - 遗漏期数
                            # 满足指定期数条件 -> 启动下注
                            $bmsg = '符合条件【'.$area_arise_qishus.'<=('.$area_all_qishus.'-'.$area_yl_qishus.')】';
                            $hzArr['start_qihao'] = HN0898Service::getQihao($lottery_type); # 当前期号，统计利润时候不包含记录的记录的期号
                            $areaBetStatus = 1;
                        }
                        $next_single_key = 0;
                        $hzArr['area_arise_qishus'] = $area_arise_qishus;
                        $logArr = array_merge($logArr, [
                            'area_arise_qishus' => $area_arise_qishus,
                            'bet_msg' => '监控中-'.$bmsg.'['.$UserSysPlan->id.']',
                        ]);
                    }else{
                        $profits = SscDataService::getPlanProfits($UserSysPlan, ['>=', 'qihao', $hzArr['start_qihao']]); # 一个计划当前利润
                        $hzArr['current_area_profits'] = $profits;
                        $bmsg = '不符合止盈'.$hzArr['area_profits'].'止损'.$hzArr['area_loss'];
                        if($profits<0 && $area_loss<(0-$profits)){
                            $bmsg = '符合止损:'.$area_loss.'<('.(0-$profits).')';
                            $areaBetStatus = 0;
                            $hzArr['current_area_profits'] = 0.00;
                            $hzArr['start_qihao'] = HN0898Service::getQihao($lottery_type); # 重新设置开始计算期号，避免无时间间隔的连续止损，大遗漏倍投问题
                            $next_single_key = 0; # 止损，倍数重新
                        }else{
                            if($profits>=$area_profits){
                                $bmsg = '符合止赢:'.$profits.'>'.$area_profits;
                                $areaBetStatus = 0;
                                $hzArr['area_arise_qishus'] = 0;
                                $hzArr['current_area_profits'] = 0.00;
                                $hzArr['start_qihao'] = HN0898Service::getQihao($lottery_type); # 重新设置开始计算期号，避免大遗漏倍投问题
                            }
                            $isZjBefore = SscDataService::isZjBefore($UserSysPlan->id);
                            $next_single_key = (int)$hzArr['singles_key'];
                            if(!$isZjBefore){
                                self::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
                            }else{
                                $next_single_key = 0;
                            }
                        }


                        $logArr['bet_msg'] = '下注中，本回合盈利：'.$profits.','.$bmsg.'['.$UserSysPlan->id.']';
                    }

                    $single = $singles[$next_single_key] ? :$single;

                    $hzArr['singles_key'] = $next_single_key; # 下一期倍数
                    $hzArr['area_profits'] = $area_profits; # 区间止盈
                    $hzArr['area_loss'] = $area_loss; # 区间止损
                    $hzArr['areaBetStatus'] = $areaBetStatus; # 投注状态

                    $logArr['hzArr_update_after'] = $hzArr;

                    $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
                    $updateData = ['single'=>$single, 'hz_Arr'=>json_encode($hzArr, 320)];
                    $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
                    $logArr['save_rst'] = $rst;
                    Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'INFO', '计划更新前后21', $logArr);
                }
            }
        }catch (\Exception $exception){
            Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type.'_'.$UserSysPlan->id, 'ERR', '区间遗漏投-处理错误', ['plan_id'=>$UserSysPlan->id, 'err_msg'=>$exception->getMessage()]);
            return false;
        }

        return true;
    }

    /**
     * 18 - 遗漏x期投y期
     * @param $UserSysPlan
     * @param $currentKjQiHao
     * @return true
     */
    public static function operatePlans18($UserSysPlan, $currentKjQiHao): bool
    {
        $planId = $UserSysPlan->id;
        $lottery_type = $UserSysPlan->lottery_type;
        $flag = SscDataService::isZjBefore($UserSysPlan->id);
        # 遗漏期数[不中奖期数]
        //$lossQs = SscDataService::getLossQs($UserSysPlan->id);

        $hzArr = json_decode($UserSysPlan->hz_Arr, true);
        $beforeHzArr = $hzArr;
        if(isset($hzArr['filters'])){
            $hzArr['filters']['current_kj_qihao'] = $currentKjQiHao;
        }

        $betStatus = $hzArr['betStatus']??0; # 开奖之后初始标识改成 0
        $current_miss = $hzArr['current_miss']??0; # 当前遗漏
        $singles_key = $hzArr['singles_key']??0; # 倍数索引
        $betWhileMiss = $hzArr['bet_while_miss']??0;
        $has_bet_nums = $hzArr['has_bet_nums']??0; # 已投数量
        $singles = explode('-', trim($UserSysPlan->singles));
        if(empty($singles)) $singles = [$UserSysPlan->single]; # 不填的情况
        $singles_count = count($singles);

        if(in_array($betStatus, [SscDataService::PLAN_BET_STATUS_INIT, SscDataService::PLAN_BET_STATUS_WAIT])){
            if($flag){
                # 中奖
                $singles_key = 0;
                $current_miss = 0;
                $has_bet_nums = 0;
            }else{
                # 不中奖
                $current_miss += 1;
                if($current_miss>=$betWhileMiss){
                    $singles_key = 0;
                    $betStatus = SscDataService::PLAN_BET_STATUS_BETTING; // 进入下注状态
                    $has_bet_nums = 1;
                }
            }
        }elseif($betStatus == SscDataService::PLAN_BET_STATUS_BETTING){
            if($flag){
                $current_miss = 0;
            }else{
                $current_miss += 1;
            }
            if($singles_key<($singles_count-1)){
                $singles_key += 1; # 还没投完继续投
                $has_bet_nums += 1;
            }else{
                $singles_key = 0; # 投完倍数进入等待状态
                $betStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                $has_bet_nums = 0;
                $current_miss = 0;
            }
        }
        if($has_bet_nums>$singles_count){
            $has_bet_nums = 1;
        }
        $next_single_key = $has_bet_nums - 1;
        if($has_bet_nums==0 OR $has_bet_nums>$singles_count){
            $singles_key = 0;
        }

        //$single = self::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
        $single = $singles[$singles_key];
        $hzArr = array_merge($hzArr, [
            'current_miss' => $current_miss,
            'singles_key' => $singles_key,
            'betStatus' => $betStatus,
            'has_bet_nums' => $has_bet_nums,
        ]);
        $updateData = ['hz_Arr'=>json_encode($hzArr, 320), 'single'=>$single];
        $rst = UserSysPlans::updateAll($updateData, ['id'=>$UserSysPlan->id]);
        Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '遗漏x期投y期', [
            'planId'=>$planId,
            'flag'=>$flag,
            'singles'=>$singles,
            'singles_count'=>$singles_count,
            'beforeHzArr'=>$beforeHzArr,
            'afterHzArr'=>$hzArr,
            'rst'=>$rst,
        ]);

        return true;
    }

    /**
     * 19 - 遗漏x期起投
     * @param $UserSysPlan
     * @param $currentKjQiHao
     * @return true
     */
    public static function operatePlans19($UserSysPlan, $currentKjQiHao): bool
    {
        $planId = $UserSysPlan->id;
        $lottery_type = $UserSysPlan->lottery_type;
        $flag = SscDataService::isZjBefore($UserSysPlan->id);
        # 遗漏期数[不中奖期数]
        //$lossQs = SscDataService::getLossQs($UserSysPlan->id);

        $hzArr = json_decode($UserSysPlan->hz_Arr, true);
        $beforeHzArr = $hzArr;
        if(isset($hzArr['filters'])){
            $hzArr['filters']['current_kj_qihao'] = $currentKjQiHao;
        }

        $betStatus = $hzArr['betStatus']??0; # 开奖之后初始标识改成 0
        $current_miss = $hzArr['current_miss']??0; # 当前遗漏
        $singles_key = $hzArr['singles_key']??0; # 倍数索引
        $betWhileMiss = $hzArr['bet_while_miss']??0;
        $has_bet_nums = $hzArr['has_bet_nums']??0; # 已投数量
        $singles = explode('-', trim($UserSysPlan->singles));
        if(empty($singles)) $singles = [$UserSysPlan->single]; # 不填的情况
        $singles_count = count($singles);

        if(in_array($betStatus, [SscDataService::PLAN_BET_STATUS_INIT, SscDataService::PLAN_BET_STATUS_WAIT])){
            if($flag){
                $betStatus = SscDataService::PLAN_BET_STATUS_WAIT; # 进入等待状态
                # 中奖
                $singles_key = 0;
                $current_miss = 0;
                $has_bet_nums = 0;
            }else{
                # 不中奖
                $current_miss += 1;
                if($current_miss>=$betWhileMiss){
                    $singles_key = 0;
                    $betStatus = SscDataService::PLAN_BET_STATUS_BETTING; // 进入下注状态
                    $has_bet_nums = 1;
                }
            }
        }elseif($betStatus == SscDataService::PLAN_BET_STATUS_BETTING){
            $betStatus = SscDataService::PLAN_BET_STATUS_BETTING;
            $has_bet_nums += 1;
            if($flag){
                $singles_key = 0; # 中回到第一个倍数
                $current_miss = 0;
            }else{
                $current_miss += 1;
                if($singles_key<($singles_count-1)){
                    $singles_key += 1; # 不中，还没投完继续投
                    //$single = self::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
                }else{
                    $singles_key = 0;
                    //$betStatus = SscDataService::PLAN_BET_STATUS_WAIT; # 投完倍数进入等待状态
                }
            }
        }
        $singles_key = $singles_key??0;
        $single = $singles[$singles_key];

        $hzArr = array_merge($hzArr, [
            'current_miss' => $current_miss,
            'singles_key' => $singles_key,
            'betStatus' => $betStatus,
            'has_bet_nums' => $has_bet_nums,
        ]);
        $updateData = ['hz_Arr'=>json_encode($hzArr, 320), 'single'=>$single];
        $rst = UserSysPlans::updateAll($updateData, ['id'=>$UserSysPlan->id]);
        Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '遗漏x期起投', [
            'planId' => $planId,
            'flag' => $flag,
            'singles' => $singles,
            'singles_count' => $singles_count,
            'beforeHzArr' => $beforeHzArr,
            'afterHzArr' => $hzArr,
            'rst' => $rst,
        ]);

        return true;
    }

    /**
     * 20 - 中则倍投2
     * @param $UserSysPlan
     * @param $currentKjQiHao
     * @return true
     */
    public static function operatePlans20($UserSysPlan, $currentKjQiHao): bool
    {
        $planId = $UserSysPlan->id;
        $lottery_type = $UserSysPlan->lottery_type;
        $flag = SscDataService::isZjBefore($UserSysPlan->id);
        # 遗漏期数[不中奖期数]
        //$lossQs = SscDataService::getLossQs($UserSysPlan->id);

        $hzArr = json_decode($UserSysPlan->hz_Arr, true);
        $beforeHzArr = $hzArr;
        if(isset($hzArr['filters'])){
            $hzArr['filters']['current_kj_qihao'] = $currentKjQiHao;
        }

        $betStatus = $hzArr['betStatus']??0; # 开奖之后初始标识改成 0
        $current_miss = $hzArr['current_miss']??0; # 当前遗漏
        $singles_key = $hzArr['singles_key']??0; # 倍数索引
        $has_bet_nums = $hzArr['has_bet_nums']??0; # 已投数量
        $singles = explode('-', trim($UserSysPlan->singles));
        if(empty($singles)) $singles = [$UserSysPlan->single]; # 不填的情况
        $singles_count = count($singles);
        $has_bet_nums += 1;

        if(in_array($betStatus, [SscDataService::PLAN_BET_STATUS_INIT, SscDataService::PLAN_BET_STATUS_WAIT])){
            $betStatus = SscDataService::PLAN_BET_STATUS_BETTING; # 进入等待状态
            if($flag){
                # 中奖
                $singles_key += 1;
                $current_miss = 0;
            }else{
                # 不中奖
                $current_miss += 1;
                $singles_key = 0;
            }
        }elseif($betStatus == SscDataService::PLAN_BET_STATUS_BETTING){
            $betStatus = SscDataService::PLAN_BET_STATUS_BETTING;
            if($flag){
                $current_miss = 0;
                if($singles_key<($singles_count-1)){
                    $singles_key += 1; # 不中，还没投完继续投
                }else{
                    $singles_key = 0;
                }
            }else{
                $singles_key = 0; # 不中回到第一个倍数
                $current_miss += 1;
            }
        }
        $single = $singles[(int)$singles_key];

        $hzArr = array_merge($hzArr, [
            'current_miss' => $current_miss,
            'singles_key' => $singles_key,
            'betStatus' => $betStatus,
            'has_bet_nums' => $has_bet_nums,
        ]);
        $updateData = ['hz_Arr'=>json_encode($hzArr, 320), 'single'=>$single];
        $rst = UserSysPlans::updateAll($updateData, ['id'=>$UserSysPlan->id]);
        Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '遗漏x期起投', [
            'planId' => $planId,
            'flag' => $flag,
            'singles' => $singles,
            'singles_count' => $singles_count,
            'beforeHzArr' => $beforeHzArr,
            'afterHzArr' => $hzArr,
            'rst' => $rst,
        ]);

        return true;
    }

    /**
     * 21 - 亏损金额启动
     * @param $UserSysPlan
     * @param $currentKjQiHao
     * @return true
     */
    public static function operatePlans21($UserSysPlan, $currentKjQiHao): bool
    {
        $planId = $UserSysPlan->id;
        $lottery_type = $UserSysPlan->lottery_type;
        $isZjBefore = SscDataService::isZjBefore($UserSysPlan->id);
        # 遗漏期数[不中奖期数]
        //$lossQs = SscDataService::getLossQs($UserSysPlan->id);

        $hzArr = json_decode($UserSysPlan->hz_Arr, true);
        $beforeHzArr = $hzArr;
        if(isset($hzArr['filters'])){
            $hzArr['filters']['current_kj_qihao'] = $currentKjQiHao;
        }

        $betStatus = $hzArr['betStatus']??0; # 开奖之后初始标识改成 0
        $current_miss = $hzArr['current_miss']??0; # 当前遗漏
        $singles_key = $hzArr['singles_key']??0; # 倍数索引
        $has_bet_nums = $hzArr['has_bet_nums']??0; # 已投数量
        $areaLossStart = $hzArr['area_loss_start']??0; # 区间亏损起投金额
        $startLoss = $hzArr['start_loss']??0; # 触发启动金额
        $areaLossEnd = $hzArr['area_loss']??0; # 区间止损金额
        $areaProfitsEnd = $hzArr['area_profits']??0; # 区间止盈金额
        $singles = (!empty(trim($UserSysPlan->singles))) ? explode('-', trim($UserSysPlan->singles)) : [];
        if(empty($singles)) $singles = [$UserSysPlan->single]; # 不填的情况
        $singles_count = count($singles);
        $has_bet_nums += 1;

        //$areaLoss = SscDataService::getPlanAreaLoss($UserSysPlan, $hzArr['start_qihao']);
        $areaProfits = SscDataService::getPlanProfits($UserSysPlan, ['>=', 'qihao', $hzArr['filters']['start_qihao']], 1); # 计划当前区间利润
        $hzArr['current_area_profits'] = $areaProfits; # 当前区间利润
        # 2 # 监控中状态统计
        if(in_array($betStatus, [SscDataService::PLAN_BET_STATUS_INIT, SscDataService::PLAN_BET_STATUS_WAIT])){
            if((0-$areaProfits) >= $areaLossStart){ # 亏损 > 起始亏损金
                # 满足指定期数条件 -> 启动下注
                $areaMsg = '【亏'.abs($areaProfits).'>='.$areaLossStart.'符合启动条件，开始下注...】';
                $hzArr['filters']['start_qihao'] = HN0898Service::getQihao($lottery_type); # 当前期号，统计利润时候不包含记录的记录的期号
                $startLoss = $areaProfits;
                $betStatus = SscDataService::PLAN_BET_STATUS_BETTING;
            }else{
                $betStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                $areaMsg = '不符合条件，等待中...';
            }
            $singles_key = 0;
            $hzArr['current_area_profits'] = $areaProfits; # 当前区间利润
        }else{
            if((0-$areaProfits)>=$areaLossEnd){
                $areaMsg = '符合止损:亏'.(0-$areaProfits).'>='.$areaLossEnd;
                $betStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                $hzArr['current_area_profits'] = 0.00;
                $hzArr['filters']['start_qihao'] = HN0898Service::getQihao($lottery_type); # 重新设置开始计算期号，避免无时间间隔的连续止损，大遗漏倍投问题
                $singles_key = 0; # 止损，倍数重新
            }elseif($areaProfits>=$areaProfitsEnd){
                $areaMsg = '符合止赢:赢'.$areaProfits.'>'.$areaProfitsEnd;
                $betStatus = SscDataService::PLAN_BET_STATUS_WAIT;
                $hzArr['current_area_profits'] = 0.00;
                $hzArr['filters']['start_qihao'] = HN0898Service::getQihao($lottery_type); # 重新设置开始计算期号，避免大遗漏倍投问题
            }else{
                //$areaMsg = '不符合止盈'.$areaProfitsEnd.'止损'.$areaLossEnd;
                $areaMsg = '下注中...';
            }
            if(!$isZjBefore){
                //self::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
                $current_miss += 1;
                if($singles_key<($singles_count-1)){
                    $singles_key += 1; # 不中，还没投完继续投
                }else{
                    $singles_key = 0;
                }
            }else {
                $current_miss = 0;
                $singles_key = 0;
            }
        }
        $hzArr['betStatus'] = $betStatus; # 下注状态
        $hzArr['singles_key'] = $singles_key; # 下注状态
        $hzArr['filters']['current_kj_qihao'] = $currentKjQiHao;

        $single = $singles[(int)$singles_key];
        $hzArr = array_merge($hzArr, [
            'current_area_profits' => $areaProfits,
            'current_miss' => $current_miss,
            'singles_key' => $singles_key,
            'betStatus' => $betStatus,
            'start_loss' => $startLoss,
            'has_bet_nums' => $has_bet_nums,
            'area_msg' => $areaMsg,
        ]);
        $updateData = ['hz_Arr'=>json_encode($hzArr, 320), 'single'=>$single];
        $rst = UserSysPlans::updateAll($updateData, ['id'=>$UserSysPlan->id]);
        Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '遗漏x期起投', [
            'planId' => $planId,
            'isZjBefore' => $isZjBefore,
            'singles' => $singles,
            'singles_count' => $singles_count,
            'beforeHzArr' => $beforeHzArr,
            'afterHzArr' => $hzArr,
            'areaMsg' => $areaMsg,
            'rst' => $rst,
        ]);

        return true;
    }

    /**
     * @desc 获取计划下一个倍数
     * @param $plan_id
     * @param int $singles_key
     * @param int $next_single_key
     * @param int $lottery_type
     * @return mixed
     */
    public static function getPlanNextSingle($plan_id, $singles_key = 0, &$next_single_key = 0, $lottery_type = DEFAULT_LOTTERY_TYPE){
        if(!$singles_key) $singles_key = 0;
        $m = \Yii::$app->cache;
        $UserSysPlans = UserSysPlans::findOne($plan_id);
        $singles = $UserSysPlans->singles;
        $singlesArr = explode(',', str_replace('-', ',', $singles));
        if($BettingRecords = BettingRecords::find()->select(['id', 'qihao','status'])->where(['plan_id'=>$plan_id, 'status'=>1])->orderBy(['id'=>SORT_DESC])->limit(1)->one()){
            $mkey = 'getPlanNextSingle_1_'.$plan_id.'_'.$BettingRecords->qihao;
            if(!$next_single_key = $m->get($mkey)){
                //$key = array_search($single, $singlesArr);
                $next_single_key = $singles_key + 1;
                if(!isset($singlesArr[$next_single_key])){
                    $next_single_key = 0;
                }
            }
        }else{
            $next_single_key = $singles_key;
        }
        $nextSingle = $singlesArr[$next_single_key];
        $time = 7*86400;
        $logArr = ['plan_id'=>$plan_id, 'single_key'=>$singles_key, 'qihao'=>$BettingRecords->qihao, 'next_single_key'=>$next_single_key, 'time'=>$time, 'lottery_type'=>$lottery_type];
        Tool_Common::log('getPlanNextSingle', 'INFO', '倍数获取', $logArr);
        $m->set($mkey, $next_single_key, $time);

        return $nextSingle;
    }
}
