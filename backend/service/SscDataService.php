<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
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
use common\service\CommonService;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use backend\models\SscDwHzYl;
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
     * @param $lottery_type integer 彩种类型：1:1.5分 2:3分 3:5分 4:10分
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

        if($next_qihao<=$last_qihao){
            $new_qihao = SscKjData::find()->where(['qihao'=>$next_qihao, 'lottery_type'=>$lottery_type])->one()->qihao;
            if($new_qihao){
                $flag = SscDataService::insertSscKjDataDs($new_qihao, $lottery_type);
            }
            $m->set($mkey, $new_qihao, 24*60*60);
        }
        //p([$new_qihao, $next_qihao, $last_qihao, $lottery_type],0);

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
    public static function sscDwsHzNums(){
        $intervals = [ 200,500,1000,2000,5000];
        $m = \Yii::$app->cache;
        $mkey = 'DWS_HZ_COUNT_NUMS_1';
        if(!$id = $m->get($mkey)){
            $id = 47920; // 2019-02-03
        }
        $id = $id + 1;
        foreach ($intervals as $key => $interval) {
            $last_id = SscDataService::getKjDataLastId();

            if($id<=$last_id){
                $new_qihao = SscKjData::findOne($id)->qihao;
                $logArr = ['id'=>$id, [$interval, $new_qihao, $id]];
                $flag = SscDataService::insertSscDwsHzNums($interval, $new_qihao, $id);
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
        $last_id = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['max(id) as last_id'])->limit(1)->asArray()->one()['last_id'];

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
            /*
            */
        ];
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
                        //if(!$SscDsYl)p([$zhi, $position, $SscDsYl]);
                        $SscDsYl->zhi = (string)$zhi;
                        $SscDsYl->positions = $position;
                        $SscDsYl->type = 4;
                    }else{
                        $where = ['AND', ['=', 'positions', $position], ['=','zhi', $num], ['=', 'lottery_type', $lottery_type], ['=', 'LENGTH(zhi)', strlen($num)]];
                        $SscDsYl = SscDsYl::find()->where($where)->orderBy(['id'=>SORT_DESC])->one();
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
                    //p($SscDsYl->attributes);
                    $rst = $SscDsYl->save();
                    if($num == '1212'){
                        //p([$dsData['numsArr'],$YL_data, $miss,$num,$rst]);
                    }
                    if(!$rst){
                        $logArr = ['attributes'=>$SscDsYl->attributes, 'msg'=>$SscDsYl->getErrors()];
                        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Y-m-d').'/static_SscDwsDsNums','INFO','统计号码出现次数', $logArr);
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

        $SscStaticVals = SscStaticVal::find()->where(['type'=>$type])->asArray()->all();
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
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Y-m-d').'/updateCodeTypeYL','INFO','统计号码出现次数', $logArr);
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
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Y-m-d').'/updateCodeTypeYL','INFO','统计号码出现次数', $logArr);
            }
        }

        return $rst;
    }

    /**
     * @param $type 类型：1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重
     * @return array|bool
     */
    public static function updateCodeTypeYLs($type, $lottery_type = DEFAULT_LOTTERY_TYPE){
        if(!in_array($type, [3, 4, 5])) return false;
        $rst = [];
        $SscStaticVals = SscStaticVal::find()->where(['type'=>$type, 'status'=>1])->asArray()->all();
        foreach ($SscStaticVals as $dsData){
            if(!$SscStaticYl = SscStaticYl::findOne(['lottery_type'=>$lottery_type, 'val'=>$dsData['val']])){
                $SscStaticYl = new SscStaticYl();
                $SscStaticYl->created_at = time();
                $SscStaticYl->static_nums = 250;
            }
            //$vals = explode(',', $dsData['val']);
            $count = SscDataService::getCodeTypeNumCounts($type);
            //p([$dsData, $count]);
            $SscStaticYl->lottery_type = $lottery_type;
            $SscStaticYl->updated_at = time();
            $SscStaticYl->val = $dsData['val'];
            $SscStaticYl->type = $type;
            $getDataType = SscDataService::staticGetDataType();
            $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'code_3n', 'code_4n', 'qihao', 'kj_code'])->orderBy('id DESC')->limit(1)->one();
            $field = strlen($dsData['val']) == 3 ? 'code_3n' : 'code_4n';
            $flag = strpos($SscKjDatas->$field, $dsData['val']) !== false;
            if(in_array($type, [ 3 ]) OR $getDataType == 0 OR $flag){
                # 中的执行这里
                $miss = SscDataService::getCodeTypeYlHistoryMiss($dsData['val'], $lottery_type, $dsData['static_nums']);
            }else{
                # 遗漏本表数据做计算，不中的情况执行这里
                $miss = SscDataService::getCodeTypeYlByTab($dsData['val'], $lottery_type, $type);
            }
            if($miss['current_times'])
            //$SscDsYl->current_miss = $YL_data[$num];  // 1、当前遗漏次数
            $SscStaticYl->lottery_type = $lottery_type; # 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
            $SscStaticYl->current_miss = $miss['current_times'];  // 1、当前遗漏次数
            $SscStaticYl->last_time_miss = $miss['last_times']; // 2、上次遗漏
            $SscStaticYl->last_time_miss_range = $miss['last_time_miss_range']; // 3、上次遗漏范围
            $SscStaticYl->max_miss = $miss['max_miss'];      // 4、近200期内最大遗漏
            $SscStaticYl->max_range = $miss['max_range']; // 5、200期内最大遗漏范围
            $SscStaticYl->yl_records = $miss['current_times'].'-'.$miss['yl_str']; // 5、200期内最大遗漏范围
            $SscStaticYl->count = $count;
            $SscStaticYl->status = $miss['current_times'] > $dsData['static_nums'] ? 0 : 1; # 前台显示

            $qishu = SscDataService::getQishus($lottery_type);

            $len = strlen($dsData['val']);
            $field = $len == 3? 'code_3n' : 'code_4n';
            $where = ['AND', ['LIKE', $field, $dsData['val']]];
            $SscStaticYl->theory_nums_perdate = (string)round(($count*$qishu*0.1) / 995, 2); # 理论次数/天
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
            //p($SscStaticYl->attributes,0);
            $rst = $SscStaticYl->save();
            if(!$rst){
                $logArr = ['attributes'=>$SscStaticYl->attributes, 'msg'=>$SscStaticYl->getErrors()];
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Y-m-d').'/updateCodeTypeYL','INFO','统计号码出现次数', $logArr);
            }
        }


        return $rst;
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
     * @desc 计算遗漏获取数据类型 1取本表数据做变更0扫表重新计算数据（比如：遗漏、数量等统计）
     * @return int
     */
    public static function staticGetDataType(){

        $status = SystemConfig::findOne(['key'=>'getDataType'])->value;

        return $status;
    }

    /**
     * @desc 返回记录数，组数
     * @param $type 3三字现带双重4四字现带双重5四字现不带双重
     * @param $vals
     * @return int
     */
    public static function getCodeTypeNumCounts($type = 2){
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

        $field = 'code_'.str_replace(',','_',$position);
        if(is_array($num)){
            $where = ['AND', ['IN', $field, $num],['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
        }else{
            $where = ['AND', ['=', $field, $num],['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
        }
        //$where = "$field=$num AND id>$min_id";
        $SscKjDataDs = SscKjDataDs::find()->select(['id', 'index_id', 'qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        //p($where);
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
    public static function getCodeTypeYlHistoryMiss($value, $lottery_type = DEFAULT_LOTTERY_TYPE, $recently = 2000){
        //if(!is_array($num)) $num = [ $num ];
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
        $where = ['AND', ['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type],['LIKE', $field, $value]];
        $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'code_3n', 'code_4n', 'qihao', 'kj_code'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        //p($SscKjDatas);
        //if($value == '0026') p([$rstData, $SscKjDatas]);
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
        if(empty($yl_str)) $yl_str = $last_times;

        $rstData = [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss ? $max_miss : $last_times,   // 近200期内的最大遗漏
            'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'yl_str' => $yl_str,
        ];
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
        if($m->get($mkey)) return false;

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
        //p($rstData);
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

        $where = ['AND', ['IN', 'codes_4nums_hz', $zuHes], ['>=', 'id', $min_id]];
        $SscKjData = SscKjData::find()->select(['id','index_id','qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        //p([$zuHes, $last, $SscKjData[0]->id, $SscKjData[1]->id]);
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
        $current_times = $last['last_id'] - $SscKjData[0]->index_id;
        //p([$last['last_id'] , $SscKjData[0]]);

        $rstData = [
            'current_times' => $current_times,    // 当前遗漏次数
            'last_times' => $last_times,    // 上次遗漏次数
            'last_time_miss_range' => $last_time_miss_range,    // 上次遗漏范围
            'max_miss' => $max_miss,   // 近200期内的最大遗漏
            'max_range' => $max_range,   // 近200期内的最大遗漏范围
            'yl_str' => $yl_str,
            'zihes' => $zuHes,
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
        $last = SscKjData3num::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
        $min_id = $last['last_id'] - $recently - 1;
        $m = \Yii::$app->cache;
        $key = 'get3NumHistoryMiss_ID_'.$lottery_type.'_'.$min_id;
        //if(!$rst = $m->get($key)){
            $field = 'code_3n';
            $where = ['AND',['like',$field,$num],['=', 'lottery_type', $lottery_type],['>','index_id', $min_id]];
            //p($where,0);
            //$where = "$field=$num AND id>$min_id";
            $SscKjData3Nums = SscKjData3Num::find()->select(['id', 'index_id', 'qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
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
        //}


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
        if($r = $m->get($mkey)) return ['status'=>'300', 'msg'=>'已经处理完成...'];
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
     * @desc 每期开奖三字现记录-已完成
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
        for($i = 10000; $i<=13501; $i++){
        //for($i = 13500; $i<=16501; $i++){
        //for($i = 16500; $i<=19999; $i++){
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
     * @desc 三字现
     * @param int $isDouble 0不带双重1带双重
     * @param $type # 类型：1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重
     * @return bool
     */
    public static function insertCode($type = 3){
        if($type<3) return false;

        $setData = [];
        switch ($type){
            case 3:
                $codes = BaseNumService::getRepeat3Codes($isRepeat = 1);
                break;
            case 4:
                $codes = BaseNumService::getRepeat4Codes($isRepeat = 1);
                break;
            case 5:
                $codes = BaseNumService::getRepeat4Codes($isRepeat = 0);
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

            $SscStaticVal->setAttributes($setData);
            //p($SscStaticVal->attributes);
            $SscStaticVal->save();

        }
        p($codes);

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
        foreach ($updateDsDatas as $Data){
            //if($Data['id'] != 61) continue;
            $zuHes = explode(',', $Data['val']);
            $where = [ 'AND',[ '=', 'val', $Data['val']], ['=', 'lottery_type', $lottery_type] ];

            if(!$SscSdHzYl = SscSdHzYl::find()->where($where)->orderBy(['id'=>SORT_DESC])->one()){
                $SscSdHzYl = new SscSdHzYl();
                $SscSdHzYl->created_at = time();
                $SscSdHzYl->val = $Data['val'];
            }
            $Num4Type = Num4Type::find()->select('COUNT(id) AS count')->where(['codes_hz'=>$zuHes])->asArray()->one();
            $SscSdHzYl->count = $Num4Type['count']; # 组合总共组数
            $SscSdHzYl->updated_at = time();
            //$SscDsYl->zhi = (string)$num;
            $qishu = SscDataService::getQishus($lottery_type);
            $SscSdHzYl->theory_nums_perdate = (string)round(($Num4Type['count']*$qishu*0.1) / 995, 2); # 理论次数/天
            $SscSdHzYl->today_nums = SscKjData::find()->select(['COUNT(id) AS nums'])->where(['date'=>date('Y-m-d'),'codes_4nums_hz'=>$zuHes, 'lottery_type'=>$lottery_type])->asArray()->one()['nums'];

            $SscSdHzYl->updated_at = time();
            $miss = SscDataService::getSdHzYlHistoryMiss($zuHes, $lottery_type, $SscSdHzYl->static_nums);
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
            //p($SscSdHzYl);
            $rst = $SscSdHzYl->save();
            if(!$rst){
                $logArr = ['attributes'=>$SscSdHzYl->attributes, 'msg'=>$SscSdHzYl->getErrors()];
                Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Y-m-d').'/static_SscDwsDsNums','INFO','统计号码出现次数', $logArr);
            }

        }

        return $rst;
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














}