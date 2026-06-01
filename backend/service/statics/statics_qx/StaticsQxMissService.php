<?php
namespace backend\service\statics\statics_qx;
use backend\models\SscKjData;
use backend\models\SscKjDataDs;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\SscDataService;

class StaticsQxMissService extends BaseService {

    /**
     * 返回历史和值遗漏
     * @param $num
     * @param $position
     * @param int $recently - 多少期内，默认为200
     * @return array
     */
    public static function getDwHistoryMiss($num, $position, $lottery_type = DEFAULT_LOTTERY_TYPE, int $recently = 200): array
    {
        $times = 0;
        $max_miss = 0;
        $max_range = '';
        $yl_str = '';
        $field = 'code_'.str_replace(',','_',$position);
        $last_index_id = SscDataService::getLastIndexId($lottery_type);
        $min_id = $last_index_id - $recently - 1;
        $where = ['AND',['=',$field,$num],['>','id', $min_id], ['=', 'lottery_type', $lottery_type]];
        //$where = "$field=$num AND id>$min_id";
        $SscKjData = SscKjData::find()->select(['id', 'index_id','qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        $dataCount = count($SscKjData);
        if($dataCount == 0){
            return [
                'current_times' => $last_index_id,
                'times' => $times,
                'last_time_miss_range' => '',
                'max_miss' => $max_miss,
                'max_range' => $max_range,
                'yl_str' => $yl_str,
            ];
        }
        if($dataCount > 1){
            $times = $SscKjData[0]->id - $SscKjData[1]->id - 1;  // 上次遗漏次数
        }

        # 最大遗漏期间计算 start
        $tmpKjData = $SscKjData;
        if(count($tmpKjData) > 2){
            $range = [];
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
            $max_range = ($tmpArr[1] ?? '').'-'.($tmpArr[0] ?? '');  // 近200期内最大遗漏
            $yl_str = implode('-',$range);
            # 最大遗漏期间计算 end
            //p([$field=>$num,$min_id,'times'=>$times,$SscKjData[0]->id, $SscKjData[1]->id,$max_range]);
        }elseif($dataCount > 1){
            $max_range = $SscKjData[1]['qihao'] ."-". $SscKjData[0]['qihao'];
        }else{
            $max_range = $SscKjData[0]['qihao'] ."-". $SscKjData[0]['qihao'];
        }
        $last_time_miss_range = $dataCount > 1 ? $SscKjData[1]['qihao'] ."-". $SscKjData[0]['qihao'] : $SscKjData[0]['qihao'] ."-". $SscKjData[0]['qihao'];
        $current_times = $last_index_id - $SscKjData[0]->id;

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
     * 返回历史单双遗漏
     * @param $num
     * @param $position
     * @param int $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param int $recently - 多少期内，默认为
     * @return array
     */
    public static function getDsHistoryMiss($num, $position, int $lottery_type = DEFAULT_LOTTERY_TYPE, int $recently = 472): array
    {
        //if(!is_array($num)) $num = [ $num ];
        $last_times = 0;
        $max_miss = 0;
        $max_range = '';
        $yl_str = '';
        $last_index_id = SscDataService::getLastIndexId($lottery_type);
        $min_id = $last_index_id - $recently - 1;
        $min_id = $min_id ? $min_id : $last_index_id;
        //p(['num'=>$num, 'position'=>$position, 'last_index_id'=>$last_index_id, 'recently'=>$recently, 'min'=>$min_id, 'recently'=>$recently]);

        $field = 'code_'.str_replace(',','_',$position);
        if(is_array($num)){
            $where = ['AND', ['IN', $field, $num],['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
        }else{
            $where = ['AND', ['=', $field, $num],['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
        }
        //$where = "$field=$num AND id>$min_id";
        //p($where);
        $SscKjDataDs = SscKjDataDs::find()->select(['id', 'index_id', 'qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        //p($SscKjDataDs);
        $dataCount = count($SscKjDataDs);
        if($dataCount == 0){
            return [
                'current_times' => $last_index_id,
                'last_times' => $last_times,
                'last_time_miss_range' => '',
                'max_miss' => $max_miss,
                'max_range' => $max_range,
                'yl_str' => $yl_str,
            ];
        }

        if($dataCount > 1){
            $last_times = $SscKjDataDs[0]->index_id - $SscKjDataDs[1]->index_id - 1;  // 上次遗漏次数
        }

        # 最大遗漏期间计算 start
        $tmpKjData = $SscKjDataDs;
        if(count($tmpKjData) > 2){
            $range = [];
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
            $max_range = ($tmpArr[1] ?? '').'-'.($tmpArr[0] ?? '');  // 近200期内最大遗漏
            $yl_str = implode('-',$range);
            # 最大遗漏期间计算 end
            //p([$field=>$num,$min_id, $SscKjData[1]->id,$max_range]);
        }elseif($dataCount > 1){
            $max_range = $SscKjDataDs[1]['qihao'] ."-". $SscKjDataDs[0]['qihao'];
        }else{
            $max_range = $SscKjDataDs[0]['qihao'] ."-". $SscKjDataDs[0]['qihao'];
        }
        $last_time_miss_range = $dataCount > 1 ? $SscKjDataDs[1]['qihao'] ."-". $SscKjDataDs[0]['qihao'] : $SscKjDataDs[0]['qihao'] ."-". $SscKjDataDs[0]['qihao'];
        $current_times = $last_index_id - $SscKjDataDs[0]->index_id;

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
     * 返回号码单双类型遗漏
     * @param $num
     * @param int $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param int $recently - 多少期内，默认为
     * @return array
     */
    public static function getCodeTypeHistoryMiss($vals, int $lottery_type = DEFAULT_LOTTERY_TYPE, int $recently = 472): array
    {

        //if(!is_array($num)) $num = [ $num ];
        $last_times = 0;
        $max_miss = 0;
        $max_range = '';
        $yl_str = '';
        $last_index_id = SscDataService::getLastIndexId($lottery_type);
        $min_id = $last_index_id - $recently - 1;

        if(strpos($vals, '+') !== false){
            # 1、# 四现：号码 + 三兄弟 2、三现：双重+两兄弟
            $valArr = explode('+', $vals);
            $codesArr = SscDataService::getTypeCode($valArr);
            $where = ['AND', ['>', 'index_id', $min_id], ['=', 'lottery_type', $lottery_type]];
            if($valArr[0] == 'code_4n'){
                $codesWhere = ['IN', 'code_4n', $codesArr];
            }elseif($valArr[0] == 'code_3n'){
                $codesWhere = ['OR'];
                foreach ($codesArr as $code){
                    $codesWhere = array_merge($codesWhere, [['LIKE', 'code_3n', $code]]);
                }
            }
            $where = array_merge($where, [$codesWhere]);
            $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'qihao'])->where($where)->orderBy('id DESC')->limit($recently)->all();
        }else{
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
        }
        //$where = "$field=$num AND id>$min_id";
        $dataCount = count($SscKjDatas);
        if($dataCount == 0){
            return [
                'current_times' => $last_index_id,
                'last_times' => $last_times,
                'last_time_miss_range' => '',
                'max_miss' => $max_miss,
                'max_range' => $max_range,
                'yl_str' => $yl_str,
            ];
        }
        if($dataCount > 1){
            $last_times = $SscKjDatas[0]->index_id - $SscKjDatas[1]->index_id - 1;  // 上次遗漏次数
        }

        # 最大遗漏期间计算 start
        $tmpKjData = $SscKjDatas;
        if(count($tmpKjData) > 2){
            $range = [];
            foreach($tmpKjData as $key=>$r){
                if($key == 0) continue;
                $range[$tmpKjData[$key-1]['index_id']."_".$tmpKjData[$key]['index_id']] = $tmpKjData[$key-1]['index_id'] - $r['index_id'] - 1;
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
            $max_range = ($tmpArr[1] ?? '').'-'.($tmpArr[0] ?? '');  // 近200期内最大遗漏
            $yl_str = implode('-',$range);
            # 最大遗漏期间计算 end
            //p([$field=>$num,$min_id, $SscKjData[1]->id,$max_range]);
        }elseif($dataCount > 1){
            $max_range = $SscKjDatas[1]['qihao'] ."-". $SscKjDatas[0]['qihao'];
        }else{
            $max_range = $SscKjDatas[0]['qihao'] ."-". $SscKjDatas[0]['qihao'];
        }
        $last_time_miss_range = $dataCount > 1 ? $SscKjDatas[1]['qihao'] ."-". $SscKjDatas[0]['qihao'] : $SscKjDatas[0]['qihao'] ."-". $SscKjDatas[0]['qihao'];
        $current_times = $last_index_id - $SscKjDatas[0]->index_id;

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
     * @param $value - 例如：001[type:3] 或者 1223[type:4] 或者 1234[type:5] === 3三字现带双重4四字现带双重5四字现不带双重
     * @param int $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @param int $recently - 多少期内，默认为
     * @return array
     */
    public static function getCodeTypeYlHistoryMiss($value, int $lottery_type = DEFAULT_LOTTERY_TYPE, int $recently = 2000, $type = 4, $isCache = 1): array
    {
        $m = \Yii::$app->cache;
        $SscKjDatas = SscDataService::getTabLastKjData($lottery_type);
        $mkey = 'getCodeTypeYlHistoryMiss_'.$lottery_type.'_'.$recently.'_'.$isCache.'_'.$value;
        $field = strlen($value) == 3 ? 'code_3n' : 'code_4n'; # 三字现、四字现
        $flag = strpos($SscKjDatas[$field] ?? '', $value) !== false; # 匹配则说明中奖

        $staticFlag = BetService::getConfig('is_cache_data');

        if($isCache && !$flag && $staticFlag && $rstData = $m->get($mkey)){
            $rstData['current_times'] = $rstData['current_times'] + 1;
            return $rstData;
        }
        $last_times = 0;
        $max_miss = 0;
        $max_range = '';
        $yl_str = '';
        $last_index_id = SscDataService::getLastIndexId($lottery_type);
        //$min_id = $last_index_id - $recently;
        $min_id = SscDataService::getMinStaticId($last_index_id, $recently);
        //p([$value, $recently, $min_id]);
        $where = ['AND', ['=', 'lottery_type', $lottery_type], ['>', 'index_id', $min_id], ['LIKE', $field, $value]];
        if($type == 5){
            # 四字现双重 如：123，包括：1123、1223、1233
            $where = array_merge($where, [['=', 'type_2', 1]]);
        }
        $SscKjDatas = SscKjData::find()->select(['id', 'index_id', 'code_3n', 'code_4n', 'qihao', 'kj_code'])->where($where)->orderBy('id DESC')->asArray()->all();
        //p([$where, $SscKjDatas]);
        $dataCount = count($SscKjDatas);
        if($dataCount == 0){
            $rstData = [
                'current_times' => $last_index_id,
                'last_times' => $last_times,
                'last_time_miss_range' => '',
                'max_miss' => $max_miss,
                'max_range' => $max_range,
                'yl_str' => $yl_str,
            ];
            $m->set($mkey, $rstData, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);
            return $rstData;
        }
        if($dataCount > 1){
            $last_times = $SscKjDatas[0]['index_id'] - $SscKjDatas[1]['index_id'] - 1;  // 上次遗漏次数
        }

        # 最大遗漏期间计算 start
        $tmpKjData = $SscKjDatas;
        if(count($tmpKjData) > 2){
            $max_len = 0;
            $allKjData = [];
            foreach($tmpKjData as $key=>$r){
                if($key == 0) continue;
                $len = $tmpKjData[$key-1]['index_id'] - $tmpKjData[$key]['index_id'] - 1;
                $range[$tmpKjData[$key-1]['index_id'].'_'.$tmpKjData[$key]['index_id']] = $len;
                $allKjData[$r['index_id']] = $r;
                if($len > $max_len){
                    $max_len =  $len;
                    $tmpArrKey = [$tmpKjData[$key]['index_id'], $tmpKjData[$key-1]['index_id']];
                }
            }
            $tmpArr[0] = $allKjData[$tmpArrKey[0]]['qihao'];
            $tmpArr[1] = $allKjData[$tmpArrKey[1]]['qihao'];

            $max_miss = max($range);
            $max_range = $tmpArr[1].'-'.$tmpArr[0];  // 近200期内最大遗漏
            $yl_str = implode('-',$range);
            # 最大遗漏期间计算 end
            //p([$field=>$num,$min_id, $SscKjData[1]->id,$max_range]);
        }elseif($dataCount > 1){
            $max_range = $SscKjDatas[1]['qihao'] .'-'. $SscKjDatas[0]['qihao'];
        }else{
            $max_range = $SscKjDatas[0]['qihao'] .'-'. $SscKjDatas[0]['qihao'];
        }
        $last_time_miss_range = $dataCount > 1 ? $SscKjDatas[1]['qihao'] .'-'. $SscKjDatas[0]['qihao'] : $SscKjDatas[0]['qihao'] .'-'. $SscKjDatas[0]['qihao'];
        $current_times = $last_index_id - $SscKjDatas[0]['index_id'];
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
        return $rstData;
    }
}
