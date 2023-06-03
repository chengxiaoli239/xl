<?php
namespace backend\service\clients;

use backend\models\AgentUserBetLogs;
use backend\service\AgentUsersService;
use backend\service\BetService;
use backend\service\HN0898Service;
use backend\service\numbers\CodeTypeService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class AgentClientsService extends ClientsBaseService{
    const ALL_CODE_TYPE_1_NUMS = 10;
    const ALL_CODE_TYPE_2_NUMS = 100;
    const ALL_CODE_TYPE_3_NUMS = 1000;
    const ALL_CODE_TYPE_4_NUMS = 10000;

    public static function validateSyncMemberBetLogs($member_bet_logs){
        if($member_bet_logs['Status'] != 1){
            throw_info('日志请求状态异常Status:'.$member_bet_logs['Status']);
        }
        if(empty($member_bet_logs['Data']) OR empty($member_bet_logs['Data']['Rows']) OR $member_bet_logs['Data']['RecordCount']==0){
            throw_info('日志数据为空');
        }

        return [0, $member_bet_logs['Data']['Rows'], '校验成功'];
    }

    /**
     * @desc 客户端bet日志同步
     * @param array $member_bet_logs
     * @param string $access_token
     * @param string $from_type
     * @param string $from
     * @param int $buy_type
     * @param int $lottery_type
     * @return array
     * @throws \yii\db\Exception
     */
    public static function syncMemberBetLogs($member_bet_logs=[], $access_token='', $from_type='kuaixuan', $from='api', $lottery_type=DEFAULT_LOTTERY_TYPE){
        try {
            $data = [];
            $transaction = \Yii::$app->db->beginTransaction();

            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            $flow_wp_accounts = explode(',', $TzSystemsUsers->flow_wp_accounts);  # 反买账号，后续加正买账号

            $now_time = time();
            list($code, $logDatas, $err_msg) = AgentClientsService::validateSyncMemberBetLogs($member_bet_logs);
            foreach ($logDatas as $logData){
                try {
                    if(!in_array($logData['account'], $flow_wp_accounts)){
                        throw_info('不在跟随账号范围之内');
                    }
                    $buy_type = 0;  # 购买类型，反买账号，后续加正买账号

                    $AgentUserBetLogs = AgentUserBetLogs::findOne(['access_token'=>$access_token, 'wp_record_id'=>$logData['log_member_quick_select_id']]);
                    if(!empty($AgentUserBetLogs)){
                        throw_info('日志记录已存在');
                    }else{
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

                    $bet_codes_op = BetService::getHzCodes($data['tz_type'], json_encode($data['codes_hz']), $buy_type);  # 反买号码
                    $bet_op_counts = count(explode('@', $bet_codes_op));  # 实际反买组数
                    if($bet_op_theory_counts != $bet_op_counts){
                        throw_info('组数不符，理论组数：'.$bet_op_theory_counts.' 组，实际：'.$bet_op_counts.' 组 ');
                    }

                    $setDatas = [
                        'access_token' => $access_token,
                        'uid' => $TzSystemsUsers->uid,
                        'wp_record_id' => $logData['log_member_quick_select_id'],
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
                        'bet_type' => $buy_type, # 下注类型：1反买2正买  默认反买

                        'member_bet_time' => date('Y-').$logData['operation_datetime'],

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
                    $bet_single = ($buy_type==1) ? $single : $bet_single_op;
                    $rst = (new \backend\service\Lucky5\Lucky5Service($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id))->pushIntoBetTask($qihao, $codes, $data['tz_type'], $bet_single, $playway, $TzSystemsUsers->uid, $lottery_type);
                    Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '日志同步', ['account'=>$logData['account'], 'logData'=>$logData, 'attributes'=>$AgentUserBetLogs->getAttributes(), 'rst'=>$rst]);
                }catch (\Exception $e){
                    Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '代理日志记录-异常', ['account'=>$logData['account'], 'flow_wp_accounts'=>$flow_wp_accounts, 'logData'=>$logData, 'err_msg'=>$e->getMessage()]);
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
     * @return array
     */
    public static function getPlaywayByBetLogs(&$bet_logs='', &$codes_hz=[]){
        $playway = 0;

        $playwayTxtArr = ['一定位', '二定位', '三定位', '四定位'];
        $strArr = [',', '，'];
        foreach ($playwayTxtArr as $txt){
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
        $bet_log = str_replace(';', '；', $bet_log);
        $bet_log = str_replace(',', '，', $bet_log);
        #p(['initLog'=>$bet_log], 0);
        list($playway, $tz_type, $code_type) = AgentClientsService::getPlaywayByBetLogs($bet_log, $codes_hz);
        //p(['playway'=>$playway, 'codes_hz'=>$codes_hz, 'bet_log'=>$bet_log], 0);

        $dataArr = explode('，', $bet_log);
        # 重新拼接一下每个关键词
        $dataArr = AgentClientsService::resetDataArr($dataArr);
        //p(['调整后的dataArr'=>$dataArr], 0);

        # 第一步：关键词1
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
                if(strpos($vdata, $keyword1) !== false){
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
                    if(empty($operateStr)){
                        unset($dataArr[$k1]);
                        continue;
                    }

                    switch ($keyword2){
                        case CodeTypeService::KX_KW_2_FIXED_POS_1: # 定位置：千
                        case CodeTypeService::KX_KW_2_FIXED_POS_2: # 定位置：百
                        case CodeTypeService::KX_KW_2_FIXED_POS_3: # 定位置：十
                        case CodeTypeService::KX_KW_2_FIXED_POS_4: # 定位置：个
                        case CodeTypeService::KX_KW_2_FIXED_FILTER: # 定位置“除”
                        case CodeTypeService::KX_KW_2_FIXED_GET: # 定位置“取”
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateFixedPositionStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_FIXED_POS: # 固定位置
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateFixedPosStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_PEISHU_GET: # 配数取
                        case CodeTypeService::KX_KW_2_PEISHU_FILTER: # 配数取
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::opratePeiShuStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_FIXED_HF_FILTER:
                        case CodeTypeService::KX_KW_2_FIXED_HF_GET:
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateFixedPosHfStrCondition($operateStr));
                            unset($dataArr[$k1]);
                            break;
                        case CodeTypeService::KX_KW_2_NOT_FIXED_HF_2NUM: # 不定合分值(两数合)
                        case CodeTypeService::KX_KW_2_NOT_FIXED_HF_3NUM: # 不定合分值(三数合)
                            var_dump('keyword2：'.$keyword2.'=='.$tmpStr);
                            $keyword2Condition = array_merge($keyword2Condition, CodeTypeService::oprateNotFixed2_3Condition($operateStr));
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
                    }

                    $codes_hz = array_merge($codes_hz, $keyword2Condition);
                    //p(['codes_hz'=>$codes_hz]);
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
        $newDataArr = [];

        //p([$dataArr, $newDataArr], 0);
        $current_key = '';
        foreach ($dataArr as $k2=>$tmpStr){
            if($k2>0 && (
                strpos(trim($tmpStr), '第') === 0 OR
                strpos(trim($tmpStr), '内容') === 0 OR
                strpos(trim($tmpStr), '千=') === 0 OR
                strpos(trim($tmpStr), '百=') === 0 OR
                strpos(trim($tmpStr), '十=') === 0 OR
                strpos(trim($tmpStr), '个') === 0)
            ){
                $dataArr[$current_key] = $dataArr[$current_key].'，'.$tmpStr;
                unset($dataArr[$k2]);
            }else{
                $current_key = $k2;
            }
        }
        $newDataArr = $dataArr;

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

    public static function getBuyTypeByUserAccount($uid='', $userAccount=''){

    }
}
