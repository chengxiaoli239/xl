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
use backend\service\SscDataService;
use backend\service\TzService;
use common\kj\cqssc\CqsscKcw;
use common\service\CommonService;
use common\service\ssc\QihaoService;
use backend\service\CurlService;
use backend\service\HN0898Service;
use backend\service\BaseNumService;
use backend\service\OpKjService;
use common\tools\Tools;
use backend\models\BettingRecords;
use backend\service\StaticService;
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
     * @return array
     */
    public static function grabKjDatas($lottery_types = []){
        $msg = ['status'=>200, 'msg'=>'操作成功~'];

        if(empty($lottery_types)) $lottery_types = StaticService::getLotteryTypes();
        foreach ($lottery_types as $lottery_type){
            KjDataGet::grabOneLotteryKjData($lottery_type);
        }

        return $msg;
    }

    /**
     * 单个彩种号码抓取
     * @param int $lottery_type
     */
    public static function grabOneLotteryKjData($lottery_type=DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $KjConfigs = KjConfig::findAll(['enable'=>1, 'lottery_type'=>$lottery_type]);
        foreach ($KjConfigs as $kjConfig){
            $status = KjDataGet::isCanGrab($lottery_type);
            if(!$status && !$kjConfig->is_batch) continue;
            $lottery_type = $kjConfig->lottery_type;
            //if($lottery_type != 8) continue; # 测试
            $url = $kjConfig->host.$kjConfig->path;
            if(!$data = CurlService::httpGet($url)) continue;
            if(isset($data['status']) && $data['status'] != 200) continue;
            if($kjConfig->is_batch == 1){
                $kjDatas = $data;
                Tool_Common::log('/kj_datas/'.__FUNCTION__, 'INFO', '批量抓取开奖号码', ['data'=>$data]);
                if($kjDatas){
                    # xjssc  1七星彩17排列五
                    $mkey = 'KJ_LOG_KEY_BATCH_1_'.$kjConfig->lottery_type;
                    Tool_Common::log('/kj_datas/'.__FUNCTION__, 'INFO', '开奖数据', ['url'=>$url, 'kjdatas'=>$kjDatas]);
                    $kjDatas = array_reverse($kjDatas); # 翻转
                    foreach ($kjDatas as $key=>$dataInfo){
                        $qihao = $dataInfo['expect'];
                        $rst = KjDataGet::insertKjData($qihao, $kjConfig->lottery_type, $dataInfo['opencode'], $dataInfo['opentime']);
                    }
                }
                $cache_time = 10;
                $logArr = ['data'=>$data, 'rst'=>$rst];
            }else{
                $mkey = 'KJ_LOG_KEY_BATCH_0_'.$kjConfig->lottery_type;
                $kjData = (isset($data['opencode']) && !empty($data['opencode'])) ? $data['opencode'] : [];
                if($kjData){
                    if($kjConfig->lottery_type == 5){
                        $qihao = substr($data['expect'],2,6).substr($data['expect'],9,3);
                    }else{
                        $qihao = $data['expect'];
                    }
                    # ssc
                    $msg = KjDataGet::insertKjData($qihao, $kjConfig->lottery_type, $kjData, $data['opentime']);
                    $cache_time = 10;
                }
                $lotteryNameArr = CqsscKcw::getLotteryNameArr();
                $logArr = ['data'=>$data, 'lottery_type'=>$lottery_type, /*'qihao'=>$qihao, 'kjData'=>$kjData, 'insertRst'=>$msg,*/ 'lottery'=>$lotteryNameArr[$kjConfig->lottery_type]];
                Tool_Common::log('insertSscKjData', 'INFO', '开奖记录', $logArr);
            }
            $mkey_qihao = 'KJ_LOG_QIHAO_'.$kjConfig->lottery_type.'_'.$qihao;
            if(!$m->get($mkey_qihao) ){
                $logArr = array_merge($logArr, [
                    'url' => $url,
                    'mkey_qihao' => $mkey_qihao,
                    'qihao' => $qihao,
                    'mkey' => $mkey,
                    'lottery_type' => $lottery_type,
                ]);
                Tool_Common::log('insertSscKjData-c', 'INFO', '开奖记录', $logArr);
                $m->set($mkey, 1, $cache_time);
                $m->set($mkey_qihao, 1, $cache_time);
            }
        }
        /* 处理系统投注计划 add 2019-01-21 */
        KjDataGet::afterKj($lottery_type); # 处理系统投注计划，更新统计数据
        /* 处理系统投注计划 add 2019-01-21 */

    }

    /**
     * @desc 判断时间段是否可以抓取开奖号码  主要针对半夜不开奖时间段
     * @param int $lottery_type
     * @return bool
     */
    public static function isCanGrab($lottery_type = DEFAULT_LOTTERY_TYPE) {
        $flag = true;
        $date_time = date('H:i');
        if (in_array($lottery_type, [5, 6])){
            if ('04:00' < $date_time && $date_time < '07:10') {
                $flag = false;
            }
        }elseif($lottery_type == 8){ # 幸运五星
            # 用户报表需求 24小时抓取开奖数据
            if ('04:10' < $date_time && $date_time < '09:00') {
                $flag = false; # 全天开奖，这里先去掉
            }
        }elseif(in_array($lottery_type, [10, 11, 12, 13])){ # 冰岛90s、3分
            if ('03:10' < $date_time && $date_time < '08:55') {
                $flag = false;
            }
        }elseif(in_array($lottery_type, [17])){ # 排列五
            if('20:15'>$date_time OR $date_time>'23:00'){
                $flag = false;
            }
        }elseif(in_array($lottery_type, [1])){ # 七星
            $w = date('w'); # 周几：0,1,2,3,4,5,6  ==> 周日到周六
            if(!in_array($w, [0, 2, 5])){
                $flag = false;
            }
            if('20:00'>$date_time OR $date_time>'23:00'){
                $flag = false;
            }
        }elseif(in_array($lottery_type, [18])){ # 台湾快五
            if ('02:10' < $date_time && $date_time < '07:00') {
                $flag = false;
            }
        }

        return $flag;
    }

    /**
     * @desc 开奖后处理的数据
     */
    public static function afterKj($lottery_type = DEFAULT_LOTTERY_TYPE){

        SscDataService::insertDealDataTask($lottery_type); # 数据处理任务写入

        $rst['OpKjService'] = OpKjService::opSscKjData($lottery_type); # 处理投注数据
        #$rst['TzService'] = TzService::opSystemBetPlans($lottery_type); # 处理系统投注计划，更新统计数据、
        $lottery_name = \common\service\CommonService::getLotteryName($lottery_type);
        push_queue(\backend\service\jobs\kj_data\OperateBetPlans::class, ['lottery_type'=>$lottery_type, 'lottery_name'=>$lottery_name]);
        //StaticService::opStaticProfits(); # 投注利润统计
        //SscDataService::updateDsData(); // 更新单双
        //StaticService::static4dMonthsProfits(); # 每月四定单双利润统计，四定类型详见：StaticService::$typeArr
        //StaticService::static4dPerDateProfits(); # 每天四定利润统计，四定类型详见：StaticService::$typeArr
        //StaticService::static4DdsLastTime(); # 记录上次单双值, 主要针对当前四定组合排除最近一期的号码
        //StaticService::staticSDHzPerDateProfits(); # 每天四定和值利润统计
        //StaticService::staticHzMonthsProfits(); # 每月四定和值利润统计
        return $rst;
    }

    /**
     * @desc ssc开奖data
     * @param $qihao 181120059
     * @param int $lottery_type 彩票类型1:1.5彩2:3分彩3:5分彩4:10分彩
     * @param $kjDatas
     * @param string $opentime 2022-04-03 21:00:00
     * @return array|bool
     */
    public static function insertKjData($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE, $kjData='', $opentime = ''){
        $kjDatas = str_replace(',', '', $kjData);
        if(!$qihao OR !$kjDatas) return false;
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
            $tmpDate = date('Y-m-d H:i:s');
        }
        $codesArr = [$kjDatasArr[0],$kjDatasArr[1],$kjDatasArr[2],$kjDatasArr[3]];
        sort($codesArr);
        $code_3n = CommonService::get3n($codesArr);
        $insertData = [
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
            'code6'=>isset($kjDatasArr[5]) ? $kjDatasArr[5] : NULL,
            'code7'=>isset($kjDatasArr[6]) ? $kjDatasArr[6] : NULL,
            'code_1_2'=>$kjDatasArr[0]+$kjDatasArr[1],
            'code_1_3'=>$kjDatasArr[0]+$kjDatasArr[2],
            'code_1_4'=>$kjDatasArr[0]+$kjDatasArr[3],
            'code_2_3'=>$kjDatasArr[1]+$kjDatasArr[2],
            'code_2_4'=>$kjDatasArr[1]+$kjDatasArr[3],
            'code_3_4'=>$kjDatasArr[2]+$kjDatasArr[3],
            'code_1_2_3_4' => SscDataService::getCodesDS($kjData),
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
            'lottery_type' => $lottery_type,
            'date' => date('Y-m-d',strtotime($tmpDate)),
        ];
        if(!empty($opentime)){
            $insertData['created_at'] = (int)strtotime($opentime);
        }

        SscDataService::getAriseCodes([implode('', $codesArr)]); # 缓存开奖号码四定组合

        if(!$kjDatas) return false;
        if (!$SscKjData = SscKjData::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type])) {
            $SscKjData = new SscKjData();
            $lastIndexId = SscDataService::getKjDataLastIndexId($lottery_type);
            $index_id = $lastIndexId + 1;
            $insertData = array_merge($insertData, ['index_id'=>$index_id]);
        }

        $SscKjData->setAttributes($insertData);
        if (!$insertRst = $SscKjData->save()) {
            $msg = current($SscKjData->getErrors());
            $lotteryNameArr = CqsscKcw::getLotteryNameArr();
            $logArr = ['msg'=>$msg, 'qihao'=>$qihao, 'kjData'=>$kjData, 'lottery'=>$lotteryNameArr[$lottery_type]];
            Tool_Common::log('insertSscKjData_err', 'INFO', '开奖记录-错误', $logArr);
            return ['status' => 300, 'msg' => $msg];
        }

        return ['status'=>200, 'msg'=>'开奖数据写入成功', 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'insertData'=>$insertData, 'insertRst'=>$insertRst, 'id'=>$SscKjData->id];
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
     * @desc 获取给定期号的上一期
     * @param string $qihao
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @return bool|int|string
     */
    public static function getBeforeQihaoByQihao($qihao = '180101001', $lottery_type = DEFAULT_LOTTERY_TYPE){
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
                $beforeQihao = $beforeQihao;
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
            $KjData = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy('id DESC')->limit(1)->asArray()->one();
            if(!$KjData) $KjData['qihao'] = '20180101000';
            $endQihao = $KjData['qihao'];
        }

        return $endQihao;
    }

    /**
     * @desc 自动更新 万千百十个数据
     */
    public static function updateNullCode( $times = 5000, $lottery_type = DEFAULT_LOTTERY_TYPE){
        /*
        $Num4Types = Num4Type::find()->where(['AND', ['IS', 'type_ds', NULL], '1=1'])->orderBy('id DESC')->limit($times)->all();
        foreach ($Num4Types as $k=>$num4Type){
            $code1_ds = $num4Type->code_1=='X' ? 'X' : ($num4Type->code_1===NULL ? '': ($num4Type->code_1%2==0? '2':'1'));
            $code2_ds = $num4Type->code_2=='X' ? 'X' : ($num4Type->code_2===NULL ? '': ($num4Type->code_2%2==0? '2':'1'));
            $code3_ds = $num4Type->code_3=='X' ? 'X' : ($num4Type->code_3===NULL ? '': ($num4Type->code_3%2==0? '2':'1'));
            $code4_ds = $num4Type->code_4=='X' ? 'X' : ($num4Type->code_4===NULL ? '': ($num4Type->code_4%2==0? '2':'1'));
            $code5_ds = $num4Type->code_5=='X' ? 'X' : ($num4Type->code_5===NULL ? '': ($num4Type->code_5%2==0? '2':'1'));
            $num4Type->type_ds = $code1_ds.$code2_ds.$code3_ds.$code4_ds.$code5_ds;
            $rst[$k] = $num4Type->save();
        }
        */

        $msg = ['status'=>200, 'msg'=>'操作成功！'];
        $kjDatas = SscKjData::find()->where(['OR', ['IS', 'type_22', NULL], ['IS', 'code_4n_str', NULL]])->orderBy('id DESC')->limit($times)->all();
        foreach ($kjDatas as $key=>$kjData){
            //$kjDs = SscDataService::getCodesDS($kjData['code_str']);
            $codes = $kjData['code1'].','.$kjData['code2'].','.$kjData['code3'].','.$kjData['code4'];
            $updateData = [
                'code_4n_str' => $codes, # 四字定str
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
                */
            ];
            //$kjData->type_22b = CommonService::isCodeType22b($codes);
            $kjData->setAttributes($updateData);
            if(!$rst = $kjData->save()){
                $msg = current($kjData->getErrors());
                Tool_Common::log('updateNullCode', 'ERR', '更新空值', ['msg'=>$msg]);
                $msg = ['status'=>300, 'msg'=>$msg];
            }
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
