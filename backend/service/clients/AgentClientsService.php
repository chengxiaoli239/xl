<?php
namespace backend\service\clients;

use backend\models\AgentUserBetLogs;
use backend\models\BetErrorPlansTask;
use backend\models\DataDealStatus;
use backend\models\SscKjData;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\BetService;
use backend\service\Lucky5\Lucky5Service;
use common\kj\ssc\Lucky5;
use common\service\CommonService;
use common\service\jobs\kj_data\GrabKjDatasJob;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class AgentClientsService extends ClientsBaseService{

    public static function validateSyncMemberBetLogs($member_bet_logs){
        if($member_bet_logs['Status'] != 1){
            throw_info('日志请求状态异常Status:'.$member_bet_logs['Status']);
        }
        if(empty($member_bet_logs['Data']) OR empty($member_bet_logs['Data']['Rows']) OR $member_bet_logs['Data']['RecordCount']==0){
            throw_info('日志数据为空');
        }

        return [0, $member_bet_logs, '校验成功'];
    }

    /**
     * @desc 客户端bet日志同步
     * @param array $member_bet_logs
     * @param string $access_token
     * @param int $lottery_type
     * @return array
     */
    public static function syncMemberBetLogs($member_bet_logs=[], $log_type='kuaixuan', $access_token='', $from='api', $lottery_type=DEFAULT_LOTTERY_TYPE){
        try {
            $transaction = \Yii::$app->db->beginTransaction();

            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            $flow_wp_accounts = explode($TzSystemsUsers->flow_wp_accounts, ',');
            $data = [];

            $now_time = time();
            list($code, $logDatas, $err_msg) = AgentClientsService::validateSyncMemberBetLogs($member_bet_logs);
            foreach ($logDatas as $logData){
                try {
                    if(!in_array($logData['account'], $flow_wp_accounts)){
                        throw_info('不在跟随账号范围之内');
                    }
                    $AgentUserBetLogs = AgentUserBetLogs::findOne(['access_token'=>$access_token, 'wp_record_id'=>$logData['log_member_quick_select_id']]);
                    if(!empty($AgentUserBetLogs)){
                        throw_info('日志记录已存在');
                    }else{
                        $AgentUserBetLogs = new AgentUserBetLogs();
                    }
                    list($playway, $kuaiyi_desc) = AgentClientsService::getKuaiYiDescByOperationLogs($logData['operation_content']);
                    $single = number_format($logData['bet_money']/$logData['bet_count'], 2); # 倍数
                    $setDatas = [
                        'access_token' => $access_token,
                        'uid' => $TzSystemsUsers->uid,
                        'wp_record_id' => $logData['log_member_quick_select_id'],
                        'member_id' => $logData['member_id'],
                        'account' => $TzSystemsUsers->account,
                        'bet_logs' => $logData['operation_content'], # 原始日志
                        'bet_logs_n' => $kuaiyi_desc, # 转换后的快译
                        'bet_counts' => $logData['bet_count'], # 原始下注组数
                        'bet_money' => $logData['bet_money'], # 原始下注金额
                        'bet_single' => $single,

                        # 反买
                        'bet_codes_op' => '', # 反买号码
                        'bet_op_counts' => '', # 反买组数
                        'bet_op_single' => $single, # 反买倍数
                        'bet_op_money' => ($single * $bet_op_counts), # 反买金额
                        'bet_type' => 1, # 下注类型：1反买2正买  默认反买

                        'member_bet_time' => date('Y-').$logData['operation_datetime'],

                        'tz_system_id' => 9,
                        'created_at' => $now_time,
                        'updated_at' => $now_time,
                        'planway' => $playway,
                        'log_type' => $logData['log_type'],  # 目前看都是102
                    ];
                    $AgentUserBetLogs->setAttributes($setDatas);
                    Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '日志同步', ['logData'=>$logData, 'attributes'=>$AgentUserBetLogs->getAttributes()]);
                    $flag = $AgentUserBetLogs->save();
                    if(empty($flag)){
                        throw_info($AgentUserBetLogs->getErrors());
                    }
                }catch (\Exception $e){
                    Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '日志记录-异常', ['account'=>$logData['account'], 'flow_wp_accounts'=>$flow_wp_accounts, 'logData'=>$logData, 'err_msg'=>$e->getMessage()]);
                }
            }

            #$params = ['lottery_type'=>$lottery_type, 'title'=>BetService::getLotteryName($lottery_type).'_网盘推送bet日志', 'business_id'=>$expect];
            #push_queue(GrabKjDatasJob::class, $params);
            $transaction->commit();
        }catch (\Exception $e){
            $transaction->rollBack();
            return ['status'=>301, 'data'=>$data, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'data'=>$data, 'msg'=>'数据同步成功'];
    }

    /**
     * 定位类型
     * @param $bet_logs
     * @return int
     */
    public static function getPlaywayByBetLogs($bet_logs){
        $playway = 3;
        if(strpos($bet_logs, '四定') !== false){
            $playway = 3;
        }elseif (strpos($bet_logs, '三定') !== false){
            $playway = 2;
        }elseif (strpos($bet_logs, '二定') !== false){
            $playway = 1;
        }elseif (strpos($bet_logs, '一定') !== false){
            $playway = 4;
        }

        return $playway;
    }

    /**
     * 获取快译描述 by 下注日志
     * @param string $bet_log
     * @return int[]
     */
    public static function getKuaiYiDescByOperationLogs($bet_log=''){
        $playway = self::getPlaywayByBetLogs($bet_log);

        return [$playway, $kuaiYiDesc];
    }
}
