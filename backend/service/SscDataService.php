<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\BettingRecords;
use backend\models\DataDealStatus;
use backend\models\ImportPlanCodes;
use backend\models\LotteryDataDealStatus;
use backend\models\Num4Type;
use backend\models\PlanStaticProfits;
use backend\models\searchs\SscDwsHzNums;
use backend\models\Ssc3numYl;
use backend\models\SscDsTypeDatas;
use backend\models\SscDsYl;
use backend\models\SscDwHzStatic;
use backend\models\SscKjData;
use backend\models\SscKjData3num;
use backend\models\SscKjDataDs;
use backend\models\SscSdHzVal;
use backend\models\SscSdHzYl;
use backend\models\SscStaticVal;
use backend\models\SscStaticYl;
use backend\models\StaticPeiShuCodeDateProfits;
use backend\models\StaticPeiShuCodeTrueFalse;
use backend\models\StaticProfits;
use backend\models\SystemConfig;
use backend\models\ThreeNum;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\numbers\NumCodeService;
use backend\service\statics\plan\OperatePlanService;
use backend\service\statics\statics_base\BaseDataService;
use backend\service\statics\statics_base\DealDataService;
use backend\service\statics\statics_qx\StaticsQxMissService;
use common\service\cache\CacheKeyService;
use common\service\CommonService;
use common\service\jobs\plan\UserPlanInitJob;
use common\service\lottery\CommonLotteryService;
use common\service\lottery\LotteryTypeService;
use common\service\ssc\QihaoService;
use common\service\thirdD\CommonBaseService;
use common\tools\KjDataGet;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use backend\models\SscDwHzYl;
use  yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseStringHelper;
use yii\helpers\Json;

class SscDataService extends BaseService {
    public static $fb_plan_types = [2, 3, 4, 5, 9, 10, 16]; # 翻倍计划类型
    public static $zzt_plan_types = [6, 8]; # 中则投计划类型
    public static $zzt_else_fanmai_types = [7]; # 中则投否则反卖
    const TEST_NO = 0;
    const TEST_YES = 1;
    const TEST_TYPE_OPTIONS = [
        self::TEST_NO => '真实',
        self::TEST_YES => '模拟',
    ];

    public static array $dealDataStatusFields = [
        'status' => '全局状态',
        'static4dPerDateProfits_status' => 'A每天四定利润统计',
        'updateDs_status' => 'B单双处理状态',
        'updateDsYL_status' => 'C单双遗漏处理状态',
        'update3NumYL_status' => 'D开奖三字现处理状态',
        'updateSdHzYL_status' => 'E和值遗漏状态',
        'opProfitsPlans_status' => 'F投注计划处理状态',
    ];

    const DEAL_DATA_STATUS_PENDDING = 0; # 待处理
    const DEAL_DATA_STATUS_SUCCESS = 2; # 处理成功
    const DEAL_DATA_STATUS_FAIL = 3; # 处理失败
    const DEAL_DATA_STATUS_NOT_NEED_DEAL = 4; # 无需处理

    const PLAN_TYPE_NORMAL = 0; # 正常
    const PLAN_TYPE_SINGLES_BET = 2; # 倍投
    const PLAN_TYPE_SINGLES_BET_WIN = 6; #  中则投、中则投+翻倍梯度倍投
    const PLAN_TYPE_YL_BET = 8; # 遗漏投
    const PLAN_TYPE_YL_BET_SINGLES = 9; # 遗漏倍投
    const PLAN_TYPE_AREA_SINGLES_BET = 14; # 区间遗漏投
    const PLAN_TYPE_SINGLES_BET_2 = 15; # 中则倍投
    const PLAN_TYPE_BT_SINGLES_BET = 10; # 中则波推倍投，类似于中则投+翻倍梯度倍投
    const PLAN_TYPE_YL_BET_SINGLES_2 = 16; # 遗漏倍投2
    const PLAN_TYPE_YL_ZZ_SINGLES_BET = 17; # 遗漏中则倍投
    const PLAN_TYPE_YL_BET_SINGLES_NUM = 18; # 遗漏x期投y期
    const PLAN_TYPE_YL_START_BET_SINGLES = 19; # 遗漏x期起投
    const PLAN_TYPE_ZZ_BET_SINGLES_2 = 20; # 中则倍投2
    const PLAN_TYPE_LOSS_MONEY_BET_SINGLES = 21; # 区间亏损起投
    const PLAN_TYPE_OPTIONS = [
        # 计划类型:0正常1止盈止损计划
        self::PLAN_TYPE_NORMAL => '正常',
        //1=>'止盈止损',
        self::PLAN_TYPE_SINGLES_BET => '倍投',
        //3=>'倍投&止盈止损',
        //4=>'倍投&号码切换',
        //5=>'倍投&号码切换止盈止损',
        self::PLAN_TYPE_SINGLES_BET_WIN => '中则投',
        7=>'中则投否则反买',
        8 => '遗漏投',  # 遗漏x期数则开始投，投中了后就再等遗漏x期再继续投
        self::PLAN_TYPE_YL_BET_SINGLES => '遗漏倍投', # 遗漏x期数则开始倍投，投中了后就回到第一个倍数再等遗漏x期再继续倍投
        self::PLAN_TYPE_BT_SINGLES_BET => '中则波推倍投',
        //11=>'中则交叉正反',
        12=>'A出x次B出y次投B',
        13=>'A出x次B出y次投B_2',
        self::PLAN_TYPE_AREA_SINGLES_BET => '区间遗漏投',
        self::PLAN_TYPE_SINGLES_BET_2 => '中则倍投',
        self::PLAN_TYPE_YL_BET_SINGLES_2 => '遗漏倍投2',
        self::PLAN_TYPE_YL_ZZ_SINGLES_BET => '遗漏中则倍投',
        self::PLAN_TYPE_YL_BET_SINGLES_NUM => '遗漏x期投y期',
        self::PLAN_TYPE_YL_START_BET_SINGLES => '遗漏x期起投',
        self::PLAN_TYPE_ZZ_BET_SINGLES_2 => '中则倍投2',
        self::PLAN_TYPE_LOSS_MONEY_BET_SINGLES => '区间亏损起投',
    ];

    const PLAN_BET_STATUS_INIT = 0; # 初始状态
    const PLAN_BET_STATUS_BETTING = 1; # 正在下注状态
    const PLAN_BET_STATUS_WAIT = 2; # 等待状态

    /**
     * @desc 定位和值统计
     * @return mixed
     */
    public static function heZhiStatics(){
        $intervals = [ 20,50,100,120,200,300,500,1000,2000,5000 ];
        foreach ($intervals as $key => $interval) {
            $rst[$interval] = SscDataService::heZhiStatic($interval);
        }

        return $rst;
    }

    /**
     * @description 单双遗漏统计
     * @param int $interval
     * @return mixed
     */
    public static function dsYLStatic($interval = 20){
        $rst = [];

        $max_qihao = SscKjDataDs::find()->select('max(qihao) as max_qihao')->limit(1)->orderBy('id DESC')->asArray()->limit($interval)->one()['max_qihao'];
        # 1、二定
        $zuHes = [ [1,2], [2,3], [3,4], [1,4] ];
        foreach ($zuHes as $key=>$zuHe){
            $field = 'code_'.implode('_',$zuHe);
            $last = SscKjDataDs::find()->select(['max(id) as last_id'])->limit(1)->asArray()->one();
            $start_id = $last['last_id'] - $interval;
            $data = SscKjDataDs::find()->select($field.',COUNT(id) AS num')->where('id>'.$start_id)->groupBy($field)->orderBy('id DESC')->limit($interval)->asArray()->all();
            $updateData = ['positions'=>implode(',',$zuHe),'max_qihao'=>$max_qihao];
            $zhi = [11,12,21,22]; // 单单、单双、双单、双双
            foreach ($data as $k=>$v){
                $tmpZhi = $v[$field];
                $updateData['hz_'.$tmpZhi] = $v['num'];
                unset($zhi[$tmpZhi]);
            }
            # 没出现的码补0
            foreach ($zhi as $key1=>$v1){
                $updateData['hz_'.$v1] = 0;
            }
            $SscDwHzStatic = SscStaticYl::findOne(['positions'=>implode(',',$zuHe),'periods'=>$interval]);
            $SscDwHzStatic->setAttributes($updateData);
            $rst = $SscDwHzStatic->save();
        }

        return $rst;
    }

    /**
     * @desc 更新单双
     * @param $lottery_type integer 彩种类型：1:1.5分 2:3分 3:5分 4:10分 5重庆 6新疆 7北京快乐8
     * @return bool
     */
    public static function updateDsData(int $lottery_type = DEFAULT_LOTTERY_TYPE)
    {
        list($lastQiHao, $lastIndexId, $lastId, $nextQihao) = SscDataService::getKjDataLastIndexId($lottery_type);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '更新单双状态', ['lottery_type'=>$lottery_type, 'lastQiHao'=>$lastQiHao, 'nextQihao'=>$nextQihao]);
        $flag = SscDataService::insertSscKjDataDs($lastQiHao, $lottery_type);

        return (boolean)$flag;
    }

    /**
     * @desc 更新单双
     * @param $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return bool
     */
    public static function update3NumData($lottery_type = DEFAULT_LOTTERY_TYPE){
        $mkey = 'CODE_COUNT_3NUMS_'.$lottery_type.'_04';
        $m = \Yii::$app->cache;

        $next_qihao = QihaoService::getNextStaticDsQiHao($lottery_type);
        $last_qihao = SscDataService::getKjDataLastQihao($lottery_type);

        //p([$next_qihao, $last_qihao, $qihao, $lottery_type],0);

        if($next_qihao<=$last_qihao){
            $new_qihao = SscKjData::find()->where(['qihao'=>$next_qihao, 'lottery_type'=>$lottery_type])->one()->qihao;
            if(!$new_qihao){ # 防止官网某一期不开的情况, 自动获取开奖表下一期的开奖号码
                $new_qihao = SscKjData::find()->where(['AND', ['>', 'qihao', $next_qihao], ['=', 'lottery_type', $lottery_type]])->orderBy('id ASC')->limit(1)->one()->qihao;
            }
            $flag = BaseDataService::insertSscKjData3Num($new_qihao, $lottery_type);
            //p($flag);
            $m->set($mkey, $new_qihao, 7*24*3600);
        }

        return $flag;
    }


    /**
     * @desc 统计数据
     * @return bool
     */
    public static function sscDwsHzNums($lottery_type = DEFAULT_LOTTERY_TYPE){
        $intervals = [ 200,500,1000,2000,5000];
        $m = \Yii::$app->cache;
        $mkey = 'DWS_HZ_COUNT_NUMS_1';
        if(!$id = $m->get($mkey)){
            $id = 47920; // 2019-02-03
        }
        $id = $id + 1;
        foreach ($intervals as $key => $interval) {
            $last_id = SscDataService::getKjDataLastId($lottery_type);

            if($id<=$last_id){
                $new_qihao = SscKjData::findOne($id)->qihao;
                $logArr = ['id'=>$id, [$interval, $new_qihao, $id]];
                $flag = BaseDataService::insertSscDwsHzNums($lottery_type, $interval, $new_qihao, $id);
                $logArr['flag'] = $flag;
                Tool_Common::log('SscDwsHzNums','INFO','统计区间某和值出现次数', $logArr);
                $m->set($mkey, $id, 60*60);
            }
        }

        return $flag;
    }

    /**
     * @desc 更新和值汇总
     * @param int $interval
     * @return bool
     */
    public static function heZhiStatic($interval = 120){
        $zuHes = [ [1,2], [1,3], [1,4], [2,3], [2,4], [3,4] ];
        $max_qihao = SscKjData::find()->select('max(qihao) as max_qihao')->limit(1)->orderBy('id DESC')->asArray()->limit($interval)->one()['max_qihao'];
        foreach ($zuHes as $key=>$zuHe){
            $field = 'code_'.implode('_',$zuHe);
            $last = SscKjData::find()->select(['max(id) as last_id'])->asArray()->one();
            $start_id = $last['last_id'] - $interval;
            $data = SscKjData::find()->select($field.',COUNT(id) AS num')->where('id>'.$start_id)->groupBy($field)->orderBy('id DESC')->limit($interval)->asArray()->all();
            $updateData = ['positions'=>implode(',',$zuHe),'max_qihao'=>$max_qihao];
            $zhi = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18];
            foreach ($data as $k=>$v){
                $tmpZhi = $v[$field];
                $updateData['hz_'.$tmpZhi] = $v['num'];
                unset($zhi[$tmpZhi]);
            }
            # 没出现的码补0
            foreach ($zhi as $key1=>$v1){
                $updateData['hz_'.$v1] = 0;
            }
            $SscDwHzStatic = SscDwHzStatic::findOne(['positions'=>implode(',',$zuHe),'periods'=>$interval]);
            $SscDwHzStatic->setAttributes($updateData);
            $rst = $SscDwHzStatic->save();
        }

        return $rst;
    }

    /**
     * @description 给定期数获取起始id
     * @param int $interval
     */
    public static function getKjDataStartId($lottery_type = DEFAULT_LOTTERY_TYPE, $interval = 200, $qihao = ''){
        if($qihao){
            $last_id = SscKjData::find()->select(['id'])->where(['qihao'=>$qihao, 'lottery_type'=>$lottery_type])->asArray()->one()['id'];
        }else{
            $last_id = SscKjData::find()->select(['last_id'=>'index_id', 'lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one()['last_id'];
        }
        $start_id = $last_id - $interval;

        return $start_id;
    }

    /**
     * @description 最后id
     * @param int $interval
     */
    public static function getKjDataLastId($lottery_type = DEFAULT_LOTTERY_TYPE){
        //$last_id = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['max(index_id) as last_id'])->limit(1)->asArray()->one()['last_id']
        list($lastQiHao, $lastIndexId, $lastId, $nextQiHao) = SscDataService::getKjDataLastIndexId($lottery_type);


        return $lastIndexId;
    }

    /**
     * @description 最后顺序id
     * @param int $lottery_type
     * @return array
     */
    public static function getKjDataLastIndexId($lottery_type = DEFAULT_LOTTERY_TYPE): array
    {
        $mKey = CacheKeyService::lotteryLastIndexKey($lottery_type);
        $f = true;
        if(!$last = commonRedis()->get($mKey)){
            $f = false;
            $lastQuery = SscKjData::find()->select(['qihao', 'index_id', 'id'])->where(['lottery_type'=>$lottery_type]);
            //$sql = $lastQuery->createCommand()->getRawSql();p($sql);
            $last = $lastQuery->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            commonRedis()->setex($mKey, 5, $last);
        }
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '更新单双状态', ['lottery_type'=>$lottery_type, 'f'=>$f]);
        if(empty($last)){
            $lastIndexId = 0;
        }else{
            $lastQihao = (string)$last['qihao'];
            $lastIndexId = (int)$last['index_id'];
            $lastId = (int)$last['id'];
            $nextQihao = KjDataGet::getNextQihaoByQihao($lastQihao, $lottery_type);
        }

        return [$lastQihao, $lastIndexId, $lastId, $nextQihao];
    }

    /**
     * @description 给定期数获取起始id
     * @param $lottery_type
     * @param int $interval
     */
    public static function getKjDataLastQihao($lottery_type = DEFAULT_LOTTERY_TYPE){
        //$last_qihao = SscKjData::find()->select(['max(qihao) as last_qihao'])->where(['lottery_type'=>$lottery_type])->asArray()->one()['last_qihao'];
        //$last_qihao = SscKjData::find()->select(['qihao as last_qihao'])->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->asArray()->one()['last_qihao'];
        list($lastQiHao, $lastIndexId, $lastId, $nextQiHao) = SscDataService::getKjDataLastIndexId($lottery_type);

        return $lastQiHao;
    }

    /**
     * @desc 获取图表数据
     * @param $periods
     * @param $postion
     * @return array
     */
    public static function getDwHzChartsData($periods = '200', $positions = '2,3'){
        $data = [];
        $data['xAxis'] = [0 , 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
        //$data['xAxis'] = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14 ];
        $data['positions'] = [$positions];
        $where = ['periods'=>$periods, 'positions'=>$positions];
        $dataStatic = SscDwHzStatic::findOne($where);
        //p($dataStatic,0);
        $series = [
            'name' => $positions.'出现次数',
            'type' => 'bar',
            'stack' => '出现次数',
        ];
        $showArr = [6, 7, 8, 9, 10, 11, 12];
        foreach ($data['xAxis'] as $k=>$v){
            if(in_array($v, $showArr)){
                $tmpData[] = $dataStatic['hz_'.$v];
            }else{
                $tmpData[] = 0;
            }
        }
        $series['data'] = $tmpData;
        $data['series'] = $series;

        return $data;
    }

    /**
     * @description 和值区间统计
     * @param int $hezhi
     * @param $periodsArr
     * @param $positions
     * @param int $interval
     * @return mixed
     */
    public static function getHzNumsChartsData($hezhi=9, $periodsArr, $positions, $interval = 1000){
        $data['xAxis'] = [ 'data'=>[] ];    // 期号
        $series = [];
        $times = [6=>0.07, 7=>0.08, 8=>0.09, 9=>0.10, 10=>0.09, 11=>0.08, 12=>0.07];

        foreach ($periodsArr as $periods){
            $where = [ 'positions'=>$positions, 'hezhi'=>$hezhi, 'periods'=>$periods];
            $fields = ['hezhi','positions', 'periods', 'qihao', 'nums'];
            $SscDwsHzNums = SscDwsHzNums::find()->select($fields)->where($where)->limit($interval)->orderBy('qihao DESC')->all();
            $tmpData = [];
            $tmpData['name'] = $periods.'期出现次数';
            $tmpData['type'] = 'line';
            $tmpData['stack'] = '次数';
            $numsData = [];
            $SscDwsHzNums = array_reverse($SscDwsHzNums);
            foreach ($SscDwsHzNums as $SscDwsHzNum){
                !in_array($SscDwsHzNum->qihao, $data['xAxis']['data']) && $data['xAxis']['data'][] = $SscDwsHzNum->qihao;
                $numsData[] = $SscDwsHzNum->nums - ceil($periods * $times[$hezhi]);
            }
            $tmpData['data'] = $numsData;
            $series[] = $tmpData;
            $data['series'] = $series;
            unset($tmpData);
        }
        //p($data);
        //$data['xAxis']['data'] = array_reverse($data['xAxis']['data']);
        return $data;
    }


    /**
     * @description  更新和值遗漏
     * @return array
     */
    public static function updateHeZhiYL($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];
        $zuHes = [ [1,2], [1,3], [1,4], [2,3], [2,4], [3,4] ];
        $numsArr = [6,7,8,9,10,11,12];//[8,9,10,11,12,13];  // 和值
        $newRecord = SscKjData::find()->select(['qihao','code_str'])->orderBy('id DESC')->asArray()->limit(1)->one();
        foreach ($zuHes as $key => $zuHe) {
            $YL_data = BaseNumService::getDwHeZhiCurrentYL($zuHe, $numsArr, 120)['data']; // 和值为8、9在200期里边遗漏期数
            foreach ($numsArr as $num){
                $position = implode(',',$zuHe);
                $SscDwHzYl = SscDwHzYl::findOne(['positions'=>$position,'zhi'=>$num]);
                $SscDwHzYl->zhi = $num;
                //$SscDwHzYl->qihao = $newRecord['qihao'].'：'.$newRecord['code_str'];
                $miss = StaticsQxMissService::getDwHistoryMiss($num, $position, $lottery_type); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
                //$SscDwHzYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
                $SscDwHzYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
                $SscDwHzYl->last_time_miss = $miss['times']; // 2、上次遗漏
                $SscDwHzYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
                $SscDwHzYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
                $SscDwHzYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
                $SscDwHzYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
                $SscDwHzYl->history_max_miss = max($miss['current_times'],$SscDwHzYl->max_miss,$SscDwHzYl->history_max_miss); // 6、历史最大遗漏
                //p($updateData);
                //if($YL_data[$num] > $SscDwHzYl->max_miss && $YL_data[$num] > $SscDwHzYl->history_max_miss){
                //}
                $rst = $SscDwHzYl->save();
                if(!$rst){
                    $logArr = ['attributes'=>$SscDwHzYl->attributes, 'msg'=>$SscDwHzYl->getErrors()];
                    Tool_Common::log('static_SscDwsHzNums','INFO','统计号码出现次数', $logArr);
                }
            }

        }

        return $rst;
    }

    /**
     * @desc 二定、三定、四定单双遗漏统计
     * @param $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * 12XX 21XX X12X X21X XX12 XX21 1XX2 2XX1 1111 2222
     */
    public static function updateDsYL($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];

        $start_time = microtime(true);
        try {
            $DataDealStatus = DealDataService::judgeDealTaskStatus($lottery_type, '', $field='updateDsYL_status');
            if($DataDealStatus->$field == SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL){
                throw_info('未开启统计：'.DealDataService::$dealDataStatusFields[$field], 40001);
            }
            $SDNumsArr = StaticService::$typeArr;
            unset($SDNumsArr[0],$SDNumsArr[1],$SDNumsArr[8],$SDNumsArr[9],$SDNumsArr[10],$SDNumsArr[11]);
            # 大数组：包括二定、三定、四定
            $updateDsDatas = [
                # 二定单双
                'dwds2' => [
                    'zuHes' => [ [1,2], [1,3], [1,4], [2,3], [2,4], [3,4] ],
                    'numsArr' => [11,12,21,22],  // [8,9,10,11,12,13];  // 值
                ],
                # 三定单双
                'dwds3' => [
                    'zuHes' => [ [1,2,3], [1,2,4], [1,3,4], [2,3,4] ],
                    'numsArr' => [111,112,121,122,211,212,221,222],  // [8,9,10,11,12,13];  // 值
                ],
                # 四定单双
                'dwds4' => [
                    'zuHes' => [ [1,2,3,4] ],
                    'numsArr' => [1111,1112,1121,1122,1211,1212,1221,1222,2111,2112,2121,2122,2211,2212,2221,2222],  // [8,9,10,11,12,13];  // 值
                ],
                /*
                */
                # 四定组合单双
                'dwds5' => [
                    'zuHes' => [ [1,2,3,4] ],
                    'numsArr' => $SDNumsArr,
                ],
            ];
            //p($updateDsDatas);
            //$rst[$interval] = SscDataService::dsYLStatic($interval);
            foreach ($updateDsDatas as $dsData){
                foreach ($dsData['zuHes'] as $key => $zuHe) {
                    // 和值为8、9在200期里边遗漏期数
                    //$YL_data = BaseNumService::getDwDsCurrentYL($zuHe, $dsData['numsArr'], 100)['data']; // 当前遗漏
                    foreach ($dsData['numsArr'] as $k=>$num){
                        //if(is_array($num) && in_array($k, [0,1,8,9,10,11])) continue;
                        $position = implode(',',$zuHe);
                        if(is_array($num)){
                            $zhi = implode(',',$num);
                            $where = ['positions'=>$position, 'zhi'=>$zhi, 'lottery_type'=>$lottery_type];
                            $SscDsYl = SscDsYl::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(1)->one();
                            $type = 4;
                            //if(!$SscDsYl)p([$zhi, $position, $SscDsYl]);
                        }else{
                            $zhi = $num;
                            $where = ['AND', ['=', 'positions', $position], ['=','zhi', $num], ['=', 'lottery_type', $lottery_type], ['=', 'LENGTH(zhi)', strlen($num)]];
                            $SscDsYl = SscDsYl::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(1)->one();
                            $type = 3;
                        }
                        if(empty($SscDsYl)){
                            $SscDsYl = new SscDsYl();
                            $SscDsYl->lottery_type = $lottery_type;
                            $SscDsYl->zhi = (string)$zhi;
                            $SscDsYl->positions = $position;
                            $SscDsYl->type = $type;
                        }

                        $SscDsYl->updated_at = time();
                        $miss = StaticsQxMissService::getDsHistoryMiss($num, $position, $lottery_type, $SscDsYl->static_nums); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
                        //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
                        $SscDsYl->lottery_type = $lottery_type; # 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
                        $SscDsYl->current_miss = $miss['current_times'] < 0 ? 0 : (string)$miss['current_times'];  // 1、当前遗漏次数
                        $SscDsYl->last_time_miss = (string)$miss['last_times']; // 2、上次遗漏
                        $SscDsYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
                        $SscDsYl->max_miss = (string)$miss['max_miss'];      // 4、近200期内最大遗漏
                        $SscDsYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
                        $SscDsYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
                        //p($updateData);
                        //if($YL_data[$num] > $SscDsYl->max_miss && $YL_data[$num] > $SscDsYl->history_max_miss){
                        //}
                        $SscDsYl->history_max_miss = (string)max($miss['current_times'],$SscDsYl->max_miss,$SscDsYl->history_max_miss); // 6、历史最大遗漏
                        $SscDsYl->update_time = date('Y-m-d H:i:s');
                        //p($SscDsYl->attributes);
                        $rst = $SscDsYl->save();
                        if(!$rst){
                            $logArr = ['attributes'=>$SscDsYl->attributes, 'msg'=>$SscDsYl->getErrors()];
                            Tool_Common::log('static_SscDwsDsNums','INFO','统计号码出现次数', $logArr);
                        }
                    }

                }
            }
            $dealStatus = 2;
        }catch (\Exception $e){
            $dealStatus = (strpos($e->getMessage(), '已经处理') !== false) ? 2 : ($e->getCode()>40000? 4: 3);
            Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '数据处理异常1', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
        }

        $end_time = microtime(true);
        DealDataService::dealDataRecord($DataDealStatus, $field, $dealStatus, $dealDesc = ['time_consume'=>($end_time-$start_time).'s', 'deal_time'=>date('Y-m-d H:i:s')]);

        return $rst;
    }

    /**
     * @desc 双重、三重、双双重遗漏统计
     * @param $type - 类型：1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重
     * @param $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     */
    public static function updateCodeTypeYL($type = 2, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];

        $SscStaticVals = SscStaticVal::find()->where(['type'=>$type, 'status'=>1])->asArray()->all();
        foreach ($SscStaticVals as $dsData){
            if(!$SscStaticYl = SscStaticYl::findOne(['lottery_type'=>$lottery_type, 'val'=>$dsData['val']])){
                $SscStaticYl = new SscStaticYl();
                $SscStaticYl->created_at = time();
            }
            $SscStaticYl->codes_hz = $dsData['codes_hz'];

            $SscStaticYl->static_nums = $dsData['static_nums'];
            //$count = SscDataService::getNumCounts($vals);
            $count = $dsData['count'];
            //if($dsData['val'] == 'type_2,type_3b') p([$count, $dsData['val']]);
            $SscStaticYl->lottery_type = $lottery_type;
            $SscStaticYl->updated_at = time();
            $SscStaticYl->val = $dsData['val'];
            $SscStaticYl->type = $type;
            $miss = StaticsQxMissService::getCodeTypeHistoryMiss($dsData['val'], $lottery_type, $SscStaticYl->static_nums); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
            //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
            $SscStaticYl->lottery_type = $lottery_type; # 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
            $SscStaticYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
            $SscStaticYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
            $SscStaticYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
            $SscStaticYl->max_miss = $miss['max_miss']?:0;      // 4、近200期内最大遗漏
            $SscStaticYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
            $SscStaticYl->yl_records = $miss['yl_str']; // 5、200期内最大遗漏范围
            $SscStaticYl->count = $count;

            $SscStaticYl->type_2b = (int)$dsData['type_2b'];
            $SscStaticYl->type_3b = (int)$dsData['type_3b'];
            $SscStaticYl->type_4b = (int)$dsData['type_4b'];
            $SscStaticYl->type_2 = (int)$dsData['type_2'];
            $SscStaticYl->type_3 = (int)$dsData['type_3'];
            $SscStaticYl->type_4 = (int)$dsData['type_4'];
            $SscStaticYl->type_22 = (int)$dsData['type_22'];
            $SscStaticYl->type_4d = (int)$dsData['type_4d'];
            $SscStaticYl->type_4s = (int)$dsData['type_4s'];
            $SscStaticYl->type_log = (int)$dsData['type_log'];

            $countArr = self::getAriseCounts($dsData['val'], $count, $lottery_type);
            //p($countArr,0);
            $SscStaticYl->theory_nums_perdate = $countArr['theory_nums_perdate'];
            $SscStaticYl->today_nums = $countArr['today_nums'];
            $SscStaticYl->ytd_nums = $countArr['ytd_nums'];
            //================================= 改造结束 ==========================================

            $SscStaticYl->history_max_miss = max($miss['current_times'],$SscStaticYl->max_miss,$SscStaticYl->history_max_miss); // 6、历史最大遗漏
            $SscStaticYl->update_time = date('Y-m-d H:i:s');
            //p($SscStaticYl->attributes);
            $rst = $SscStaticYl->save();
            if(!$rst){
                $logArr = ['attributes'=>$SscStaticYl->attributes, 'msg'=>$SscStaticYl->getErrors()];
                Tool_Common::log('updateCodeTypeYL','INFO','统计号码出现次数', $logArr);
            }
        }

        return $rst;
    }

    /**
     * @param $vals
     * @param $count
     * @param int $lottery_type
     * @return array
     */
    public static function getAriseCounts($vals, $count, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $rstData = [];

        $qiShu = SscDataService::getQiShu($lottery_type);
        if(strpos($vals, '+') !== false){
            $valArr = explode('+', $vals);
            if($valArr[0] == 'code_3n'){
                $codes = ['009','001','011','112','122','223','233','334','344','445','455','556','566','667','677','778','788','889','899','099'];
                //$count = 1752;
                $likeWhere = ['OR'];
                foreach ($codes as $code){
                    $likeWhere = array_merge($likeWhere, [['LIKE', 'code_3n', $code]]);
                }

                $date = date('Y-m-d');
                $where = ['AND', ['=', 'lottery_type', $lottery_type],['=', 'date', $date], $likeWhere];#今日
                $rstData['today_nums'] = SscKjData::find()->select(['COUNT(id) AS nums'])->where($where)->asArray()->all()[0]['nums'];

                $date = date('Y-m-d',strtotime("-1 day") ); # 昨日
                $where = ['AND', ['=', 'lottery_type', $lottery_type],['=', 'date', $date], $likeWhere];#昨日
                $rstData['ytd_nums'] = SscKjData::find()->select(['COUNT(id) AS nums'])->where($where)->asArray()->all()[0]['nums'];
            }elseif($valArr[0] == 'code_4n'){
                $num = $valArr[1];
                $codesArr = [
                    0 => ['0012', '0123', '0234', '0345', '0456', '0567', '0678', '0789', '0019', '0089'],
                    1 => ['1123', '1234', '1345', '1456', '1567', '1678', '1789', '0189', '0119', '0112'],
                    2 => ['0122', '1223', '2234', '2345', '2456', '2567', '2678', '2789', '0289', '0129'],
                    3 => ['0123', '1233', '2334', '3345', '3456', '3567', '3678', '3789', '0389', '0139'],
                    4 => ['0124', '1234', '2344', '3445', '4456', '4567', '4678', '4789', '0489', '0149'],
                    5 => ['0125', '1235', '2345', '3455', '4556', '5567', '5678', '5789', '0589', '0159'],
                    6 => ['0126', '1236', '2346', '3456', '4566', '5667', '6678', '6789', '0689', '0169'],
                    7 => ['0127', '1237', '2347', '3457', '4567', '5677', '6778', '7789', '0789', '0179'],
                    8 => ['0128', '1238', '2348', '3458', '4568', '5678', '6788', '7889', '0889', '0189'],
                    9 => ['0129', '1239', '2349', '3459', '4569', '5679', '6789', '7899', '0899', '0199'],
                ];
                $codes = $codesArr[$num];
                $count = 204;

                $date = date('Y-m-d');
                $where = ['AND', ['=', 'lottery_type', $lottery_type],['=', 'date', $date], ['IN', 'code_4n', $codes]];#今日
                $rstData['today_nums'] = SscKjData::find()->select(['COUNT(id) AS nums'])->where($where)->asArray()->all()[0]['nums'];

                $date = date('Y-m-d',strtotime("-1 day") ); # 昨日
                $where = ['AND', ['=', 'lottery_type', $lottery_type],['=', 'date', $date], ['IN', 'code_4n', $codes], ['=', 'date', date('Y-m-d')]];#今日
                $rstData['ytd_nums'] = SscKjData::find()->select(['COUNT(id) AS nums'])->where($where)->asArray()->all()[0]['nums'];
            }

        }else{

            $date = date('Y-m-d');
            $rstData['today_nums'] = self::getAcountByDate($vals, $date, $lottery_type);

            $date = date('Y-m-d',strtotime("-1 day") );
            $rstData['ytd_nums'] =  self::getAcountByDate($vals, $date, $lottery_type);
        }
        $rstData['theory_nums_perdate'] = (string)round(($count*$qiShu*0.1)/995, 2); # 理论次/天

        return $rstData;
    }

    public static function getAcountByDate($vals, $date, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $vals = explode(',', $vals);
        //================================= 改造开始 ==========================================
        $where = ['AND'];
        foreach ($vals as $val){
            $where  = array_merge($where, [['=', $val, 1]]);
        }
        $today_nums_where = array_merge($where,[['=', 'lottery_type', $lottery_type],['=', 'date', $date]]);
        $nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where($today_nums_where)->asArray()->all()[0]['nums'];

        return $nums;
    }

    /**
     * @desc 三字现带双重遗漏统计 未完待续 2019.06.13
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     */
    public static function update3CodeRepeatYL($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];

        $SscStaticVals = SscStaticVal::find()->where(['type'=>3])->asArray()->all();
        foreach ($SscStaticVals as $dsData){
            if(!$SscStaticYl = SscStaticYl::findOne(['lottery_type'=>$lottery_type, 'val'=>$dsData['val']])){
                $SscStaticYl = new SscStaticYl();
                $SscStaticYl->created_at = time();
                $SscStaticYl->static_nums = 250;
            }
            $vals = explode(',', $dsData['val']);
            $count = SscDataService::getNumCounts($vals);
            //if($dsData['val'] == 'type_2,type_3b') p([$count, $dsData['val']]);
            $SscStaticYl->lottery_type = $lottery_type;
            $SscStaticYl->updated_at = time();
            $SscStaticYl->val = $dsData['val'];
            $miss = SscDataService::get3CodeRepeatHistoryMiss($dsData['val'], $lottery_type, $SscStaticYl->static_nums); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
            //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
            $SscStaticYl->lottery_type = $lottery_type; # 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
            $SscStaticYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
            $SscStaticYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
            $SscStaticYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
            $SscStaticYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
            $SscStaticYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
            $SscStaticYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
            $SscStaticYl->count = $count;

            $qishu = SscDataService::getQiShu($lottery_type);
            $where = ['AND', ['=', 'code_type', 4]];
            foreach ($vals as $val){
                $where  = array_merge($where, [['=', $val, 1]]);
            }
            $Num4Type = Num4Type::find()->select('COUNT(id) AS count')->where($where)->limit(1)->asArray()->one();
            $SscStaticYl->theory_nums_perdate = (string)round(($Num4Type['count']*$qishu*0.1) / 995, 2); # 理论次/天
            $today_nums_where = array_merge($where,[['=', 'lottery_type', $lottery_type],['=', 'date', date('Y-m-d')]]);
            //$today_nums_where = array_merge($where,[['=', 'lottery_type', $lottery_type],['=', 'date', '2019-05-24']]);
            $today_nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where($today_nums_where)->asArray()->all()[0]['nums'];
            $SscStaticYl->today_nums = $today_nums;

            # 昨日出现次数
            $ytd_nums_where = array_merge($where,[['=', 'lottery_type', $lottery_type],['=', 'date', date('Y-m-d',strtotime("-1 day") )]]);
            $ytd_nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where($ytd_nums_where)->asArray()->all()[0]['nums'];
            $SscStaticYl->ytd_nums = $ytd_nums;

            $SscStaticYl->history_max_miss = max($miss['current_times'],$SscStaticYl->max_miss,$SscStaticYl->history_max_miss); // 6、历史最大遗漏
            $SscStaticYl->update_time = date('Y-m-d H:i:s');
            //p($SscStaticYl->attributes);
            $rst = $SscStaticYl->save();
            if(!$rst){
                $logArr = ['attributes'=>$SscStaticYl->attributes, 'msg'=>$SscStaticYl->getErrors()];
                Tool_Common::log('updateCodeTypeYL','INFO','统计号码出现次数', $logArr);
            }
        }

        return $rst;
    }

    /**
     * @param $type - 类型：1和值2号码类型[例如:双双重、三重]3带双三字现4四字现5不带双三字现
     * @return array|bool
     */
    public static function updateCodeTypeYLs($type, $lottery_type = DEFAULT_LOTTERY_TYPE){
        if(!in_array($type, [3, 4, 5])) return false;
        $rst = [];

        $now_time = time();
        $qiShu = SscDataService::getQiShu($lottery_type);
        $SscStaticYls = self::getSscStaticYls($lottery_type, $type);
        $yDate = date('Y-m-d',strtotime("-1 day"));
        $tDate = date('Y-m-d');
        $SscKjData = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy('id DESC')->limit(1)->one();
        $zjCodeSets = array_merge([$SscKjData->code_4n], explode(',', $SscKjData->code_3n));

        # 中奖号码遗漏记录
        $SscStaticVals = self::getSscStaticVal($type, $zjCodeSets);
        # 1、不中奖号码遗漏更新
        $whereNoZj = ['AND', ['=', 'lottery_type', $lottery_type], ['NOT IN', 'val', $zjCodeSets], ['=', 'type', $type]];
        SscStaticYl::updateAll(['current_miss'=>new Expression('`current_miss`+1'), 'update_time'=>date('Y-m-d H:i:s')], $whereNoZj);

        # 2、中奖号码遗漏更新
        foreach ($SscStaticVals as $dsData){
            $count = $dsData['count'];
            if(!$SscStaticYl = $SscStaticYls[$dsData['val']]){
                continue;
            }
            $SscStaticYl->updated_at = $now_time;
            $miss = StaticsQxMissService::getCodeTypeYlHistoryMiss($dsData['val'], $lottery_type, $dsData['static_nums'], $type);

            //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
            $SscStaticYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
            $SscStaticYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
            $SscStaticYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
            $SscStaticYl->yl_records = $miss['yl_str']; // 5、200期内最大遗漏范围
            $SscStaticYl->status = $dsData['status']; # 前台显示

            $len = strlen($dsData['val']);
            $field = $len == 3 ? 'code_3n' : 'code_4n';
            $where = ['AND', ['LIKE', $field, $dsData['val']]];
            $nums = self::getTheoryNums($count, $qiShu);
            $SscStaticYl->theory_nums_perdate = (string)$nums; # 理论次/天

            $SscStaticYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
            $SscStaticYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
            # 今日出现次数
            $today_nums_where = array_merge($where,[['=', 'lottery_type', $lottery_type],['=', 'date', $tDate]]);
            $today_nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where($today_nums_where)->asArray()->all()[0]['nums'];
            $SscStaticYl->today_nums = $today_nums;
            $SscStaticYl->val = $dsData['val'];
            # 昨日出现次数
            $ytd_nums = self::getCodeTypeYtdNums($field, $dsData['val'], $lottery_type, $yDate);

            $SscStaticYl->ytd_nums = $ytd_nums;

            $SscStaticYl->history_max_miss = max($miss['current_times'], $SscStaticYl->max_miss, $SscStaticYl->history_max_miss); // 6、历史最大遗漏
            $SscStaticYl->update_time = date('Y-m-d H:i:s');
            $rst = $SscStaticYl->save();
            if(!$rst){
                $logArr = ['attributes'=>$SscStaticYl->attributes, 'msg'=>$SscStaticYl->getErrors()];
                Tool_Common::log('updateCodeTypeYL','ERR','号码类型遗漏统计', $logArr);
            }
            $logArr = ['lottery_type'=>$lottery_type, 'type'=>$type, 'val'=>$dsData['val']];
            Tool_Common::log('updateCodeTypeYL','INFO','号码类型遗漏统计', $logArr);
        }

        return $rst;
    }

    /**
     * @desc 获取理论出现次数
     * @param int $count
     * @param int $qishu
     * @return float|mixed
     */
    public static function getTheoryNums($count = 24, $qishu = 59){

        return round(($count*$qishu*0.1) / 995, 2);
    }

    /**
     * @desc 获取号码类型
     * @param int $type
     * @return array|SscStaticVal[]|mixed
     */
    public static function getSscStaticVal($type = 3, $vals=[]){
        $m = \Yii::$app->cache;
        $mkey = 'getSscStaticVal_'.$type;
        $where = ['type'=>$type, 'status'=>1];
        if(!empty($vals)){
            $mkey = $mkey . yii\helpers\Json::encode($vals);
            $where['val'] = $vals;
        }
        if(!$SscStaticVals = $m->get($mkey)){
            $SscStaticVals = SscStaticVal::find()->where($where)->asArray()->all();
        }

        $m->set($mkey, $SscStaticVals, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);

        return $SscStaticVals;
    }

    /**
     * @desc 获取一个号码昨天出现次数
     * @param string $field
     * @param string $val
     * @param int $lottery_type
     * @param $date
     * @return mixed|int
     */
    public static function getCodeTypeYtdNums($field = 'code_4n', $val = '0123', $lottery_type = DEFAULT_LOTTERY_TYPE, $date){
        $m = \Yii::$app->cache;

        $mkey = 'getCodeTypeYtdNums_'.$lottery_type.'_'.$date.'_'.$field.'_'.$val;
        if(!$nums = $m->get($mkey)){
            $where = ['AND', ['LIKE', $field, $val]];
            $ytd_nums_where = array_merge($where,[['=', 'lottery_type', $lottery_type],['=', 'date', $date ]]);
            $nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where($ytd_nums_where)->asArray()->all()[0]['nums'];
            $m->set($mkey, $nums, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME'] * 4);
        }

        return $nums;
    }

    /**
     * @desc 返回记录数，组数
     * @param $vals
     * @return int
     */
    public static function getNumCounts($vals){

        if(count($vals) == 1){
            $where = ['AND', ['=', 'code_type', 4], ['=', $vals[0], 1]];
        }else{
            $where = ['AND', ['=', 'code_type', 4]];
            foreach ($vals as $val){
                $where = array_merge($where, [ ['=', $val, 1] ]);
            }
        }
        $Num4Type = Num4Type::find()->where($where)->all();

        return count($Num4Type);
    }

    /**
     * @desc 获取遗漏记录objects
     * @param int $lottery_type
     * @param int $type
     * @return mixed|static[]
     */
    public static function getSscStaticYls($lottery_type = DEFAULT_LOTTERY_TYPE, $type = 3){
        //$SscStaticYls = SscStaticYl::findAll(['lottery_type'=>$lottery_type, 'type'=>$type]);
        $m = \Yii::$app->cache;
        $mkey = 'getSscStaticYls_'.$lottery_type.'_'.$type;
        if(empty($SscStaticYls)){
            $SscStaticYls = SscStaticYl::find()->where(['lottery_type'=>$lottery_type, 'type'=>$type])->indexBy('val')->all();
            $m->set($mkey, $SscStaticYls, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);
        }

        return $SscStaticYls;
    }

    /**
     * @desc 返回记录数，组数
     * @param $type 3三字现带双重4四字现带双重5四字现不带双重
     * @param $vals
     * @return int
     */
    public static function getCodeTypeNumCounts($type = 2, $val = 3){
        $codesTypes = [
            //2 => 106,
            3 => 106,
            4 => 12,
            5 => 24,
        ];


        return $codesTypes[$type];
    }

    /**
     * @desc 三字现遗漏统计
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     */
    public static function update3NumYL($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];
        $threeNums = BaseNumService::getAll3Num();

        $start_time = microtime(true);
        try {
            $DataDealStatus = DealDataService::judgeDealTaskStatus($lottery_type, '', $field='update3NumYL_status');
            if($DataDealStatus->$field == SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL){
                throw_info('未开启统计：'.DealDataService::$dealDataStatusFields[$field], 40001);
            }

            $zhis = $threeNums;
            // 和值为8、9在200期里边遗漏期数
            //$YL_data = BaseNumService::get3NCurrentYL($zhis)['data']; // 当前遗漏
            //p($YL_data);
            foreach ($zhis as $num){
                //p(['zhi'=>$num]);
                if(!$Ssc3numYl = Ssc3numYl::findOne(['zhi'=>$num, 'lottery_type'=>$lottery_type])){
                    $Ssc3numYl = new Ssc3numYl();
                }

                $Ssc3numYl->zhi = $num;
                $Ssc3numYl->lottery_type = $lottery_type;
                $miss = SscDataService::get3NumHistoryMiss($num, $lottery_type); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
                //$Ssc3numYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
                $Ssc3numYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
                $Ssc3numYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
                $Ssc3numYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
                $Ssc3numYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
                $Ssc3numYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
                //$Ssc3numYl->yl_records = $YL_data[$num].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
                $Ssc3numYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
                $Ssc3numYl->history_max_miss = max($miss['current_times'],$Ssc3numYl->max_miss,$Ssc3numYl->history_max_miss); // 6、历史最大遗漏
                //$Ssc3numYl->update_time = date('Y-m-d H:i:s');
                //p($Ssc3numYl->attributes);
                $rst = $Ssc3numYl->save();
                if(!$rst){
                    $logArr = ['attributes'=>$Ssc3numYl->attributes, 'msg'=>$Ssc3numYl->getErrors()];
                    Tool_Common::log('/WORK/LOG/lottery_xl/static_Ssc3Nums','INFO','统计号码出现次数', $logArr);
                }
            }
            $dealStatus = 2;
        }catch (\Exception $e){
            $dealStatus = (strpos($e->getMessage(), '已经处理') !== false) ? 2 : ($e->getCode()>40000? 4: 3);
            Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '数据处理异常2', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
        }

        $end_time = microtime(true);
        DealDataService::dealDataRecord($DataDealStatus, $field, $dealStatus, $dealDesc = ['time_consume'=>($end_time-$start_time).'s', 'deal_time'=>date('Y-m-d H:i:s')]);


        return $rst;
    }

    /**
     * @desc 获取统计最小id
     * @param $last_index_id
     * @param int $recently
     * @return int|mixed
     */
    public static function getMinStaticId($last_index_id, $recently = 2000){
        $m = \Yii::$app->cache;
        $mkey = 'getMinStaticId_'.$last_index_id.'_'.$recently;

        if(!$min_id = $m->get($mkey)){
            $min_id = $last_index_id - $recently;
            $m->set($mkey, $min_id, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);
        }

        return $min_id;
    }

    /**
     * @desc 获取表中最后一个开奖记录
     * @param int $lottery_type
     * @return array|SscKjData|mixed|null
     */
    public static function getTabLastKjData($lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'getTabLastKjData_'.$lottery_type;
        if(!$SscKjDatas = $m->get($mkey)){
            $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'code_3n', 'code_4n', 'qihao', 'kj_code'])->where(['lottery_type'=>$lottery_type])->orderBy('id DESC')->limit(1)->asArray()->one();
            $m->set($mkey, $SscKjDatas, 120);
        }

        return $SscKjDatas;
    }

    /**
     * @desc 获取最后的index_id
     * @param int $lottery_type
     * @return mixed
     */
    public static function getLastIndexId($lottery_type = DEFAULT_LOTTERY_TYPE, $dateNum=0, $useCache=false){
        $m = \Yii::$app->cache;
        $mkey = 'getLastIndexId_x_'.$lottery_type.'_'.$dateNum;
        if(!$useCache OR !$index_id = $m->get($mkey)){
            $lastQuery = SscKjData::find()->where(['lottery_type'=>$lottery_type])
                ->select(['index_id'])->orderBy(['id'=>SORT_DESC])->limit(1);
            if($dateNum>0){
                $date = date('Y-m-d', time()-$dateNum*86400);
                $lastQuery->andWhere(['<=', 'date', $date]);
            }
            //p($lastQuery->createCommand()->getRawSql());
            $last = $lastQuery->asArray()->one();
            $index_id = $last['index_id'];
            //$qihao = HN0898Service::getQihao($lottery_type);
            //$time = BetService::getBetCacheTime($lottery_type, $qihao);
            $m->set($mkey, $index_id, 30);
        }

        return $index_id;
    }

    /**
     * @description 返回遗漏数据 by 本表
     * @param $value 例如：001[type:3] 或者 1223[type:4] 或者 1234[type:5] === 3三字现带双重4四字现带双重5四字现不带双重
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param $type 1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重, 目前暂支持4、5
     * @return array
     */
    public static function getCodeTypeYlByTab($value, $lottery_type = DEFAULT_LOTTERY_TYPE, $type = 3){
        $m = \Yii::$app->cache;
        $qihao = HN0898Service::getQihao($lottery_type);
        $mkey = 'getCodeTypeYlByTab_'.$lottery_type.'_'.$value;
        //if($m->get($mkey)) return false;

        if(!$SscStaticYl = SscStaticYl::findOne(['val'=>$value, 'type'=>$type, 'lottery_type'=>$lottery_type])){
            return [];
        }

        $last_time_miss_range = $SscStaticYl->last_time_miss_range;
        $yl_records = explode('-', $SscStaticYl->yl_records);
        $current_times = $SscStaticYl->current_miss + 1;
        //$yl_records[0] = $current_times;
        $last_times = $SscStaticYl->last_time_miss;
        $max_range = $SscStaticYl->max_range;

        unset($yl_records[0]);
        $yl_str = implode('-', $yl_records);

        $max_miss = max($yl_records);

        $rstData = [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss,   // 近200期内的最大遗漏
            'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'yl_str' => $yl_str,
        ];
        $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
        $m->set($mkey, 1, $time);
        //p([$value, $rstData]);
        return $rstData;
    }

    /**
     * @description 三字现带双重遗漏 未完待续 2019.06.13
     * @param $num
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param $recently 多少期内，默认为
     * @return array
     */
    public static function get3CodeRepeatHistoryMiss($vals, $lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 472){
        //if(!is_array($num)) $num = [ $num ];
        $last_times = 0;
        $last = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
        $min_id = $last['last_id'] - $recently - 1;

        $valArr = explode(',', $vals);
        if(count($valArr) == 1){
            $where = ['AND', ['IN', $valArr[0], 1],['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
            $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        }else{
            $where = ['AND',['>', 'index_id', $min_id], ['=','lottery_type',$lottery_type]];
            foreach ($valArr as $val){
                $where = array_merge($where,[['=', $val, 1]]);
            }
            $query = SscKjData::find()->select(['id', 'index_id', 'qihao'])->where($where);
            $SscKjDatas = $query->orderBy('id DESC')->all();
        }
        //$where = "$field=$num AND id>$min_id";
        if(count($SscKjDatas)>1){
            $last_times = $SscKjDatas[0]->index_id - $SscKjDatas[1]->index_id - 1;  // 上次遗漏次数
        }

        # 最大遗漏期间计算 start
        $tmpKjData = $SscKjDatas;
        if(count($tmpKjData) > 2){
            foreach($tmpKjData as $key=>$r){
                if($key == 0) continue;
                $range[$tmpKjData[$key-1]['index_id']."_".$tmpKjData[$key]['index_id']] = $tmpKjData[$key-1]['index_id'] - $tmpKjData[$key]['index_id'] - 1;
            }

            $max_miss = max($range);
            $maxKey = array_search($max_miss, $range);
            $keyArr = explode('_',$maxKey);
            $tmpArr = [];
            foreach($tmpKjData as $key=>$r){
                if(in_array($r['index_id'], $keyArr)){
                    $tmpArr[] = $r['qihao'];
                }
            }
            $max_range = $tmpArr[1].'-'.$tmpArr[0];  // 近200期内最大遗漏
            $yl_str = implode('-',$range);
            # 最大遗漏期间计算 end
            //p([$field=>$num,$min_id, $SscKjData[1]->id,$max_range]);
        }else{
            $max_range = $SscKjDatas[1]['qihao'] ."-". $SscKjDatas[0]['qihao'];
        }
        $last_time_miss_range = $SscKjDatas[1]['qihao'] ."-". $SscKjDatas[0]['qihao'];
        $current_times = $last['last_id'] - $SscKjDatas[0]->index_id;

        $rstData = [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss,   // 近200期内的最大遗漏
            'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'yl_str' => $yl_str,
        ];
        //if($vals == 'type_2,type_3b')p($rstData);
        return $rstData;
    }

    /**
     * @description 返回历史四定和值遗漏
     * @param $zuHes
     * @param int $lottery_type
     * @param int $recently 多少期内，默认为 4天
     * @return array
     */
    public static function getSdHzYlHistoryMiss($zuHes, $lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 250){
        $last_times = 0;
        list($lastQihao, $lastIndexId, $lastId) = SscDataService::getKjDataLastIndexId($lottery_type);
        $min_id = max($lastIndexId - $recently - 1, 0);
        //p([$lastIndexId, $recently, $min_id]);

        $where = ['AND', ['=', 'lottery_type', $lottery_type], ['IN', 'codes_4nums_hz', $zuHes], ['>=', 'index_id', $min_id]];
        $SscKjData = SscKjData::find()->select(['id','index_id','qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        //p([$where, $zuHes, $last, $SscKjData[0]->id, $SscKjData[1]->id, $recently]);
        if(count($SscKjData)>1){
            $last_times = $SscKjData[0]->index_id - $SscKjData[1]->index_id - 1;  // 上次遗漏次数
        }

        # 遗漏期间计算 start
        $tmpKjData = $SscKjData;
        if(count($tmpKjData) > 2){
            $range = [];
            foreach($tmpKjData as $key=>$r){
                if($key == 0) continue;
                $range[$tmpKjData[$key-1]['index_id']."_". $r['index_id']] = $tmpKjData[$key-1]['index_id'] - $r['index_id'] - 1;
            }

            $max_miss = max($range);
            /*
            $maxKey = array_search($max_miss, $range);
            $keyArr = explode('_',$maxKey);
            $tmpArr = [];
            foreach($tmpKjData as $key=>$r){
                if(in_array($r['index_id'], $keyArr)){
                    $tmpArr[] = $r['qihao'];
                }
            }
            $max_range = $tmpArr[1].'-'.$tmpArr[0];  // 近200期内最大遗漏
            */
            $yl_str = implode('-',$range);
            # 最大遗漏期间计算 end
            //p([$field=>$num,$min_id,'times'=>$times,$SscKjData[0]->id, $SscKjData[1]->id,$max_range]);
        }else{
            //$max_range = $SscKjData[1]['qihao'] ."-". $SscKjData[0]['qihao'];
        }
        $last_time_miss_range = $SscKjData[1]['qihao'] ."-". $SscKjData[0]['qihao'];
        $current_times = $lastIndexId - $SscKjData[0]->index_id;
        //p([$last['last_id'] , $SscKjData[0]]);

        $rstData = [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss,   // 近200期内的最大遗漏
            //'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'val' => implode(',', $zuHes),
            'yl_str' => BaseStringHelper::truncate($yl_str,3000),
            //'zihes' => $zuHes,
        ];
        //p($rstData);

        return $rstData;
    }

    /**
     * @description 返回历史三字现遗漏
     * @param $num 值
     * @param $lottery_type -彩种类型1重庆时时彩2七星彩3排列三4排列五5福彩3D
     * @param $recently -多少期内，默认为1000期
     * @return array
     */
    public static function get3NumHistoryMiss($num, $lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 1200){
        $last_times = 0;
        //$last = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
        $last_index_id = self::getLastIndexId($lottery_type);
        $min_id = $last_index_id - $recently - 1;
        $m = \Yii::$app->cache;
        $mkey = 'get3NumHistoryMiss_ID_'.$lottery_type.'_'.$min_id;
        if(true OR !$rst = $m->get($mkey)){
            $field = 'code_3n';
            $where = ['AND',['like', $field, $num],['=', 'lottery_type', $lottery_type],['>','index_id', $min_id]];
            $SscKjData3Nums = SscKjData::find()->select(['id', 'index_id', 'qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
            //p($SscKjData3Nums);
            if(count($SscKjData3Nums)>1){
                $last_times = $SscKjData3Nums[0]->index_id - $SscKjData3Nums[1]->index_id - 1;  // 上次遗漏次数
            }

            # 最大遗漏期间计算 start
            $tmpKjData = $SscKjData3Nums;
            if(count($tmpKjData) > 2){
                foreach($tmpKjData as $key=>$r){
                    if($key == 0) continue;
                    $range[$tmpKjData[$key-1]['index_id']."_".$tmpKjData[$key]['index_id']] = $tmpKjData[$key-1]['index_id'] - $tmpKjData[$key]['index_id'] - 1;
                }

                $max_miss = max($range);
                $maxKey = array_search($max_miss, $range);
                $keyArr = explode('_',$maxKey);
                $tmpArr = [];
                foreach($tmpKjData as $key=>$r){
                    if(in_array($r['index_id'], $keyArr)){
                        $tmpArr[] = $r['qihao'];
                    }
                }
                $max_range = $tmpArr[1].'-'.$tmpArr[0];  // 近200期内最大遗漏
                $yl_str = implode('-',$range);
                # 最大遗漏期间计算 end
                //p([$field=>$num,$min_id,'times'=>$times,$SscKjData[0]->id, $SscKjData[1]->id,$max_range]);
            }else{
                $max_range = $SscKjData3Nums[1]['qihao'] ."-". $SscKjData3Nums[0]['qihao'];
            }
            $last_time_miss_range = $SscKjData3Nums[1]['qihao'] ."-". $SscKjData3Nums[0]['qihao'];
            $current_times = $last_index_id - $SscKjData3Nums[0]->index_id;

            $rst = [
                'current_times' => $current_times,    // 当前遗漏次数
                'last_times' => $last_times,    // 上次遗漏次数
                'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
                'max_miss' => $max_miss,   // 近200期内的最大遗漏
                'max_range' => $max_range,   // 近200期内的最大遗漏范围
                'yl_str' => $yl_str,
            ];
            $m->set($mkey, $rst,60*60);
        }


        return $rst;
    }

    /**
     * @desc 每期开奖单双记录-已完成
     * @param $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @param string $qihao
     */
    public static function insertSscKjDataDs($qihao = '', $lottery_type = DEFAULT_LOTTERY_TYPE){

        $start_time = microtime(true);
        try {

            $DataDealStatus = DealDataService::judgeDealTaskStatus($lottery_type, $qihao, $field='updateDs_status');
            if($DataDealStatus->$field == SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL){
                throw_info('未开启统计：'.DealDataService::$dealDataStatusFields[$field], 40001);
            }

            $SscKjData = SscKjData::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
            $data = explode(',',$SscKjData['code_str']);
            $m = \Yii::$app->cache;
            $mkey = 'InsertSscKjDataDs_'.$lottery_type.'_'.$qihao;
            //if($r = $m->get($mkey)) return ['status'=>'300', 'msg'=>'已经处理完成...'];
            //array_pop($data);
            $tmpData = [];
            foreach ($data as $key=>$v){
                $pos = $key + 1;
                if($v%2 == 0){
                    $tmpData[$pos] = 2; // 双
                }else{
                    $tmpData[$pos] = 1; // 单
                }
            }
            $opData = [];
            $opData['updated_at'] = time();
            $opData['updated_time'] = date('Y-m-d H:i:s');
            //p(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
            $SscKjDataDs = SscKjDataDs::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
            if($SscKjDataDs){
                throw_info($lottery_type.'=='.$qihao.'单双更新已经存记录');
            }
            $SscKjDataDs = new SscKjDataDs();
            $opData['created_at'] = time();
            $opData['index_id'] = $SscKjData->index_id;

            # 1、一定
            foreach ($tmpData as $key=>$tmp){
                $sField = 'code_'.$key;
                $opData[$sField] = $tmp;
            }

            # 2、二定
            $zuHes = [ [1,2], [1,3], [1,4], [2,3], [2,4], [3,4] ];
            foreach ($zuHes as $key=>$zuHe){
                $sField = 'code_'.$zuHe[0].'_'.$zuHe[1];
                $opData[$sField] = $tmpData[$zuHe[0]].$tmpData[$zuHe[1]];
            }

            # 3、三定
            $zuHes = [ [1,2,3], [1,2,4], [1,3,4], [2,3,4] ];
            foreach ($zuHes as $key=>$zuHe){
                $sField = 'code_'.$zuHe[0].'_'.$zuHe[1].'_'.$zuHe[2];
                $opData[$sField] = $tmpData[$zuHe[0]].$tmpData[$zuHe[1]].$tmpData[$zuHe[2]];
            }

            # 4、四定
            $zuHe = [ 1,2,3,4 ];
            $sField = 'code_'.$zuHe[0].'_'.$zuHe[1].'_'.$zuHe[2].'_'.$zuHe[3];
            $opData[$sField] = $tmpData[$zuHe[0]].$tmpData[$zuHe[1]].$tmpData[$zuHe[2]].$tmpData[$zuHe[3]];

            $opData['qihao'] = (string)$qihao;
            $opData['code_str'] = $SscKjData['code_str'];
            $opData['date'] = $SscKjData['date'];
            $opData['updated_at'] = time();
            $opData['lottery_type'] = $lottery_type;
            $opData['update_time'] = date('Y-m-d H:i:s');
            $SscKjDataDs->setAttributes($opData);
            $rst =$SscKjDataDs->save();
            if($rst){
                $val = SystemConfig::findOne(['key'=>'ssc_kj_time_period'])->value; # 开奖时间间隔:20分钟
                $m->set($mkey, 1,$val*60);
            }else{
                //p([$rst, $tmpData,$SscKjDataDs->attributes,$SscKjDataDs->getErrors()]);
                throw_info(Json::encode($SscKjDataDs->getErrors(), 320));
            }
            $dealStatus = 2;
        }catch (\Exception $e){
            $rst = 1;
            $dealStatus = (strpos($e->getMessage(), '已经处理') !== false) ? 2 : ($e->getCode()>40000? 4: 3);
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '数据处理异常3', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
        }

        $end_time = microtime(true);
        DealDataService::dealDataRecord($DataDealStatus, $field, $dealStatus, $dealDesc = ['time_consume'=>($end_time-$start_time).'s', 'deal_time'=>date('Y-m-d H:i:s')]);

        return $rst;
    }

    /**
     * @desc 获取开奖单双 by kj_str
     * @param string $codes 例如：1,2,3,4,5
     * @return string 1212
     */
    public static function getCodesDS($codes){
        $str = '';
        $data = explode(',',$codes);
        if($codes){
            $tmpData = [];
            foreach ($data as $key=>$v){
                $pos = $key + 1;
                if($v%2 == 0){
                    $tmpData[$pos] = 2; // 双
                }else{
                    $tmpData[$pos] = 1; // 单
                }
            }
            $str = $tmpData[1].$tmpData[2].$tmpData[3].$tmpData[4];

        }

        return $str;
    }

    /**
     * @desc 获取0898网站开奖号码
     * @param string $qihao
     */
    public static function getSscKjData0898($qihao = ''){
        if(!$qihao) return false;
        $url = 'https://9900001.com/kaijiang/list.aspx?lot=ssc';
        //$data = CurlService::httpGet($url);
        $content = RemoteHtmlService::getRemoteHtmlContent($url);
        //p($content,0);
        $preg = "/<td>".$qihao."<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism"; // 这里是表达式，大神看看
        preg_match_all($preg,$content,$matches);

        /*
        $preg = "/<td>(.*?)<\/td>/ism"; // 这里是表达式，大神看看
        $key = array_search($qihao, $matches[1]);
        $kjData_key = $key+2;
        $kjData = $matches[1][$kjData_key];
        */
        $kjData = $matches[2][0];

        if(!$kjData) return false;

        return $kjData;
    }

    /**
     * @desc 判断大小单双
     * @param $data
     * @return false|int|string
     */
    public static function justDataSingleOrDouble($data){
        $jData = ['01234567890','56789','01234','13579','02468'];

        if(in_array($data, $jData)) return array_search($data, $jData);
        return false;
    }

    /**
     * @desc 判断投注方式和号码是否匹配
     * @param $post
     * @return bool
     */
    public static function just3DwRight($post){
        $flag = true;
        $p_1 = $post['position_1'];
        $p_2 = $post['position_2'];
        $p_3 = $post['position_3'];
        $p_4 = $post['position_4'];
        if($post['playway'] == 2){
            $tmpArr = [$p_1,$p_2,$p_3,$p_4];
            $tmpArr = array_filter($tmpArr);
            if( count($tmpArr) != 3 ){
                $flag = false;
            }
        }

        return $flag;
    }

    /**
     * @desc 计算投注金额
     * @param $codes 2三子定：13579,X,02468,02468   3四字定：01234,01234,56789,56789
     * @param int $playway 2:三字定 3四字定 4:一字定
     * @param float $single
     * @return float
     */
    public static function calTzTotalMoney($codes, $single = 0.1, $playway = 2, $zhuSplit = '@', $codeSplit = ','){
        $zhuNums = explode($zhuSplit, $codes);
        $all_moneys = 0.00;

        foreach ($zhuNums as $key=>$codes){
            $codes = explode($codeSplit, $codes);
            if($playway == 2){  # 三字定
                $key = array_search('X',$codes);
                unset($codes[$key]);
            }
            if($playway == 4 OR $playway == 2){
                for ($i=0; $i<3; $i++){
                    $key = array_search('X',$codes);
                    unset($codes[$key]);
                }
            }

            $zhus = 1;
            foreach ($codes as $k=>$code){
                $zhus = $zhus * strlen($code);    // 注数
            }
            $total_money = $zhus * $single;
            $all_moneys += $total_money;
        }

        return $all_moneys;
    }

    /**
     * @desc 计算三字定单双每个遗漏次数统计
     */
    public static function calcDsProfit(){
        //$SscDsYls = SscDsYl::find()->where(['and', ['=','type',3], ['<=', 'history_max_miss', 80]])->all();
        $SscDsYls = SscDsYl::find()->where(['type'=>3])->all();
        $yl_nums = [0=>0,1=>0, 2=>0, 3=>0,4=>0,5=>0, 6=>0,7=>0,8=>0,9=>0, 10=>0, 11=>0]; #  遗漏次数为1、2、3、6统计

        $num_yl_static = [];
        $counts = 0;
        foreach ($SscDsYls as $key=>$sscDsYl){
            //p($sscDsYl->attributes);
            $yls = explode('-',$sscDsYl->yl_records);
            $counts += count($yls);
            //p([$sscDsYl->yl_records,'count'=>count($yls)],0);
            foreach ($yls as $yl){
                foreach ($yl_nums as $k=>$num){
                    if($yl == $k){
                        $yl_nums[$k] = $yl_nums[$k] + 1;
                    }
                }
            }
        }
        $num_yl_static = ['counts'=>$counts, 'yl_nums'=>$yl_nums];
        p($num_yl_static);
    }

    /**
     * @desc 获取条件号码个数
     * @param $data
     */
    public static function countCodes($type = 1){
        $m = \Yii::$app->cache;
        $mkey = 'KJ_CODES_1_1_1_1_1_1';
        //if(!$codesArr = $m->get($mkey)){
            $codesArr = [];
            if($type == 1){
                # 1、双重、双双重、三重、四重、三兄弟、四兄弟、四单四双
                $where = ['AND', ['=', 'code_type', 1],['OR', ['=', 'type_2', 1], ['=','type_22', 1], ['=','type_3', 1], ['=','type_4', 1], ['=','type_3b', 1], ['=','type_4b', 1], ['type_4ds'=>[1,2]]]];
            }elseif($type == 2){
                # 2、排除双重、双双重、三重、四重、三兄弟、四兄弟、四单四双
                $where = ['AND', ['=', 'code_type', 1], ['<>', 'type_2', 1], ['<>','type_22', 1], ['<>','type_3', 1], ['<>','type_4', 1], ['<>','type_3b', 1], ['<>','type_4b', 1], ['=', 'type_4ds', 0]];
            }else{
                # 3、双重
                $where = ['AND', ['=', 'code_type', 1], ['=', 'type_2', 1]];
            }
            $Num4Types = Num4Type::find()->where($where)->asArray()->all();
            //p($Num4Types);
            foreach ($Num4Types as $Num4Type){
                $codesArr[] = $Num4Type['code'];
            }
        //}
        $m->set($mkey, $codesArr, 12*60*60);

        return $codesArr;
    }

    /**
     * @desc 每天zj次数
     * @param string $date
     */
    public static function countZj($date = '2019-03-06'){
        $date_start = strtotime($date);
        $datesArr = [];
        for ($s=30; $s>0; $s--){
            $val = date('Y-m-d', $date_start - 86400*$s);
            if('2019-02-03'<$val && $val<'2019-02-11') continue;
            $datesArr[] = $val;
        }
        $profitsArr = [];
        $codesArrs = [];

        # 1、双重、双双重、三重、四重、三兄弟、四兄弟、四单四双
        # 2、排除双重、双双重、三重、四重、三兄弟、四兄弟、四单四双
        # 3、只取双重
        $typeArr = [1, 2, 3];

        foreach ($typeArr as $type) {
            $codesArr = self::countCodes($type);
            $codesArrs[$type] = $codesArr;
        }
        //p($codesArrs);
        $profits1 = 0.00;
        $profits2 = 0.00;
        $profits3 = 0.00;
        foreach ($datesArr as $date){

            $SscKjDatas = SscKjData::find()->select(['LEFT(code_str, 7) AS code_str'])->where(['date'=>$date])->asArray()->all();
            foreach ($typeArr as $type){
                $kjDatas = [];
                foreach ($SscKjDatas as $sscKjData){
                    $kjDatas[] = $sscKjData['code_str'];
                }
                $i = 0;
                //p($kjDatas);
                foreach($kjDatas as $kjData){
                    if(in_array($kjData, $codesArrs[$type])){
                        $i++;
                    }
                }
                $nums = count($codesArrs[$type]);
                $profitsArr[$date][$type]['profits'] = $i*999.5 - 59*0.1*$nums;
                $profitsArr[$date][$type]['zjCounts'] = $i;
                $profitsArr[$date][$type]['nums'] = $nums;
                $profits_fields = 'profits'.$type;
                $$profits_fields += $i*999.5 - 59*0.1*$nums;
            }
        }
        $profitsArr['总计'] = ['profits1'=>$profits1, 'profits2'=>$profits2, 'profits3'=>$profits3];


        p($profitsArr);
    }

    public static function getSDYL(){
        $where = ['AND', ['<', 'current_miss', 18], ['>', 'current_miss', 5], ['=', 'positions', '1,2,3,4'], ['=', 'LENGTH(zhi)', 4]];
        $SscDsYls = SscDsYl::find()->select(['id', 'zhi', 'current_miss'])->where($where)->asArray()->all();
        p($SscDsYls);
    }

    /**
     * @desc 根据统计type 返回名称
     * @param string $type
     * @return mixed
     */
    public static function getStaticNameByType($type = 'type_2'){

        $m = \Yii::$app->cache;
        $mkey = 'mkey_StaticNameByType';
        if(!$StaticNamesArr = $m->get($mkey)){
            $StaticNamesArr = [];
            $SscStaticVals = SscStaticVal::find()->all();
            foreach ($SscStaticVals as $sscStaticVal){
                $StaticNamesArr[$sscStaticVal->val] = $sscStaticVal->name;
            }
            $m->set($mkey, $StaticNamesArr, 300);
        }

        if($type && isset($StaticNamesArr[$type])) return $StaticNamesArr[$type];

        return $StaticNamesArr;
    }

    /**
     * @desc 四定和值遗漏统计 add 2019-03-24
     * @param int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return array|bool
     */
    public static function updateSdHzYl($lottery_type = DEFAULT_LOTTERY_TYPE, $type=1){
        $rst = [];

        $start_time = microtime(true);
        try {
            $DataDealStatus = DealDataService::judgeDealTaskStatus($lottery_type, '', $field='updateSdHzYL_status');
            if($DataDealStatus->$field == SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL){
                throw_info('未开启统计：'.DealDataService::$dealDataStatusFields[$field], 40001);
            }
            # 大数组：包括二定、三定、四定
            $updateDsData = SscSdHzVal::find()->asArray()->All();
            //$rst[$interval] = SscDataService::dsYLStatic($interval);
            //p($updateDsData);
            $qishu = SscDataService::getQiShu($lottery_type);
            foreach ($updateDsData as $Data){
                $t1 = microtime(true);
                //if($Data['id'] != 61) continue;
                $zuHes = explode(',', $Data['val']);
                $where = [ 'AND',[ 'IN', 'val', $Data['val']], ['=', 'lottery_type', $lottery_type] ];

                if(!$SscSdHzYl = SscSdHzYl::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(1)->one()){
                    $SscSdHzYl = new SscSdHzYl();
                    $SscSdHzYl->created_at = time();
                    $SscSdHzYl->val = $Data['val'];
                }
                //$count = self::getCountByHzs($zuHes);
                $count = $Data['count'];
                //if($type == 2)p([$count, $zuHes]);
                $SscSdHzYl->count = $count; # 组合总共组数
                $SscSdHzYl->updated_at = time();
                //$SscDsYl->zhi = (string)$num;
                $SscSdHzYl->theory_nums_perdate = (string)round(($count*$qishu*0.1) / 995, 2); # 理论次/天
                $SscSdHzYl->today_nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where(['date'=>date('Y-m-d'),'codes_4nums_hz'=>$zuHes, 'lottery_type'=>$lottery_type])->asArray()->limit(1)->one()['nums'];

                $SscSdHzYl->updated_at = time();
                $miss = SscDataService::getSdHzYlHistoryMiss($zuHes, $lottery_type, $Data['static_nums']);
                //p($miss);
                //if($zuHes == [5,6,7,8,9,10]) p([$zuHes,$miss, $Data['static_nums']]);
                $SscSdHzYl->static_nums = $Data['static_nums'];
                $SscSdHzYl->status = $Data['status'];
                //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
                $SscSdHzYl->current_miss = (string)$miss['current_times'];  // 1、当前遗漏次数
                $SscSdHzYl->last_time_miss = (string)$miss['last_times']; // 2、上次遗漏
                $SscSdHzYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
                $SscSdHzYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
                //$SscSdHzYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
                $SscSdHzYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
                $SscSdHzYl->history_max_miss = (string)max($miss['current_times'],$SscSdHzYl->max_miss,$SscSdHzYl->history_max_miss); // 6、历史最大遗漏
                //$SscSdHzYl->status = $Data['status']; // 7、前台显示状态
                $SscSdHzYl->lottery_type = $lottery_type; // 彩种类型
                $SscSdHzYl->update_time = date('Y-m-d H:i:s');
                //if($type == 2)p($SscSdHzYl->attributes);
                $rst = $SscSdHzYl->save();
                if(!$rst){
                    $logArr = ['attributes'=>$SscSdHzYl->attributes, 'msg'=>$SscSdHzYl->getErrors()];
                    Tool_Common::log('/data/'.__FUNCTION__,'ERR','四定和值遗漏统计', $logArr);
                }
                $t2 = microtime(true);
                Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '数据处理耗时', ['lottery_type'=>$lottery_type, 'val'=>$Data['val'], 'time_consume'=>($t2-$t1).'s']);
            }
            $dealStatus = 2;
        }catch (\Exception $e){
            $dealStatus = (strpos($e->getMessage(), '已经处理') !== false) ? 2 : ($e->getCode()>40000? 4: 3);
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '数据处理异常4', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage(), 'file'=>$e->getFile().'_'.$e->getLine()]);
        }

        $end_time = microtime(true);
        DealDataService::dealDataRecord($DataDealStatus, $field, $dealStatus, $dealDesc = ['time_consume'=>($end_time-$start_time).'s', 'deal_time'=>date('Y-m-d H:i:s')]);

        return $rst;
    }

    /**
     * @desc 获取和值组合总号码数量
     * @param $hzs
     * @return mixed
     */
    public static function getCountByHzs($hzs, $code_type = 4){
        $m = \Yii::$app->cache;
        $mkey = 'getCountByHzs_Z_'.implode(',', $hzs);
        if(!$counts = $m->get($mkey)){
            $Num4Type = Num4Type::find()->select('COUNT(id) AS count')->where(['AND', ['=', 'code_type', $code_type], ['IN', 'codes_hz', $hzs]])->limit(1)->asArray()->one();
            $counts = $Num4Type['count'];
            $m->set($mkey, $counts, 3600);
        }

        return $counts;
    }

    /**
     * @desc 获取每天开奖期数
     * @param int $lottery_type
     * @return mixed|string
     */
    public static function getQiShu($lottery_type = DEFAULT_LOTTERY_TYPE){
        $lotteryBaseInfo = CommonLotteryService::getLotteryBaseInfo($lottery_type, $useCache=0);

        return $lotteryBaseInfo['num']??288;
    }

    public static function clearDataTables(){
        $rst = ['status'=>200, 'msg'=>'处理成功！'];

        //$sqls = "show tables";
        //$tables = \Yii::$app->db->createCommand($sqls)->queryAll();// p($tables);
        $val = SystemConfig::findOne(['key'=>'clearDataTables'])->value;
        if($val != 1) return ['status'=>300, 'msg'=>'全局数据清理开关未开启'];

        $tables = [
            'lt_admin_log', 'lt_betting_records' , 'lt_ssc_ds_static', 'lt_ssc_dw_hz_static', 'lt_ssc_dws_hz_nums','lt_ssc_he9_data', 'lt_ssc_kj_data', 'lt_ssc_kj_data_3num',
            'lt_ssc_kj_data_ds','lt_ssc_sd_hz_yl','lt_ssc_static_yl','lt_static_3num_arise_perdate','lt_static_4d_profits','lt_static_4d_profits_day','lt_static_4d_profits_month',
            'lt_static_4d_profits_perdate','lt_static_code_type_arise_perdate','lt_static_hz_arise_perdate','lt_static_hz_profits','lt_static_hz_profits_perdate',
            'lt_static_per_hz_perdate_profits','lt_static_per_hz_profits','lt_static_profits','lt_user_custom_plans','lt_user_sys_plans','lt_wx_friends','lt_wx_msg_status','lt_wx_msg_types'
        ];

        foreach ($tables as $table){
            $sql = 'TRUNCATE TABLE '.$table;
            $r = \Yii::$app->db->createCommand($sql)->execute();//p($rst);
        }

        return $rst;
    }

    /**
     * @desc 获取号码的四字全导号码， 预计提供给开奖之后用，系统推荐投注号码
     * @param array $codes
     * @return array
     */
    public static function getAriseCodes($codesArr = ['1234']){
        $codesData = [];
        $m = \Yii::$app->cache;
        foreach ($codesArr as $code){
            $mkey = 'getAccountCodes_'.$code;
            if(!$data = $m->get($mkey)){
                $data = NumService::getAllCombination4($code); # 获取全倒号码
                $m->set($mkey, $data, 24 * 3600 * 30); # 30天
            }

            $codesData = array_merge($codesData, $data);
        }

        return $codesData;
    }

    /**
     * @desc 统计排除多少期内的号码投注利润
     * @param int $sumNums 最近sumNums期内利润
     * @param int $beforeQishus 取前beforeQishus号码
     * @return float
     */
    public static function calulateBeforeProfits($sumNums = 500, $beforeQishus = 277, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $last = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
        $min_id = $last['last_id'] - $sumNums + 1;
        $qs = [ '0_0_0' => 24, '0_0_1' => 12, '0_1_1' => 16, '1_1_1' => 1, ]; # type_4 type_3 type_2

        $staticQishus = ['统计最近期数'=>$sumNums, '排除前期数'=>$beforeQishus, 'y'=>0, 'n'=>0];
        $where = ['AND', ['>=', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
        $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'qihao', 'code_4n'])->where($where)->asArray()->all();
        $tzZushu = 0;
        foreach ($SscKjDatas as $SscKjData){
            $sumMinIndexId = $SscKjData['index_id'] - $beforeQishus;
            //$sumMaxIndexId = $SscKjData['index_id'] - $nBeforeQishus;
            $sumMaxIndexId = $SscKjData['index_id'] - 1;
            $where = ['AND', ['>=', 'index_id', $sumMinIndexId], ['<', 'index_id', $sumMaxIndexId], ['=', 'lottery_type', $lottery_type]];
            $beforeKjCodes = SscKjData::find()->select(['qihao', 'code_4n', 'type_4', 'type_3', 'type_2'])->where($where)->asArray()->all();
            foreach ($beforeKjCodes as $beforeKjCode){
                # 投注组数统计
                $tzZushu += $qs[$beforeKjCode['type_4'].'_'.$beforeKjCode['type_3'].'_'.$beforeKjCode['type_2']];
            }
            //p([$tzZushu, $beforeKjCode['qihao']]);
            $kjCodes = yii\helpers\ArrayHelper::getColumn($beforeKjCodes, 'code_4n');
            if(in_array($SscKjData['code_4n'], $kjCodes)){
                # 中奖
                $staticQishus['y'] += 1;
            }else{
                # 不中奖
                $staticQishus['n'] += 1;
            }
        }
        $staticQishus['tzZushu'] = $tzZushu;


        return $staticQishus;
    }

    /**
     * @desc 获取每天排除前多少利润
     * @return array
     */
    public static function getSomeDatesBeforedProfits($lottery_type = DEFAULT_LOTTERY_TYPE){

        $profits = [];
        $month = '2019-09';
        $time = strtotime($month.'-01');

        for ($i=0; $i<30; $i++){
            $date = date('Y-m-d', $time+$i*24*3600);
            $profits[$date] = round(self::getOneDateBeforeProfits($date, $beforeQishus = 400, $lottery_type), 2);
            $profits[$month] += $profits[$date];
        }

        return $profits;
    }

    /**
     * @desc 获取某一天排除掉最近$beforeQishus期的号码利润
     * @param string $date 统计日期
     * @param int $beforeQishus 排除的最近$beforeQishus期号码全倒
     * @return float
     */
    public static function getOneDateBeforeProfits($date = '2019-09-28', $beforeQishus = 400, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;

        $mkey = 'getOneDateBeforeProfits_'.$date;
        if(!$profits = $m->get($mkey)){
            $where = ['AND', ['=', "FROM_UNIXTIME(created_at,'%Y-%m-%d')", $date], ['=', 'lottery_type', $lottery_type]];
            $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'qihao', 'code_4n'])->where($where)->asArray()->all();
            $profits = 0.00;
            foreach ($SscKjDatas as $SscKjData){
                $profitsData = self::getProfitsBeforeProfitsByQihao($SscKjData['qihao'], $beforeQishus, $lottery_type);
                //p($profitsData,0);
                $profits += $profitsData['profits'];
            }
        }
        if($date < date('Y-m-d')){
            $m->set($mkey, $profits, 30*24*3600);
        }

        return $profits;
    }

    /**
     * @desc 获取某一期排除前beforeeQishu期后投注利润
     * @param $qihao
     * @param int $beforeQishus 前xx期
     * @param int $lottery_type
     * @param int $direction 1取2排除
     */
    public static function getProfitsBeforeProfitsByQihao($qihao, $beforeQishus = 400, $lottery_type = DEFAULT_LOTTERY_TYPE, $direction = 2){

        if(empty($qihao)) return [];

        $SscKjData = SscKjData::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);

        $index_id = $SscKjData->index_id;

        $sumMinIndexId = $index_id - $beforeQishus;
        $sumMaxIndexId = $index_id - 1;

        $staticQishus = ['qihao'=>$qihao, 'lottery_type'=>$lottery_type, '排除前期数'=>$beforeQishus.'期', 'y'=>0, 'n'=>0];
        $where = ['AND', ['>=', 'index_id', $sumMinIndexId], ['<=', 'index_id', $sumMaxIndexId], ['=', 'lottery_type', $lottery_type]];
        $beforeKjCodes = SscKjData::find()->select(['qihao', 'code_4n', 'type_4', 'type_3', 'type_2'])->where($where)->asArray()->all();

        //p([$qihao,$beforeKjCodes]);

        /*
        $qs = [ '0_0_0' => 24, '0_0_1' => 12, '0_1_1' => 16, '1_1_1' => 1]; # type_4 type_3 type_2
        $latelyCodesNums = 0;
        # 统计近xx期总共号码
        foreach ($beforeKjCodes as $beforeKjCode){
            $latelyCodesNums += $qs[$beforeKjCode['type_4'].'_'.$beforeKjCode['type_3'].'_'.$beforeKjCode['type_2']];
        }
        */
        //p([$latelyCodesNums, $beforeKjCodes]);

        $code_4ns = yii\helpers\ArrayHelper::getColumn($beforeKjCodes, 'code_4n'); # 近xx期开奖 四字现 code_4ns:['1234','6789']

        $latelyCodes = SscDataService::getAriseCodes($code_4ns); # 缓存开奖号码四定组合 ,最近开奖号码全倒 code_4ns:['1234','6789'] latelyCodes:['1,2,3,4', '1,2,4,3', '1,3,2,4', '1,3,4,2'.....]
        $latelyCodes = array_unique($latelyCodes);
        $latelyCodesNums = count($latelyCodes);
        //p([$beforeQishus, $latelyCodesNums]);
        /*
        */

        if($direction == 1){
            $tzNums = $latelyCodesNums; # 统计投注多少注数, 正向(取)：投注号码为最近400号码
        }else{
            $tzNums = 10000 - $latelyCodesNums; # 统计投注多少注数, 反向(除): 投注号码为出去最近400期号码后剩下的号码
        }

        //p([$SscKjData['code_4n'], $code_4ns]);
        if(!in_array($SscKjData['code_4n'], $code_4ns)){
            # 中奖
            $staticQishus['y'] = 1;
            $staticQishus['profits'] = 995 - $tzNums * 0.1;
        }else{
            # 不中奖
            $staticQishus['n'] = 1;
            $staticQishus['profits'] = 0 - $tzNums * 0.1;
        }
        $staticQishus['tzNums'] = $tzNums;

        return $staticQishus;
    }

    /**
     * @desc 获取某个号码带三兄弟组合
     * @param int $valArr 1、三现：双重+两兄弟 ['code_3n', 'type_2', 'type_2b'] 四现：双重+两兄弟 、['code_4n', 0, 'type_3b']
     * @return mixed
     */
    public static function getTypeCode($valArr = ['code_3n', 'type_2', 'type_2b']){
        if($valArr[0] == 'code_3n'){
            $codes = ['009','001','011','112','122','223','233','334','344','445','455','556','566','667','677','778','788','889','899','099'];
        }elseif($valArr[0] == 'code_4n'){
            $num = $valArr[1];
            $codesArr = [
                //['0123', '1234', '2345', '3456', '4567', '5678', '6789', '0789', '0189', '0129'],
                0 => ['0012', '0123', '0234', '0345', '0456', '0567', '0678', '0789', '0019', '0089'],
                1 => ['1123', '1234', '1345', '1456', '1567', '1678', '1789', '0189', '0119', '0112'],
                2 => ['0122', '1223', '2234', '2345', '2456', '2567', '2678', '2789', '0289', '0129'],
                3 => ['0123', '1233', '2334', '3345', '3456', '3567', '3678', '3789', '0389', '0139'],
                4 => ['0124', '1234', '2344', '3445', '4456', '4567', '4678', '4789', '0489', '0149'],
                5 => ['0125', '1235', '2345', '3455', '4556', '5567', '5678', '5789', '0589', '0159'],
                6 => ['0126', '1236', '2346', '3456', '4566', '5667', '6678', '6789', '0689', '0169'],
                7 => ['0127', '1237', '2347', '3457', '4567', '5677', '6778', '7789', '0789', '0179'],
                8 => ['0128', '1238', '2348', '3458', '4568', '5678', '6788', '7889', '0889', '0189'],
                9 => ['0129', '1239', '2349', '3459', '4569', '5679', '6789', '7899', '0899', '0199'],
            ];
            $codes = $codesArr[$num];
        }

        return $codes;
    }

    /**
     * @desc 获取一个计划当前的利润
     * @param $UserSysPlan
     * @param array $andWhere
     * @return bool|int|mixed|string|null  正负数
     */
    public static function getPlanProfits($UserSysPlan, $andWhere = [], $simple=0){
        $where = ['AND',
            ['=', 'plan_id', $UserSysPlan->id],
        ];
        if(!$simple){
            $where[] = ['=', 'is_profits_record', 1];
            $where[] = ['OR',
                ['=', 'is_simulate', 0],
                ['AND', ['=','is_simulate', 1], ['=', 'sn', BetService::$test_true_sn]],
            ];
        }
        if(!empty($andWhere)){
            $where[] = $andWhere;
        }
        $query = BettingRecords::find()->where($where);
        //$sql = $query->createCommand()->getRawSql();p($sql);
        $profits = $query->sum('profits');

        return $profits??0.00;
    }

    /**
     * @desc 获取一个计划当前的利润
     * @param $UserSysPlan
     * @param array $andWhere
     * @return bool|int|mixed|string|null
     */
    public static function getUserBetProfits($uid, $andWhere = []){
        $where = ['AND',
            ['=', 'uid', $uid],
            ['=', 'is_profits_record', 1],
            ['OR',
                ['=', 'is_simulate', 0],
                ['AND', ['=','is_simulate', 1], ['=', 'sn', BetService::$test_true_sn]],
            ],
        ];
        if(!empty($andWhere)){
            $where[] = $andWhere;
        }
        $profits = BettingRecords::find()->where($where)->sum('profits');

        return $profits;
    }

    /**
     * z账号级别的止盈止损
     * @param string $uid
     */
    public static function updateUserProfits($uid=''){
        try {
            $profits = \backend\service\SscDataService::getUserBetProfits($uid);
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
            $TzSystemsUsers->current_profits = round($profits, 2);
            if($TzSystemsUsers->current_profits == 0.00){
                $TzSystemsUsers->desc = '';
            }
            $TzSystemsUsers->save();
        }catch (\Exception $e){
            return [10000, [], $e->getMessage()];
        }

        return [0, $TzSystemsUsers, '处理成功'];
    }

    /**
     * @desc 处理止盈止损计划
     * @param int $lottery_type
     * @param string $qihao 最新已经开奖的期号
     * @return array
     */
    public static function operateProfitsPlans($lottery_type = DEFAULT_LOTTERY_TYPE, $qihao=''): array
    {
        $now_HI = date('H:i:s');
        $lotteryTypeData = LotteryTypeService::getLotteryTypeData();
        $openingTime = $lotteryTypeData[$lottery_type]['opening_time'];
        $closingTime = $lotteryTypeData[$lottery_type]['closing_time'];
        if($lottery_type==8 && $closingTime<$now_HI && $now_HI<$openingTime){
            return ['status'=>300, 'msg'=>'非开盘时间不统计'];
        }
        $RedisLock = new RedisLock();

        $start_time = microtime(true);
        $nowTime = time();
        try {
            $logArr = [];
            Tool_Common::log('opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划1', ['lottery_type'=>$lottery_type]);
            $DataDealStatus = DealDataService::judgeDealTaskStatus($lottery_type, $qihao, $field='opProfitsPlans_status');
            if($DataDealStatus->$field == SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL){
                throw_info('未开启统计：'.DealDataService::$dealDataStatusFields[$field], 40001);
            }
            # 止盈止损、翻倍止盈止损 计划
            $where = [
                'OR',
                [ 'AND', ['IN', 'plan_type', [0, 1, 3, 5]], ['=', 'status', 1], ['=', 'is_batch_simulate', 0] ],
                [ 'AND', ['>', 'take_profits', 0], ['>', 'stop_loss', 0], ['=', 'status', 1], ['=', 'is_batch_simulate', 0] ]
            ];
            Tool_Common::log('opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划2', ['lottery_type'=>$lottery_type]);

            $logArr = [];
            $current_kj_qihao = $qihao?:HN0898Service::getCurrentQihao($lottery_type);

            //$planIds = UserSysPlans::find()->select('id')->where(['status'=>1])->column();
            $planIdData = UserSysPlans::find()->select(['uid', 'id'])->where(['status'=>1])->asArray()->all();
            // 使用 ArrayHelper 提取 uid 和 id 的集合
            $userIds = array_unique(ArrayHelper::getColumn($planIdData, 'uid'));
            $planIds = array_unique(ArrayHelper::getColumn($planIdData, 'id'));
            //p([array_unique($uids), array_unique($planIds), $planIdData]);
            $planStaticProfitsData = PlanStaticProfits::find()->where(['plan_id'=>$planIds])->indexBy('plan_id')->all();

            //$bets = SscDataService::isZjBeforeData($lottery_type);

            $t1 = microtime(true);
            $UserSysPlans = UserSysPlans::find()->where($where)->andWhere(['=', 'lottery_type', $lottery_type]);
            foreach ($UserSysPlans->each(10) as $UserSysPlan){
                try {
                    $Rkey = __FUNCTION__.'_redis_op_plan_0_1_3_5_'.$lottery_type.'_'.$UserSysPlan->id;
                    if(!$RedisLock->lock($Rkey, 30)){
                        \Yii::$app->redis->expire($Rkey, 120);
                        Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '重复处理忽略1', ['lottery_type'=>$lottery_type, 'err_msg'=>'获取锁失败']);
                        continue;
                    }
                    \Yii::$app->redis->expire($Rkey, 120);
                    try {
                        $transaction = \Yii::$app->db->beginTransaction();
                        //$planStaticProfits = PlanStaticProfits::find()->where(['plan_id'=>$UserSysPlan->id])->limit(1)->one();
                        $planStaticProfits = $planStaticProfitsData[$UserSysPlan->id]??[];
                        $currentQiProfits = SscDataService::getPlanProfits($UserSysPlan, ['=', 'qihao', $current_kj_qihao]); # 计划本期利润
                        if(!empty($planStaticProfits)){
                            if($planStaticProfits->current_qihao == $current_kj_qihao){
                                throw_info('已经统计过');
                            }
                            $beforeProfits = $planStaticProfits->cut_profits;
                            $afterProfits = $beforeProfits + $currentQiProfits;
                            PlanStaticProfits::updateAll([
                                'cut_profits'=>$afterProfits,
                                'current_qihao' => $current_kj_qihao,
                                'uid' => $UserSysPlan->uid,
                                'updated_at' => $nowTime,
                                'created_at' => $nowTime,
                            ], ['plan_id'=>$UserSysPlan->id]);
                        }else{
                            $beforeProfits = 0.00;
                            $planStaticProfits = new PlanStaticProfits();
                            $setPlanStaticProfitsData = [
                                'plan_id' => $UserSysPlan->id,
                                'current_qihao' => $current_kj_qihao,
                                'uid' => $UserSysPlan->uid,
                                'cut_profits' => $currentQiProfits,
                                'updated_at' => $nowTime,
                                'created_at' => $nowTime,
                            ];
                            $planStaticProfits->setAttributes($setPlanStaticProfitsData, false);
                            $planStaticProfits->save();
                        }

                        $profits = PlanStaticProfits::find()->select(['cut_profits'])->where(['plan_id'=>$UserSysPlan->id])->scalar();
                        $transaction->commit();
                    }catch (\Exception $e){
                        $profits = SscDataService::getPlanProfits($UserSysPlan);
                        $transaction->rollBack();
                        Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '单个计划利润统计-异常', ['plan_id'=>$UserSysPlan->id, 'err_msg'=>$e->getMessage()]);
                    }
                    Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '单个计划利润统计', [
                        'plan_id' => $UserSysPlan->id,
                        'qiHao' => $current_kj_qihao,
                        'beforeProfits' => $beforeProfits,
                        'currentQiProfits' => $currentQiProfits,
                        'afterProfits' => $profits,
                    ]);

                    $maxQihao = BetService::$maxQihaoArr[$lottery_type];
                    $qihao = substr($current_kj_qihao,-3); # 最后三位
                    if(in_array($lottery_type, [8]) && $maxQihao == $qihao){
                        //$profits = 0.00; # 每天的盈利重新计算
                    }

                    //if(($UserSysPlan->take_profits!=0 && $UserSysPlan->stop_loss!=0) AND ($profits>$UserSysPlan->take_profits OR $UserSysPlan->stop_loss<(0-$profits))){
                    if($profits>=$UserSysPlan->take_profits OR $UserSysPlan->stop_loss<=(0-$profits)){
                        $UserSysPlan->status = 0;
                        Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '止盈止损-计划关闭', [
                            'plan_id' => $UserSysPlan->id,
                            'qiHao' => $current_kj_qihao,
                            'beforeProfits' => $beforeProfits,
                            'currentQiProfits' => $currentQiProfits,
                            'afterProfits' => $profits,
                            'isTakeProfits' => $profits>=$UserSysPlan->take_profits,
                            'isStopLoss' => $UserSysPlan->stop_loss<=(0-$profits),
                        ]);
                    }
                    $hzArr = json_decode($UserSysPlan->hz_Arr, 320);
                    if(isset($hzArr['filters'])){
                        $hzArr['filters']['current_kj_qihao'] = $current_kj_qihao;
                    }
                    $UserSysPlan->hz_Arr = json_encode($hzArr, 320);
                    $UserSysPlan->current_profits = $profits;
                    $saveFlag = $UserSysPlan->save();

                    $logArr['plan_1_3_5'][$UserSysPlan->id] = [
                        'saveFlag'=>$saveFlag,
                        'current_profits'=>$profits,
                        'take_profits'=>$UserSysPlan->take_profits,
                        'stop_loss'=>$UserSysPlan->stop_loss,
                        'beforeProfits' => $beforeProfits,
                        'currentQiProfits' => $currentQiProfits,
                        'afterProfits' => $profits,
                    ];
                }catch (\Exception $e){
                    $logArr['plan_1_3_5'][$UserSysPlan->id] = ['err_msg'=>$e->getMessage()];
                }
            }
            $t2 = microtime(true);
            $logArr['time_consume'] = ($t2-$t1).'s';
            Tool_Common::log('opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划3', $logArr);

            $flags = []; # 计划是否中奖标识
            # 不中倍投：翻倍计划、翻倍止盈止损，倍投 连续x期不中 决定倍数
            //$fb_plan_types = [2, 3, 4, 5, 9, 10];
            $fb_plan_types = SscDataService::$fb_plan_types;
            $logArr = [];
            $where = ['AND', ['IN', 'plan_type', $fb_plan_types], ['=', 'status', 1], ['=', 'is_batch_simulate', 0], ['=', 'lottery_type', $lottery_type]];
            $UserSysPlans = UserSysPlans::find()->where($where);
            foreach ($UserSysPlans->each(10) as $UserSysPlan){
                try {
                    $Rkey = __FUNCTION__.'_redis_op_plan_'.implode('_', $fb_plan_types).'_'.$lottery_type.'_'.$UserSysPlan->id;
                    if(!$RedisLock->lock($Rkey, 30)){
                        Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '重复处理忽略2', ['lottery_type'=>$lottery_type, 'err_msg'=>'获取锁失败']);
                        continue;
                    }
                    \Yii::$app->redis->expire($Rkey, 120);
                    $flag = SscDataService::isZjBefore($UserSysPlan->id);
                    //$flag = SscDataService::isZjBeforeNew($bets[$UserSysPlan->id]??[]);
                    $flags[$UserSysPlan->uid][$UserSysPlan->id] = $flag;
                    $originSingle = $UserSysPlan->single;

                    # 遗漏期数[不中奖期数]
                    //$lossQs = self::getLossQs($UserSysPlan->id);

                    $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['flag'] = $flag; # 中奖标识
                    //$logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['lossQs'] = $lossQs; # 遗漏期数

                    # 倍数处理，中的计划回第一个倍数
                    $singles = explode('-', trim($UserSysPlan->singles));
                    if(empty($singles)) $singles = [$UserSysPlan->single];
                    $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['singles'] = $singles; # 翻倍数据
                    $singles_count = count($singles); # 倍数个数

                    $is_init = 1; # 是否初始真实投注
                    $codes_hz = json_decode($UserSysPlan->hz_Arr, true);
                    if(isset($codes_hz['filters'])){
                        $codes_hz['filters']['current_kj_qihao'] = $current_kj_qihao;
                    }
                    if($flag == 1){ # 中奖
                        $next_single_key = 0;
                        $single = $singles[$next_single_key];
                        if(in_array($UserSysPlan->plan_type, [9, 16])){ # plan_type:遗漏倍投
                            $current_miss = 0;
                            $is_init = 1; # 等待状态
                        }elseif($UserSysPlan->plan_type == 10) { # plan_type:中则倍投，不中则回第一个倍数
                            $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $codes_hz['singles_key'], $next_single_key, $lottery_type);
                        }
                    }else{ # 不中奖
                        if($UserSysPlan->plan_type == SscDataService::PLAN_TYPE_YL_BET_SINGLES) { # 9 遗漏倍投
                            $current_miss = (int)($codes_hz['current_miss'] + 1); # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
                            if ($current_miss <= $codes_hz['bet_while_miss']) {
                                $is_init = 2; # 不中未达到遗漏期数状态 等待状态...
                                $next_single_key = 0;
                                $single = $singles[$next_single_key];
                            } else {
                                $is_init = 3; # 开始投注，正在下注状态
                                $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $codes_hz['singles_key'], $next_single_key, $lottery_type);
                                if ($codes_hz['is_init'] == 2) {
                                    $next_single_key = 1;
                                    $single = $singles[$next_single_key];
                                }
                            }
                        }elseif($UserSysPlan->plan_type == SscDataService::PLAN_TYPE_YL_BET_SINGLES_2){ # 16 plan_type:遗漏倍投2
                            $current_miss = (int)$codes_hz['current_miss'] + 1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
                            if ($current_miss <= $codes_hz['bet_while_miss']) {
                                $is_init = 2; # 不中未达到遗漏期数状态 等待状态...
                                $next_single_key = 0;
                                $single = $singles[$next_single_key];
                            } else {
                                if($current_miss>=($codes_hz['bet_while_miss']+$singles_count)){
                                    # 投完所有倍数不中，则进入等待状态
                                    $is_init = 2; # 不中未达到遗漏期数状态 等待状态...
                                    $next_single_key = 0;
                                    $single = $singles[$next_single_key];
                                    $current_miss = 0;
                                }else{
                                    $is_init = 3; # 开始投注，正在下注状态
                                    $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $codes_hz['singles_key'], $next_single_key, $lottery_type);
                                    if ($codes_hz['is_init'] == 2) {
                                        $next_single_key = 1;
                                        $single = $singles[$next_single_key];
                                    }
                                }
                            }
                        }elseif($UserSysPlan->plan_type == 10){ # plan_type:中则倍投，不中则回第一个倍数
                            $next_single_key = 0;
                            $single = $singles[$next_single_key];
                        }else{
                            $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $codes_hz['singles_key'], $next_single_key, $lottery_type);
                        }
                    }
                    $codes_hz['singles_key'] = $next_single_key;
                    if(in_array($UserSysPlan->plan_type, [9, 16])){ # plan_type:倍投、遗漏倍投
                        $codes_hz['current_miss'] = $current_miss;
                        $codes_hz['is_init'] = $is_init; # 开奖之后初始标识改成 0
                    }
                    $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
                    $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['single'] = $single; # 最新更新倍数
                    $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['before_singles_key'] = $codes_hz['singles_key']; # 更新前倍数key
                    $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['next_single_key'] = $next_single_key; # 最新即将下注的倍数key, singles的 key
                    $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['whereUpdate'] = $whereUpdate;

                    $single = (!empty($single)) ? $single : $originSingle;
                    $updateData = ['single'=>$single];
                    if(isset($codes_hz['status_val'])){ # 号码切换&倍投
                        # 号码切换
                        if($flag == 1) { # 中奖
                            $codes_hz['status_val'] = ($codes_hz['status_val'] == 1) ? 1 : 2;
                        }else{
                            $codes_hz['status_val'] = ($codes_hz['status_val'] == 1) ? 2 : 1;
                        }
                    }
                    $updateData['hz_Arr'] = json_encode($codes_hz, 320);
                    $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['updateData'] = $codes_hz;

                    $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
                    $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['rst'] = $rst;
                }catch (\Exception $e){
                    Tool_Common::log('/plan/'.__FUNCTION__.'_err', 'ERR', '计划处理异常666', ['lottery_type'=>$lottery_type, 'plan_id'=>$UserSysPlan->id, 'err_msg'=>$e->getMessage()]);
                }
            }
            $t3 = microtime(true);
            $logArr['time_consume'] = ($t3-$t2).'s';
            Tool_Common::log('opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划4', $logArr);

            # plan_type: 6:中则投，不中则不投、 8:遗漏投
            $logArr = [];
            $where = ['AND',
                ['IN', 'plan_type', [
                    SscDataService::PLAN_TYPE_SINGLES_BET_WIN,
                    SscDataService::PLAN_TYPE_YL_BET,
                    SscDataService::PLAN_TYPE_BT_SINGLES_BET,
                    SscDataService::PLAN_TYPE_SINGLES_BET_2,
                    SscDataService::PLAN_TYPE_YL_ZZ_SINGLES_BET,
                    SscDataService::PLAN_TYPE_YL_BET_SINGLES_NUM,
                    SscDataService::PLAN_TYPE_YL_START_BET_SINGLES,
                    SscDataService::PLAN_TYPE_ZZ_BET_SINGLES_2,
                    SscDataService::PLAN_TYPE_LOSS_MONEY_BET_SINGLES,
                ]],
                ['=', 'status', 1], ['=', 'lottery_type', $lottery_type]
            ];
            $UserSysPlans = UserSysPlans::find()->where($where);
            foreach ($UserSysPlans->each(10) as $UserSysPlan){
                try {
                    switch ($UserSysPlan->plan_type){
                        case self::PLAN_TYPE_SINGLES_BET_WIN: # 中则投、中则投 + 翻倍梯度
                        case self::PLAN_TYPE_BT_SINGLES_BET: # 中则波推倍投
                            $logArr['plan_type_6_10'][$UserSysPlan->id]['rst'] = OperatePlanService::operatePlans_6($UserSysPlan, $current_kj_qihao);
                            break;
                        case self::PLAN_TYPE_SINGLES_BET_2:
                            # 中则投倍投
                            $logArr['plan_type_15'][$UserSysPlan->id]['rst'] = OperatePlanService::operatePlans_15($UserSysPlan, $current_kj_qihao);
                            break;
                        case 8:
                            # 遗漏投
                            $logArr['plan_type_8'][$UserSysPlan->id]['rst'] = OperatePlanService::operatePlans_8($UserSysPlan, $current_kj_qihao);
                            break;
                        case self::PLAN_TYPE_YL_ZZ_SINGLES_BET:
                            # 遗漏中则投
                            $logArr['plan_type_17'][$UserSysPlan->id]['rst'] = OperatePlanService::operatePlans_17($UserSysPlan, $current_kj_qihao);
                            break;
                        case self::PLAN_TYPE_YL_BET_SINGLES_NUM:
                            # 遗漏x期投y期
                            $logArr['plan_type_18'][$UserSysPlan->id]['rst'] = OperatePlanService::operatePlans18($UserSysPlan, $current_kj_qihao);
                            break;
                        case self::PLAN_TYPE_YL_START_BET_SINGLES:
                            # 遗漏x期起投
                            $logArr['plan_type_19'][$UserSysPlan->id]['rst'] = OperatePlanService::operatePlans19($UserSysPlan, $current_kj_qihao);
                            break;
                        case self::PLAN_TYPE_ZZ_BET_SINGLES_2:
                            # 中则倍投2
                            $logArr['plan_type_20'][$UserSysPlan->id]['rst'] = OperatePlanService::operatePlans20($UserSysPlan, $current_kj_qihao);
                            break;
                        case self::PLAN_TYPE_LOSS_MONEY_BET_SINGLES:
                            # 区间亏损起投
                            $logArr['plan_type_21'][$UserSysPlan->id]['rst'] = OperatePlanService::operatePlans21($UserSysPlan, $current_kj_qihao);
                            break;
                    }
                }catch (\Exception $e){
                    Tool_Common::log('/plan/'.__FUNCTION__.'_err', 'ERR', '计划处理异常777', ['lottery_type'=>$lottery_type, 'plan_id'=>$UserSysPlan->id, 'err_msg'=>$e->getMessage()]);
                }
            }
            $t4 = microtime(true);
            $logArr['time_consume'] = ($t4-$t3).'s';
            Tool_Common::log('opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划5', $logArr);

            # plan_type:7 中则继续投否则反买
            $logArr = [];
            $where = ['AND', ['IN', 'plan_type', [7]], ['=', 'status', 1], ['=', 'is_batch_simulate', 0], ['=', 'lottery_type', $lottery_type]];
            $UserSysPlans = UserSysPlans::find()->where($where);
            foreach ($UserSysPlans->each(10) as $UserSysPlan){
                try {
                    $Rkey = __FUNCTION__.'_redis_op_plan_7_'.$lottery_type.'_'.$UserSysPlan->id;
                    if(!$RedisLock->lock($Rkey, 30)){
                        Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '重复处理忽略4', ['lottery_type'=>$lottery_type, 'err_msg'=>'获取锁失败']);
                        continue;
                    }
                    \Yii::$app->redis->expire($Rkey, 120);
                    $flag = SscDataService::isZjBefore($UserSysPlan->id);
                    //$flag = SscDataService::isZjBeforeNew($bets[$UserSysPlan->id]??[]);
                    $buy_type = ($flag == 1) ? $UserSysPlan->buy_type : ($UserSysPlan->buy_type == 1 ? 0 : 1);

                    $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
                    $updateData = ['buy_type'=>$buy_type];
                    $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
                    $logArr['plan_7'][$UserSysPlan->id]['updateData'] = $updateData;
                    $logArr['plan_7'][$UserSysPlan->id]['rst'] = $rst;
                }catch (\Exception $e){
                    Tool_Common::log('/plan/'.__FUNCTION__.'_err', 'ERR', '计划处理异常888', ['lottery_type'=>$lottery_type, 'plan_id'=>$UserSysPlan->id, 'err_msg'=>$e->getMessage()]);
                }            }
            $t5 = microtime(true);
            $logArr['time_consume'] = ($t5-$t4).'s';
            Tool_Common::log('opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划6', $logArr);

            # 玩法类型，号码导入:tz_type \Yii::$app->params['IMPORT_CODES_TYPES']
            $logArr = [];
            $where = ['AND', ['IN', 'tz_type', \Yii::$app->params['IMPORT_CODES_TYPES']], ['=', 'status', 1], ['=', 'is_batch_simulate', 0], ['=', 'lottery_type', $lottery_type]];
            $UserSysPlans = UserSysPlans::find()->where($where);
            $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);
            foreach ($UserSysPlans->each(10) as $UserSysPlan){
                try {
                    $Rkey = __FUNCTION__.'_redis_op_plan_'.implode('_', \Yii::$app->params['IMPORT_CODES_TYPES']).'_'.$lottery_type.'_'.$UserSysPlan->id;
                    if(!$RedisLock->lock($Rkey, 30)){
                        \Yii::$app->redis->expire($Rkey, 120);
                        Tool_Common::log('/plan/'.__FUNCTION__.$lottery_type, 'ERR', '重复处理忽略5', ['lottery_type'=>$lottery_type, 'err_msg'=>'获取锁失败']);
                        continue;
                    }
                    \Yii::$app->redis->expire($Rkey, 120);
                    $hzArr = json_decode($UserSysPlan->hz_Arr, true);
                    $flag = SscDataService::isZjBefore($UserSysPlan->id);
                    if(isset($hzArr['filters'])){
                        $hzArr['filters']['current_kj_qihao'] = $current_kj_qihao;
                    }
                    if(isset($hzArr['change_per']) && $hzArr['change_per'] == 1){ # 每期轮换
                        $turn_key = \Yii::$app->params['IMPORT_CODES_TURN'] - 1;
                        if (isset($hzArr['change_turn_pos']) && $hzArr['change_turn_pos']>0){
                            if($hzArr['change_turn_pos'] == 6){
                                if($flag == 1){
                                    $turn_key = 0; # 中回0组
                                }else{
                                    $imports = ImportPlanCodes::find()->select(['uid', 'plan_id', 'plan_id_sort_key'])->where([
                                        'AND',
                                        ['=', 'plan_id', $UserSysPlan->id],
                                        ['!=', 'codes', ''],
                                        ['=','status', 1]
                                    ])->asArray()->all();
                                    $sortKeys = yii\helpers\ArrayHelper::getColumn($imports, 'plan_id_sort_key');
                                    $current_key = array_search($hzArr['turn_key'], $sortKeys);
                                    $next_key = ($current_key+1>count($sortKeys)) ? 0 : $current_key+1;
                                    $turn_key = $sortKeys[$next_key];
                                }
                            }else{
                                # 指定位置号码数字，决定号码组数
                                //$newKjCodesStr = SscKjData::find()->where(['lottery_type'=>$lottery_type])->asArray()->orderBy(['id'=>SORT_DESC])->limit(1)->one()['code_str'];
                                $newKjCodes = explode(',', $historyKjData['code_str']);
                                $turn_key = $newKjCodes[$hzArr['change_turn_pos']-1];
                            }
                            Tool_Common::log('/plans/'.__FUNCTION__, 'INFO', '计划位置组', ['planId'=>$UserSysPlan->id, 'newKjCodes'=>$newKjCodes, 'turn_key'=>$turn_key, 'pos'=>$hzArr['change_turn_pos']-1]);
                        }elseif($hzArr['turn_key']>=$turn_key) {
                            $turn_key = 0;#非轮换0，轮换:turn_key+1
                        }else{
                            $imports = ImportPlanCodes::find()->select(['uid', 'plan_id', 'plan_id_sort_key'])->where(['AND', ['=', 'plan_id', $UserSysPlan->id], ['!=', 'codes', '']])->asArray()->all();
                            $sortKeys = yii\helpers\ArrayHelper::getColumn($imports, 'plan_id_sort_key');
                            $current_key = array_search($hzArr['turn_key'], $sortKeys);
                            $next_key = ($current_key+1>count($sortKeys)) ? 0 : $current_key+1;
                            $turn_key = $sortKeys[$next_key];
                        }
                        $hzArr['turn_key'] = $turn_key;
                        $HI = date('H:i:s');
                        if($closingTime<=$HI && $HI<$openingTime){
                            $hzArr['turn_key'] = 0; # 开盘默认从0组开始
                        }
                    }

                    $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
                    $updateData = ['hz_Arr'=>json_encode($hzArr, 320)];
                    $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
                    $logArr['plan_8'][$UserSysPlan->id]['updateData'] = $updateData;
                    $logArr['plan_8'][$UserSysPlan->id]['rst'] = $rst;
                }catch (\Exception $e){
                    Tool_Common::log('/plan/'.__FUNCTION__.'_err', 'ERR', '计划处理异常999', ['lottery_type'=>$lottery_type, 'plan_id'=>$UserSysPlan->id, 'err_msg'=>$e->getMessage()]);
                }
            }
            $t6 = microtime(true);
            $logArr['time_consume'] = ($t6-$t5).'s';
            Tool_Common::log('/plan/opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划7', $logArr);

            OperatePlanService::opProfitsPlans12_13($lottery_type); # A出x次B出y次投B、A出x次B出y次投B_2 计划处理

            OperatePlanService::opProfitsPlans14($lottery_type); # 区间遗漏投 止盈止损 计划处理
            $qihao = HN0898Service::getQihao($lottery_type);
            $t7 = microtime(true);
            $logArr['time_consume'] = ($t7-$t6).'s';
            foreach($userIds as $userId){
                # 账号级别的盈利
                \backend\service\SscDataService::updateUserProfits($userId);
            }
            $nowHIp2 = date('H:i:s', time() + 300);
            if ($closingTime < $nowHIp2 && $nowHIp2 < $openingTime) {
                //OperatePlanService::initPlanPerDate($lottery_type); // 每天收盘初始化需要初始化的计划
                push_queue(UserPlanInitJob::class, ['lottery_type'=>$lottery_type, 'queue_delay_time'=>180]);
            }
            Tool_Common::log('opProfitsPlans_'.$lottery_type, 'INFO', '处理止盈止损\倍投计划', ['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
            $dealStatus = 2;
        }catch (\Exception $e){
            $dealStatus = (strpos($e->getMessage(), '已经处理') !== false) ? 2 : ($e->getCode()>40000? 4: 3);
            Tool_Common::log('/plan/'.__FUNCTION__, 'ERR', '数据处理异常5', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage().'-File-'.$e->getFile().'--line-'.$e->getLine()]);
        }

        $end_time = microtime(true);
        DealDataService::dealDataRecord($DataDealStatus, $field, $dealStatus, $dealDesc = ['time_consume'=>($end_time-$start_time).'s', 'deal_time'=>date('Y-m-d H:i:s')]);

        return $logArr;
    }

    /**
     * @desc 查询指定计划在最近期数上了几次
     * @param $UserSysPlan
     * @param $recent_qishus - 最近多少期
     * @param int $area_bet_type 1用下注记录统计2最近开奖记录
     * @return
     */
    public static function get_area_arise_qishus($plan, $recent_qishus, $start_qihao='', $area_bet_type = 1){
        $plan_id = $plan->id;

        if($area_bet_type == 2){
            # 最近开奖统计
            $query = SscKjData::find()->select(['last_id'=>'index_id', 'code_str'])
                ->where(['lottery_type'=>$plan->lottery_type]);
            if(!empty($start_qihao)){
                $query->andWhere(['>=', 'qihao', $start_qihao]);
            }
            $last = $query->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
            $start_index_id = $last['last_id'] - $recent_qishus;
            $codes = BetService::getCodes($plan->tz_type, $plan->buy_type, json_decode($plan->hz_Arr), $plan->id); # 格式：0,0,X,X@0,2,X,X@2,0,X,X@0,4,X,X

            $where = ['AND', ['=', 'lottery_type', $plan->lottery_type], ['>', 'index_id', $start_index_id]];
            $limit = min($recent_qishus, 100); # 最多100条
            //p([$last, $codes, $limit]);
            $kjDatas = SscKjData::find()->select(['code_str', 'qihao', 'index_id'])->where($where)->orderBy(['id'=>SORT_DESC])->asArray()->limit($limit)->all();
            $zj_nums = 0;
            foreach ($kjDatas as $kjData){
                $zj_nums += OpKjService::opKjData4($codes, $kjData['code_str']);
            }
        }else{
            # 用户下注记录统计
            $where = ['AND', ['=', 'plan_id', $plan_id], ['=', 'status', 1]];
            $query = BettingRecords::find()->where($where);
            if(!empty($start_qihao)){
                $query->andWhere(['>=', 'qihao', $start_qihao]);
            }
            $BettingRecords = $query->limit($recent_qishus)->orderBy(['id'=>SORT_DESC])->all();
            $count_records = count($BettingRecords);
            if($count_records<$recent_qishus){
                # 下注记录不够汇总记录数
                return $recent_qishus;
            }
            $start_id = end($BettingRecords)->id;

            $where = array_merge($where, [['>=', 'id', $start_id], ['>', 'profits', 0]]);
            $pBettingRecords = BettingRecords::find()->where($where)->limit($recent_qishus)->orderBy(['id'=>SORT_DESC])->all();

            $zj_nums = count($pBettingRecords);
            //p([$start_id, $end_id, count($BettingRecords), 'num'=>count($pBettingRecords)]);
            return $zj_nums;
        }

        return $zj_nums;
    }

    /**
     * @desc 获取中奖组 - 时时下注判断
     * @param string $UserSysPlan_id
     * @param string $s_time
     * @param $qihao
     * @param array $zjResult
     * @return string
     */
    public static function getNewZjGroupByPlanId($UserSysPlan_id='', $s_time='', &$qihao, &$zjResult=[]){
        $ImportPlanCodes = ImportPlanCodes::find()->where(['plan_id'=>$UserSysPlan_id, 'status'=>1])->all();
        $UserSysPlan = UserSysPlans::findOne($UserSysPlan_id);
        $lottery_type = $UserSysPlan->lottery_type;
        foreach ($ImportPlanCodes as $importPlanCode){
            if($importPlanCode->plan_id_sort_key != 'arise_B_codes') continue;
            $codes = $importPlanCode->codes;
            $where = ['lottery_type'=>$lottery_type];

            $kjData = SscKjData::find()->where($where)->andWhere(['>', 'created_at', strtotime($s_time)])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            $qihao = $kjData['qihao'];
            $zjTimes = OpKjService::opKjData4($codes, $kjData['code_str']);
            if($zjTimes == 1){
                return $importPlanCode->plan_id_sort_key;
            }
        }

        return $ImportPlanCodes[0]->plan_id_sort_key;
    }

    /**
     * @desc 获取中奖组 - 根据指定期号
     * @param string $UserSysPlan_id
     * @param string $s_time
     * @param $qihao
     * @param array $zjResult
     * @return string
     */
    public static function getNewZjGroupByPlanIdB($UserSysPlan_id='', $current_qihao='', &$qihao, &$zjResult=[]){
        $ImportPlanCodes = ImportPlanCodes::find()->where(['plan_id'=>$UserSysPlan_id, 'status'=>1])->all();
        $UserSysPlan = UserSysPlans::findOne($UserSysPlan_id);
        $lottery_type = $UserSysPlan->lottery_type;
        foreach ($ImportPlanCodes as $importPlanCode){
            if($importPlanCode->plan_id_sort_key != 'arise_B_codes') continue;
            $codes = $importPlanCode->codes;
            $where = ['lottery_type'=>$lottery_type, 'qihao'=>$current_qihao];

            $kjData = SscKjData::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            $qihao = $current_qihao;
            $zjTimes = OpKjService::opKjData4($codes, $kjData['code_str']);
            if($zjTimes > 0){
                return $importPlanCode->plan_id_sort_key;
            }
        }

        return $ImportPlanCodes[0]->plan_id_sort_key;
    }

    /**
     * @desc 1、上A：A出x次B出y次投B 计划处理
     * @param int $A_x_B_y_status
     * @param array $hzArr
     * @return bool
     */
    public static function operateZjGroupA($A_x_B_y_status = 0, $plan_type=12, &$hzArr=[]){
        $rst = true;
        $hzArr['current_yl_desc'] .= '-A';
        if(in_array($A_x_B_y_status, [0, 1])){
            $hzArr['current_arise_A_times'] += 1;
            $hzArr['current_arise_B_times'] = 0;
            #$hzArr['start_bet_yl_nums'] = -1;
            $hzArr['A_x_B_y_status'] = 1;
        }elseif($A_x_B_y_status == 2){
            if($plan_type == 13){
                $hzArr['A_x_B_y_status'] = 1; #
                $hzArr['current_arise_B_times'] -= 1;
                $hzArr['current_arise_A_times'] = 1; #
                $hzArr['current_arise_B_times'] = max([$hzArr['current_arise_B_times'], 0]);
                $hzArr['current_yl_desc'] = 'A';
            }else{
                $hzArr['start_bet_yl_nums'] += 1;
            }
        }
        $hzArr['current_yl_desc'] = trim($hzArr['current_yl_desc'], '-');

        return $rst;
    }

    /**
     * @desc 1、上B：A出x次B出y次投B 计划处理
     * @param int $A_x_B_y_status
     * @param array $hzArr
     * @return bool
     */
    public static function operateZjGroupB($A_x_B_y_status = 0, $plan_type=12, &$hzArr=[]){
        $rst = true;
        if($A_x_B_y_status == 0){
            # 1、初始化
            $hzArr['current_arise_A_times'] = 0;
            $hzArr['current_arise_B_times'] = 0;
            $hzArr['current_yl_desc'] = '';
            $hzArr['start_bet_yl_nums'] = -1;
            $hzArr['A_x_B_y_status'] = 1;
        }elseif($A_x_B_y_status == 1){
            # 2、等待中
            if($hzArr['current_arise_A_times'] >= $hzArr['arise_A_times'] && $hzArr['current_arise_B_times'] < $hzArr['arise_B_times']){
                # 已经满足A的情况，开始进入B累加
                $hzArr['current_arise_B_times'] += 1;
            }

            # 中途上B，重新开始监控
            if($hzArr['current_arise_A_times']<$hzArr['arise_A_times']){
                $hzArr['current_arise_A_times'] = 0;
                $hzArr['current_arise_B_times'] = 0;
                $hzArr['current_yl_desc'] = '';
            }

            # 开始启动
            if($hzArr['current_arise_A_times'] >= $hzArr['arise_A_times'] && $hzArr['current_arise_B_times'] == $hzArr['arise_B_times']){
                $hzArr['A_x_B_y_status'] = 2;
                $hzArr['current_yl_desc'] .= '-B';
                $hzArr['start_bet_yl_nums'] += 1;
            }
        }elseif($A_x_B_y_status == 2){
            # 3、正在投
            if($plan_type == 13 && $hzArr['current_arise_A_times'] >= $hzArr['arise_A_times'] && $hzArr['current_arise_B_times'] < $hzArr['arise_B_times']){
                # 已经满足A的情况，开始进入B累加
                $hzArr['current_arise_B_times'] += 1;
            }else{
                $hzArr['current_arise_A_times'] = 0;
                $hzArr['current_arise_B_times'] = 0;
                $hzArr['current_yl_desc'] = '';
                $hzArr['start_bet_yl_nums'] = -1;
                $hzArr['A_x_B_y_status'] = 1; # -- 改成下个轮回的等待
            }
        }
        $hzArr['current_yl_desc'] = trim($hzArr['current_yl_desc'], '-');

        return $rst;
    }

    /**
     * @desc 上期是否中奖
     * @param int $plan_id
     * @return bool
     */
    public static function isZjBefore($plan_id = 0, &$recordData = ''){
        if(empty($plan_id)) return false;
        # flag 是否中奖金，中的计划回0.1、不中的计划翻倍
        $BettingRecords = BettingRecords::find()->select(['id', 'profits', 'qihao', 'status'])
            ->where(['plan_id'=>$plan_id])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();

        if(!empty($BettingRecords) && $BettingRecords['status'] != 1){
            //throw_info('未开奖暂不处理');
        }
        $recordData = [
            'record_id' => $BettingRecords['id'],
            'profits' => $BettingRecords['profits'],
            'status' => $BettingRecords['status'],
        ];
        Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '是否中奖', ['plan_id'=>$plan_id, 'bettingRecord'=>$BettingRecords]);

        if(empty($BettingRecords)) return -1;

        # 最近一期是否中中奖
        return $BettingRecords['profits']>0 ? 1 : 0;
    }

    /**
     * @desc 所有计划上期是否中奖
     * @param $planIds
     * @return array
     */
    public static function isZjBeforeData($lotteryType=DEFAULT_LOTTERY_TYPE, $planIds=Null): array
    {
        list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($lotteryType);
        $where = ['qihao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType];
        if(!empty($planIds)){
            $where['plan_id'] = $planIds;
        }

        # 最近一期是否中中奖
        return BettingRecords::find()->select(['id', 'plan_id', 'profits', 'status'])
            ->where($where)
            ->indexBy('plan_id')
            ->orderBy(['id'=>SORT_DESC])->asArray()->all();
    }

    /**
     * 上期是否中奖判断  是否中奖金，中的计划回0.1、不中的计划翻倍
     * @param $BettingRecords
     * @return int
     */
    public static function isZjBeforeNew($BettingRecords=[]): int
    {
        if(empty($BettingRecords)) return -1;

        # 最近一期是否中中奖
        return $BettingRecords['profits']>0 ? 1 : 0;
    }


    /**
     * @desc 计算某个计划多少期不中
     * @return int
     */
    public static function getLossQs($plan_id): int
    {
        $where = ['plan_id' =>$plan_id];
        $i = 0;
        if($BettingRecords = BettingRecords::find()->select(['id', 'profits'])->where($where)->asArray()->orderBy(['id'=>SORT_DESC])->limit(100)->all()){
            foreach ($BettingRecords as $BettingRecord){
                if($BettingRecord['profits']<0){
                    $i = $i + 1;
                }else{
                    return $i;
                }
            }
        }

        return $i;
    }

    /**
     * @desc 单个计划处理
     * @param string $plan_id
     * @param string $qihao 下注记录写表期号
     * @param int $is_simulate_bet
     * @return array
     */
    public static function handleOnePlanStatic($plan_id='', $qihao='', $is_simulate_bet=0){
        if(SscDataService::isCanHandleOnePlan($plan_id, $qihao)){
            return ['status'=>300, 'msg'=>'计划处理已经锁定'];
        }

        SscDataService::beforeHandleOnePlan($plan_id, $qihao); # 操作计划之前锁定

        $UserSysPlan = UserSysPlans::findOne($plan_id);
        if(empty($UserSysPlan)){
            return ['status'=>300, 'msg'=>'找不到对应计划'];
        }
        if($UserSysPlan->status != 1){
            return ['status'=>300, 'msg'=>'计划为激活不做处理'];
        }

        $rst = ['status'=>200, 'data'=>['plan_id'=>$plan_id], 'msg'=>'操作成功'];

        # 1、利润统计
        $rst['data']['profits'] = self::handleOnePlanProfits($UserSysPlan, $is_simulate_bet);

        # 2、翻倍
        $rst['data']['fan_bei'] = self::handleOnePlanFanBei($UserSysPlan, $is_simulate_bet);

        # 5、号码轮换
        $rst['data']['codes_change'] = self::handleOnePlanCodesChange($UserSysPlan, $is_simulate_bet);

        switch ($UserSysPlan->plan_type){
            case 6:
            case 8:
                # 3、中则投、遗漏投
                $rst['data']['zzt_ylt'] = self::handleOnePlanZzt($UserSysPlan, $is_simulate_bet);
                break;
            case 7:
                # 4、中则投否则反买
                $rst['data']['zzt_else_fan_mai'] = self::handleOnePlanZztElseFanMai($UserSysPlan, $is_simulate_bet);
                break;
            case 12:
            case 13:
                # 6、A、B计划类型
                $rst['data']['A_x_arise_B_y_arise'] = self::handleOneAxBy12_13($UserSysPlan, $qihao, $is_simulate_bet);
                break;
            case 14:
                # 7、A、B计划类型B
                $rst['data']['A_x_arise_B_y_arise_2'] = self::handleOneAxBy14($UserSysPlan, $qihao, $is_simulate_bet);
                break;
            default:
                break;
        }

        $afterRst = SscDataService::afterHandleOnePlan($plan_id, $qihao); # 操作计划之后解锁
        if($afterRst){
            # 记录一个计划的利润
            SscDataService::recordOnePlanProfits($UserSysPlan);

            # 操作完计划开启下一期下注计划
            $next_qihao = KjDataGet::getNextQihaoByQihao($qihao, $UserSysPlan->lottery_type);
            $flag = SscDataService::openOnePlanBetStatus($plan_id, $next_qihao);
        }
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '单个计划处理', ['plan_id'=>$plan_id, 'afterRst'=>$afterRst, 'next_qihao'=>$next_qihao, 'flag'=>$flag, 'rst'=>$rst]);

        return $rst;
    }

    /**
     * @param object $UserSysPlan
     * @return bool
     */
    public static function recordOnePlanProfits(object $UserSysPlan){
        try {
            $hzArr = yii\helpers\Json::decode($UserSysPlan->hz_Arr, 320);
            $current_kj_qihao = $hzArr['filters']['current_kj_qihao'];
            if(empty($current_kj_qihao)){
                throw_info('期号为空:'.$UserSysPlan->hz_Arr);
            }
            $PlanStaticProfits = PlanStaticProfits::findOne(['plan_id'=>$UserSysPlan->id, 'current_qihao'=>$current_kj_qihao]);
            if(!empty($PlanStaticProfits)){
                throw_info('记录已经存在');
            }
            $now_time = time();
            $PlanStaticProfits = new PlanStaticProfits();
            $setData = [
                'plan_id' => $UserSysPlan->id,
                'uid' => $UserSysPlan->uid,
                'current_qihao' => $current_kj_qihao,
                'cut_profits' => $UserSysPlan->current_profits,
                'created_at' => $now_time,
                'updated_at' => $now_time,
            ];
            $PlanStaticProfits->setAttributes($setData);
            if(!$PlanStaticProfits->save()){
                throw_info(yii\helpers\Json::encode($PlanStaticProfits->getErrors()));
            }

        }catch (\Exception $exception){
            Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '利润记录异常', ['plan_id'=>$UserSysPlan->id, 'err_msg'=>$exception->getMessage()]);
            //return false;
            p($exception->getMessage());
        }

        return true;
    }

    /**
     * @desc 计划对应期号的操作key
     * @param string $plan_id
     * @param string $qihao
     * @return string
     */
    public static function buildOneHandPlanKey($plan_id='', $qihao=''){
        $mkey = 'beforeHandleOnePlan_'.$plan_id.'_'.$qihao;

        return $mkey;
    }

    /**
     * @desc 操作计划前锁定
     * @param string $plan_id
     * @param string $qihao
     */
    public static function beforeHandleOnePlan($plan_id='', $qihao=''){
        $m = \Yii::$app->cache;
        $mkey = SscDataService::buildOneHandPlanKey($plan_id, $qihao);

        $m->set($mkey, 60);
    }

    /**
     * @desc 操作计划前锁定
     * @param string $plan_id
     * @param string $qihao
     */
    public static function afterHandleOnePlan($plan_id='', $qihao=''){
        $m = \Yii::$app->cache;
        $mkey = SscDataService::buildOneHandPlanKey($plan_id, $qihao);

        return $m->delete($mkey);
    }

    /**
     * @desc 一个计划是否可以处理
     * @param string $plan_id
     * @param string $qihao
     * @return bool
     */
    public static function isCanHandleOnePlan($plan_id='', $qihao=''){
        $m = \Yii::$app->cache;
        $mkey = SscDataService::buildOneHandPlanKey($plan_id, $qihao);

        $flag = $m->get($mkey);

        return (boolean)$flag;
    }

    /**
     * @desc 单个利润统计
     * @param object $UserSysPlan
     * @param int $is_simulate_bet
     */
    private static function handleOnePlanProfits(object $UserSysPlan, $is_simulate_bet=0){
        #$UserSysPlan = UserSysPlans::findOne($plan_id);
        # 1、利润计算 start
        $plan_id = $UserSysPlan->id;
        $where = ['AND', ['=', 'plan_id', $plan_id], ['=', 'is_profits_record', 1], ['=', 'is_batch_simulate', $is_simulate_bet]];
        $profits = BettingRecords::find()->where($where)->sum('profits');
        $lottery_type = $UserSysPlan->lottery_type;

        try {
            //$maxQihao = BetService::$maxQihaoArr[$lottery_type];
            //$current_kj_qihao = HN0898Service::getCurrentQihao($lottery_type);
            $current_kj_qihao = BettingRecords::find()->where(['plan_id'=>$plan_id, 'lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->limit(1)->one()->qihao;
            //$qihao = substr($current_kj_qihao,-3); # 最后三位
            if($profits>$UserSysPlan->take_profits OR $UserSysPlan->stop_loss<(0-$profits)){
                $UserSysPlan->status = 0;
            }
            $hzArr = json_decode($UserSysPlan->hz_Arr, 320);
            if(isset($hzArr['filters'])){
                $hzArr['filters']['current_kj_qihao'] = $current_kj_qihao;
            }
            $UserSysPlan->hz_Arr = json_encode($hzArr, 320);
            $UserSysPlan->current_profits = $profits;
            $UserSysPlan->updated_at = time();
            $saveFlag = $UserSysPlan->save();
            $logArr['plan_1_3_5'][$UserSysPlan->id] = ['saveFlag'=>$saveFlag, 'current_profits'=>$profits, 'take_profits'=>$UserSysPlan->take_profits, 'stop_loss'=>$UserSysPlan->stop_loss];
            if(!empty($saveFlag)){
                $logArr['plan_1_3_5'][$UserSysPlan->id]['err_msg'] = $UserSysPlan->getErrors();
                Tool_Common::log('/statics/'.__FUNCTION__.'_err', 'ERR', '单计划-利润统计-错误1', ['plan_id'=>$plan_id, 'err_msg'=>$UserSysPlan->getErrors()]);
            }
            $rst = ['status'=>200, 'data'=>['plan_id'=>$plan_id, 'profits'=>$profits, 'updateRst'=>$saveFlag], 'msg'=>'操作成功'];
            # 1、利润计算 end
        }catch (\Exception $exception){
            Tool_Common::log('/statics/'.__FUNCTION__.'_err', 'ERR', '单计划-利润统计-错误2', ['plan_id'=>$plan_id, 'err_msg'=>$exception->getMessage()]);
            $rst = ['status'=>200, 'data'=>['plan_id'=>$plan_id], 'msg'=>$exception->getMessage()];
        }

        return $rst;
    }

    /**
     * @desc 不中倍投：翻倍计划、翻倍止盈止损，倍投 连续x期不中 决定倍数
     * @param object $UserSysPlan
     * @param int $is_simulate_bet
     */
    private static function handleOnePlanFanBei(object $UserSysPlan, $is_simulate_bet=0){
        #$UserSysPlan = UserSysPlans::find()->where(['AND', ['=', 'id', $plan_id], ['=', 'is_batch_simulate', $is_simulate_bet]])->limit(1)->one();
        if($UserSysPlan->status != 1){
            return ['status'=>300, 'msg'=>'未激活计划不处理'];
        }
        # 1、利润计算 start
        $lottery_type = $UserSysPlan->lottery_type;
        $fb_plan_types = SscDataService::$fb_plan_types;
        if(!in_array($UserSysPlan->plan_type, $fb_plan_types)){
            return ['status'=>300, 'msg'=>'不是翻倍类型计划，不处理'];
        }

        try {
            $flag = SscDataService::isZjBefore($UserSysPlan->id);
            $flags[$UserSysPlan->uid][$UserSysPlan->id] = $flag;

            # 遗漏期数[不中奖期数]
            //$lossQs = self::getLossQs($UserSysPlan->id);

            $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['flag'] = $flag; # 中奖标识
            //$logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['lossQs'] = $lossQs; # 遗漏期数

            # 倍数处理，中的计划回第一个倍数
            $singles = explode('-', trim($UserSysPlan->singles));
            if(empty($singles)) $singles = [$UserSysPlan->single];
            $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['singles'] = $singles; # 翻倍数据

            $is_init = 1; # 是否初始真实投注
            $codes_hz = json_decode($UserSysPlan->hz_Arr, true);
            if($flag == 1){ # 中奖
                $next_single_key = 0;
                $single = $singles[$next_single_key];
                if(in_array($UserSysPlan->plan_type, [9])){ # plan_type:遗漏倍投
                    $current_miss = 0;
                    $is_init = 1;
                }elseif(in_array($UserSysPlan->plan_type, [10])) { # plan_type:中则倍投，不中则回第一个倍数
                    $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $codes_hz['singles_key'], $next_single_key, $lottery_type);
                }
                $history_max_miss = (int)$codes_hz['history_max_miss'];
            }else{ # 不中奖
                $history_max_miss = $codes_hz['history_max_miss']?(int)$codes_hz['history_max_miss']:1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
                if(in_array($UserSysPlan->plan_type, [9])) { # 遗漏倍投
                    $current_miss = $codes_hz['current_miss'] + 1; # 获取当前计划从统计开始到现在的遗漏，如果is_init = 0
                    $history_max_miss = max($current_miss, $history_max_miss);
                    if ($current_miss <= $codes_hz['bet_while_miss']) {
                        $is_init = 2; # 不中未达到遗漏期数状态
                        $next_single_key = 0;
                        $single = $singles[$next_single_key];
                    } elseif ($current_miss > $codes_hz['bet_while_miss']) {
                        $is_init = 3; # 开始投注
                        $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $codes_hz['singles_key'], $next_single_key, $lottery_type);
                        if ($codes_hz['is_init'] == 2) {
                            $next_single_key = 1;
                            $single = $singles[$next_single_key];
                        }
                    }
                }elseif(in_array($UserSysPlan->plan_type, [10])){ # plan_type:中则倍投，不中则回第一个倍数
                    $next_single_key = 0;
                    $single = $singles[$next_single_key];
                }else{
                    $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $codes_hz['singles_key'], $next_single_key, $lottery_type);
                }
            }
            $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['single'] = $single; # 最新更新倍数
            $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['before_singles_key'] = $codes_hz['singles_key']; # 更新前倍数key
            $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['next_single_key'] = $next_single_key; # 最新即将下注的倍数key, singles的 key
            if(in_array($UserSysPlan->plan_type, [9])){ # plan_type:遗漏倍投
                $codes_hz['current_miss'] = $current_miss;
                $codes_hz['is_init'] = $is_init; # 开奖之后初始标识改成 0
            }
            $codes_hz['singles_key'] = $next_single_key;
            $codes_hz['history_max_miss'] = $history_max_miss;
            $whereUpdate = ['id'=>$UserSysPlan->id ]; # 更新条件
            $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['whereUpdate'] = $whereUpdate;

            $updateData = ['single'=>$single];
            if(isset($codes_hz['status_val'])){ # 号码切换&倍投
                # 号码切换
                if($flag == 1) { # 中奖
                    $codes_hz['status_val'] = ($codes_hz['status_val'] == 1) ? 1 : 2;
                }else{
                    $codes_hz['status_val'] = ($codes_hz['status_val'] == 1) ? 2 : 1;
                }
            }
            $updateData['hz_Arr'] = json_encode($codes_hz, 320);
            $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['updateData'] = $codes_hz;

            $updateRst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $rst = ['status'=>200, 'data'=>['plan_id'=>$UserSysPlan->id, 'updateRst'=>$updateRst], 'msg'=>'操作成功'];
            $logArr['plan_'.implode('_', $fb_plan_types)][$UserSysPlan->id]['updateRst'] = $updateRst;
        }catch (\Exception $exception){
            Tool_Common::log('/statics/'.__FUNCTION__.'_err', 'ERR', '单计划-利润统计-错误2', ['plan_id'=>$UserSysPlan->id, 'err_msg'=>$exception->getMessage()]);
            $rst = ['status'=>300, 'data'=>['plan_id'=>$UserSysPlan->id], 'msg'=>$exception->getMessage()];
        }

        return $rst;
    }

    /**
     * @desc 6中则投、8:遗漏投、计划
     * @param object $UserSysPlan
     * @param int $is_simulate_bet
     */
    private static function handleOnePlanZzt(object $UserSysPlan, $is_simulate_bet=0){
        #$UserSysPlan = UserSysPlans::findOne($plan_id);
        if($UserSysPlan->status != 1){
            return ['status'=>300, 'msg'=>'未激活计划不处理'];
        }
        # 1、利润计算 start
        $lottery_type = $UserSysPlan->lottery_type;
        $zzt_plan_types = SscDataService::$zzt_plan_types;
        if(!in_array($UserSysPlan->plan_type, $zzt_plan_types)){
            return ['status'=>300, 'msg'=>'不是中则投/遗漏类型计划，不处理'];
        }
        $update_flag = true;
        try {
            $flag = SscDataService::isZjBefore($UserSysPlan->id);
            $codes_hz = json_decode($UserSysPlan->hz_Arr, true);
            if(!is_array($codes_hz)) ['status'=>300, 'msg'=>'codes_hz格式错误']; # 部分投注方式 hz_Arr 不是json 防止错误，
            if($flag == 1 OR (in_array($UserSysPlan->plan_type, [8]) && $codes_hz['current_miss']>=$codes_hz['bet_while_miss'])){ # plan_type:8、9 遗漏xx期投、遗漏xx期投
                $betStatus = 1;
            }else{
                $betStatus = 0;
            }
            if(in_array($UserSysPlan->plan_type, [8])){
                if(in_array($flag, [1, -1])){
                    $current_miss = 0;
                }else{
                    $current_miss = $codes_hz['current_miss'] + 1;
                }
                $codes_hz['current_miss'] = $current_miss;
            }
            $codes_hz['betStatus'] = $betStatus;
            $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
            $updateData = ['hz_Arr'=>json_encode($codes_hz, 320)];
            $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $logArr['6_8'][$UserSysPlan->id]['rst'] = $rst;
            $update_flag = $rst;

            $logArr['plan_'.implode('_', $zzt_plan_types)][$UserSysPlan->id]['rst'] = $rst;
        }catch (\Exception $exception){
            Tool_Common::log('/statics/'.__FUNCTION__.'_err', 'ERR', '单计划-中则投-错误2', ['plan_id'=>$UserSysPlan->id, 'err_msg'=>$exception->getMessage()]);
            $update_flag = false;
        }

        return $update_flag;
    }

    /**
     * @desc 7中则投否则反买
     * @param object $UserSysPlan
     * @param int $is_simulate_bet
     */
    private static function handleOnePlanZztElseFanMai(object $UserSysPlan, $is_simulate_bet=0){
        //$UserSysPlan = UserSysPlans::findOne($plan_id);
        if($UserSysPlan->status != 1){
            return ['status'=>300, 'msg'=>'未激活计划不处理'];
        }
        # 1、利润计算 start
        $lottery_type = $UserSysPlan->lottery_type;
        $plan_types = SscDataService::$zzt_else_fanmai_types;
        if(!in_array($UserSysPlan->plan_type, $plan_types)){
            return ['status'=>300, 'msg'=>'不是中则投否则反买，不处理'];
        }
        $update_flag = true;

        try {
            $flag = SscDataService::isZjBefore($UserSysPlan->id);
            $buy_type = ($flag == 1) ? $UserSysPlan->buy_type : ($UserSysPlan->buy_type == 1 ? 0 : 1);

            $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
            $updateData = ['buy_type'=>$buy_type];
            $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $logArr['plan_7'][$UserSysPlan->id]['updateData'] = $updateData;
            $logArr['plan_7'][$UserSysPlan->id]['rst'] = $rst;

            $logArr['plan_'.implode('_', $plan_types)][$UserSysPlan->id]['rst'] = $rst;
        }catch (\Exception $exception){
            Tool_Common::log('/statics/'.__FUNCTION__.'_err', 'ERR', '单计划-中则投-错误2', ['plan_id'=>$UserSysPlan->id, 'err_msg'=>$exception->getMessage()]);
            $update_flag = false;
        }

        return $update_flag;
    }

    /**
     * @desc 号码轮换
     * @param object $UserSysPlan
     * @param int $is_simulate_bet
     */
    private static function handleOnePlanCodesChange(object $UserSysPlan, $is_simulate_bet=0){
        #$UserSysPlan = UserSysPlans::findOne($plan_id);
        if($UserSysPlan->status != 1){
            return ['status'=>300, 'msg'=>'未激活计划不处理'];
        }
        # 1、利润计算 start
        $lottery_type = $UserSysPlan->lottery_type;
        $plan_types = \Yii::$app->params['IMPORT_CODES_TYPES'];
        if(!in_array($UserSysPlan->plan_type, $plan_types)){
            return ['status'=>300, 'msg'=>'不是号码轮换类型计划，不处理'];
        }

        $update_flag = true;
        try {
            $hzArr = json_decode($UserSysPlan->hz_Arr, true);
            if(isset($hzArr['change_per'])){ # 每期轮换
                $imports = ImportPlanCodes::find()->select(['uid', 'plan_id', 'plan_id_sort_key'])->where(['AND', ['=', 'plan_id', $UserSysPlan->id], ['!=', 'codes', '']])->asArray()->all();
                $sortKeys = yii\helpers\ArrayHelper::getColumn($imports, 'plan_id_sort_key');
                $current_key = array_search($hzArr['turn_key'], $sortKeys);
                $next_key = ($current_key+1 > count($sortKeys)) ? 0 : $current_key+1;
                $turn_key = \Yii::$app->params['IMPORT_CODES_TURN'] - 1;
                $hzArr['turn_key'] = ($hzArr['change_per']==0 OR ($hzArr['change_per'] == 1 && $hzArr['turn_key']>=$turn_key)) ? 0 : $sortKeys[$next_key];#非轮换0，轮换:turn_key+1
            }

            $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
            $updateData = ['hz_Arr'=>json_encode($hzArr, 320)];
            $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $logArr['plan_8'][$UserSysPlan->id]['updateData'] = $updateData;
            $logArr['plan_8'][$UserSysPlan->id]['rst'] = $rst;

            $logArr['plan_'.implode('_', $plan_types)][$UserSysPlan->id]['rst'] = $rst;
        }catch (\Exception $exception){
            Tool_Common::log('/statics/'.__FUNCTION__.'_err', 'ERR', '单计划-中则投-错误2', ['plan_id'=>$UserSysPlan->id, 'err_msg'=>$exception->getMessage()]);
            $update_flag = false;
        }

        return $update_flag;
    }

    /**
     * @desc A、B计划类型
     * @param object $UserSysPlan
     * @param string $current_qihao
     * @param $is_simulate_bet
     * @return array|bool
     */
    public static function handleOneAxBy12_13(object $UserSysPlan, $current_qihao='', $is_simulate_bet){

        try{
            #$UserSysPlan = UserSysPlans::findOne($plan_id);
            if(empty($UserSysPlan)){
                return false;
            }

            if($UserSysPlan->status != 1){
                return ['status'=>300, 'msg'=>'未激活计划不处理'];
            }
            if(!in_array($UserSysPlan->plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types)){
                return ['status'=>301, 'msg'=>'不是AB计划类型'];
            }
            $plan_type = $UserSysPlan->plan_type;
            $lottery_type = $UserSysPlan->lottery_type;
            $hzArr = json_decode($UserSysPlan->hz_Arr, true);
            $zj_group = SscDataService::getNewZjGroupByPlanIdB($UserSysPlan->id, $current_qihao, $qihao, $zjResult);
            $hzArr_update_before = $hzArr;
            $A_x_B_y_status = $hzArr['A_x_B_y_status']; # 状态：0初始1等待中2正在投
            //$A_x_B_y_status = $hzArr[];

            if(!$hzArr['A_x_B_y_start_time']){
                $hzArr['A_x_B_y_start_time'] = date('Y-m-d H:i:s'); # 计划最新条件起始时间
            }
            if($hzArr['A_x_B_y_status'] == 0){
                $hzArr['A_x_B_y_status'] = 1;
            }

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
                        $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
                    }
                }elseif($plan_type == 13){
                    $next_single_key = $hzArr['singles_key'];
                }
            }else{
                # 上 B
                SscDataService::operateZjGroupB($A_x_B_y_status, $plan_type, $hzArr);
                if($plan_type == 13){
                    if($A_x_B_y_status == 2 && $hzArr['A_x_B_y_status'] == 2 && ($hzArr['current_arise_B_times'] == $hzArr['arise_B_times'])) {
                        $single = OperatePlanService::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
                    }elseif ($A_x_B_y_status == 1 && $hzArr['A_x_B_y_status'] == 2 && ($hzArr['current_arise_B_times'] == $hzArr['arise_B_times'])){
                        $next_single_key = $hzArr['start_bet_yl_nums'];
                    }else{
                        $next_single_key = 0;
                        $single = $singles[$next_single_key];
                    }
                }else{
                    $next_single_key = 0;
                    $single = $singles[$next_single_key];
                }
            }
            $hzArr['singles_key'] = $next_single_key;
            $hzArr_update_after = $hzArr;
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '计划更新前后12', ['plan_id'=>$UserSysPlan->id, 'zj_group'=>$zj_group, 'qihao'=>$qihao, 'zjResult'=>$zjResult, 'hzArr_update_before'=>$hzArr_update_before, 'hzArr_update_after'=>$hzArr_update_after, 'next_single_key'=>$next_single_key, 'single'=>$single, 'singles'=>$UserSysPlan->singles]);

            $whereUpdate = ['id'=>$UserSysPlan->id]; # 更新条件
            $updateData = ['single'=>$single, 'hz_Arr'=>json_encode($hzArr, 320)];
            $rst = UserSysPlans::updateAll($updateData, $whereUpdate);
            $logArr['plan_8'][$UserSysPlan->id]['updateData'] = $updateData;
            $logArr['plan_8'][$UserSysPlan->id]['rst'] = $rst;
        }catch (\Exception $e){
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '计划更新前后22', ['plan_id'=>$UserSysPlan->id, 'qihao'=>$qihao, 'lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
        }
    }
    /**
     * @desc A、B遗漏区间投
     * @param object $UserSysPlan
     * @param string $current_qihao 已经写投注表待处理开奖的期号
     * @param $is_simulate_bet
     * @return array|bool
     */
    public static function handleOneAxBy14(object $UserSysPlan, $current_qihao='', $is_simulate_bet){

        try{
            #$UserSysPlan = UserSysPlans::findOne($plan_id);
            if(empty($UserSysPlan)){
                return false;
            }

            if($UserSysPlan->status != 1){
                return ['status'=>300, 'msg'=>'未激活计划不处理'];
            }
            if(!in_array($UserSysPlan->plan_type, [14])){
                return ['status'=>301, 'msg'=>'不是遗漏区间投计划'];
            }
            $lottery_type = $UserSysPlan->lottery_type;
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
                    $hzArr['start_qihao'] = KjDataGet::getNextQihaoByQihao($current_qihao, $lottery_type); # 当前期号，统计利润时候不包含记录的记录的期号
                    $areaBetStatus = 1;
                }
                $next_single_key = 0;
                $hzArr['area_arise_qishus'] = $area_arise_qishus;
                $logArr['area_arise_qishus'] = $area_arise_qishus;
                $logArr['bet_msg'] = '监控中-'.$bmsg.'['.$UserSysPlan->id.']';
            }else{
                $profits = SscDataService::getPlanProfits($UserSysPlan, ['>=', 'qihao', $hzArr['start_qihao']]); # 一个计划当前利润
                $hzArr['current_area_profits'] = $profits;
                $bmsg = '不符合止盈'.$hzArr['area_profits'].'止损'.$hzArr['area_loss'];
                if($profits<0 && $area_loss<(0-$profits)){
                    $bmsg = '符合止损:'.$area_loss.'<('.(0-$profits).')';
                    $areaBetStatus = 0;
                    $hzArr['current_area_profits'] = 0.00;
                    $hzArr['start_qihao'] = KjDataGet::getNextQihaoByQihao($current_qihao, $lottery_type); # 重新设置开始计算期号，避免无时间间隔的连续止损，大遗漏倍投问题
                    $next_single_key = 0; # 止损，倍数重新
                }else{
                    if($profits>$area_profits){
                        $bmsg = '符合止赢:'.$profits.'>'.$area_profits;
                        $areaBetStatus = 0;
                        $hzArr['area_arise_qishus'] = 0;
                        $hzArr['current_area_profits'] = 0.00;
                        $hzArr['start_qihao'] = KjDataGet::getNextQihaoByQihao($current_qihao, $lottery_type); # 重新设置开始计算期号，避免大遗漏倍投问题
                    }
                    $isZjBefore = SscDataService::isZjBefore($UserSysPlan->id);
                    $next_single_key = (int)$hzArr['singles_key'];
                    if(!$isZjBefore){
                        OperatePlanService::getPlanNextSingle($UserSysPlan->id, $hzArr['singles_key'], $next_single_key, $lottery_type);
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
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '计划更新前后23', $logArr);
        }catch (\Exception $e){
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '计划更新前后24', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
        }
    }

    /**
     * @description 某个计划利润统计
     * @param int $hezhi
     * @param $periodsArr
     * @param $positions
     * @param int $interval
     * @return mixed
     */
    public static function getPlanChartsData($plan_id = 981, $interval = 100){
        $data['xAxis'] = [ 'data'=>[] ];    // 期号
        $series = [];
        $times = [6=>0.07, 7=>0.08, 8=>0.09, 9=>0.10, 10=>0.09, 11=>0.08, 12=>0.07];
        $start_time = strtotime('2018-01-01 00:00:00');
        $end_time = date('Y-m');
        $periodsArr = [];
        for ($i=0; $i<120; $i++){
            $end = date('Y-m', strtotime('+'.$i.' month', $start_time));
            if($end > $end_time) break;
            $periodsArr[] = $end;
        }
        //p($periodsArr);

        $data['range'] = 35000;
        foreach ($periodsArr as $periods){
            $where = ['AND', ['=', 'plan_id', $plan_id], ['>=', 'static_time', $periods]];
            $fields = ['id', 'plan_id','static_time', 'qihao', 'cut_profits'];
            $datas = StaticProfits::find()->select($fields)->where($where)->limit($interval)->orderBy('qihao DESC')->all();
            $tmpData = [];
            $tmpData['name'] = $periods.'月';
            $tmpData['type'] = 'line';
            $tmpData['stack'] = '次数';
            $tmpData['symbolSize'] = 'symbolSize';
            $tmpData['smooth'] = true;
            $numsData = [];
            $datas = array_reverse($datas);
            foreach ($datas as $key=>$d){
                if($key == 0){
                    $dif_cut_profits = 0 - $d->cut_profits;
                }
                //!in_array($d->qihao, $data['xAxis']['data']) && $data['xAxis']['data'][] = $d->qihao;
                !in_array($key, $data['xAxis']['data']) && $data['xAxis']['data'][] = $key;
                //$numsData[] = $d->cut_profits - ceil($periods * $times[$plan_id]);
                $numsData[] = [$key, $d->cut_profits + $dif_cut_profits];
                //$numsData[] = $d->cut_profits + $dif_cut_profits;
            }
            $tmpData['data'] = $numsData;
            $series[] = $tmpData;
            $data['series'] = $series;
            unset($tmpData);
        }
        //p($data);
        //$data['xAxis']['data'] = array_reverse($data['xAxis']['data']);
        return $data;
    }

    /**
     * @desc 获取投注组数
     * @param string $codes
     * @return int
     */
    public static function getBetNums($codes = ''){
        $nums = 0;
        # codes :X,02468,02468,13579@02468,X,02468,13579@02468,02468,X,13579@X,02468,02468,01234@02468,X,02468,01234@02468,02468,X,01234@X,02468,01234,13579@02468,X,01234,13579
        //$codes = '02468,X,02468,13579@X,02468,02468,13579@02468,02468,X,13579@X,02468,02468,01234@02468,X,02468,01234@02468,02468,X,01234@X,02468,01234,13579@02468,X,01234,13579';

        $Arrs = explode('@', $codes);
        foreach ($Arrs as $arr){
            $arr = trim(str_replace("X,", '', ','.$arr.','), ',');
            $tmpCodes = explode(',', $arr);
            $n = 1;
            foreach ($tmpCodes as $tmpCode){
                $n = $n * strlen($tmpCode);
            }
            $nums = $nums + $n;
        }

        return $nums;
    }

    /**
     * @desc 获取号码的定位类型
     * @param string $codes
     * @return int
     */
    public static function getPlaywayByCodes($codes = ''){
        //$codes = '02468,3,02468,13579@X,02468,02468,13579@02468,02468,X,13579@X,02468,02468,01234@02468,X,02468,01234@02468,02468,X,01234@X,02468,01234,13579@02468,X,01234,13579';
        $playway = 3;
        $Arrs = explode('@', $codes);
        $data = $Arrs[0];
        $XNums = array_count_values(explode(',', $data))['X'];
        $playway = $playway - $XNums;

        return $playway;
    }

    /**
     * @desc 配数每期对错处理
     * @param int $lottery_type
     * @return array
     */
    public static function staticPeiShuTrueFalse($lottery_types = []){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $lottery_types = empty($lottery_types) ? StaticService::getLotteryTypes() : $lottery_types;
        foreach ($lottery_types as $lottery_type){
            $isEmpty = SscDataService::getPeiShuIsEmpty($lottery_type);
            if($isEmpty){
                $staticStartDate = date("Y-m-d", time()-86400*2);
                $where = ['AND', ['=', 'lottery_type',$lottery_type], ['>=', 'date', $staticStartDate]];
                $SscKjDatas = SscKjData::find()->select(['code_4n_str', 'date', 'code_str', 'qihao'])->where($where)->orderBy(['qihao'=>SORT_ASC])->asArray()->all();
                foreach ($SscKjDatas as $sscKjData){
                    $rst[$sscKjData['qihao']] = SscDataService::setOnePeiShuTrueFalse($lottery_type, $sscKjData);
                }
            }else{
                $sscKjData = SscKjData::find()->select(['code_4n_str', 'date', 'code_str', 'qihao'])->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
                $rst = SscDataService::setOnePeiShuTrueFalse($lottery_type, $sscKjData);
            }
            Tool_Common::log('staticPerShuTrueFalse', 'INFO', '配数每期对错处理', ['rst'=>$rst]);
        }

        return $rst;
    }

    /**
     * @desc 设置单个配数对错
     * @param int $lottery_type
     * @param array $sscKjData
     */
    public static function setOnePeiShuTrueFalse($lottery_type = DEFAULT_LOTTERY_TYPE, $sscKjData = []){
        $setDatas = [];
        $qihao = $sscKjData['qihao'];
        $code_4n_str = $sscKjData['code_4n_str'];
        $kj_code = $sscKjData['code_str'];
        $date = $sscKjData['date'];
        $peiShus = StaticService::getAllPeiShu();
        if(!$row = StaticPeiShuCodeTrueFalse::find()->where(['lottery_type'=>$lottery_type, 'qihao'=>$qihao])->one()){
            $row = new StaticPeiShuCodeTrueFalse();
            $setDatas = array_merge($setDatas, [
                'lottery_type' => $lottery_type,
                'qihao' => $qihao,
                'date' => $date,
                'kj_code' => $kj_code,
            ]);
        }else{
            return ['status'=>300, 'msg'=>'已统计过该期号'];
        }
        $setDatas['updated_at'] = time();

        foreach ($peiShus as $peiShu){
            if(strpos($peiShu, '_') !== false){
                $ps = explode('_', $peiShu);
                $codes_hz = ['ps_1'=>$ps[0], 'ps_2'=>$ps[1]];
            }else{
                $codes_hz = ['type_'.$peiShu=>1];
            }
            //$codes = NumService::getCodesKuaiXuan($codes_hz, $code_type=4);p(['peiShu'=>$peiShu, $codes_hz, 'codes'=>$codes]);
            $codes = SscDataService::getCacheCodeByCodeHz($codes_hz, $code_type=4);
            $setDatas = array_merge($setDatas, [
                'code_'.$peiShu => in_array(substr($kj_code, 0,7), $codes) ? 1 : 0,
            ]);
        }
        $row->setAttributes($setDatas);
        if(!$flag = $row->save()){
            return['msg'=>$row->getErrors()];
        }

        return ['qihao'=>$qihao, 'flag'=>$flag];
    }

    /**
     * @desc 根据条件获取号码 - md5 key 缓存
     * @param array $codes_hz
     * @param int $code_type
     * @return array|mixed
     */
    public static function getCacheCodeByCodeHz($codes_hz = [], $code_type=4){
        $m = \Yii::$app->cache;
        $mkey = 'getCacheCodeByCodeHz_'.md5(json_encode($codes_hz).'_'.$code_type);

        if(!$codes = $m->get($mkey)){
            $codes = NumService::getCodesKuaiXuan($codes_hz, $code_type=4);//p(['peiShu'=>$peiShu, $codes_hz, 'codes'=>$codes]);
            $m->set($mkey, $codes, 86400);
        }

        return $codes;
    }

    /**
     * @desc 配数对错统计是否为空
     * @return int
     */
    public static function getPeiShuIsEmpty($lottery_type = DEFAULT_LOTTERY_TYPE){
        $isEmpty = 1;
        $r = StaticPeiShuCodeTrueFalse::find()->where(['lottery_type'=>$lottery_type])->limit(1)->one();
        if(!empty($r)){
            $isEmpty = 0;
        }

        return $isEmpty;
    }

    /**
     * @param int $lottery_type
     * @return array
     */
    public static function staticPeiShuProfitsSection($lottery_type=DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        return $rst;
    }

    /**
     * @desc 计划脚本实时统计利润
     * @param int $lottery_type
     * @return array
     */
    public static function cronStaticPeiShuProfits($lottery_type=DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $time_HI = date('H:i');
        $pre_date = date('Y-m-d', time()-86400);
        $date = date('Y-m-d');
        if($lottery_type==6){
            if('00:00'<$time_HI && $time_HI<'02:05'){
                $date = $pre_date;
            }
        }else{
            if('00:00'<$time_HI && $time_HI<'05:05'){
                $date = $pre_date;
            }
        }
        $rst['data'] = SscDataService::staticPeiShuDateProfits($lottery_type, $date);

        return $rst;
    }

    /**
     * @desc 配数每天利润统计
     * @param string $date
     * @param int $lottery_type
     * @return array
     */
    public static function staticPeiShuDateProfits($lottery_type=DEFAULT_LOTTERY_TYPE, $date = ''){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        if(empty($date)) $date = date('Y-m-d');

        $where = ['AND', ['=', 'lottery_type', $lottery_type]];
        if(in_array($lottery_type, [1, 17])){
            $where[] = ['=', 'date', $date];
        }else{
            $date_s = str_replace('-', '', $date);
            $where[] = ['LIKE', 'qihao', $date_s.'%', false];
        }

        if($lottery_type==8){
            $sort_num = 3;
            $where = array_merge($where, [['>', 'RIGHT(qihao,'.$sort_num.')', '108'], ['<', 'RIGHT(qihao,'.$sort_num.')', '48']]);
        }else{
            $sort_num = 2;
        }
        $query = SscKjData::find()->select(['date', 'code_str'=>'LEFT(code_str,7)', 'qihao', 'sort_qihao'=>'RIGHT(qihao,'.$sort_num.')'])->where($where)->orderBy(['qihao'=>SORT_ASC]);
        $SscKjDatas = $query->asArray()->all();
        $kjDatas = yii\helpers\ArrayHelper::getColumn($SscKjDatas, 'code_str');
        $peiShus = StaticService::getAllPeiShu();

        $time = time();
        $row = StaticPeiShuCodeDateProfits::findOne(['lottery_type'=>$lottery_type, 'date'=>$date]);
        $setDatas = [];
        if(empty($row)){
            $row = new StaticPeiShuCodeDateProfits();
            $setDatas = [
                'date' => $date,
                'lottery_type' => $lottery_type,
                'create_time' => $time,
            ];
        }
        $setDatas['updated_at'] = $time;
        $codes_fields = [];
        foreach ($peiShus as $peiShu){
            if(strpos($peiShu, '_') !== false){
                $ps = explode('_', $peiShu);
                $codes_hz = ['ps_1'=>$ps[0], 'ps_2'=>$ps[1]];
            }else{
                $codes_hz = ['type_'.$peiShu=>1];
            }
            $codes = SscDataService::getCacheCodeByCodeHz($codes_hz, $code_type=4);
            $codes_fields['code_'.$peiShu] = $codes;
        }
        //p(['kjDatas'=>$kjDatas, 'codes'=>$codes]);
        //p($codes_fields);

        $zjTimes = [];
        foreach ($codes_fields as $field=>$codes){
            $zjTimes[$field] = 0;
            foreach ($kjDatas as $kjData){
                if(in_array($kjData, $codes)){
                    $zjTimes[$field] += 1;
                }
            }
        }
        foreach ($zjTimes as $field=>$zjTime){
            //p([$zjTimes[$field]*980, count($codes_fields[$field]) * 0.1*count($kjDatas)]);
            $setDatas = array_merge($setDatas, [
                $field => $zjTimes[$field]*995 - count($codes_fields[$field]) * 0.1 * count($kjDatas), # 中奖 - 成本
            ]);
        }
        $row->setAttributes($setDatas);
        if(!$row->save()){
            return ['status'=>300, 'msg'=>$row->getErrors()];
        }

        return $rst;
    }

    /**
     * @desc
     * @param int $lottery_type
     * @return array
     */
    public static function staticPeiShuDate($lottery_type=DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        if($lottery_type==8){
            $start_date = '2022-01-01';
            $end_date = '2022-12-10';
            $dateArr = StaticService::getStartAndEndDate($start_date, $end_date);
        }elseif(in_array($lottery_type, [1, 17])){
            $SscKjData = SscKjData::find()->select(['date'])->where(['lottery_type'=>$lottery_type])->asArray()->all();
            $dateArr = \yii\helpers\ArrayHelper::getColumn($SscKjData, 'date');
        }
        foreach ($dateArr as $date){
            $rst['data'][$date] = SscDataService::staticPeiShuDateProfits($lottery_type, $date);
        }

        return $rst;
    }

    /**
     * @desc 指定日期利润统计时间区间
     * @param int $lottery_type
     * @param string $date
     * @return array
     */
    public static function getStaticStartAndEndDate($lottery_type=DEFAULT_LOTTERY_TYPE, $date=''){
        if(empty($date)) $date = date('Y-m-d');
        $next_date = date('Y-m-d', strtotime($date.'00:00:00') + 86400);
        if($lottery_type==8){
            $start_time = $date.' '.'09:00:00';
            $end_time = $next_date.' 04:05:00';
        }else{
            $start_time = $date.' '.'10:00:00';
            $end_time = $next_date.' 02:05:00';
        }

        return ['start_time'=>$start_time, 'end_time'=>$end_time];
    }

    /**
     * @desc 是否可以下注
     * @param $plan_id
     * @param $current_qihao
     * @return bool
     */
    public static function isCanBet($plan_id, $current_qihao): bool
    {
        $mkey = CacheKeyService::planBetKey($plan_id, $current_qihao);
        $flag = commonRedis()->get($mkey);

        return (boolean)$flag;
    }

    /**
     * @param $plan_id
     * @param $current_qihao
     * @return bool
     */
    public static function openOnePlanBetStatus($plan_id, $current_qihao): bool
    {
        $mkey = CacheKeyService::planBetKey($plan_id, $current_qihao);
        $flag = commonRedis()->get($mkey);
        if(empty($flag)) {
            $flag = commonRedis()->setex($mkey, 12 * 3600, 1);
        }

        return $flag;
    }

    /**
     * @desc 获取号码的展示 0,0,0,X@0,0,1,X@0,0,5,X --> 000X,001X,005X
     * @param string $str
     * @return mixed|string|string[]
     */
    public static function getCodesDesc($str=''){

        if(strpos($str, '@') !== false){
            $str = str_replace('@', ',',str_replace(',','', $str));
        }

        return $str;
    }

    /**
     * @desc 记录处理数据任务
     * @param $lottery_type
     * @param string $qihao 开奖号码对应的期号
     * @return array
     */
    public static function insertDealDataTask($lottery_type, $qihao=''): array
    {
        try {
            if(empty($qihao)){
                $SscKjDataQuery = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->limit(1);
                $SscKjData = $SscKjDataQuery->one();
                $qihao = $SscKjData->qihao;
            }

            $where = ['lottery_type'=>$lottery_type, 'qihao'=>$qihao];
            $DataDealStatus = DataDealStatus::findOne($where);
            if(!empty($DataDealStatus)){
                throw new \Exception('数据处理任务记录已存在'.$lottery_type.'_'.$qihao);
            }
            $LotteryDataDealStatus = LotteryDataDealStatus::findOne(['lottery_type'=>$lottery_type]);

            $now_time = time();
            $setDatas = [
                'status' => SscDataService::DEAL_DATA_STATUS_PENDDING, # 所有数据处理状态
                'qihao' => $qihao, # 期号
                'lottery_type' => $lottery_type,
                # A每天四定利润统计状态
                'static4dPerDateProfits_status' => $LotteryDataDealStatus->static4dPerDateProfits_status==0 ? SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL : SscDataService::DEAL_DATA_STATUS_PENDDING,
                # B单双处理状态
                'updateDs_status' => $LotteryDataDealStatus->updateDs_status==0 ? SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL : SscDataService::DEAL_DATA_STATUS_PENDDING,
                # C单双遗漏处理状态
                'updateDsYL_status' => $LotteryDataDealStatus->updateDsYL_status==0 ? SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL : SscDataService::DEAL_DATA_STATUS_PENDDING,
                # D单双遗漏处理状态
                'update3NumYL_status' => $LotteryDataDealStatus->update3NumYL_status==0 ? SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL : SscDataService::DEAL_DATA_STATUS_PENDDING,
                # E单双遗漏处理状态
                'updateSdHzYL_status' => $LotteryDataDealStatus->updateSdHzYL_status==0 ? SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL : SscDataService::DEAL_DATA_STATUS_PENDDING,
                # F投注计划处理状态
                'opProfitsPlans_status' => $LotteryDataDealStatus->opProfitsPlans_status==0 ? SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL : SscDataService::DEAL_DATA_STATUS_PENDDING,
                'created_at' => $now_time,
                'updated_at' => $now_time,
            ];
            if(in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES)){
                $nextQihao = (int)$qihao + 1;
                $setDatas = array_merge($setDatas, [
                    'next_qihao' => (string) $nextQihao,
                    'status' => SscDataService::DEAL_DATA_STATUS_SUCCESS
                ]);
            }
            $DataDealStatus = new DataDealStatus();
            $DataDealStatus->setAttributes($setDatas);
            if(!$DataDealStatus->save()){
                throw new \Exception(json_encode($DataDealStatus->getErrors(), 320));
            }

        }catch (\Exception $e){
            Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '数据处理任务写入异常', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'err_msg'=>$e->getMessage()]);
            return [10000, $e->getMessage()];
        }

        return [0, $qihao];
    }

}