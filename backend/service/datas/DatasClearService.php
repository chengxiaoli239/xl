<?php
namespace backend\service\datas;

use backend\service\BaseService;
use backend\service\UserSysPlansService;
use common\tools\Tool_Common;

class DatasClearService extends BaseService{

    /**
     * @return array
     */
    public static function deleteLatestRecords(): array
    {
        self::clearBettingRecords();
        self::clearQueueJobRecords();

        return ['status'=>200, 'msg'=>'操作成功'];
    }

    public static function clearBettingRecords(): bool
    {
        #$lottery_types = UserSysPlansService::getMyLotteryTypes($uid=1);
        $db = \Yii::$app->db;
        $lottery_types = [['lottery_type'=>8], ['lottery_type'=>23], ['lottery_type'=>24]];

        foreach ($lottery_types as $lottery){
            $lottery_type = $lottery['lottery_type'];
            try {
                $date_nums = DatasClearService::getClearBeforeXDate($lottery_type);
                $dates = [];
                for ($i=0; $i<$date_nums; $i++){
                    $dates[] = date('Y-m-d', time()-$i*86400);
                }

                try {
                    # 游戏记录
                    $count_sql = 'SELECT COUNT(id) FROM {{%betting_records}} WHERE create_time NOT REGEXP "'.implode('|', $dates).'" AND lottery_type='.$lottery_type;
                    $rst_count = $db->createCommand($count_sql)->queryScalar();
                    $delete_sql = 'DELETE FROM {{%betting_records}} WHERE create_time NOT REGEXP "'.implode('|', $dates).'" AND lottery_type='.$lottery_type;
                    $rst_delete = $db->createCommand($delete_sql)->execute();
                }catch (\Exception $e){}

                try {
                    # 真实下注任务记录
                    $task_count_sql = 'SELECT COUNT(id) FROM {{%bet_error_plans_task}} WHERE updated_time NOT REGEXP "'.implode('|', $dates).'" AND lottery_type='.$lottery_type;
                    $rst_task_count = $db->createCommand($task_count_sql)->queryScalar();
                    $task_delete_sql = 'DELETE FROM {{%bet_error_plans_task}} WHERE updated_time NOT REGEXP "'.implode('|', $dates).'" AND lottery_type='.$lottery_type;
                    $rst_task_delete = $db->createCommand($task_delete_sql)->execute();
                } catch (\Exception $e){}

                try {
                    # 状态处理记录
                    $deal_status_delete_sql = 'DELETE FROM {{%data_deal_status}} WHERE update_time NOT REGEXP "'.implode('|', $dates).'" AND lottery_type='.$lottery_type;
                    $deal_status_delete = $db->createCommand($deal_status_delete_sql)->execute();
                } catch (\Exception $e){}

                try {
                    # admin_log记录
                    $admin_log_delete_sql = 'DELETE FROM {{%admin_log}} WHERE update_time NOT REGEXP "'.implode('|', $dates).'"';
                    $admin_log_delete = $db->createCommand($admin_log_delete_sql)->execute();
                } catch (\Exception $e){}

                try {
                    # agent_user_bet_logs记录
                    $agent_user_bet_logs_delete_sql = 'DELETE FROM {{%agent_user_bet_logs}} WHERE update_time NOT REGEXP "'.implode('|', $dates).'"';
                    $agent_user_bet_logs_delete = $db->createCommand($agent_user_bet_logs_delete_sql)->execute();
                } catch (\Exception $e){
                }

                $logArr = [
                    'lottery_type'=>$lottery_type,
                    'count_sql'=>$count_sql,
                    'rst_count'=>$rst_count,
                    'delete_sql'=>$delete_sql,
                    'rst_delete'=>$rst_delete,
                    'rst_task_count'=>$rst_task_count,
                    'rst_task_delete'=>$rst_task_delete,
                    'deal_status_delete_sql'=>$deal_status_delete_sql,
                    'deal_status_delete'=>$deal_status_delete,
                    'admin_log_delete_sql' => $admin_log_delete_sql,
                    'admin_log_delete' => $admin_log_delete,
                    'agent_user_bet_logs_delete' => $agent_user_bet_logs_delete,
                ];
                Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '清理数据', $logArr);
            }catch (\Exception $exception){
                Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '清理数据', ['lottery_type'=>$lottery_type, 'err_msg'=>$exception->getMessage()]);
                return false;
            }
        }

        return true;
    }

    /**
     * 消息队列任务清理
     * @return bool|string
     */
    public static function clearQueueJobRecords(){

        try {
            $db = \Yii::$app->db;

            $date_nums = DatasClearService::getClearBeforeXDate();
            $dates = [];
            for ($i=0; $i<$date_nums; $i++){
                $dates[] = date('Y-m-d', time()-$i*86400);
            }

            # 消息队列记录
            $count_sql = 'SELECT COUNT(id) FROM {{%queue_log}} WHERE create_time NOT REGEXP "'.implode('|', $dates).'"';
            $rst_count = $db->createCommand($count_sql)->queryScalar();
            $delete_sql = 'DELETE FROM {{%queue_log}} WHERE create_time NOT REGEXP "'.implode('|', $dates).'"';
            $rst_delete = $db->createCommand($delete_sql)->execute();
            $logArr = ['rst_count'=>$rst_count, 'rst_delete'=>$rst_delete];
            Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '清理数据', $logArr);
        }catch (\Exception $e){
            return $e->getMessage();
        }

        return true;
    }

    /**
     * @desc 保留最近几天的下注记录
     * @param int $lottery_type
     * @return int
     */
    public static function getClearBeforeXDate($lottery_type = DEFAULT_LOTTERY_TYPE){
        $date_nums = 2;
        if(in_array($lottery_type, [1, 17, 23, 24])){
            //$date_nums = 30;
        }

        return $date_nums;
    }
}