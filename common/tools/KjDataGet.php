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
use backend\models\QxcKjData;
use backend\models\SscKjData;
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
    public static function grabOne(){
        $msg = ['status'=>200, 'msg'=>'操作成功~'];

        $m = \Yii::$app->cache;
        $KjConfigs = KjConfig::findAll(['enable'=>1]);
        foreach ($KjConfigs as $kjConfig){
            $lottery_type = $kjConfig->lottery_type;
            $url = $kjConfig->host.$kjConfig->path;
            $data = CurlService::httpGet($url);
            if($kjConfig->is_batch == 1){
                $kjData = isset($data['opencode']) ? $data['opencode'] : [];
                if($kjData){
                    $mkey = 'KJ_LOG_KEY_BATCH_1_'.$kjConfig->lottery_type;
                    if($kjConfig->lottery_type == 1){
                    }elseif($kjConfig->lottery_type == 2){
                        foreach ($kjData as $key=>$dataInfo){
                            $rst = KjDataGet::insertQxcKjData($dataInfo['qihao'], $dataInfo['codes'], $dataInfo['date']);
                        }
                    }
                }
                $cache_time = 10;
                $logArr = ['data'=>$data, 'rst'=>$rst];
            }else{
                $mkey = 'KJ_LOG_KEY_BATCH_0_'.$kjConfig->lottery_type;
                $kjData = isset($data['opencode']) ? $data['opencode'] : [];
                if($kjData){
                    if($kjConfig->lottery_type != 99){
                        $qihao = substr($data['expect'],2,6).substr($data['expect'],9,3);
                        # ssc
                        $msg = KjDataGet::insertKjData($qihao, $kjConfig->lottery_type, $kjData);
                        //if($kjConfig->lottery_type ==2) p([$qihao, $kjConfig->lottery_type, $kjData, $msg]);
                        $cache_time = 5;
                    }elseif($kjConfig->lottery_type == 99){
                        $qihao = $data['expect'];
                        $date = date('Y-m-d',$data['opentime']);
                        # qxc
                        //p($kjConfig);
                        $msg = KjDataGet::insertQxcKjData($qihao, $kjData, $date);
                        $cache_time = 30*60;
                    }
                }
                $logArr = ['data'=>$data, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'kjData'=>$kjData, /*'insertRst'=>$msg,*/ 'lottery'=>CqsscKcw::$lotteryNameArr[$kjConfig->lottery_type]];
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/insertSscKjData', 'INFO', '开奖号码记录', $logArr);
            }
            $mkey_qihao = 'KJ_LOG_QIHAO_'.$kjConfig->lottery_type.'_'.$qihao;
            //if(!$m->get($mkey) OR ($kjConfig->lottery_type == 1 && !$m->get($mkey_qihao))){
            if(!$m->get($mkey_qihao) ){
                $logArr['url'] = $url;
                $logArr['mkey'] = $mkey;
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/insertSscKjData', 'INFO', '开奖号码记录', $logArr);
                $m->set($mkey, 1, $cache_time);
                $m->set($mkey_qihao, 1, $cache_time);
            }
            /* 处理系统投注计划 add 2019-01-21 */
            KjDataGet::afterKj($kjConfig->lottery_type); # 处理系统投注计划，更新统计数据
            /* 处理系统投注计划 add 2019-01-21 */
        }

        return $msg;
    }

    /**
     * @desc 开奖后处理的数据
     */
    public static function afterKj($lottery_type = DEFAULT_LOTTERY_TYPE){
        OpKjService::opSscKjData($lottery_type); # 处理投注数据
        TzService::opSystemBetPlans($lottery_type); # 处理系统投注计划，更新统计数据、
        //StaticService::opStaticProfits(); # 投注利润统计
        //SscDataService::updateDsData(); // 更新单双
        //StaticService::static4dMonthsProfits(); # 每月四定单双利润统计，四定类型详见：StaticService::$typeArr
        //StaticService::static4dPerDateProfits(); # 每天四定利润统计，四定类型详见：StaticService::$typeArr
        //StaticService::static4DdsLastTime(); # 记录上次单双值, 主要针对当前四定组合排除最近一期的号码
        //StaticService::staticSDHzPerDateProfits(); # 每天四定和值利润统计
        //StaticService::staticHzMonthsProfits(); # 每月四定和值利润统计
    }

    /**
     * @desc ssc开奖data
     * @param $qihao 181120059
     * @param int $lottery_type 彩票类型1:1.5彩2:3分彩3:5分彩4:10分彩
     * @param $kjDatas
     * @return array|bool
     */
    public static function insertKjData($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE, $kjData){
        $kjDatas = str_replace(',', '', $kjData);
        if(!$qihao OR !$kjDatas) return false;
        $kjDatasArr = explode(',',$kjData);
        $codes_4nums = $kjDatasArr; unset($codes_4nums[4]);

        $codes = $kjDatasArr[0].','.$kjDatasArr[1].','.$kjDatasArr[2].','.$kjDatasArr[3];
        $tmpDate = '20'.substr($qihao,0,6).' '.'00:00:00';
        $codesArr = [$kjDatasArr[0],$kjDatasArr[1],$kjDatasArr[2],$kjDatasArr[3]];
        sort($codesArr);
        $code_3n = CommonService::get3n($codesArr);
        $insertData = [
            'kj_code' => $kjDatas,
            'qihao' => $qihao,
            'code_str' => $kjData,
            'codes_hz'=> array_sum($kjDatasArr),
            'codes_4nums_hz'=> array_sum($codes_4nums),
            'code1'=>$kjDatasArr[0],
            'code2'=>$kjDatasArr[1],
            'code3'=>$kjDatasArr[2],
            'code4'=>$kjDatasArr[3],
            'code5'=>$kjDatasArr[4],
            'code_1_2'=>$kjDatasArr[0]+$kjDatasArr[1],
            'code_1_3'=>$kjDatasArr[0]+$kjDatasArr[2],
            'code_1_4'=>$kjDatasArr[0]+$kjDatasArr[3],
            'code_2_3'=>$kjDatasArr[1]+$kjDatasArr[2],
            'code_2_4'=>$kjDatasArr[1]+$kjDatasArr[3],
            'code_3_4'=>$kjDatasArr[2]+$kjDatasArr[3],
            'code_3n' => implode(',', $code_3n),
            'code_4n' => implode('', $codesArr),
            'type_2' => CommonService::isCodeType2($codes), # 是否双重
            'type_22' => CommonService::isCodeType22($codes), # 是否双双重
            'type_3' => CommonService::isCodeType3($codes), # 是否三重
            'type_4' => CommonService::isCodeType4($codes), # 是否四重
            'type_2b' => CommonService::isCodeType2b($codes), # 是否两兄弟
            'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
            'type_4b' => CommonService::isCodeType4b($codes), # 是否四兄弟
            'type_4ds' => CommonService::isCodeType4ds($codes), # 是否四单双：0非四单四双1四单2四双
            'lottery_type' => $lottery_type,
            'date' => date('Y-m-d',strtotime($tmpDate)),
        ];

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
            $logArr = ['msg'=>$msg, 'qihao'=>$qihao, 'kjData'=>$kjData, 'lottery'=>CqsscKcw::$lotteryTypeArr[$lottery_type]];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/insertSscKjData', 'INFO', '开奖号码记录-错误', $logArr);
            return ['status' => 300, 'msg' => $msg];
        }

        # index_id
        $beforeQihao = KjDataGet::getBeforeQihaoByQihao($qihao);
        $beforeIndexId = SscKjData::findOne(['qihao'=>$beforeQihao, 'lottery_type'=>$lottery_type])->index_id;
        $index_id = $beforeIndexId + 1;
        $SscKjData = SscKjData::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
        $SscKjData->index_id = $index_id;
        $SscKjData->save();

        return ['status'=>200, 'msg'=>'开奖数据写入成功', 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'insertData'=>$insertData, 'insertRst'=>$insertRst, 'msg'=>$SscKjData->getFirstErrors()];
    }

    /**
     * @desc qxc开奖data
     * @param $qihao 2018139
     * @param $kjDatas 8,5,3,9,8,1,4
     */
    public static function insertQxcKjData($qihao, $kjData, $date = ''){
        $kjDatas = str_replace(',', '', $kjData);
        if(!$qihao OR !$kjDatas) return false;
        $kjDatasArr = explode(',',$kjData);

        $code4 = [$kjDatasArr[0],$kjDatasArr[1],$kjDatasArr[2],$kjDatasArr[3]];
        $insertData = [
            'kj_code' => implode(',',$code4),
            'qihao' => $qihao,
            'kj_7code' => $kjDatasArr[0].','.$kjDatasArr[1].','.$kjDatasArr[2].','.$kjDatasArr[3].','.$kjDatasArr[4].','.$kjDatasArr[5].','.$kjDatasArr[6],
            'hezhi'=> array_sum($code4),
            'code1'=>$kjDatasArr[0],
            'code2'=>$kjDatasArr[1],
            'code3'=>$kjDatasArr[2],
            'code4'=>$kjDatasArr[3],
            'code5'=>$kjDatasArr[4],
            'code6'=>$kjDatasArr[5],
            'code7'=>$kjDatasArr[6],
            'created_at'=>time(),
            'time'=>time(),
            'date' => $date ? $date : date('Y-m-d'),
        ];

        if(!$kjDatas) return false;
        if (!$QxcKjData = QxcKjData::findOne(['qihao' => $qihao])) {
            $QxcKjData = new QxcKjData();
        }
        $insertRst = $QxcKjData->setAttributes($insertData);
        if (!$QxcKjData->save()) {
            $msg = current($QxcKjData->getErrors());
            $logArr = ['msg'=>$msg, 'qihao'=>$qihao, 'kjData'=>$kjData];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/insertQxcKjData', 'INFO', '开奖号码记录', $logArr);
            return ['status' => 300, 'msg' => $msg];
        }
        return ['status'=>200, 'msg'=>'开奖数据写入成功', 'insertRst'=>$insertRst];
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
     * @param 批量抓取七星彩开奖数据
     * @param string $date_start
     * @return array|bool
     */
    public static function grabQxc($is_all = 1){
        $msg = ['status'=>200, 'msg'=>'操作成功~'];
        self::_init('qxc');
        # 时时彩
        $tmpInsertData = [];
        $fields = ['kj_code', 'kj_7code', 'qihao', 'date_time', 'time'];
        # 开奖数据 start
        $qihao = self::getNextQihao('qxc');
        $kjData = CommonService::getAwardNumberByQihao($qihao, 'qxc');
        $kj_7code = str_replace(',','',$kjData['kj_code']);
        $code = substr($kj_7code,0,4);
        if(!$code) return ['status'=>'404', 'msg'=>$qihao.'未查找到开奖数据'];
        # 开奖数据 end
        if($is_all){
            $tmpData = [
                $code, // 3358
                $kj_7code, // 3356889
                //$kjData['code'], // 3,3,5,6,8,8,9
                $kjData['qihao'], // 2018016
                $kjData['date_time'],   // 2018-02-09 20:31:40
                $kjData['time'] // 1518352300
            ];
            $tmpInsertData[] = $tmpData;
        }
        $end_qihao = self::getEndQihao('qxc');
        //p($tmpData);
        if ($is_all && (count($tmpInsertData) == 5 OR $qihao == $end_qihao)) {
            //p($tmpInsertData);
            foreach ($tmpInsertData as $key=>$insertData){
                if($insertData[0] == '') unset($tmpInsertData[$key]);
            }
            if (!$rst = \Yii::$app->db->createCommand()->batchInsert("{{%qxc_kj_data}}", $fields, $tmpInsertData)->execute()) {
                $msg['status'] = 300;
                $msg['msg'] = '数据处理异常';
            }
            $tmpInsertData = [];
            //$mcQihao = end($tmpInsertData)[2];
        }else{
            if (!$QxcKjData = QxcKjData::findOne(['qihao' => $kjData['qihao']])) {
                $QxcKjData = new QxcKjData();
            }
            $insertData = [
                'kj_code' => $code,
                'kj_7code' => $kj_7code,
                'qihao' => $kjData['qihao'],
                'date_time' => $kjData['date_time'],
                'time' => $kjData['time']

            ];
            $QxcKjData->setAttributes($insertData);
            if (!$QxcKjData->save()) {
                return ['status' => 300, 'msg' => current($QxcKjData->getErrors())];
            }
        }

        return $msg;
    }

    /**
     * @desc 获取表中记录的下一期
     * @param string $lottery_type
     * @return mixed
     */
    public static function getNextQihao($lottery_type = 'ssc'){
        if($lottery_type == 'qxc'){
            $kjData = QxcKjData::find()->where(['kj_code'=>null])->orderBy('id DESC')->asArray()->one();
            if($kjData){
                $nextQihao = $kjData['qihao'];
            }else{
                $kjData = QxcKjData::find()->orderBy('id DESC')->asArray()->one();
                if(!$kjData) $kjData['qihao'] = '2018015';
                $year = substr($kjData['qihao'],0,4);
                $qihao = substr($kjData['qihao'],4,3);
                if($qihao >= 154){
                    $nextQihao = ($year+1).'001';
                }else{
                    $nextQihao = $year.$qihao + 1;
                }
            }
        }else{
            $kjData = SscKjData::find()->where(['kj_code'=>null])->orderBy('id DESC')->asArray()->one();
            if($kjData){
                $nextQihao = $kjData['qihao'];
            }else{
                $kjData = SscKjData::find()->orderBy('id DESC')->asArray()->one();
                if(!$kjData) $kjData['qihao'] = '180101001';
                $nextQihao = KjDataGet::getNextQihaoByQihao($kjData['qihao']);
            }
        }

        return $nextQihao;
    }


    /**
     * @desc 获取给定期号的下一期
     * @param string $qihao
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @return bool|int|string
     */
    public static function getNextQihaoByQihao($qihao = '180101001', $lottery_type = DEFAULT_LOTTERY_TYPE){
        if($lottery_type == 'qxc'){
            # 未完
        }else{
            $nextQihao = $qihao + 1;
            $year = '20'.substr($qihao,0,2);
            $date = '20'.substr($qihao,0,6);
            $qihao = substr($qihao,6,3);
            $maxQihaoArr = BetService::$maxQihaoArr;
            $maxQihao = $maxQihaoArr[$lottery_type];
            if($date == $year.'1231' && $qihao >=$maxQihao){
                $nextQihao = ($year+1).'0101001';
            //}elseif($qihao >= 120){
            }elseif($qihao >= $maxQihao){
                $nextQihao = ltrim(Tools::getNextDate($date),'20').'001';
            }
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
    public static function updateNullCode( $times = 500){
        $msg = ['status'=>200, 'msg'=>'操作成功！'];
        $kjDatas = SscKjData::find()->where(['type_2'=>null])->orderBy('id DESC')->asArray()->limit($times)->all();
        foreach ($kjDatas as $kjData){
            $sumArr = explode(',',$kjData['code_str']);
            $codesArr = [$sumArr[0],$sumArr[1],$sumArr[2],$sumArr[3]];
            sort($codesArr);
            $codes = $sumArr[0].','.$sumArr[1].','.$sumArr[2].','.$sumArr[3];
            $code_3n = CommonService::get3n($codesArr);
            $updateData = [
                /*
                'code_3n' => implode(',', $code_3n),
                'code_4n' => implode('', $codesArr),
                'code1'=>$kjData['kj_code'][0],
                'code2'=>$kjData['kj_code'][1],
                'code3'=>$kjData['kj_code'][2],
                'code4'=>$kjData['kj_code'][3],
                'code5'=>$kjData['kj_code'][4],
                'codes_4nums_hz'=> array_sum($sumArr)
                */
                'type_2' => CommonService::isCodeType2($codes), # 是否双重
                'type_22' => CommonService::isCodeType22($codes), # 是否双双重
                'type_3' => CommonService::isCodeType3($codes), # 是否三重
                'type_4' => CommonService::isCodeType4($codes), # 是否四重
                'type_2b' => CommonService::isCodeType2b($codes), # 是否两兄弟
                'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
                'type_4b' => CommonService::isCodeType4b($codes), # 是否四兄弟
                'type_4ds' => CommonService::isCodeType4ds($codes), # 是否四单双：0非四单四双1四单2四双
                /*
                */
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
