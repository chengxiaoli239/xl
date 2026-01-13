<?php
namespace backend\service\clients;

use backend\models\AgentUserBetLogs;
use backend\models\TzSystemsUsers;
use backend\service\agent\AgentService;
use backend\service\agent\AgentUsersService;
use backend\service\BetService;
use backend\service\HN0898Service;
use backend\service\numbers\CodeTypeService;
use common\helpers\LotteryType;
use common\models\wechat\WechatUser;
use common\service\cache\CacheKeyService;
use common\service\open\telegram\MessageOperateService;
use common\tools\Tool_Common;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class AgentClientsService extends ClientsBaseService{
    const ALL_CODE_TYPE_1_NUMS = 10;
    const ALL_CODE_TYPE_2_NUMS = 100;
    const ALL_CODE_TYPE_3_NUMS = 1000;
    const ALL_CODE_TYPE_4_NUMS = 10000;

    const BET_WARING_CODE = 48888;
    const BET_INVALIDE_CODE = 40004; # 无效不需记日志

    public static function validateSyncMemberBetLogs($member_bet_logs){
        if($member_bet_logs['Status'] != 1){
            throw_info('日志请求状态异常Status:'.$member_bet_logs['Status']);
        }
        if(empty($member_bet_logs['Data']) OR empty($member_bet_logs['Data']['Rows']) OR $member_bet_logs['Data']['RecordCount']==0){
            throw_info('日志数据为空');
        }

        $Rows = $member_bet_logs['Data']['Rows'];

        return [0, $Rows, '校验成功，条数:'.count($Rows)];
    }

    /**
     * @desc 客户端bet日志同步
     * @param array $member_bet_logs
     * @param string $access_token
     * @param string $from_type
     * @param string $from
     * @param int $lottery_type
     * @return array
     * @throws Exception
     */
    public static function syncMemberBetLogs($member_bet_logs=[], $access_token='', $from_type='kuaixuan', $from='api', $lottery_type=DEFAULT_LOTTERY_TYPE){
        try {
            $s1 = microtime(true);
            $data = [];
            $transaction = \Yii::$app->db->beginTransaction();

            list($code, $logDatas, $err_msg) = AgentClientsService::validateSyncMemberBetLogs($member_bet_logs);
            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            if(!$TzSystemsUsers->follow_status){
                throw_info('跟随开关已关闭');
            }
            AgentClientsService::checkProfits($TzSystemsUsers);

            $flow_wp_accounts = explode(',', str_replace('，', ',', trim($TzSystemsUsers->flow_wp_accounts)));  # 正买账号
            $flow_wp_accounts = array_filter($flow_wp_accounts);
            $flow_op_accounts = explode(',', str_replace('，', ',', trim($TzSystemsUsers->flow_op_accounts)));  # 反买账号flow_op_accounts
            $flow_op_accounts = array_filter($flow_op_accounts);

            $record = 'not_in';
            foreach ($logDatas as $key=>$logData){
                try {
                    // 重新生成log_member_quick_select_id
                    $originalId = $logData['log_member_quick_select_id'];
                    $md5Value = md5($logData['operation_datetime'] . $logData['operation_content']);
                    $logData['log_member_quick_select_id'] = self::regenerateLogMemberQuickSelectId($originalId, $md5Value, $key);

                    $record_id = $logData['log_member_quick_select_id'];
                    list($code, $qihao) = AgentClientsService::operateOneBetLog($logData, $access_token, $from_type, $from, $lottery_type);
                    $record = 'record';
                }catch (\Exception $e){
                    $mcKey = 'wp_record_xxx_'.$access_token.'_'.$record_id.'_'.md5($logData['operation_content']);
                    $num = \Yii::$app->redis->incr($mcKey);
                    \Yii::$app->redis->expire($mcKey, 10);
                    if($num>2) continue;
                    if($e->getCode()<40000){
                        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '代理日志记录-异常', ['account'=>$logData['account'], 'record_id'=>$record_id, 'flow_wp_accounts'=>$flow_wp_accounts, 'flow_op_accounts'=>$flow_op_accounts, /*'logData'=>$logData,*/ 'err_msg'=>$e->getMessage()]);
                    }elseif($e->getCode() == self::BET_WARING_CODE){
                        Tool_Common::log('/client_xy/'.__FUNCTION__.'_warn', 'INFO', '代理日志记录-警告', ['account'=>$logData['account'], 'record_id'=>$record_id, 'flow_wp_accounts'=>$flow_wp_accounts, 'flow_op_accounts'=>$flow_op_accounts, 'logData'=>$logData, 'err_msg'=>$e->getMessage()]);
                    }else{
                        Tool_Common::log('/client_xy/'.__FUNCTION__.'_invalid', 'INFO', '代理日志记录-无效', ['account'=>$logData['account'], 'record_id'=>$record_id, 'flow_wp_accounts'=>$flow_wp_accounts, 'flow_op_accounts'=>$flow_op_accounts, /*'logData'=>$logData,*/ 'err_msg'=>$e->getMessage()]);
                    }
                }
            }
            $s2 = microtime(true);

            #$params = ['lottery_type'=>$lottery_type, 'title'=>BetService::getLotteryName($lottery_type).'_网盘推送bet日志', 'business_id'=>$expect];
            #push_queue(GrabKjDatasJob::class, $params);
            $transaction->commit();
            $rst = ['status'=>200, 'data'=>$data, 'msg'=>'数据同步成功'];
        }catch (\Exception $e){
            $transaction->rollBack();
            $rst = ['status'=>301, 'data'=>$data, 'msg'=>$e->getMessage()];
        }

        # 特殊处理
        $toUsernames = [];
        switch ($TzSystemsUsers->username){
            case 'as06':
                $toUsernames = ['aa99'];
                break;
            case 'aa99':
                $toUsernames = ['as06'];
                break;
        }
        if(!empty($toUsernames))foreach ($toUsernames as $toUsername){
            $toTzSystemsUsers = TzSystemsUsers::findOne(['username'=>$toUsername]);
            $toAccessToken = $toTzSystemsUsers->access_token;
            try {
                #self::syncMemberBetLogs($member_bet_logs, $toAccessToken, $from_type, $from, $lottery_type);
                if(!$toTzSystemsUsers->follow_status){
                    throw_info('跟随开关已关闭');
                }
                AgentClientsService::checkProfits($toTzSystemsUsers);
                foreach ($logDatas as $k=>$logData){
                    try {
                        // 重新生成log_member_quick_select_id
                        $originalId = $logData['log_member_quick_select_id'];
                        $md5Value = md5($logData['operation_datetime'] . $logData['operation_content']);
                        $logData['log_member_quick_select_id'] = self::regenerateLogMemberQuickSelectId($originalId, $md5Value, $k);

                        $to_record_id = $logData['log_member_quick_select_id'];
                        $toMcKey = 'wp_record_xxx_'.$access_token.'_'.$to_record_id;
                        AgentClientsService::operateOneBetLog($logData, $toAccessToken, $from_type, $from, $lottery_type);
                    }catch (\Exception $e){
                        $num = \Yii::$app->redis->incr($toMcKey);
                        \Yii::$app->redis->expire($toMcKey, 10);
                        if($num>2) continue;
                        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '共用代理错误', ['access_token'=>$toAccessToken, 'username'=>$TzSystemsUsers->username, 'err_msg'=>$e->getMessage()]);
                    }
                }
            }catch (\Exception $exception){}
        }
        $s3 = microtime(true);
        $c1 = ($s2-$s1).'s';
        $c2 = ($s3-$s2).'s';
        Tool_Common::log('/client_xy/'.__FUNCTION__.'_t', 'INFO', '时间耗时', ['username'=>$TzSystemsUsers->username, 'qihao'=>$qihao, 'flag'=>$record, 'c1'=>$c1, 'c2'=>$c2]);

        return $rst;
    }

    /**
     * 重新生成log_member_quick_select_id
     * 格式：{原来的log_member_quick_select_id}{MD5的最后6位数字}
     * @param string $originalId 原来的log_member_quick_select_id
     * @param string $md5Value operation_datetime和operation_content的MD5值
     * @return string 新的log_member_quick_select_id
     */
    public static function regenerateLogMemberQuickSelectId($originalId, $md5Value, $key=0){
        // 从MD5中提取所有数字字符
        // MD5是32位十六进制字符串（0-9, a-f），我们需要提取其中的数字部分（0-9）
        preg_match_all('/\d+/', $md5Value, $matches);
        $allDigits = implode('', $matches[0]);

        // 如果提取的数字长度>=6，取最后6位；否则用0补齐到6位
        if(strlen($allDigits) >= 6){
            $last6Digits = substr($allDigits, -6);
        } else {
            $last6Digits = str_pad($allDigits, 6, '0', STR_PAD_LEFT);
        }

        // 组合：原来的ID + MD5的最后6位数字
        return $originalId . $last6Digits+(string)$key;
    }

    /**
     * 处理一个日志
     * @param array $logData
     * @throws \common\exceptions\InfoException
     */
    public static function operateOneBetLog($logData=[], $access_token='', $from_type='kuaixuan', $from='api', $lottery_type=DEFAULT_LOTTERY_TYPE){

        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
        $now_time = time();
        $before_5min_time = date('Y-m-d H:i:s', time()-300); # 5分钟前记录
        $start_time = microtime(true);
        $record_id = $logData['log_member_quick_select_id'];
        $member_bet_time = date('Y-').$logData['operation_datetime'];
        if($TzSystemsUsers->username != 'aa33' && $member_bet_time < $before_5min_time){
            throw_info('校验成功，历史下注记录不同步：用户下注时间:'.$member_bet_time. '，当前5分钟前:'.$before_5min_time, self::BET_INVALIDE_CODE);
        }

        $flow_wp_accounts = explode(',', str_replace('，', ',', trim($TzSystemsUsers->flow_wp_accounts)));  # 正买账号
        $flow_wp_accounts = array_filter($flow_wp_accounts);
        $flow_op_accounts = explode(',', str_replace('，', ',', trim($TzSystemsUsers->flow_op_accounts)));  # 反买账号flow_op_accounts
        $flow_op_accounts = array_filter($flow_op_accounts);

        if(!in_array($logData['account'], $flow_wp_accounts) && !in_array($logData['account'], $flow_op_accounts)){
            throw_info('不在跟随账号范围之内, account:'.$logData['account'], 40002);
        }
        $buy_type = in_array($logData['account'], $flow_wp_accounts) ? 1 : 0;  # 购买类型，0反买账号，1正买账号

        // 使用access_token + wp_record_id作为联合索引（恢复原来的方式）
        $AgentUserBetLogs = AgentUserBetLogs::findOne([
            'access_token' => $access_token,
            'wp_record_id' => $logData['log_member_quick_select_id']
        ]);
        if(!empty($AgentUserBetLogs)){
            throw_info('日志记录已存在 wp_record_id:'.$logData['log_member_quick_select_id'], 40003);
        } else {
            $AgentUserBetLogs = new AgentUserBetLogs();
        }
        $qihao = HN0898Service::getQihao($lottery_type, substr($logData['time_value'], -8), date('Y').'-'.substr($logData['operation_datetime'], 0, 5));
        $bet_log_n = str_replace(['[', ']'], '', $logData['operation_content']);
        list($code, $data, $err_msg) = AgentClientsService::getKuaiYiDescByOperationLogs($bet_log_n);
        if($code>0){
            throw_info($err_msg);
        }
        $playway = $data['playway'];
        $single = number_format($logData['bet_money']/$logData['bet_count'], 2); # 倍数
        $bet_single_op = $single; # 反买倍数默认等于正常下注倍数

        $bet_op_theory_counts = AgentClientsService::getOpBetCounts($data['code_type'], $logData['bet_count']);  # 理论反买组数
        $bet_codes = BetService::getHzCodes($data['tz_type'], json_encode($data['codes_hz']));  # 正买号码
        $bet_counts = count(explode('@', $bet_codes));  # 实际正买组数

        $bet_codes_op = BetService::getHzCodes($data['tz_type'], json_encode($data['codes_hz']), $buy_type);  # 反买号码
        $bet_op_counts = count(explode('@', $bet_codes_op));  # 实际反买组数
        //p(['lll'.rand(), $data['tz_type'], $data['codes_hz'], $bet_counts, $bet_op_counts]);

        if($data['code_type']==4 && $buy_type==0 && $bet_counts<1000){
            throw_info('反买:正买组数少于1000组，不反买，正买：'.$bet_counts.' 组 ', self::BET_WARING_CODE);
        }

        # 反买组数校验
        if($buy_type==0 && $data['code_type']==4 && $bet_op_theory_counts != $bet_op_counts){
            throw_info('反买:组数不符，理论组数：'.$bet_op_theory_counts.' 组，实际：'.$bet_op_counts.' 组 ', self::BET_WARING_CODE);
        }
        # 正买组数校验
        if($buy_type==1 && $logData['bet_count'] != $bet_counts){
            throw_info('code_type:'.$data['code_type'].' 正买组数不符，理论组数：'.$logData['bet_count'].' 组，实际：'.$bet_op_counts.' 组 ', self::BET_WARING_CODE);
        }

        $setDatas = [
            'access_token' => $access_token,
            'uid' => $TzSystemsUsers->uid,
            'wp_record_id' => $record_id,
            'member_id' => $logData['member_id'],
            'account' => $TzSystemsUsers->account,
            'bet_logs' => $logData['operation_content'], # 原始日志
            'bet_logs_codes_hz' => json_encode($data['codes_hz'], 320), # 解析成系统的codes_hz
            'bet_logs_n' => (string)$bet_log_n, # 转换后的快译
            'bet_codes' => $bet_codes, # 用户正常下注号码
            'bet_counts' => $logData['bet_count'], # 原始下注组数
            'bet_money' => (float)$logData['bet_money'], # 原始下注金额
            'bet_single' => $single,

            # 反买
            'bet_codes_op' => $bet_codes_op, # 反买号码
            'bet_op_counts' => (int)$bet_op_counts, # 反买组数
            'bet_op_single' => $bet_single_op, # 反买倍数
            'bet_op_money' => ($bet_single_op * $bet_op_counts), # 反买金额
            'bet_type' => $buy_type, # 下注类型：0反买1正买  默认反买

            'member_bet_time' => $member_bet_time,

            'lottery_type' => $lottery_type,
            'qihao' => $qihao,
            'tz_system_id' => 9,
            'created_at' => $now_time,
            'updated_at' => $now_time,
            'playway' => $playway,
            'from_type' => $from_type,  # 来源：快选，快译
            'from' => $from,  # 来源：api、page
            'log_type' => $logData['log_type'],  # 目前看都是102
        ];
        $AgentUserBetLogs->setAttributes($setDatas);
        //p($AgentUserBetLogs->getAttributes());
        $flag = $AgentUserBetLogs->save();
        if(empty($flag)){
            throw_info(Json::encode($AgentUserBetLogs->getErrors(), 320));
        }
        $codes = ($buy_type==1) ? $bet_codes : $bet_codes_op;
        list($code, $bet_single) = AgentUsersService::getFlowSingle($TzSystemsUsers, $single, $buy_type, $playway);
        $rst = (new \backend\service\Lucky5\Lucky5Service($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id))
            ->pushIntoBetTask($qihao, $codes, $data['tz_type'], $bet_single, $playway, $TzSystemsUsers->uid, $plan_id=$record_id, $lottery_type);
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';

        $m = \Yii::$app->cache;
        $mkey = TzSystemUsersService::buildUserPlanTasksKey($access_token, $qihao);
        $m->delete($mkey);
        Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '日志同步', ['account'=>$logData['account'], 'logData'=>$logData, 'attributes'=>$AgentUserBetLogs->getAttributes(), 'rst'=>$rst, 'consume_time'=>$consume_time]);

        return [0, $qihao];
    }

    /**
     * @param $TzSystemsUsers
     * @return bool
     * @throws \common\exceptions\InfoException
     */
    public static function checkProfits($TzSystemsUsers){

        if($TzSystemsUsers->take_profits>0.00 && $TzSystemsUsers->stop_loss>0.00){
            # 账号级别的盈利
            list($code, $TzSystemsUsers, $msg) = \backend\service\SscDataService::updateUserProfits($TzSystemsUsers->uid);
            if($code>0){
                throw_info('利润统计错误：'.$msg);
            }
            Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '止盈止损计算', ['username'=>$TzSystemsUsers->username, 'account'=>$TzSystemsUsers->account, 'current_profits'=>$TzSystemsUsers->current_profits, 'take_profits'=>$TzSystemsUsers->take_profits, 'stop_loss'=>$TzSystemsUsers->stop_loss]);
            if($TzSystemsUsers->current_profits>=$TzSystemsUsers->take_profits OR $TzSystemsUsers->current_profits<=(0-$TzSystemsUsers->stop_loss)){
                Tool_Common::log('/client_xy/'.__FUNCTION__.'_s', 'INFO', '止盈止损记录', ['username'=>$TzSystemsUsers->username, 'account'=>$TzSystemsUsers->account, 'current_profits'=>$TzSystemsUsers->current_profits, 'take_profits'=>$TzSystemsUsers->take_profits, 'stop_loss'=>$TzSystemsUsers->stop_loss]);
                $err_msg = '触发止盈止损，止盈：'.$TzSystemsUsers->take_profits.'，止损：'.$TzSystemsUsers->stop_loss.'，当前：'.$TzSystemsUsers->current_profits;
                $TzSystemsUsers->desc = $err_msg;
                $TzSystemsUsers->save();
                throw_info($err_msg);
            }
        }
        return true;
    }

    /**
     * 定位类型
     * @param $bet_logs
     * @return array
     */
    public static function getPlaywayByBetLogs(&$bet_logs='', &$codes_hz=[]){
        $playway = 0;

        $strArr = [',', '，'];
        foreach (LotteryType::LT_PLAY_WAY_OPTIONS as $tPlayWay=>$txt){
            if(strpos($bet_logs, $txt) === false){
                continue;
            }

            if(strpos($bet_logs, '四定位') !== false){
                $playway = 3;
                $tz_type = 25;
            }elseif (strpos($bet_logs, '三定位') !== false){
                $playway = 2;
                $tz_type = 29;
            }elseif (strpos($bet_logs, '二定位') !== false){
                $playway = 1;
                $tz_type = 30;
            }elseif (strpos($bet_logs, '一定位') !== false){
                $playway = 4;
                $tz_type = 18;
            }elseif (strpos($bet_logs, '五位二定') !== false){
                $playway = 1;
                $tz_type = 31;
            }

            if($playway>0){
                $bet_logs = str_replace($txt.'，', '', $bet_logs);
                break;
            }
            // 构建正则表达式模式
            $pattern = '/^[' . preg_quote(implode('', $strArr), '/') . ']+|[' . preg_quote(implode('', $strArr), '/') . ']+$/u';
            // 剔除字符串前后的特定字符
            $bet_logs = preg_replace($pattern, '', $bet_logs);
        }
        $code_type = $playway + 1;
        $codes_hz['playway'] = $playway;
        $codes_hz['code_type'] = $code_type;

        return [$playway, $tz_type, $code_type];
    }

    /**
     * 获取快译描述 by 下注日志
     * @param string $bet_log
     * @return int[]
     */
    public static function getKuaiYiDescByOperationLogs($bet_log=''){
        #$bet_log = '四定位，配数“取”:第1位: 356789，第2位: 045678，固定合分除值：，不定合分值(两数合):01356，合分值范围:9-28，包含“取”数:0258,三兄弟“除”操作，四兄弟“除”操作，双数“除”数:第2位，第3位，第4位';
        $bet_log = str_replace(['[', ']'], '', $bet_log);
        $bet_log = str_replace(';', '；', $bet_log);
        $bet_log = str_replace(',', '，', $bet_log);
        #p(['initLog'=>$bet_log], 0);
        list($playway, $tz_type, $code_type) = AgentClientsService::getPlaywayByBetLogs($bet_log, $codes_hz);
        //p(['playway'=>$playway, 'codes_hz'=>$codes_hz, 'bet_log'=>$bet_log], 0);

        //p(['bet_log2'=>$bet_log], 0);
        $dataArr = explode('，', $bet_log);
        # 重新拼接一下每个关键词
        $dataArr = AgentClientsService::resetDataArr($dataArr);
        //p(['调整后的dataArr'=>$dataArr], 0);

        # 第一步：关键词1 -- 号码类型
        list($bet_log, $dataArr, $codes_hz) = AgentClientsService::matchKeyword1($bet_log, $dataArr, $codes_hz);
        #p(['step'=>'111', 'codes_hz'=>$codes_hz, 'dataArr'=>$dataArr, 'bet_log'=>$bet_log], 0);
        # 第一步：关键词2 + 数字
        list($bet_log, $dataArr, $codes_hz) = AgentClientsService::matchKeyword2($bet_log, $dataArr, $codes_hz);
        #p(['step'=>'222', 'codes_hz'=>$codes_hz, 'dataArr'=>$dataArr, 'bet_log'=>$bet_log], 0);
        # 第一步：关键词3 大、小、单、双 位置除、取
        list($bet_log, $dataArr, $codes_hz) = AgentClientsService::matchKeyword3($bet_log, $dataArr, $codes_hz);
        //p(['step'=>'333', 'codes_hz'=>$codes_hz, 'dataArr'=>$dataArr, 'bet_log'=>$bet_log], 0);

        //echo "===============end==================\r\n";

        # 关键词2匹配
        $data = ['playway'=>$playway, 'code_type'=>$code_type, 'tz_type'=>$tz_type, 'codes_hz'=>$codes_hz, 'dataArr'=>$dataArr, 'bet_log'=>$bet_log];

        // 输出结果
        return [0, $data, '处理成功'];
    }

    # 关键词1匹配
    public static function matchKeyword1($bet_log='', &$dataArr=[], &$codes_hz=[]){
        foreach ($dataArr as $k=>$vdata){
            foreach (array_keys(CodeTypeService::$keywordsWhere1) as $kk=>$keyword1){
                if(strpos(trim($vdata), $keyword1) === 0){
                    #var_dump($k.'=='.$vdata.'=='.$kk.'=='.$keyword1."\r\n");
                    $codes_hz = array_merge($codes_hz, CodeTypeService::$keywordsWhere1[$keyword1]);
                    unset($dataArr[$k]);
                }
            }
        }

        return [$bet_log, $dataArr, $codes_hz];
    }

    # 关键词2+数字匹配  \backend\service\numbers\CodeTypeService::$keywords2
    public static function matchKeyword2($bet_log='', &$dataArr=[], &$codes_hz=[]){

        foreach ($dataArr as $k1=>$tmpStr){
            foreach (array_keys(CodeTypeService::$keywords2) as $kk=>$keyword2){
                if(strpos($tmpStr, $keyword2) !== false){
                    $keyword2Condition = CodeTypeService::$keywords2[$keyword2];
                    $operateStr = trim(str_replace($keyword2.'：', '', $tmpStr));
                    //if($operateStr!=='0') continue;
                    if($operateStr!=='0' && $operateStr!==0 && empty($operateStr)){
                        unset($dataArr[$k1]);
                        continue;
                    }

                    switch ($keyword2){
                        case CodeTypeService::KX_KW_2_FIXED_POS_1: # 定位置：千
                        case CodeTypeService::KX_KW_2_FIXED_POS_2: # 定位置：百
                        case CodeTypeService::KX_KW_2_FIXED_POS_3: # 定位置：十
                        case CodeTypeService::KX_KW_2_FIXED_POS_4: # 定位置：个
                        case CodeTypeService::KX_KW_2_FIXED_POS_5: # 定位置：五
                        case CodeTypeService::KX_KW_2_FIXED_FILTER: # 定位置“除”
                        case CodeTypeService::KX_KW_2_FIXED_GET: # 定位置“取”
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateFixedPositionStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_FIXED_POS: # 固定位置 - 四定
                        case CodeTypeService::KX_KW_2_FIXED_POS_2_3: # 乘号位置
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateFixedPosStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_PEISHU_GET: # 配数取
                        case CodeTypeService::KX_KW_2_PEISHU_FILTER: # 配数取
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::opratePeiShuStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_LOG_GET: # 对数取
                        case CodeTypeService::KX_KW_2_LOG_FILTER: # 对数取
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateLogStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_FIXED_HF_FILTER: # 固定合分除值
                        case CodeTypeService::KX_KW_2_FIXED_HF_GET: # 固定合分取值
                            //var_dump('keyword2：'.$keyword2.'=='.$tmpStr);
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateFixedPosHfStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_NOT_FIXED_HF_2NUM: # 不定合分值(两数合)
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateNotFixed2Condition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_NOT_FIXED_HF_3NUM: # 不定合分值(三数合)
                            //var_dump('keyword2：'.$keyword2.'=='.$tmpStr);
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateNotFixed3Condition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_FU_SHI_FILTER: # 复式“除”数
                        case CodeTypeService::KX_KW_2_FU_SHI_GET: # 复式“取”数
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateFuShiCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_HF_ZHI_ZONE: # 合分值范围
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateHfZoneStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_BAO_HAN_FILTER: # 包含“除”数
                        case CodeTypeService::KX_KW_2_BAO_HAN_GET: # 包含“取”数
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateBaoHanCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_EXCLUDE_CODE: #  # 排除数
                            //p(['codes_hz'=>$codes_hz, $keyword2Condition, 'before'], 0);
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateExcludeCodesCondition($operateStr));
                            break;
                        case CodeTypeService::KX_KW_2_ARISE_CODE: #  # 全转数
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateAriseCondition($operateStr));
                            break;
                    }

                    //p(['codes_hz'=>$codes_hz, $keyword2Condition, 'after'], 0);
                    $codes_hz = array_merge($codes_hz, $keyword2Condition);
                    //p(['codes_hz'=>$codes_hz, 'end']);
                    break;
                }
            }
        }
        //p(['dataArr'=>$dataArr], 0);

        return [$bet_log, $dataArr, $codes_hz];
    }

    # 关键词2+数字匹配  大、小、单、双 位置过滤
    public static function matchKeyword3($bet_log='', &$dataArr=[], &$codes_hz=[]){

        foreach ($dataArr as $k1=>$tmpStr){
            foreach (array_keys(CodeTypeService::$keywords3) as $kk=>$keyword3){
                if(strpos($tmpStr, $keyword3) !== false){
                    $keyword3Condition = CodeTypeService::$keywords3[$keyword3];
                    #p($keyword3Condition);
                    //var_dump('keyword3：'.$keyword3.'=='.$tmpStr);
                    $operateStr = trim(str_replace($keyword3.'：', '', $tmpStr));
                    if(empty($operateStr)){
                        unset($dataArr[$k1]);
                        continue;
                    }
                    $keyword3Condition = array_merge($keyword3Condition, CodeTypeService::oddEvenBigSmallPosFilter($operateStr, $keyword3Condition));
                    unset($keyword3Condition['key']);

                    $codes_hz = array_merge($codes_hz, $keyword3Condition);
                    unset($dataArr[$k1]);
                    break;
                }
            }
        }
        //p(['dataArr333'=>$dataArr], 0);

        /*
        foreach ($dataArr as $k=>$vdata){
            $current_keyword = '';
            foreach (array_keys(CodeTypeService::$keywordsWhere2) as $kk=>$keyword2){
                if(strpos($vdata, $keyword2) !== false){
                    $codes_hz = array_merge($codes_hz, CodeTypeService::$keywordsWhere2[$keyword2]);
                    p([$keyword2, $codes_hz]);
                    switch ($keyword2){
                        case CodeTypeService::KX_POS_TYPE_FILTER_ODD:
                            break;
                        case CodeTypeService::KX_POS_TYPE_GET_ODD:
                            break;
                        case CodeTypeService::KX_POS_TYPE_FILTER_EVEN:
                            break;
                        case CodeTypeService::KX_POS_TYPE_GET_EVEN:
                            break;
                        case CodeTypeService::KX_POS_TYPE_FILTER_BIG:
                            break;
                        case CodeTypeService::KX_POS_TYPE_GET_BIG:
                            break;
                        case CodeTypeService::KX_POS_TYPE_FILTER_SMALL:
                            break;
                        case CodeTypeService::KX_POS_TYPE_GET_SMALL:
                            break;
                    }
                    var_dump($k.'=='.$vdata.'=='.$kk.'=='.$keyword2);
                    #$codes_hz = array_merge($codes_hz, self::$keywordsWhere2[$keyword2]);
                    #unset($dataArr[$k]);
                    #$bet_log = str_replace($keyword1, '', $bet_log);
                    $bet_log = str_replace('，，', '', $bet_log);
                    #p($dataArr);
                }else{
                    # 匹配不到则归属上一个关键词
                }
            }
            //var_dump($vdata);
        }
        */

        return [$bet_log, $dataArr, $codes_hz];
    }

    /**
     * 重置关键词
     * @param array $dataArr
     * @return array
     */
    private static function resetDataArr($dataArr=[]){
        //p(['$dataArrxx'=>$dataArr], 0);
        $current_key = '';
        foreach ($dataArr as $k2=>$tmpStr){
            if($k2>0 && (
                strpos(trim($tmpStr), '第') === 0 OR
                strpos(trim($tmpStr), '内容') === 0 OR
                strpos(trim($tmpStr), '千=') === 0 OR
                strpos(trim($tmpStr), '百=') === 0 OR
                strpos(trim($tmpStr), '十=') === 0 OR
                strpos(trim($tmpStr), '个') === 0) OR
                strpos(trim($dataArr[$k2-1]), '对数') === 0
            ){
                $dataArr[$current_key] = $dataArr[$current_key].'，'.$tmpStr;
                unset($dataArr[$k2]);
            }else{
                $current_key = $k2;
            }
        }
        $newDataArr = array_filter($dataArr);

        return $newDataArr;
    }

    /**
     * 获取反买组数
     * @param int $code_type
     * @param int $originCounts
     * @return int
     */
    public static function getOpBetCounts($code_type=4, $originCounts=0){
        $opCounts = 0;
        switch ($code_type){
            case 1:
                $opCounts = AgentClientsService::ALL_CODE_TYPE_1_NUMS - (int)$originCounts;
                break;
            case 2:
                $opCounts = AgentClientsService::ALL_CODE_TYPE_2_NUMS - (int)$originCounts;
                break;
            case 3:
                $opCounts = AgentClientsService::ALL_CODE_TYPE_3_NUMS - (int)$originCounts;
                break;
            case 4:
                $opCounts = AgentClientsService::ALL_CODE_TYPE_4_NUMS - (int)$originCounts;
                break;
            default:
                break;
        }

        return $opCounts;
    }


    /**
     * 同步报表日志
     * @param $access_token
     * @param array $data
     * @param string $dataType
     * @return array
     */
    public static function syncClientReportData($access_token, array $data=[], string $dataType='week'): array
    {
        try {
            $data = $data['list'];
            Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '客户端报表日志', ['access_token'=>$access_token, 'data'=>$data, 'is_array'=>is_array($data), 'dataType'=>$dataType]);
            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            $userId = $TzSystemsUsers->uid;
            foreach ($data as &$datum){
                list($date, $bs, $betMoney, $profits, $backWater, $realProfits) = $datum;
                Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '客户端报表日志', ['datum'=>$datum, 'date'=>$date]);
                $date = trim(explode(' ', $date)[0]);
                $backWater = is_numeric($backWater) ? (float)$backWater : 0;
                $realProfits = is_numeric($realProfits) ? (float)$realProfits : 0;
                $datum = [$date, (float)$bs, (float)$betMoney, (float)$profits, (float)$backWater, (float)$profits];
            }
            $mKeyStaticsInfoKey = CacheKeyService::getSiteReportDataKey($userId); # 客户端推送数据
            commonRedis()->setex($mKeyStaticsInfoKey, 360, $data);

            /*
            $robotAdmin = WechatUser::find()->where(['user_id'=>$userId, 'is_admin'=>1])->asArray()->limit(1)->one();
            $messageService = new MessageOperateService($TzSystemsUsers->uid, $robotAdmin['userName']);
            list($balance, $todayPl, $todayBet, $weekBet, $weekPl, $lastWeekBet, $lastWeekPl) = AgentService::getCalcMoney($userId);
            $text = '盘口余额：'.$balance."\n"
                .'今日盈亏：'.$todayPl."\n"
                .'有效金额：'.$todayBet."\n"
                .'本周下单金额：'.$weekBet."\n"
                .'本周实际盈亏：'.$weekPl."\n"
                .'上周下单金额：'.$lastWeekBet."\n"
                .'上周实际盈亏：'.$lastWeekPl;
            $adminUserName = $messageService->robotAdmin['userName'];
            $token = $messageService->robotInfo['token'];
            $messageService->reply($userId, $text, ['targetId'=>$adminUserName, 'token'=>$token]); # 回复管理员消息
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '报表日志处理', ['text'=>$text, 'user_id'=>$userId, 'targetId'=>$adminUserName, 'token'=>$token]);
            */
        }catch (\Exception $e){
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '报表日志处理-异常', ['user_id'=>$userId, 'file'=>$e->getFile().'_'.$e->getLine(), 'err_msg'=>$e->getMessage(), 'token'=>$token]);
        }

        return ['status'=>200, 'data'=>[], 'msg'=>'操作成功'];
    }
}
