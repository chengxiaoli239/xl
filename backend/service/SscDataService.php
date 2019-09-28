<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\AdminLog;
use backend\models\Num4Type;
use backend\models\searchs\SscDwsHzNums;
use backend\models\Ssc3numYl;
use backend\models\SscDsYl;
use backend\models\SscDwHzStatic;
use backend\models\SscKjData;
use backend\models\SscKjData3num;
use backend\models\SscKjDataDs;
use backend\models\SscSdHzVal;
use backend\models\SscSdHzYl;
use backend\models\SscStaticVal;
use backend\models\SscStaticYl;
use backend\models\SystemConfig;
use backend\models\ThreeNum;
use backend\models\WxFriends;
use common\service\CommonService;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use backend\models\SscDwHzYl;
use izyue\admin\models\Log;
use  yii;

class SscDataService extends BaseService {

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
            $SscDwHzStatic = SscYLStatic::findOne(['positions'=>implode(',',$zuHe),'periods'=>$interval]);
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
    public static function updateDsData($lottery_type = DEFAULT_LOTTERY_TYPE){
        $mkey = 'DS_COUNT_NUMS_'.$lottery_type.'_05';
        $m = \Yii::$app->cache;
        if(!$qihao = $m->get($mkey)){
            $qihao = self::getMinStaticQihao($lottery_type);
        }

        $next_qihao = KjDataGet::getNextQihaoByQihao($qihao, $lottery_type);
        $last_qihao = SscDataService::getKjDataLastQihao($lottery_type);

        //p([$next_qihao, $last_qihao]);
        if($next_qihao<=$last_qihao){
            $new_qihao = SscKjData::find()->where(['qihao'=>$next_qihao, 'lottery_type'=>$lottery_type])->one()->qihao;
            if(!$new_qihao){ # 防止官网某一期不开的情况, 自动获取开奖表下一期的开奖号码
                $new_qihao = SscKjData::find()->where(['AND', ['>', 'qihao',$qihao], ['=', 'lottery_type', $lottery_type]])->orderBy('id ASC')->limit(1)->one()->qihao;
            }
            $flag = SscDataService::insertSscKjDataDs($new_qihao, $lottery_type);
            $m->set($mkey, $new_qihao, 24*60*60);
        }
        //p([$qihao, $new_qihao, $next_qihao, $last_qihao, $lottery_type]);

        return $flag;

    }

    public static function getMinStaticQihao($lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 120){
        $last_id = SscDataService::getKjDataLastId($lottery_type);
        $qihao = SscKjData::find()->where(['AND',['=', 'lottery_type', $lottery_type], ['>', 'id', $last_id-$recently]])->limit(1)->one()->qihao;

        return $qihao;
    }
    /**
     * @desc 更新单双
     * @param $lottery_type  彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return bool
     */
    public static function update3NumData($lottery_type = DEFAULT_LOTTERY_TYPE){
        $mkey = 'CODE_COUNT_3NUMS_'.$lottery_type.'_04';
        $m = \Yii::$app->cache;
        if(!$qihao = $m->get($mkey)){
            $qihao = self::getMinStaticQihao($lottery_type);
        }

        $next_qihao = KjDataGet::getNextQihaoByQihao($qihao, $lottery_type);
        $last_qihao = SscDataService::getKjDataLastQihao($lottery_type);

        //p([$next_qihao, $last_qihao, $qihao, $lottery_type],0);

        if($next_qihao<=$last_qihao){
            $new_qihao = SscKjData::find()->where(['qihao'=>$next_qihao, 'lottery_type'=>$lottery_type])->one()->qihao;
            $flag = SscDataService::insertSscKjData3Num($new_qihao, $lottery_type);
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
                $flag = SscDataService::insertSscDwsHzNums($lottery_type, $interval, $new_qihao, $id);
                $logArr['flag'] = $flag;
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/SscDwsHzNums','INFO','统计区间某和值出现次数', $logArr);
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

        //self::insertSscDwsHzNums($interval, $max_qihao);


        return $rst;
    }

    /**
     * @desc 添加和值区间统计记录
     * @param int $interval
     */
    public static function insertSscDwsHzNums($lottery_type = DEFAULT_LOTTERY_TYPE, $interval = 20, $qihao = '', $id = ''){
        //p([$interval, $qihao, $id],0);
        $zuHes = [ [1,2], [1,3], [1,4], [2,3], [2,4], [3,4] ];
        # 每期记录前多少期的和值统计
        $numsArr = [6,7,8,9,10,11,12];  // [8,9,10,11,12,13];  // 和值
        //$newRecord = SscKjData::find()->select(['qihao','code_str'])->orderBy('id DESC')->asArray()->limit(1)->one();
        $last_id = SscDataService::getKjDataStartId($lottery_type, $interval, $qihao);
        //p($last_id,0);
        foreach ($zuHes as $key => $zuHe) {
            $position = implode(',', $zuHe);
            $field = 'code_'.implode('_',$zuHe);
            foreach ($numsArr as $zhi) {
                $start_id = $last_id;
                $end_id = $last_id + $interval;
                $nums = SscKjData::find()->select('COUNT(id) as nums')->where([$field=>$zhi])->andWhere(['between', 'id', $start_id, $end_id ])->asArray()->one()['nums'];
                //p(['start_id'=>$start_id, 'end_id'=>$end_id, 'nums'=>$nums,$field=>$zhi]);
                $opData = [
                    'hezhi' => $zhi,
                    'nums' => $nums,
                    'positions'=>$position,
                    'periods'=>$interval,
                    'qihao' => $qihao,
                    'updated_at' => time(),
                ];
                $where = ['qihao'=>(string)$qihao,'positions'=>$position,'periods'=>$interval, 'hezhi'=>$zhi ];
                $SscDwsHzNums = SscDwsHzNums::findOne($where);
                if(!$SscDwsHzNums){
                    $SscDwsHzNums = new SscDwsHzNums();
                    $opData['created_at'] = time();
                }
                $SscDwsHzNums->setAttributes($opData);
                $rst = $SscDwsHzNums->save();
                $logArr = ['opData'=>$opData, 'rst'=>$rst,'nums'=>$nums, 'where'=>$where, 'id'=>$id];
                if(!$rst){
                    $logArr['msg'] = $SscDwsHzNums->getErrors();
                    Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/static_SscDwsHzNums','INFO','统计区间某个号码出现的次数', $logArr);
                }
            }
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
        $last_id = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['max(index_id) as last_id'])->limit(1)->asArray()->one()['last_id'];

        return $last_id;
    }

    /**
     * @description 最后顺序id
     * @param int $interval
     */
    public static function getKjDataLastIndexId($lottery_type = DEFAULT_LOTTERY_TYPE){
        $last_id = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['max(index_id) as index_id'])->asArray()->one()['index_id'];

        return $last_id;
    }

    /**
     * @description 给定期数获取起始id
     * @param $lottery_type
     * @param int $interval
     */
    public static function getKjDataLastQihao($lottery_type = DEFAULT_LOTTERY_TYPE){
        $last_qihao = SscKjData::find()->select(['max(qihao) as last_qihao'])->where(['lottery_type'=>$lottery_type])->asArray()->one()['last_qihao'];

        return $last_qihao;
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
    public static function updateHeZhiYL(){
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
                $miss = SscDataService::getDwHistoryMiss($num,$position); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
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
                    Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/static_SscDwsHzNums','INFO','统计号码出现次数', $logArr);
                }
            }

        }

        return $rst;
    }

    /**
     * @description 返回历史和值遗漏
     * @param $num
     * @param $position
     * @param $recently 多少期内，默认为200
     * @return array
     */
    public static function getDwHistoryMiss($num, $position, $recently = 200){
        $times = 0;
        $field = 'code_'.str_replace(',','_',$position);
        $last = SscKjData::find()->select(['max(index_id) as last_id'])->asArray()->one();
        $min_id = $last['last_id'] - $recently - 1;
        $where = ['AND',['=',$field,$num],['>','id', $min_id]];
        //$where = "$field=$num AND id>$min_id";
        $SscKjData = SscKjData::find()->select(['id', 'index_id','qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        if(count($SscKjData)>1){
            $times = $SscKjData[0]->id - $SscKjData[1]->id - 1;  // 上次遗漏次数
        }

        # 最大遗漏期间计算 start
        $tmpKjData = $SscKjData;
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
                if(in_array($r['id'], $keyArr)){
                    $tmpArr[] = $r['qihao'];
                }
            }
            $max_range = $tmpArr[1].'-'.$tmpArr[0];  // 近200期内最大遗漏
            $yl_str = implode('-',$range);
            # 最大遗漏期间计算 end
            //p([$field=>$num,$min_id,'times'=>$times,$SscKjData[0]->id, $SscKjData[1]->id,$max_range]);
        }else{
            $max_range = $SscKjData[1]['qihao'] ."-". $SscKjData[0]['qihao'];
        }
        $last_time_miss_range = $SscKjData[1]['qihao'] ."-". $SscKjData[0]['qihao'];
        $current_times = $last['last_id'] - $SscKjData[0]->id;

        return [
            'current_times' => $current_times,    // 当前遗漏次数
            'times' => $times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss,   // 近200期内的最大遗漏
            'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'yl_str' => $yl_str,
        ];
    }

    /**
     * @desc 二定、三定、四定单双遗漏统计
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * 12XX 21XX X12X X21X XX12 XX21 1XX2 2XX1 1111 2222
     */
    public static function updateDsYL($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];
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
            /*
            */
            # 四定单双
            'dwds4' => [
                'zuHes' => [ [1,2,3,4] ],
                'numsArr' => [1111,1112,1121,1122,1211,1212,1221,1222,2111,2112,2121,2122,2211,2212,2221,2222],  // [8,9,10,11,12,13];  // 值
            ],
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
                        $SscDsYl = SscDsYl::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
                        $type = 4;
                        //if(!$SscDsYl)p([$zhi, $position, $SscDsYl]);
                    }else{
                        $zhi = $num;
                        $where = ['AND', ['=', 'positions', $position], ['=','zhi', $num], ['=', 'lottery_type', $lottery_type], ['=', 'LENGTH(zhi)', strlen($num)]];
                        $SscDsYl = SscDsYl::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
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
                    $miss = SscDataService::getDsHistoryMiss($num, $position, $lottery_type); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
                    //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
                    $SscDsYl->lottery_type = $lottery_type; # 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
                    $SscDsYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
                    $SscDsYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
                    $SscDsYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
                    $SscDsYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
                    $SscDsYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
                    $SscDsYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
                    //p($updateData);
                    //if($YL_data[$num] > $SscDsYl->max_miss && $YL_data[$num] > $SscDsYl->history_max_miss){
                    //}
                    $SscDsYl->history_max_miss = max($miss['current_times'],$SscDsYl->max_miss,$SscDsYl->history_max_miss); // 6、历史最大遗漏
                    $SscDsYl->update_time = date('Y-m-d H:i:s');
                    //p($SscDsYl);
                    $rst = $SscDsYl->save();
                    if(!$rst){
                        $logArr = ['attributes'=>$SscDsYl->attributes, 'msg'=>$SscDsYl->getErrors()];
                        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/static_SscDwsDsNums','INFO','统计号码出现次数', $logArr);
                    }
                }

            }
        }

        return $rst;
    }

    /**
     * @desc 双重、三重、双双重遗漏统计
     * @param $type 类型：1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     */
    public static function updateCodeTypeYL($type = 2, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];

        $SscStaticVals = SscStaticVal::find()->where(['type'=>$type, 'status'=>1])->asArray()->all();
        foreach ($SscStaticVals as $dsData){
            if(!$SscStaticYl = SscStaticYl::findOne(['lottery_type'=>$lottery_type, 'val'=>$dsData['val']])){
                $SscStaticYl = new SscStaticYl();
                $SscStaticYl->created_at = time();
            }
            $SscStaticYl->static_nums = $dsData['static_nums'];
            $vals = explode(',', $dsData['val']);
            $count = SscDataService::getNumCounts($vals);
            //if($dsData['val'] == 'type_2,type_3b') p([$count, $dsData['val']]);
            $SscStaticYl->lottery_type = $lottery_type;
            $SscStaticYl->updated_at = time();
            $SscStaticYl->val = $dsData['val'];
            $SscStaticYl->type = $type;
            $miss = SscDataService::getCodeTypeHistoryMiss($dsData['val'], $lottery_type, $SscStaticYl->static_nums); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
            //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
            $SscStaticYl->lottery_type = $lottery_type; # 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
            $SscStaticYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
            $SscStaticYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
            $SscStaticYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
            $SscStaticYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
            $SscStaticYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
            $SscStaticYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
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

            $qishu = SscDataService::getQishus($lottery_type);
            $where = ['AND'];
            foreach ($vals as $val){
                $where  = array_merge($where, [['=', $val, 1]]);
            }
            $Num4Type = Num4Type::find()->select('COUNT(id) AS count')->where($where)->asArray()->one();
            $SscStaticYl->theory_nums_perdate = (string)round(($Num4Type['count']*$qishu*0.1) / 995, 2); # 理论次数/天
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
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/updateCodeTypeYL','INFO','统计号码出现次数', $logArr);
            }
        }

        return $rst;
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
            //$miss = SscDataService::getCodeTypeHistoryMiss($dsData['val'], $lottery_type, $SscStaticYl->static_nums); // return ['times'=>$times, 'last_time_range'=>$last_time_range, 'max_range'=>$max_range];
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

            $qishu = SscDataService::getQishus($lottery_type);
            $where = ['AND'];
            foreach ($vals as $val){
                $where  = array_merge($where, [['=', $val, 1]]);
            }
            $Num4Type = Num4Type::find()->select('COUNT(id) AS count')->where($where)->asArray()->one();
            $SscStaticYl->theory_nums_perdate = (string)round(($Num4Type['count']*$qishu*0.1) / 995, 2); # 理论次数/天
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
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/updateCodeTypeYL','INFO','统计号码出现次数', $logArr);
            }
        }

        return $rst;
    }

    /**
     * @param $type 类型：1和值2号码类型[例如:双双重、三重]3三字现4四字现
     * @return array|bool
     */
    public static function updateCodeTypeYLs($type, $lottery_type = DEFAULT_LOTTERY_TYPE){
        if(!in_array($type, [3, 4])) return false;
        $rst = [];
        $SscStaticVals = self::getSscStaticVal($type);

        $SscStaticYls = self::getSscStaticYls($lottery_type, $type);
        $yDate = date('Y-m-d',strtotime("-1 day"));
        $tDate = date('Y-m-d');
        foreach ($SscStaticVals as $dsData){
            //p(['lottery_type'=>$lottery_type, 'type'=>$type, 'val'=>$dsData['val'], $SscStaticYls[$dsData['val']]]);
            if(!$SscStaticYl = $SscStaticYls[$dsData['val']]){
                $SscStaticYl = new SscStaticYl();
                $SscStaticYl->created_at = time();
                $SscStaticYl->lottery_type = $lottery_type; # 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
                $count = SscDataService::getCodeTypeNumCounts($type, strlen($dsData['val']));
                $SscStaticYl->type = $type;
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
                $SscStaticYl->type_4ds = (int)$dsData['type_4ds'];
                $SscStaticYl->type_log = (int)$dsData['type_log'];
            }
            $SscStaticYl->static_nums = $dsData['static_nums'];
            //$vals = explode(',', $dsData['val']); //p([$dsData, $count]);
            $SscStaticYl->updated_at = time();
            $SscStaticYl->val = $dsData['val'];
            $miss = SscDataService::getCodeTypeYlHistoryMiss($dsData['val'], $lottery_type, $dsData['static_nums']);
            //$miss2 = SscDataService::getCodeTypeYlByTab($dsData['val'], $lottery_type, $type);
            /*
            $field = strlen($dsData['val']) == 3 ? 'code_3n' : 'code_4n';
            $flag = strpos($SscKjDatas->$field, $dsData['val']) !== false; # 匹配则说明中奖
            if(in_array($type, [ 3 ]) OR $getDataType == 0 OR $flag){
                # 中的执行这里
                $miss = SscDataService::getCodeTypeYlHistoryMiss($dsData['val'], $lottery_type, $dsData['static_nums']);
                //p([$dsData['val'], $lottery_type, $getDataType, $dsData['static_nums'], $miss]);
            }else{
                # 遗漏本表数据做计算，不中的情况执行这里
                $miss = SscDataService::getCodeTypeYlByTab($dsData['val'], $lottery_type, $type);
                //p([$miss, $dsData['val'], $lottery_type, $type]);
                if(!$miss) continue;
            }
            */
            //if($miss['current_times'])
            //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
            $SscStaticYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
            $SscStaticYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
            $SscStaticYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
            $SscStaticYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
            $SscStaticYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
            $SscStaticYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
            //$SscStaticYl->status = $dsData['static_nums']; # 前台显示
            $SscStaticYl->status = $dsData['status']; # 前台显示

            $qishu = SscDataService::getQishus($lottery_type);

            $len = strlen($dsData['val']);
            $field = $len == 3 ? 'code_3n' : 'code_4n';
            $where = ['AND', ['LIKE', $field, $dsData['val']]];
            $SscStaticYl->theory_nums_perdate = (string)round(($count*$qishu*0.1) / 995, 2); # 理论次数/天

            # 今日出现次数
            $today_nums_where = array_merge($where,[['=', 'lottery_type', $lottery_type],['=', 'date', $tDate]]);
            $today_nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where($today_nums_where)->asArray()->all()[0]['nums'];
            # 昨日出现次数
            $ytd_nums = self::getCodeTypeYtdNums($field, $dsData['val'], $lottery_type, $yDate);

            $SscStaticYl->today_nums = $today_nums;
            $SscStaticYl->ytd_nums = $ytd_nums;

            $SscStaticYl->history_max_miss = max($miss['current_times'],$SscStaticYl->max_miss,$SscStaticYl->history_max_miss); // 6、历史最大遗漏
            $SscStaticYl->update_time = date('Y-m-d H:i:s');
            $rst = $SscStaticYl->save();
            //p([$rst,$SscStaticYl->attributes]);
            if(!$rst){
                $logArr = ['attributes'=>$SscStaticYl->attributes, 'msg'=>$SscStaticYl->getErrors()];
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/updateCodeTypeYL','INFO','号码类型遗漏统计', $logArr);
            }
            $logArr = ['lottery_type'=>$lottery_type, 'type'=>$type, 'val'=>$dsData['val']];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/updateCodeTypeYL','INFO','号码类型遗漏统计', $logArr);
        }


        return $rst;
    }

    /**
     * @desc 获取号码类型
     * @param int $type
     * @return array|SscStaticVal[]|mixed
     */
    public static function getSscStaticVal($type = 3){
        $m = \Yii::$app->cache;
        $mkey = 'getSscStaticVal_'.$type;
        if(!$SscStaticVals = $m->get($mkey)){
            $SscStaticVals = SscStaticVal::find()->where(['type'=>$type, 'status'=>1])->asArray()->all();
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
     * @return mixed
     */
    public static function getCodeTypeYtdNums($field = 'code_4n', $val = '0123', $lottery_type = DEFAULT_LOTTERY_TYPE, $date){
        $m = \Yii::$app->cache;

        $mkey = 'getCodeTypeYtdNums_'.$lottery_type.'_'.$date.'_'.$field.'_'.$val;
        if(!$nums = $m->get($mkey)){
            $where = ['AND', ['LIKE', $field, $val]];
            $ytd_nums_where = array_merge($where,[['=', 'lottery_type', $lottery_type],['=', 'date', $date ]]);
            $nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where($ytd_nums_where)->asArray()->all()[0]['nums'];
            $m->set($mkey, $nums, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);
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
            $where = ['=', $vals[0], 1];
        }else{
            $where = ['AND'];
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
        $SscStaticYls = SscStaticYl::findAll(['lottery_type'=>$lottery_type, 'type'=>$type]);
        $datas = [];
        foreach ($SscStaticYls as $SscStaticYl){
            $datas[$SscStaticYl->val] = $SscStaticYl;
        }
        return $datas;
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


        return $rst;
    }

    /**
     * @description 返回历史单双遗漏
     * @param $num
     * @param $position
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param $recently 多少期内，默认为
     * @return array
     */
    public static function getDsHistoryMiss($num, $position, $lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 472){
        //if(!is_array($num)) $num = [ $num ];
        $last_times = 0;
        $last = SscKjDataDs::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
        $min_id = $last['last_id'] - $recently - 1;
        $min_id = $min_id ? $min_id : $last['last_id'];
        //p([$last['last_id'], $recently, $min_id]);

        $field = 'code_'.str_replace(',','_',$position);
        if(is_array($num)){
            $where = ['AND', ['IN', $field, $num],['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
        }else{
            $where = ['AND', ['=', $field, $num],['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
        }
        //$where = "$field=$num AND id>$min_id";
        $SscKjDataDs = SscKjDataDs::find()->select(['id', 'index_id', 'qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        //p($SscKjDataDs);

        if(count($SscKjDataDs)>1){
            $last_times = $SscKjDataDs[0]->index_id - $SscKjDataDs[1]->index_id - 1;  // 上次遗漏次数
        }

        # 最大遗漏期间计算 start
        $tmpKjData = $SscKjDataDs;
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
            $max_range = $SscKjDataDs[1]['qihao'] ."-". $SscKjDataDs[0]['qihao'];
        }
        $last_time_miss_range = $SscKjDataDs[1]['qihao'] ."-". $SscKjDataDs[0]['qihao'];
        $current_times = $last['last_id'] - $SscKjDataDs[0]->index_id;

        $rstData = [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss,   // 近200期内的最大遗漏
            'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'yl_str' => $yl_str,
        ];
        return $rstData;
    }

    /**
     * @description 返回号码单双类型遗漏
     * @param $num
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param $recently 多少期内，默认为
     * @return array
     */
    public static function getCodeTypeHistoryMiss($vals, $lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 472){

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
     * @description 返回类型遗漏
     * @param $value 例如：001[type:3] 或者 1223[type:4] 或者 1234[type:5] === 3三字现带双重4四字现带双重5四字现不带双重
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param $recently 多少期内，默认为
     * @return array
     */
    public static function getCodeTypeYlHistoryMiss($value, $lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 2000, $isCache = 1){
        $m = \Yii::$app->cache;
        $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'code_3n', 'code_4n', 'qihao', 'kj_code'])->orderBy('id DESC')->limit(1)->one();
        $mkey = 'getCodeTypeYlHistoryMiss_'.$lottery_type.'_'.$value;
        $field = strlen($value) == 3 ? 'code_3n' : 'code_4n';
        $flag = strpos($SscKjDatas->$field, $value) !== false; # 匹配则说明中奖

        $staticFlag = BetService::getConfig('is_cache_data');

        if($isCache && !$flag && $staticFlag && $rstData = $m->get($mkey)){
            $rstData['current_times'] = $rstData['current_times'] + 1;
            $qihao = HN0898Service::getQihao($lottery_type);

            $logArr = ['value'=>$value, 'lottery_type'=>$lottery_type, 'field'=>$field];
            Tool_Common::log('getCodeTypeYlHistoryMiss_cache', 'INFO', '号码类型遗漏-缓存数据', $logArr);

            return $rstData;
        }
        $last_times = 0;
        $last = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
        $min_id = $last['last_id'] - $recently - 1;
        //p([$value, $recently, $min_id]);
        $len = strlen($value);
        if($len == 3){
            # 三字现
            $field = 'code_3n';
        }else{
            # 四字现
            $field = 'code_4n';
        }
        $where = ['AND', ['=', 'lottery_type', $lottery_type], ['>', 'index_id', $min_id], ['LIKE', $field, $value]];
        $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'code_3n', 'code_4n', 'qihao', 'kj_code'])->where($where)->orderBy('id DESC')->asArray()->all();
        //p([$where, $SscKjDatas]);
        if(count($SscKjDatas)>1){
            $last_times = $SscKjDatas[0]['index_id'] - $SscKjDatas[1]['index_id'] - 1;  // 上次遗漏次数
        }

        # 最大遗漏期间计算 start
        $tmpKjData = $SscKjDatas;
        if(count($tmpKjData) > 2){
            foreach($tmpKjData as $key=>$r){
                if($key == 0) continue;
                $range[$tmpKjData[$key-1]['index_id'].'_'.$tmpKjData[$key]['index_id']] = $tmpKjData[$key-1]['index_id'] - $tmpKjData[$key]['index_id'] - 1;
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
            $max_range = $SscKjDatas[1]['qihao'] .'-'. $SscKjDatas[0]['qihao'];
        }
        $last_time_miss_range = $SscKjDatas[1]['qihao'] .'-'. $SscKjDatas[0]['qihao'];
        $current_times = $last['last_id'] - $SscKjDatas[0]['index_id'];
        //p([$last['last_id'] , $SscKjDatas[0]->index_id]);
        if(empty($yl_str)) $yl_str = $last_times;

        $rstData = [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss ? $max_miss : $last_times,   // 近200期内的最大遗漏
            'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'yl_str' => $yl_str,
        ];
        $m->set($mkey, $rstData, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);
        //p($rstData);
        //if($vals == 'type_2,type_3b')p($rstData);
        return $rstData;
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
     * @param $num
     * @param $position
     * @param $recently 多少期内，默认为 4天
     * @return array
     */
    public static function getSdHzYlHistoryMiss($zuHes, $lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 250){
        $last_times = 0;
        $last = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
        $min_id = $last['last_id'] - $recently - 1;

        $where = ['AND', ['IN', 'codes_4nums_hz', $zuHes], ['>=', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
        $SscKjData = SscKjData::find()->select(['id','index_id','qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        //p($SscKjData);
        //p([$where, $zuHes, $last, $SscKjData[0]->id, $SscKjData[1]->id, $recently]);
        if(count($SscKjData)>1){
            $last_times = $SscKjData[0]->index_id - $SscKjData[1]->index_id - 1;  // 上次遗漏次数
        }

        # 遗漏期间计算 start
        $tmpKjData = $SscKjData;
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
            $max_range = $SscKjData[1]['qihao'] ."-". $SscKjData[0]['qihao'];
        }
        $last_time_miss_range = $SscKjData[1]['qihao'] ."-". $SscKjData[0]['qihao'];
        $current_times = $last['last_id'] - $SscKjData[0]->index_id;
        //p([$last['last_id'] , $SscKjData[0]]);

        $rstData = [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss,   // 近200期内的最大遗漏
            'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'val' => implode(',', $zuHes),
            'yl_str' => $yl_str,
            //'zihes' => $zuHes,
        ];
        //p($rstData);

        return $rstData;
    }

    /**
     * @description 返回历史三字现遗漏
     * @param $num 值
     * @param $lottery_type 彩种类型1重庆时时彩2七星彩3排列三4排列五5福彩3D
     * @param $recently 多少期内，默认为1000期
     * @return array
     */
    public static function get3NumHistoryMiss($num, $lottery_type = 1, $recently = 1000){
        $last_times = 0;
        $last = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
        $min_id = $last['last_id'] - $recently - 1;
        $m = \Yii::$app->cache;
        $key = 'get3NumHistoryMiss_ID_'.$lottery_type.'_'.$min_id;
        if(!$rst = $m->get($key)){
            $field = 'code_3n';
            $where = ['AND',['like', $field, $num],['=', 'lottery_type', $lottery_type],['>','index_id', $min_id]];
            //p($where,0);
            //$where = "$field=$num AND id>$min_id";
            $SscKjData3Nums = SscKjData::find()->select(['id', 'index_id', 'qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
            //if($num == '245')p($SscKjData3Nums);
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
            $current_times = $last['last_id'] - $SscKjData3Nums[0]->index_id;

            $rst = [
                'current_times' => $current_times,    // 当前遗漏次数
                'last_times' => $last_times,    // 上次遗漏次数
                'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
                'max_miss' => $max_miss,   // 近200期内的最大遗漏
                'max_range' => $max_range,   // 近200期内的最大遗漏范围
                'yl_str' => $yl_str,
            ];
            $m->set($key, $rst,60*60);
        }


        return $rst;
    }

    /**
     * @desc 每期开奖单双记录-已完成
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @param string $qihao
     */
    public static function insertSscKjDataDs($qihao = '', $lottery_type = DEFAULT_LOTTERY_TYPE){
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
        $SscKjDataDs = SscKjDataDs::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
        if(!$SscKjDataDs){
            $SscKjDataDs = new SscKjDataDs();
            $opData['created_at'] = time();
            $opData['index_id'] = $SscKjData->index_id;
        }

        # 1、一定
        foreach ($tmpData as $key=>$tmp){
            $field = 'code_'.$key;
            $opData[$field] = $tmpData[$key];
        }

        # 2、二定
        $zuHes = [ [1,2], [1,3], [1,4], [2,3], [2,4], [3,4] ];
        foreach ($zuHes as $key=>$zuHe){
            $field = 'code_'.$zuHe[0].'_'.$zuHe[1];
            $opData[$field] = $tmpData[$zuHe[0]].$tmpData[$zuHe[1]];
        }

        # 3、三定
        $zuHes = [ [1,2,3], [1,2,4], [1,3,4], [2,3,4] ];
        foreach ($zuHes as $key=>$zuHe){
            $field = 'code_'.$zuHe[0].'_'.$zuHe[1].'_'.$zuHe[2];
            $opData[$field] = $tmpData[$zuHe[0]].$tmpData[$zuHe[1]].$tmpData[$zuHe[2]];
        }

        # 4、四定
        $zuHe = [ 1,2,3,4 ];
        $field = 'code_'.$zuHe[0].'_'.$zuHe[1].'_'.$zuHe[2].'_'.$zuHe[3];
        $opData[$field] = $tmpData[$zuHe[0]].$tmpData[$zuHe[1]].$tmpData[$zuHe[2]].$tmpData[$zuHe[3]];

        $opData['qihao'] = $qihao;
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
        }
        //p([$tmpData,$SscKjDataDs->attributes,$SscKjDataDs->getErrors()],0);
        if(!$rst){
            $logArr = ['attributes'=>$SscKjDataDs->attributes, 'msg'=>$SscKjDataDs->getErrors()];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/insertSscKjDataDsError','INFO','每期开奖单双记录-插入失败', $logArr);
        }

        return $rst;
    }

    /**
     * @desc 每期开奖三字现记录-已完成  写入
     * @param $lottery_type  彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param string $qihao
     */
    public static function insertSscKjData3Num($qihao = '', $lottery_type = DEFAULT_LOTTERY_TYPE){
        $SscKjData = SscKjData::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
        $data = explode(',',$SscKjData['code_str']);
        array_pop($data);
        $nums = CommonService::get3x($data);

        $opData = [];
        $opData['code_3n'] = implode(',', $nums);
        $opData['updated_at'] = time();
        $opData['updated_time'] = date('Y-m-d H:i:s');
        $SscKjData3Num = SscKjData3num::findOne(['qihao'=>$qihao, 'lottery_type'=>$lottery_type]);
        if(!$SscKjData3Num){
            $SscKjData3Num = new SscKjData3num();
            $opData['created_at'] = time();
        }

        $opData['qihao'] = $qihao;
        $opData['index_id'] = $SscKjData->index_id;
        $opData['code_str'] = $SscKjData['code_str'];
        $opData['date'] = $SscKjData['date'];
        $opData['lottery_type'] = $lottery_type;
        $SscKjData3Num->setAttributes($opData);
        $rst = $SscKjData3Num->save();
        //p([$opData,$SscKjData3Num->attributes,$SscKjData3Num->getErrors()],0);
        if(!$rst){
            $logArr = ['attributes'=>$SscKjData3Num->attributes, 'msg'=>$SscKjData3Num->getErrors()];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/insertSscKjData3Num','INFO','统计号码出现次数', $logArr);
        }

        return $rst;
    }

    /**
     * @desc 获取开奖单双 by kj_str
     * @param $codes 例如：1,2,3,4,5
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
     * @desc 插入四定单双组合单双遗漏表
     */
    public static function insert4dDsZHData(){
        $typeArr = StaticService::$typeArr;
        foreach ($typeArr as $key=>$Arr){
            if(in_array($key, [0, 10, 11])) continue;
            $SscDsYl = SscDsYl::findOne(['positions'=>'1,2,3,4', 'zhi'=>implode(',', $Arr)]);
            if(!$SscDsYl){
                $SscDsYl = new SscDsYl();
            }
            $SscDsYl->type = 4;
            $SscDsYl->positions = '1,2,3,4';
            $SscDsYl->zhi = implode(',', $Arr);
            $SscDsYl->update_time = time();
            //p($setData, 0);
            $rst = $SscDsYl->save();
        }

        return ['status'=>$rst, 'msg'=>'处理成功~', 'rst'=>$rst];
    }

    /**
     * @desc 插入号码类型表
     * @return bool
     */
    public static function insertCodeType(){
        set_time_limit(0);

        //for($i = 10000; $i<=19999; $i++){
        //for($i = 10000; $i<=13501; $i++){
        //for($i = 13500; $i<=16501; $i++){
        for($i = 16500; $i<=19999; $i++){
            $code = substr($i, 1,4);
            $codes = $code[0].','.$code[1].','.$code[2].','.$code[3];
            if(!$Num4Type = Num4Type::findOne(['code'=>$codes])){
                $Num4Type = new Num4Type();
            }else{
                //continue;
            }

            $setData = [
                'code' => $codes, # 号码
                'code_1' => $code[0], # 第一个号码
                'code_2' => $code[1], # 第二个号码
                'code_3' => $code[2], # 第三个号码
                'code_4' => $code[3], # 第四个号码
                'type_2' => CommonService::isCodeType2($codes), # 是否双重
                'type_22' => CommonService::isCodeType22($codes), # 是否双双重
                'type_3' => CommonService::isCodeType3($codes), # 是否三重
                'type_4' => CommonService::isCodeType4($codes), # 是否四重
                'type_2b' => CommonService::isCodeType2b($codes), # 是否两兄弟
                'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
                'type_4b' => CommonService::isCodeType4b($codes), # 是否四兄弟
                'type_4ds' => CommonService::isCodeType4ds($codes), # 是否四单双：0非四单四双1四单2四双
                'type_log' => CommonService::isCodeTypeLog($codes), # 是否对数
                'codes_hz' => array_sum([$code[0],$code[1],$code[2],$code[3]]),
                'updated_at' => time(),
                'created_at' => time(),
            ];
            $Num4Type->setAttributes($setData);

            if(!$rst = $Num4Type->save()){
                p($Num4Type->getFirstErrors());
            }
        }
        return $rst;
    }

    /**
     * @desc 更新四定号码类型表号码类型
     * @return mixed
     */
    public static function insertStaticVal(){

        $SscStaticVals = SscStaticVal::findAll(['type'=>[4,5]]);
        foreach ($SscStaticVals as $SscStaticVal){
            $code = $SscStaticVal->val;
            $codes = $code[0].','.$code[1].','.$code[2].','.$code[3];
            $ds = CommonService::isCodeType4ds($codes); # 是否四单双：0非四单四双1四单2四双
            $setData = [
                'type_2' => CommonService::isCodeType2($codes), # 是否双重
                'type_22' => CommonService::isCodeType22($codes), # 是否双双重
                'type_3' => CommonService::isCodeType3($codes), # 是否三重
                'type_4' => CommonService::isCodeType4($codes), # 是否四重
                'type_2b' => CommonService::isCodeType2b($codes), # 是否两兄弟
                'type_3b' => CommonService::isCodeType3b($codes), # 是否三兄弟
                'type_4b' => CommonService::isCodeType4b($codes), # 是否四兄弟
                'type_4d' => $ds == 1 ? 1 : 0,
                'type_4s' => $ds == 2 ? 1 : 0,
                'type_log' => CommonService::isCodeTypeLog($codes), # 是否对数
            ];
            $SscStaticVal->setAttributes($setData);
            /*
            if($codes == '1,2,3,4'){
                p($setData,0);
                p(CommonService::isCodeType4b($codes), 0);
                p($SscStaticVal->attributes);
            }
            */
            $rst = $SscStaticVal->save();
        }

        return $rst;
    }

    /**
     * @desc 三字现
     * @param int $isDouble 0不带双重1带双重
     * @param $type # 类型：1和值2号码类型[例如:双双重、三重]3三字现4四字现
     * @return bool
     */
    public static function insertCode($type = 3){
        if($type<3) return false;

        $setData = [];
        switch ($type){
            case 3:
                $codes = BaseNumService::getRepeat3Codes($isRepeat = 1);  # 三字现，双重加一码、不含三重
                $codes_3 = BaseNumService::getRepeat3Codes3(); # 三重三字现
                $codes = array_merge($codes, $codes_3);
                break;
            case 4:
                $codes = BaseNumService::getRepeat4Codes($isRepeat = 1); # 四字现，双重加两码、不含三重
                $codes = array_merge($codes, BaseNumService::getRepeat4Codes($isRepeat = 0)); # 四字现不含双重
                $codes = array_merge($codes, BaseNumService::getRepeat4Codes3()); # 四字现三重
                $codes = array_merge($codes, BaseNumService::getRepeat4Codes22()); # 四字现双双重
                break;
        }
        foreach ($codes as $code){
            $where = ['val'=>$code, 'type'=>$type];
            if(!$SscStaticVal = SscStaticVal::findOne($where)){
                $SscStaticVal = new SscStaticVal();
                $setData = [
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
            }
            $setData = array_merge($setData,[
                    'type' => $type,
                    'val' => $code,
                    'name' => $code,
                    'status' => 1,
            ]);
            if($type == 3){
                # 1、三字
                $code = $code[0].','.$code[1].','.$code[2];
                $type_2 = CommonService::isCodeType2_3z($code);
                $type_3 = CommonService::isCodeType3_3z($code);
                $setData = array_merge($setData, [
                    'type_2' => $type_2,
                    'type_3' => $type_3,
                ]);

            }elseif ( $type == 4 ){
                # 四字
                $code = $code[0].','.$code[1].','.$code[2].','.$code[3];
                $ds = CommonService::isCodeType4ds($code); # 是否四单双：0非四单四双1四单2四双
                $setData = array_merge($setData, [
                    'type_2' => CommonService::isCodeType2($code),
                    'type_3' => CommonService::isCodeType3($code),
                    'type_4' => CommonService::isCodeType4($code),
                    'type_2b' => CommonService::isCodeType2b($code),
                    'type_3b' => CommonService::isCodeType3b($code),
                    'type_4b' => CommonService::isCodeType4b($code),
                    'type_22' => CommonService::isCodeType22($code),
                    'type_4d' => $ds == 1 ? 1 : 0,
                    'type_4s' => $ds == 2 ? 1 : 0,
                    'type_4ds' => in_array($ds, [1,2]) ? 1 : 0,
                    'type_log' => CommonService::isCodeTypeLog($code),
                ]);

            }

            $SscStaticVal->setAttributes($setData);
            //p($SscStaticVal->attributes);
            $SscStaticVal->save();

        }

        return true;
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
                $where = ['OR', ['=', 'type_2', 1], ['=','type_22', 1], ['=','type_3', 1], ['=','type_4', 1], ['=','type_3b', 1], ['=','type_4b', 1], ['type_4ds'=>[1,2]]];
            }elseif($type == 2){
                # 2、排除双重、双双重、三重、四重、三兄弟、四兄弟、四单四双
                $where = ['AND', ['<>', 'type_2', 1], ['<>','type_22', 1], ['<>','type_3', 1], ['<>','type_4', 1], ['<>','type_3b', 1], ['<>','type_4b', 1], ['=', 'type_4ds', 0]];
            }else{
                # 3、双重
                $where = ['=', 'type_2', 1];
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
    public static function updateSdHzYl($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = [];
        # 大数组：包括二定、三定、四定
        $updateDsDatas = SscSdHzVal::find()->asArray()->All();
        //$rst[$interval] = SscDataService::dsYLStatic($interval);
        //p($updateDsDatas);
        foreach ($updateDsDatas as $Data){
            //if($Data['id'] != 61) continue;
            $zuHes = explode(',', $Data['val']);
            $where = [ 'AND',[ '=', 'val', $Data['val']], ['=', 'lottery_type', $lottery_type] ];

            if(!$SscSdHzYl = SscSdHzYl::find()->where($where)->orderBy(['id'=>SORT_DESC])->one()){
                $SscSdHzYl = new SscSdHzYl();
                $SscSdHzYl->created_at = time();
                $SscSdHzYl->val = $Data['val'];
            }
            $count = self::getCountByHzs($zuHes);
            $SscSdHzYl->count = $count; # 组合总共组数
            $SscSdHzYl->updated_at = time();
            //$SscDsYl->zhi = (string)$num;
            $qishu = SscDataService::getQishus($lottery_type);
            $SscSdHzYl->theory_nums_perdate = (string)round(($count*$qishu*0.1) / 995, 2); # 理论次数/天
            $SscSdHzYl->today_nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where(['date'=>date('Y-m-d'),'codes_4nums_hz'=>$zuHes, 'lottery_type'=>$lottery_type])->asArray()->one()['nums'];

            $SscSdHzYl->updated_at = time();
            $miss = SscDataService::getSdHzYlHistoryMiss($zuHes, $lottery_type, $Data['static_nums']);
            //if($zuHes == [5,6,7,8,9,10]) p([$zuHes,$miss, $Data['static_nums']]);
            $SscSdHzYl->static_nums = $Data['static_nums'];
            $SscSdHzYl->status = $Data['status'];
            //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
            $SscSdHzYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
            $SscSdHzYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
            $SscSdHzYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
            $SscSdHzYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
            $SscSdHzYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
            $SscSdHzYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
            $SscSdHzYl->history_max_miss = max($miss['current_times'],$SscSdHzYl->max_miss,$SscSdHzYl->history_max_miss); // 6、历史最大遗漏
            //$SscSdHzYl->status = $Data['status']; // 7、前台显示状态
            $SscSdHzYl->lottery_type = $lottery_type; // 彩种类型
            $SscSdHzYl->update_time = date('Y-m-d H:i:s');
            //p($SscSdHzYl->attributes);
            $rst = $SscSdHzYl->save();
            if(!$rst){
                $logArr = ['attributes'=>$SscSdHzYl->attributes, 'msg'=>$SscSdHzYl->getErrors()];
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/updateSdHzYl','INFO','四定和值遗漏统计', $logArr);
            }

        }

        return $rst;
    }

    /**
     * @desc 获取和值组合总号码数量
     * @param $hzs
     * @return mixed
     */
    public static function getCountByHzs($hzs){
        $m = \Yii::$app->cache;
        $mkey = 'getCountByHzs_'.implode(',', $hzs);
        if(!$counts = $m->get($mkey)){
            $Num4Type = Num4Type::find()->select('COUNT(id) AS count')->where(['codes_hz'=>$hzs])->asArray()->one();
            $counts = $Num4Type['count'];
        }

        return $counts;
    }

    /**
     * @desc 获取每天开奖期数
     * @param string $type
     * @return mixed|string
     */
    public static function getQishus($lottery_type = DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'QISHU_PERDATE_'.$lottery_type;
        if(!$qishu = $m->get($mkey)){
            $key = 'ssc_'.$lottery_type.'_qishus_perdate';
            $qishu = SystemConfig::find()->where(['key'=>$key])->one()->value;

            $m->set($mkey, $qishu, 7*24*3600);
        }

        return $qishu;
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
        $time = strtotime('2019-08-01');

        for ($i=0; $i<10; $i++){
            $date = date('Y-m-d', $time+$i*24*3600);
            $profits[$date] = self::getOneDateBeforeProfits($date, $beforeQishus = 400, $lottery_type);
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












}