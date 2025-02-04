<?php
/**
 * 通用处理类
 * Enter description here ...
 * @author sam
 * @authors wudean(bj) 乔迁标识
 *
 */
namespace common\tools;
use backend\models\DataTime;
use backend\models\KjConfig;
use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\models\StaticCodeTypeArisePerdate;
use backend\models\UserFollowData;
use backend\service\BetService;
use backend\service\NumService;
use backend\service\SscDataService;
use backend\service\SystemService;
use backend\service\TzService;
use common\helpers\lottery\LotteryBet;
use common\helpers\LotteryType;
use common\kj\cqssc\CqsscKcw;
use common\service\cache\CacheKeyService;
use common\service\CommonService;
use common\service\jobs\kj_data\CommonDataStaticsJob;
use common\service\jobs\kj_data\GrabKjDatasJob;
use common\service\jobs\kj_data\PeiShuProfitsJob;
use common\service\jobs\kj_data\PushKjDataToOutSiteJob;
use common\service\jobs\kj_data\StaticAll2NumsYlJob;
use common\service\jobs\kj_data\StaticHzProfitsJob;
use common\service\jobs\kj_data\StaticPeiShuTrueFalseJob;
use common\service\jobs\kj_data\StaticSdProfitsJob;
use common\service\jobs\kj_data\Update1NumYlJob;
use common\service\jobs\kj_data\UpdateCodeTypeYlJob;
use common\service\lottery\LotteryTypeService;
use common\service\open\telegram\AoZhouKjService;
use common\service\ssc\QihaoService;
use backend\service\CurlService;
use backend\service\HN0898Service;
use backend\service\BaseNumService;
use backend\service\OpKjService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\OperateLotteryService;
use common\tools\Tools;
use backend\models\BettingRecords;
use backend\service\StaticService;
use DateTime;
use yii;
class KjDataGet
{
    public static function _init($lottery_type = DEFAULT_LOTTERY_TYPE){
        set_time_limit(0);

        $time = date("H:i");
        if( ('02:00' < $time && $time < '20:30') OR ('21:00' < $time && $time < '23:59') ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            //return $rst;
        }
    }

    /**
     * @description 开奖数据抓取
     * @param string $date_start 抓取开始日期
     * @param string $date_end  抓取结束日期
     * @param int $all  是否全部抓取
     * @return bool
     */
    public static function grab($date_start = '20180101'){
        $msg = ['status'=>200, 'msg'=>'操作成功~'];
        self::_init('ssc');
        $date_end = date("Ymd");
        $kjData = CommonService::getAwardNumberByQihao(str_replace('20','',$date_start.'001'));
        if(strlen($kjData)<5) return false;
        $m = \Yii::$app->cache;
        $mkey = 'LAST_GRAP_QIHAO';
        //$m->set($mkey, '180503116', 7*24*60*60); // 缓存7天
        //return $msg;
        # 时时彩
        $dateArr = CommonService::genDateArr($split = '-', $date_start, $date_end);
        $tmpInsertData = [];
        foreach ($dateArr as $key=>$date) {
            $qihao_date = str_replace('-','',$date);
            $fields = ['kj_code', 'qihao', 'date'];
            $qihao = ltrim($qihao_date . '001', '20');
            $last_update_qihao = $m->get($mkey);
            if($qihao <= $last_update_qihao) continue;
            $end_qihao = ltrim($qihao_date . '120', '20');
            for ($qihao; $qihao <= $end_qihao; $qihao++) {
                # 开奖数据 start
                $kjData = CommonService::getAwardNumberByQihao($qihao);
                $kjDatas = str_replace(',', '', $kjData);
                if(!$kjDatas) continue;
                # 开奖数据 end
                $tmpData = [$kjDatas, $qihao, '20' . $date];
                $tmpInsertData[] = $tmpData;
                if (count($tmpInsertData) == 120) {
                    foreach ($tmpInsertData as $key=>$insertData){
                        if($insertData[0] == '') unset($tmpInsertData[$key]);
                    }
                    if (!$rst = \Yii::$app->db->createCommand()->batchInsert("{{%ssc_kj_data}}", $fields, $tmpInsertData)->execute()) {
                        $msg['status'] = 300;
                        $msg['msg'] = '数据处理异常';
                    }
                    $tmpInsertData = [];
                }
                $mcQihao = end($tmpInsertData)[1];
                $m->set($mkey, $mcQihao, 7*24*60*60); // 缓存7天
            }
        }

        return $msg;
    }


    /**
     * @description 时时彩逐期抓取(以此方法为主抓取时时彩数据)
     * @return bool
     */
    public static function grabKjData(): bool
    {
        $m = \Yii::$app->cache;
        $lotteryTypeData = StaticService::getGrabDataLotteryTypes();
        foreach ($lotteryTypeData as $lotteryData){
            try {
                $lottery_type = (int)$lotteryData['lottery_type'];
                $initLotteryKey = SystemService::getInitLotteryDataKey($lottery_type);
                #KjDataGet::insertOneLotteryKjData($lottery_type);

                $exist_key = $initLotteryKey.$lottery_type;
                $flag = $m->get($initLotteryKey);
                // 防止并发售后消息通知
                $exist = \Yii::$app->redis->sadd($exist_key, $lottery_type);
                if(!$exist OR $flag){
                    //throw_info('并发消息处理'.$lottery_type, 30001);
                }
                \Yii::$app->redis->expire($exist_key, 120);
                $cacheTime = (strpos($lotteryData['typeGroupName'], '高频') !== false) ? 10 : 1800;
                $m->set($initLotteryKey, 1, $cacheTime);

                KjDataGet::isCanGrab($lottery_type, $isCanGrab);
                $status = (new LotteryBet())->checkLotteryStatus($lottery_type); # 是否封盘, 封盘之时即是抓取之时
                if($status != LotteryBet::STATUS_DRAW){
                    Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据抓取-异常28', [
                        'lottery_type'=>$lottery_type,
                        'typeGroupName'=>$lotteryData['typeGroupName'],
                        'err_msg'=>'该时间点不可抓取',
                        'status'=>$status,
                        'isCanGrab'=>$isCanGrab,
                        'status1Txt'=>LotteryBet::STATUS_OPTIONS[$status].'_'.$status,
                    ]);
                    throw_info('该时间点不可抓取-'.LotteryType::TYPE_OPTIONS[$lottery_type]);
                }

                if(!$isCanGrab && $status != LotteryBet::STATUS_DRAW) {
                    $mKey = CacheKeyService::lotteryGrabInfo($lottery_type);
                    $data = commonRedis()->get($mKey);
                    if($status == LotteryBet::STATUS_START && empty($data)){ # 开盘时间点但是抓去号码时间超过5分钟则继续抓去
                        Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据抓取-异常0', ['lottery_type'=>$lottery_type, 'typeGroupName'=>$lotteryData['typeGroupName'], 'err_msg'=>'该时间点开奖延迟补抓', 'status'=>$status]);
                    }else{
                        Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据抓取-异常1', ['lottery_type'=>$lottery_type, 'typeGroupName'=>$lotteryData['typeGroupName'], 'err_msg'=>'该时间点不可抓取', 'status'=>$status]);
                        throw_info('该时间点不可抓取');
                    }
                }

                $params = ['lottery_type'=>$lottery_type, 'title'=>$lotteryData['title'], 'business_id'=>$lottery_type, 'is_grab_history'=>1];
                var_dump('lottery_type:'.$lottery_type.' '.date('Y-m-d H:i:s'));

                push_queue(GrabKjDatasJob::class, $params);
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据抓取', ['lottery_type'=>$lottery_type, 'typeGroupName'=>$lotteryData['typeGroupName'], 'cacheTime'=>$cacheTime, 'flag'=>$flag, 'status1Txt'=>LotteryBet::STATUS_OPTIONS[$status].'_'.$status]);
                \Yii::$app->redis->srem($exist_key, $lottery_type);
            }catch (\Exception $e){
                var_dump('lottery_type:'.$lottery_type.' '.$e->getMessage().' '.date('Y-m-d H:i:s'));
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据抓取-异常2', ['lottery_type'=>$lottery_type, 'typeGroupName'=>$lotteryData['typeGroupName'], 'err_msg'=>$e->getMessage()]);
                \Yii::$app->redis->srem($exist_key, $lottery_type);
            }
        }

        return true;
    }

    /**
     * 单个彩种号码抓取
     * @param int $lottery_type
     * @return array|null
     */
    public static function insertOneLotteryKjData(int $lottery_type=DEFAULT_LOTTERY_TYPE, $qihao='', $kjData=[]): ?array
    {
        $RedisLock = new RedisLock();
        $KjConfigs = KjConfig::findAll(['enable'=>1, 'lottery_type'=>$lottery_type]);
        foreach ($KjConfigs as $kjConfig){
            try {
                $grabOneKey = 'grabOneLotteryKjData_x0_'.$lottery_type;
                $status = KjDataGet::isCanGrab($kjConfig->lottery_type);
                if(!$status && !$kjConfig->is_batch){
                    throw_info('该时间段不可抓取');
                }
                if(!$RedisLock->lock($grabOneKey, 15)){
                    throw_info('短时间内操作-暂不处理');
                }

                if($qihao && !empty($kjData)){
                    $data = $kjData;
                }else{
                    $url = trim($kjConfig->host).trim($kjConfig->path);
                    $data = CurlService::httpGet($url);
                }
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖任务获取', ['lottery_type'=>$lottery_type, 'url'=>$url, 'data'=>$data]);
                if(isset($data['status']) && $data['status'] != 200){
                    throw_info($data['msg']??'开奖数据抓取异常');
                }

                if($kjConfig->is_batch == 1){ # 批量
                    $kjDatas = $data;
                    Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '批量抓取开奖号码', ['data'=>$data]);
                    if($kjDatas){
                        # xjssc  1七星彩17排列五
                        $kjDatas = array_reverse($kjDatas); # 翻转
                        foreach ($kjDatas as $key=>$dataInfo){
                            $qihao = $dataInfo['expect'];
                            KjDataGet::insertKjData($qihao, $kjConfig->lottery_type, $dataInfo['opencode'], $dataInfo['opentime']);
                        }
                    }
                    $logArr = ['lottery_type'=>$kjConfig->lottery_type];
                }else{
                    if(!empty($data['opencode'])){
                        # ssc
                        KjDataGet::insertKjData($data['expect'], $kjConfig->lottery_type, $data['opencode'], $data['opentime']);
                    }
                    $logArr = ['data'=>$data, 'lottery_type'=>$lottery_type, 'lottery'=>CqsscKcw::getLotteryNameArr()[$kjConfig->lottery_type]];
                }
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖记录', $logArr);
                /* 处理系统投注计划 add 2019-01-21 */
                KjDataGet::afterKj($lottery_type); # 处理系统投注计划，更新统计数据
                $RedisLock->unlock($grabOneKey);
            }catch (\Exception $e){
                $RedisLock->unlock($grabOneKey);
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', '短时间内操作', ['lottery_type'=>$lottery_type, 'lottery_name'=>LotteryType::getName($lottery_type), 'err_msg'=>$e->getMessage().'_'.$e->getFile().'_'.$e->getLine()]);
                return ['code'=>$e->getCode(), 'msg'=>$e->getMessage()];
            }
        }

        return $logArr;
    }

    /**
     * @desc 判断时间段是否可以抓取开奖号码  主要针对半夜不开奖时间段
     * @param int $lottery_type
     * @param boolean $isCanBet
     * @return bool
     */
    public static function isCanBet($lottery_type = DEFAULT_LOTTERY_TYPE, &$isCanBet=true) {
        $flag = true;
        $isCanBet = true;
        $date_time = date('H:i');
        $is_init = \Yii::$app->cache->get(SystemService::getInitLotteryDataKey($lottery_type));
        if (in_array($lottery_type, [5, 6])){
            if ('04:00' < $date_time && $date_time < '07:10') {
                $isCanBet = $flag = false;
            }
        }elseif($lottery_type == 8){ # 幸运五星
            # 用户报表需求 24小时抓取开奖数据
            if ('04:10' < $date_time && $date_time < '09:00') {
                #$flag = false; # 全天开奖，这里先去掉
                $isCanBet = false;
            }
        }elseif(in_array($lottery_type, [10, 11, 12, 13])){ # 冰岛90s、3分
            if ('03:10' < $date_time && $date_time < '08:55') {
                $isCanBet = $flag = false;
            }
        }elseif(!$is_init && in_array($lottery_type, [17])){ # 排列五
            if('20:15'>$date_time OR $date_time>'23:00'){
                $isCanBet = $flag = false;
            }
        }elseif(!$is_init && in_array($lottery_type, [1])){ # 七星
            $w = date('w'); # 周几：0,1,2,3,4,5,6  ==> 周日到周六
            if(!in_array($w, [0, 2, 5])){
                $isCanBet = $flag = false;
            }
            if('20:00'>$date_time OR $date_time>'23:00'){
                $isCanBet = $flag = false;
            }
        }elseif(in_array($lottery_type, [18])){ # 台湾快五
            if ('02:10' < $date_time && $date_time < '07:00') {
                $isCanBet = $flag = false;
            }
        }

        return $flag;
    }

    /**
     * @desc 判断时间段是否可以抓取开奖号码  主要针对半夜不开奖时间段
     * @param int $lottery_type
     * @param boolean $isCanGrab
     * @return bool
     */
    public static function isCanGrab($lottery_type = DEFAULT_LOTTERY_TYPE, &$isCanGrab = true)
    {
        $flag = true;
        $isCanGrab = true;
        $date_time = date('H:i:s');
        $is_init = \Yii::$app->cache->get(SystemService::getInitLotteryDataKey($lottery_type));
        $lotteryTypeData = LotteryTypeService::getLotteryTypeData();
        $openingTime = $lotteryTypeData[$lottery_type]['opening_time'];
        $closingTime = $lotteryTypeData[$lottery_type]['closing_time'];

        $minute_nums = date('i');
        if($lottery_type == 8) { # 幸运五星
            # 用户报表需求 24小时抓取开奖数据
            if ($closingTime < $date_time && $date_time < $openingTime) {
                #$flag = false; # 全天开奖，这里先去掉
                $isCanGrab = false;
            }

            $minute_nums_d = ((int)$minute_nums) % 5;
            if (!in_array($minute_nums_d, [0, 1])) { # 最初开奖
                $isCanGrab = false;
            }
        }elseif(!$is_init && $lottery_type == 17){ # 排列五
            if('20:15:00'>$date_time OR $date_time>'23:00:00'){
                $isCanGrab = $flag = false;
            }
        }elseif(!$is_init && $lottery_type == 1){ # 七星
            $w = date('w'); # 周几：0,1,2,3,4,5,6  ==> 周日到周六
            if(!in_array($w, [0, 2, 5])){
                $isCanGrab = $flag = false;
            }
            if('20:00:00'>$date_time OR $date_time>'23:00:00'){
                $isCanGrab = $flag = false;
            }
        }elseif(in_array($lottery_type, [26, 27])){ # 福彩3d、排列3
            if ('00:00:00' < $date_time && $date_time < '21:00:00') {
                //$isCanGrab = $flag = false;
            }
        }

        return $flag;
    }

    /**
     * @desc 开奖后处理的数据
     */
    public static function afterKj($lottery_type = DEFAULT_LOTTERY_TYPE): array
    {

        list($code, $qihao) = SscDataService::insertDealDataTask($lottery_type); # 数据处理任务写入
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '开奖后处理', ['code'=>$code, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao]);
        if($lottery_type == LotteryType::LUCKY_5 && substr($qihao, -3)=='288'){
            $tmpDate = date('Y-m-d', time()-86400);
        }else{
            $tmpDate = date('Y-m-d');
        }

        $rst = ['status'=>200, 'msg'=>'处理成功'];
        if($code!=0) {
            return $rst;
        }
        $lottery_name = \common\service\CommonService::getLotteryName($lottery_type);
        switch (true){
            case in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES):
                OperateLotteryService::operate($lottery_type);  # 3D 处理3D下注记录
                break;
            case $lottery_type == CommonBaseService::LOTTERY_TYPE_AOZHOU5: // 龟盘
                push_queue_fast(PushKjDataToOutSiteJob::class, ['lottery_type'=>$lottery_type, 'business_id'=>$lottery_type]);
                $rst['OpKjService'] = OpKjService::opSscKjData($lottery_type); # 处理投注数据
                # 1、队列处理下注数据
                push_queue(\common\service\jobs\kj_data\OperateBetPlans::class, ['lottery_type'=>CommonBaseService::LOTTERY_TYPE_AOZHOU5, 'lottery_name'=>$lottery_name, 'qihao'=>$qihao, 'business_id'=>$qihao]);
                # 2、群里发开奖信息
                (new AoZhouKjService())->operateSendKjData($qihao);
                $mKey = CacheKeyService::lotteryOpenSwitch($lottery_type);
                commonRedis()->setex($mKey, 10, 1);
                break;
            case $lottery_type == CommonBaseService::LOTTERY_TYPE_LUCKY5: // 幸运五
                push_queue_fast(PushKjDataToOutSiteJob::class, ['lottery_type'=>$lottery_type, 'business_id'=>$lottery_type]);
                $rst['OpKjService'] = OpKjService::opSscKjData($lottery_type); # 处理投注数据
                # 1、队列处理下注数据
                push_queue(\common\service\jobs\kj_data\OperateBetPlans::class, ['lottery_type'=>CommonBaseService::LOTTERY_TYPE_LUCKY5, 'lottery_name'=>$lottery_name, 'qihao'=>$qihao, 'business_id'=>$qihao]);

                # 2、数据统计处理  底下的统计有待于添加开关控制
                push_queue(PeiShuProfitsJob::class, ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'title'=>$lottery_name, 'business_id'=>$qihao, 'queue_delay_time'=>5]);
                push_queue(CommonDataStaticsJob::class, ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'date'=>$tmpDate, 'title'=>$lottery_name, 'business_id'=>$qihao, 'queue_delay_time'=>5]);
                push_queue(StaticAll2NumsYlJob::class, ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'title'=>$lottery_name, 'business_id'=>$qihao, 'queue_delay_time'=>10]);
                push_queue(Update1NumYlJob::class, ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'title'=>$lottery_name, 'business_id'=>$qihao, 'queue_delay_time'=>10]);
                push_queue(StaticHzProfitsJob::class, ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'title'=>$lottery_name, 'business_id'=>$qihao, 'queue_delay_time'=>15]);
                push_queue(StaticPeiShuTrueFalseJob::class, ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'title'=>$lottery_name, 'business_id'=>$qihao, 'queue_delay_time'=>20]);
                push_queue(StaticSdProfitsJob::class, ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'title'=>$lottery_name, 'business_id'=>$qihao, 'queue_delay_time'=>25]);
                push_queue(UpdateCodeTypeYlJob::class, ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'title'=>$lottery_name, 'business_id'=>$qihao, 'queue_delay_time'=>30]);
                break;
        }
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '统计数据入列', ['qiHao'=>$qihao, 'lottery_type'=>$lottery_type, 'title'=>$lottery_name, 'msg'=>'数据入列成功']);
        return $rst;
    }

    /**
     * @desc ssc开奖data
     * @param $qihao 181120059
     * @param int $lottery_type 彩票类型1:1.5彩2:3分彩3:5分彩4:10分彩
     * @param string $kjData
     * @param string $opentime 2022-04-03 21:00:00
     * @return array|bool
     */
    public static function insertKjData($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE, $kjData='', $opentime = ''){
        $kjDatas = str_replace(',', '', $kjData);
        if(!$qihao OR !$kjDatas) return false;
        $SscKjData = SscKjData::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
        if(!empty($SscKjData)){
            return ['status'=>301, 'msg'=>'开奖号码存在'];
        }
        $SscKjData = new SscKjData();
        list($lastQiHao, $lastIndexId, $lastId) = SscDataService::getKjDataLastIndexId($lottery_type);
        $index_id = $lastIndexId + 1;

        $kjDatasArr = explode(',',$kjData);
        $codes_4nums = $kjDatasArr; unset($codes_4nums[4],$codes_4nums[5],$codes_4nums[6]); # 四定和值只取前四个号码

        $codes = $kjDatasArr[0].','.$kjDatasArr[1].','.$kjDatasArr[2].','.$kjDatasArr[3];
        if($lottery_type == 5) {
            $tmpDate = '20' . substr($qihao, 0, 6) . ' 00:00:00';
        }elseif ($lottery_type == 6){
            $tmpDate = substr($qihao, 0, 8);
        }elseif (in_array($lottery_type, [22, 23, 24, 25])){
            $tmpDate = $opentime;
        }elseif (!empty($opentime)){
            $tmpDate = substr($opentime, 0, 10);
        }else{
            if($lottery_type == LotteryType::LUCKY_5 && substr($qihao, -3)=='288'){
                $tmpDate = date('Y-m-d H:i:s', time()-86400);
            }else{
                $tmpDate = date('Y-m-d H:i:s');
            }
        }
        if(in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES)){
            $codesArr = [$kjDatasArr[0],$kjDatasArr[1],$kjDatasArr[2]];
        }else{
            $codesArr = [$kjDatasArr[0],$kjDatasArr[1],$kjDatasArr[2],$kjDatasArr[3]];
        }

        list($type_dx, $type_4dx, $type_dx_str) = CommonService::getTypeDx($codes.','.$kjDatasArr[4]);
        sort($codesArr); # 排序
        $code_3n = CommonService::get3n($codesArr, $lottery_type);
        $code_2n = CommonService::get2n($codesArr, $lottery_type);
        $insertData = [
            'index_id' => $index_id,
            'kj_code' => $kjDatas,
            'qihao' => (string)$qihao,
            'code_str' => $kjData,
            'codes_hz'=> array_sum($kjDatasArr),
            'code_4n_str' => $kjDatasArr[0].','.$kjDatasArr[1].','.$kjDatasArr[2].','.$kjDatasArr[3], # 四字定str
            'codes_4nums_hz'=> array_sum($codes_4nums),
            'code1'=>$kjDatasArr[0],
            'code2'=>$kjDatasArr[1],
            'code3'=>$kjDatasArr[2],
            'code4'=>$kjDatasArr[3],
            'code5'=>$kjDatasArr[4],
            'code6'=> $kjDatasArr[5] ?? NULL,
            'code7'=> $kjDatasArr[6] ?? NULL,
            'code_1_2'=>$kjDatasArr[0]+$kjDatasArr[1],
            'code_1_3'=>$kjDatasArr[0]+$kjDatasArr[2],
            'code_1_4'=>$kjDatasArr[0]+$kjDatasArr[3],
            'code_2_3'=>$kjDatasArr[1]+$kjDatasArr[2],
            'code_2_4'=>$kjDatasArr[1]+$kjDatasArr[3],
            'code_3_4'=>$kjDatasArr[2]+$kjDatasArr[3],
            'code_1_2_3_4' => SscDataService::getCodesDS($kjData),
            'code_2n' => implode(',', $code_2n),
            'code_3n' => implode(',', $code_3n),
            'code_4n' => implode('', $codesArr),
            'type_2' => CommonService::isCodeType2($codes), # 是否双重
            'type_22' => CommonService::isCodeType22($codes), # 是否双双重
            'type_3' => CommonService::isCodeType3($codes), # 是否三重
            'type_4' => CommonService::isCodeType4($codes), # 是否四重
            'type_2b' => CommonService::isCodeType2b($codes), # 是否两兄弟
            'type_22b' => CommonService::isCodeType22b($codes), # 是否双两兄弟
            'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
            'type_4b' => CommonService::isCodeType4b($codes), # 是否四兄弟
            'type_4ds' => CommonService::isCodeType4ds($codes), # 是否四单双：0非四单四双1四单2四双
            'type_3n_2b' => CommonService::isCodeType3n2b($codes), # 是否三现：双重+兄弟
            'type_zx_bz' => CommonService::isCodeTypeZxBz($codes), # 前三：组三、组六、豹子判读判断
            'type_log' => CommonService::isCodeTypeLog($codes), # 是否对数
            'type_2log' => CommonService::isCodeType2Log($codes), # 是否双对数

            # 大小类型
            'type_dx' => $type_dx, # 大小类型： \backend\service\NumService::$type_dx_datas
            'type_4dx' => $type_4dx, # 大小类型：1122 1指小2指大
            'type_dx_str' => $type_dx_str, # 大小类型：2大2小

            'lottery_type' => $lottery_type,
            'date' => date('Y-m-d',strtotime($tmpDate)),
        ];
        if(!empty($opentime)){
            $insertData['created_at'] = (int)strtotime($opentime);
        }

        SscDataService::getAriseCodes([implode('', $codesArr)]); # 缓存开奖号码四定组合

        $SscKjData->setAttributes($insertData);
        if (!$insertRst = $SscKjData->save()) {
            $msg = current($SscKjData->getErrors());
            $lotteryNameArr = CqsscKcw::getLotteryNameArr();
            $logArr = ['msg'=>$msg, 'qihao'=>$qihao, 'kjData'=>$kjData, 'lottery'=>$lotteryNameArr[$lottery_type]];
            Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', '开奖记录-错误', $logArr);
            return ['status' => 300, 'msg' => $msg];
        }
        Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', '开奖记录-插入', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'kjData'=>$kjData]);

        $mKey = CacheKeyService::lotteryGrabInfo($lottery_type);
        commonRedis()->setex($mKey, 300, $qihao.':'.$kjData);

        # 开奖数据缓存
        $mKey = CacheKeyService::lotteryLastIndexKey($lottery_type);
        commonRedis()->setex($mKey, 30, $SscKjData->attributes);
        return [
            'status'=>200,
            'msg'=>'开奖数据写入成功',
            'lottery_type'=>$lottery_type,
            'qihao'=>$qihao,
            'insertData'=>$insertData,
            'insertRst'=>$insertRst,
            'id'=>$SscKjData->id,
        ];
    }

    /**
     * @desc 获取表里记录往后最新一期的开奖号码
     * @注：官网有可能不开奖的情况处理
     */
    public static function getLotteryNo($qihao,$lottery_type='ssc',$date = ''){
        if(!$date) $date = date('Y-m-d');
        $m = \Yii::$app->cache;
        $ckey = 'KJ_DATA_2_'.$lottery_type.'_'.$qihao;
        if($kjData = $m->get($ckey)){
            return ['qihao'=>$qihao,'kjData'=>$kjData];
        }
        $url = 'http://wd.apiplus.net/daily.do?token=tef05c6c66079ff29k&code=cqssc&format=json&date='.$date; // 2018-10-21
        $data = file_get_contents($url);
        $datasArr = json_decode($data,320)['data'];
        $datasArr = array_reverse($datasArr);
        $i=0;
        foreach ($datasArr as $key=>$datas){
            $dataQihao = substr($datas['expect'],2);
            $ckey = 'KJ_DATA_2_'.$lottery_type.'_'.$dataQihao;
            $m->set($ckey, $datas['opencode'],7*24*60*60);
        }
        //p($datasArr);
        foreach ($datasArr as $key=>$datas){
            $dataQihao = substr($datas['expect'],2);
            if($dataQihao >= $qihao){
                $qihao = substr($datas['expect'],2);
                $kjData = $datas['opencode'];
                break;
            }
        }

        return ['qihao'=>$qihao, 'kjData'=>$kjData];
    }

    /**
     * @desc 获取给定期号的下一期
     * @param int $qihao
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @return bool|int|string
     */
    public static function getNextQihaoByQihao($qihao = 180101001, $lottery_type = DEFAULT_LOTTERY_TYPE){

        $m = \Yii::$app->cache;
        $mkey = __FUNCTION__.'_'.$lottery_type.'_'.$qihao.'_1';
        $nextQihao = $m->get($mkey);

        if(empty($nextQihao)){
            $nextQihao = $qihao + 1;
            switch ($lottery_type){
                case 1: # 七星彩
                case 17: # 排列五
                    $SscKjDatas = SscKjData::find()->select(['qihao', 'lottery_type'])
                        ->where(['AND', ['=', 'lottery_type',$lottery_type], ['>', 'qihao', $qihao]])
                        ->orderBy(['id'=>SORT_ASC])->limit(1)->one();
                    if(!empty($SscKjDatas)){
                        $nextQihao = $SscKjDatas['qihao'];
                    }
                    break;
                case 5: # 重庆
                    $year = '20'.substr($qihao,0,2);
                    $date = '20'.substr($qihao,0,6);
                    $qihao = substr($qihao,6,3);
                    $maxQihaoArr = BetService::$maxQihaoArr;
                    $maxQihao = $maxQihaoArr[$lottery_type];
                    if($date == $year.'1231' && $qihao >=$maxQihao){
                        $nextQihao = substr(((int)$year+1).'0101001', 2, 9);
                    //}elseif($qihao >= 120){
                    }elseif($qihao >= $maxQihao){
                        $nextQihao = substr(Tools::getNextDate($date),2, 6).'001';
                    }
                    break;
                case 6: # 新疆
                    $minQihao = substr($nextQihao, 8, 2);
                    $date = substr($nextQihao, 0, 4).'-'.substr($nextQihao, 4, 2).'-'.substr($nextQihao, 6, 2).' 00:00:00';
                    if($minQihao == 49){
                        $date = date('Ymd', strtotime($date) + 86400);
                        $nextQihao = $date.'01';
                    }
                    break;
                case 8: # 幸运五星彩
                    if(substr($qihao, -3, 3) >= 288){
                        $date = substr($qihao, 0, 4).'-'.substr($nextQihao, 4, 2).'-'.substr($nextQihao, 6, 2).' 00:00:00';
                        $date = date('Ymd', strtotime($date) + 86400);
                        $nextQihao = $date.'001';
                    }
                    break;
                case 23: # 以太坊3分
                    if(substr($qihao, -3, 3) > 480){
                        $date = '20'.substr($qihao, 0, 2).'-'.substr($nextQihao, 2, 2).'-'.substr($nextQihao, 4, 2).' 00:00:00';
                        $date = substr(date('Ymd', strtotime($date) + 86400), 2);
                        $nextQihao = $date.'001';
                    }
                    break;
                case 24: # 以太坊10分
                    if(substr($qihao, -3, 3) > 144){
                        $date = '20'.substr($qihao, 0, 2).'-'.substr($nextQihao, 2, 2).'-'.substr($nextQihao, 4, 2).' 00:00:00';
                        $date = substr(date('Ymd', strtotime($date) + 86400), 2);
                        $nextQihao = $date.'001';
                    }
                    break;
            }
            $m->set($mkey, $nextQihao, 600);
        }

        return $nextQihao;
    }

    /**
     * @desc 获取给定期号的上一期 - 主要针对已经历史数据的模拟
     * @param string $qihao
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @return bool|int|string
     */
    public static function getBeforeQiHaoByQiHao($qihao = '180101001', $lottery_type = DEFAULT_LOTTERY_TYPE){
        if($lottery_type == 'qxc'){
            # 未完
        }else{
            $beforeQihao = $qihao - 1;
            if($lottery_type == 5){
                $year = '20'.substr($qihao,0,2);
                $date = '20'.substr($qihao,0,6);
                $qihao = substr($qihao,6,3);
                $maxQihaoArr = BetService::$maxQihaoArr;
                $maxQihao = $maxQihaoArr[$lottery_type];
                //$maxQihao = sprintf("%03d", $maxQihaoArr[$lottery_type]);
                if($date == $year.'0101' && $qihao <= 1){
                    $beforeQihao = substr(($year-1).'1231'.$maxQihao, 2,9);
                //}elseif($qihao >= 120){
                }elseif($qihao <= 001){
                    $beforeQihao = substr(Tools::getBeforeDate($date),2,9).'0'.$maxQihao;
                }
            }elseif($lottery_type == 6){
                $minQihao = substr($beforeQihao, 8, 2);
                $date = substr($beforeQihao, 0, 4).'-'.substr($beforeQihao, 4, 2).'-'.substr($beforeQihao, 6, 2).' 00:00:00';
                if($minQihao == '00'){
                    $date = date('Ymd', strtotime($date) - 86400);
                    $beforeQihao = $date.'48';
                }
            }elseif($lottery_type == 8){  # 幸运五星彩
                if(substr($qihao, -3, 3) == '001') {
                    $date = substr($beforeQihao, 0, 4) . '-' . substr($beforeQihao, 4, 2) . '-' . substr($beforeQihao, 6, 2) . ' 00:00:00';
                    $date = date('Ymd', strtotime($date) - 86400);
                    $beforeQihao = $date.'288';
                }
            }else{
                #$beforeQihao = $beforeQihao;
                #if(in_array($lottery_type, [23, 24])) {  # 以太坊
                #    $SscKjDatas = SscKjData::find()->where(['lottery_type' => $lottery_type, 'qihao' > $qihao])->orderBy(['id' => SORT_ASC])->one()['qihao'];
                #}
                $SscKjDatas = SscKjData::find()->select(['qihao', 'lottery_type'])
                    ->where(['AND', ['=', 'lottery_type',$lottery_type], ['<', 'qihao', $qihao]])
                    ->orderBy(['id'=>SORT_DESC])->limit(1)->one();
                $beforeQihao = $SscKjDatas['qihao'];
            }
        }

        return $beforeQihao;
    }


    /**
     * @desc 获取最后表中一期期号
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @return string
     */
    public static function getEndQihao($lottery_type = DEFAULT_LOTTERY_TYPE){

        if($lottery_type == 'qxc'){

            $url = 'https://700056.com/qxc/ajax.aspx?act=getlastkj';
            $data = CurlService::httpGet($url);
            $endQihao = '20'.$data[0]['qihao'];
        }else{
            $KjData = SscKjData::find()->select(['qihao'])->where(['lottery_type'=>$lottery_type])->orderBy('id DESC')->limit(1)->asArray()->one();
            if(!$KjData) $KjData['qihao'] = '20180101000';
            $endQihao = $KjData['qihao'];
        }

        return $endQihao;
    }

    /**
     * @desc 自动更新 万千百十个数据
     */
    public static function updateNullCode($lottery_type = DEFAULT_LOTTERY_TYPE, $times=10000){
        /*
        $Num4Types = Num4Type::find()->where(['IS', 'type_dx', NULL])->orderBy('id DESC')->limit($times)->all();
        foreach ($Num4Types as $k=>$num4Type){
            $code1_dx = $num4Type->code_1=='X' ? 'X' : ($num4Type->code_1===NULL ? '': (in_array($num4Type->code_1, NumService::$MIN_CODES)? '1':'2'));
            $code2_dx = $num4Type->code_2=='X' ? 'X' : ($num4Type->code_2===NULL ? '': (in_array($num4Type->code_2, NumService::$MIN_CODES)? '1':'2'));
            $code3_dx = $num4Type->code_3=='X' ? 'X' : ($num4Type->code_3===NULL ? '': (in_array($num4Type->code_3, NumService::$MIN_CODES)? '1':'2'));
            $code4_dx = $num4Type->code_4=='X' ? 'X' : ($num4Type->code_4===NULL ? '': (in_array($num4Type->code_4, NumService::$MIN_CODES)? '1':'2'));
            $code5_dx = $num4Type->code_5=='X' ? 'X' : ($num4Type->code_5===NULL ? '': (in_array($num4Type->code_5, NumService::$MIN_CODES)? '1':'2'));

            $numArr = [$code1_dx, $code2_dx, $code3_dx, $code4_dx, $code5_dx];
            $countVals = array_count_values($numArr);
            $type_dx_str = '';
            foreach ($countVals as $countVal){
                if(isset($countVals[2])){
                    $type_dx_str .= $countVals[2].'大';
                    unset($countVals[2]);
                }elseif (isset($countVals[1])){
                    $type_dx_str .= $countVals[1].'小';
                    unset($countVals[1]);
                }
            }
            $num4Type->type_dx_str = $type_dx_str;
            if($num4Type->code_type == 4){
                $code5_dx = '';
                $type_dx = NumService::getType4dx($type_dx_str);;
            }else{
                $type_dx = NumService::getType4dx($type_dx_str);;
            }
            $num4Type->type_4dx = $code1_dx.$code2_dx.$code3_dx.$code4_dx.$code5_dx;

            $num4Type->type_dx = $type_dx;
            #p($num4Type->getAttributes());
            $flag = $num4Type->save();
            if(empty($flag)){
                print($num4Type->getErrors());
            }
            $rst[$k] = $flag;
            #p(['code'=>$num4Type->code, 'array_count_values'=>array_count_values($numArr), 'type_4dx'=>$num4Type->type_4dx, 'type_dx_str'=>$num4Type->type_dx_str, 'type_dx'=>$num4Type->type_dx, 'code_type'=>$num4Type->code_type, 'flag'=>$flag, $num4Type->id]);
            #p($rst);
        }
        */

        $dataQuery = Num4Type::find()->where(['type_2log'=>0, 'code_type'=>4])->limit($times);
        $sql = $dataQuery->createCommand()->getRawSql();
        $data = $dataQuery->orderBy('id DESC');
        foreach ($data->each(1000) as $kjDatum){
            //$kjDs = SscDataService::getCodesDS($kjData['code_str']);
            $codes = $kjDatum['code_1'].','.$kjDatum['code_2'].','.$kjDatum['code_3'].','.$kjDatum['code_4'];
            $isType2Log = CommonService::isCodeType2Log($codes);
            #p(['lottery_type'=>$kjData['lottery_type'], 'codes'=>$codes, $type_dx, $type_4dx, $type_dx_str], 0);
            $updateData = [
                //'code_4n_str' => $codes, # 四字定str
                //'index_id' => $key + 1,
                /*
                'code_3n' => implode(',', $code_3n),
                'code_4n' => implode('', $codesArr),
                'code1'=>$kjData['kj_code'][0],
                'code2'=>$kjData['kj_code'][1],
                'code3'=>$kjData['kj_code'][2],
                'code4'=>$kjData['kj_code'][3],
                'code5'=>$kjData['kj_code'][4],
                'codes_4nums_hz'=> array_sum($sumArr)
                'type_2' => CommonService::isCodeType2($codes), # 是否双重
                'type_22' => CommonService::isCodeType22($codes), # 是否双双重
                'type_3' => CommonService::isCodeType3($codes), # 是否三重
                'type_4' => CommonService::isCodeType4($codes), # 是否四重
                'type_2b' => CommonService::isCodeType2b($codes), # 是否两兄弟
                'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
                'type_4b' => CommonService::isCodeType4b($codes), # 是否四兄弟
                'type_3n_2b' => CommonService::isCodeType3n2b($codes), # 是否四单双：0非四单四双1四单2四双
                'type_22' => CommonService::isCodeType22($codes), # 是否双双重
                'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
                'type_22b' => CommonService::isCodeType22b($codes), # 是否双兄弟
                'type_dx' => $type_dx, # 大小类型
                'type_4dx' => $type_4dx, # 大小类型：1122  1小2大
                'type_dx_str' => $type_dx_str, # 大小类型：2大2小
                */
                'type_2log' => $isType2Log
            ];
            //p(['codes'=>$codes, 'updateData'=>$updateData], 0);
            $kjDatum->setAttributes($updateData);
            if(!$kjDatum->save()){
                $msg = current($kjDatum->getErrors());
                Tool_Common::log('updateNullCode', 'ERR', '更新空值', ['msg'=>$msg]);
                $msg = ['status'=>300, 'msg'=>$msg];
            }
            #p([$kjData->id, 'rst'=>$rst]);
        }

        return $msg;
    }

     /**
     * @desc 自动更新 code_str 字段
     */
    public static function updateCodeStr( $times = 50){
        $msg = ['status'=>200, 'msg'=>'操作成功！'];
        $kjDatas = SscKjData::find()->where(['code_str'=>null])->andWhere(['NOT',['kj_code'=>null]])->orderBy('id DESC')->asArray()->limit($times)->all();
        foreach ($kjDatas as $kjData){
            $code_str = $kjData['kj_code'][0].','.$kjData['kj_code'][1].','.$kjData['kj_code'][2].','.$kjData['kj_code'][3].','.$kjData['kj_code'][4];
            $updateData = [ 'code_str'=>$code_str ];
            $sscKjData = SscKjData::findOne(['qihao'=>$kjData['qihao']]);
            $sscKjData->setAttributes($updateData);
            if(!$rst = $sscKjData->save()){
                $msg = ['status'=>300, 'msg'=>current($rst->getErrors())];
            }
        }

        return $msg;
    }

     /**
     * @desc 自动更新 code_str 字段
     */
    public static function updateCodeHeZhi( $times = 50){
        $msg = ['status'=>200, 'msg'=>'操作成功！'];
        $kjDatas = SscKjData::find()->where(['code_1_2'=>null])->andWhere(['NOT',['kj_code'=>null]])->orderBy('id DESC')->asArray()->limit($times)->all();
        if(empty($kjDatas)) return ['status'=>300, 'msg'=>'已经处理完成！'];
        foreach ($kjDatas as $kjData){
            $updateData = [
                'code_1_2' => $kjData['kj_code'][0]+$kjData['kj_code'][1],
                'code_1_3' => $kjData['kj_code'][0]+$kjData['kj_code'][2],
                'code_1_4' => $kjData['kj_code'][0]+$kjData['kj_code'][3],
                'code_2_3' => $kjData['kj_code'][1]+$kjData['kj_code'][2],
                'code_2_4' => $kjData['kj_code'][1]+$kjData['kj_code'][3],
                'code_3_4' => $kjData['kj_code'][2]+$kjData['kj_code'][3],
            ];
            $sscKjData = SscKjData::findOne(['qihao'=>$kjData['qihao']]);
            $sscKjData->setAttributes($updateData);
            if(!$rst = $sscKjData->save()){
                $msg = ['status'=>300, 'msg'=>current($rst->getErrors())];
            }
        }

        return $msg;
    }


    /**
     * @description 获取开奖号码扒取时间,不在时间范围内容则不往下走扒取接口
     */
    public static function getSscGrupTime(){
        $nowTime = date('H:i:s');
        //$where = " type=1 AND actionTime<'{$nowTime}' ";
        $where = ['and', 'type=1', ['>','actionTime',$nowTime]];
        $dataTime = DataTime::find()->where($where)->orderBy('id ASC')->asArray()->one();

        $new_id = $dataTime['id'] - 1;
        $newDataTime = DataTime::find()->where(['id'=>$new_id])->asArray()->one();

        $start_time = strtotime(date("Y-m-d").$newDataTime['actionTime']);
        $get_start_time = date("H:i:s",$start_time);
        $get_end_time = date("H:i:s",$start_time + 120); // 两分钟内容扒取

        return [$get_start_time,$get_end_time];
    }

    /**
     * @description 循环跑用户计划更新参考号码
     * @return mixed
     */
    public static function updateUserFollowRefrenceCodes(){
        $UserFollowData = UserFollowData::findAll(['status'=>1]);
        foreach ($UserFollowData as $key=>$followData){
            $numsArr = [8,9,10,11];
            $unsetEle = [];
            $zuHe = explode(',',$followData->position);

            # 1、120出现次数汇总
            $heZhi_huizong = BaseNumService::getHeZhiByPositionTotal(120,$zuHe,$numsArr)['data']; // 在近xxx期期间和值汇总
            $end_hezhi_huizong = end($heZhi_huizong)['code_'.str_replace(',','_', $followData->position)];
            $unsetEle[] = $end_hezhi_huizong;

            $yilou_huizong = BaseNumService::getHeZhiYL($zuHe,$numsArr,50)['data']; // 和值为8、9在200期里边遗漏期数
            $max_yl_times = max($yilou_huizong);
            # 2、根据遗漏次数删除号码
            if(13 < $max_yl_times && $max_yl_times < 45){
                $max_yilou = array_search($max_yl_times, $yilou_huizong);
                $unsetEle[] = $max_yilou;
            }

            # 3、遗漏最小的号码，判断近30期出现的次数是否删除号码
            $min_yl_times = min($yilou_huizong);
            $min_yilou = array_search($min_yl_times, $yilou_huizong);
            $heZhi_huizong_30x_nums = current(BaseNumService::getHeZhiByPositionTotal(30,$zuHe,[$min_yilou])['data'])['nums']; // 在近xxx期期间和值汇总
            if($heZhi_huizong_30x_nums > 2){
                $unsetEle[] = $min_yilou;
            }

            OpKjService::unsetArrEle($numsArr,$unsetEle);
            $numsArr_str = implode(',',$numsArr);
            $followData->reference_codes = $numsArr_str;
            $rst = $followData->save();
        }

        return $rst;
    }

}
