<?php

namespace common\service;
use backend\models\BetErrorPlansTask;
use backend\models\BettingRecords;
use backend\models\CodeTypes;
use backend\models\LotteryType;
use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use backend\models\TzTypes;
use backend\models\UserFollowData;
use backend\models\UserSysPlans;
use backend\service\NumService;
use backend\service\StaticService;
use common\models\AuthItem;
use common\models\thirdD\PlayMethod;
use common\models\User;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\MethodMatchService;
use common\tools\Tool_Common;
use backend\service\CurlService;
use backend\service\UserService;
use backend\models\SscKjData;
use common\tools\Tools;
use Yii;

class  CommonService{


    /**
     * @description 用户设置成代理、删除用户代理时候处理业务
     * @param $admin_id
     * @param $action
     * @param string $role
     * @return bool
     */
    public static function opUser($admin_id, $action, $role = '收费会员'): bool
    {
        try {
            $role = ($role=='七星')?'收费会员':$role;
            if(!AuthItem::find()->where(['name'=>$role])->limit(1)->one()){
                $authItem = new AuthItem();
                $setData = [
                    'name' => $role,
                    'type' => 1,
                    'description' => $role,
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
                $authItem->setAttributes($setData);
                $authItem->save();
            }
            # 1、时时彩用户记录添加
            $rst1 = UserService::opUser($admin_id, $action, $role);
            //$rst1 = UserService::opTzSystemsUsers($admin_id, $action, $role);
        }catch (\Exception $e){
            Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '处理用户信息', ['admin_id', 'action'=>$action, 'role'=>$role, 'err_msg'=>$e->getMessage()]);
            throw_info($e);
        }

        return $rst1;
    }

    //返回当前的毫秒时间戳
    public static function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return $msectime;
    }

    /**
     * @decription 号码筛选处理
     * @param $init_code
     * @param $qihao
     * @param int $playway
     * @return mixed|string
     */
    public static function filterCode($init_code = ',,234567,,', $qihao , $playway = 10, $switchCode = 8 ){
        $shuffle_arr = [1, 5, 4, 3, 2, 8, 7, 9];
        shuffle($shuffle_arr);
        $preQihao_offset = $shuffle_arr; // 排除前面对应期数的号码
        $codes = explode(',', $init_code);
        foreach ($codes as $index=>$code){
            if($code){
                $position = $index;
                $tzCodes = $code;
                break;
            }
        }
        //p([$codes,$index]);
        foreach ($preQihao_offset as $offset){
            if(strlen($tzCodes) < 6) break;
            $new_qihao = $qihao - $offset;
            $getCode = CommonService::getAwardNumberByQihao($new_qihao);
            $awardCodes = explode(',', $getCode);

            if($awardCodes[$position] == $switchCode){
                $init_code = $init_code == ',,234567,,' ? ',,135678,,' : ',,234567,,';
                $userFollowData = UserFollowData::findOne(1);
                $userFollowData->code = $init_code;
                $userFollowData->save();
                return self::filterCode($init_code,$qihao,$switchCode, $playway);
            }
            $tzCodes = str_replace($awardCodes[$position],'',$tzCodes);
        }
        $codes[$index] = $tzCodes;
        $tzCodes = implode(',', $codes);

        return $tzCodes;
    }

    /**
     * @decripion 获取时时彩开奖号码
     * @param $qihao
     * @param string $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
     * @return string
     */
    public static function getAwardNumberByQihao($qihao, $lottery_type = DEFAULT_LOTTERY_TYPE): string
    {
        if(!$qihao) return false;
        $m = \Yii::$app->cache;

        $mkey = 'KJ_DATA_x3_'.$lottery_type.'_'.$qihao;
        if(!$kjData = $m->get($mkey)){
            $kjData = SscKjData::find()->select(['code_str'])->where(['qihao'=>$qihao, 'lottery_type'=>$lottery_type])->asArray()->limit(1)->one()['code_str'];
            if(!$kjData){
                return false;
            }
            $m->set($mkey, $kjData, 7*60*60);
        }

        return $kjData;
   }

    /**
     * @decription 开奖处理
     * @return array
     */
    public static function kj(){
        $rst = ['status'=>200, 'msg'=>'开奖数据处理完成!'];

        $where = ['status'=>0];
        $bettingRecords = BettingRecords::find()->where($where)->orderBy(['id'=>SORT_DESC])->all();
        foreach ($bettingRecords as $bettingRecord){
            # 开奖数据 start
            $kjData = self::getAwardNumberByQihao($bettingRecord['qihao']);
            $kjDatas = explode(',',$kjData);
            # 开奖数据 end

            $tzData = $bettingRecord['codes']; // 投注号码
            $tzDatas = explode(',',$tzData);
            foreach ($tzDatas as $key=>$data){
                if($data) {
                    $kjNum = $kjDatas[$key];
                    $record = BettingRecords::findOne($bettingRecord['id']);
                    //p([$kjNum,$bettingRecord['qihao'],$bettingRecord['codes']]);
                    if(strstr($tzData,$kjNum)){
                        # 中奖
                        $record->status = 1;
                        $record->bonus = ( $bettingRecord['betting_money'] / strlen($data) ) * 9.75;
                    }else{
                        $record->status = 2;
                    }
                    $rst = $record->save();
                }
            }
        }

        return $rst;
    }

    /**
     * @description 根据日期端生成时间数组
     * @param string $split
     * @param string $date_start
     * @param string $date_end
     * @return array
     */
    public static function genDateArr($split = '', $date_start = '20180101', $date_end = '20180502'){
        $dateArr = [];
        $date_start = strtotime($date_start);
        //$date_end =  strtotime($date_end);
        $date_end =  strtotime(date('Ymd'));

        for ($date_start;$date_start <= $date_end; $date_start += 3600*24){
            $dateArr[] = date('y'.$split.'m'.$split.'d',$date_start);//得到dataarr的日期数组。
        }
        return $dateArr;
    }

    /**
     * @description 开奖数据三字现统计记录
     * @return array
     */
    public static function opKjThreeNum($start_qihao = '001', $end_qihao = '120'){
        $msg = ['status'=>200, 'msg'=>'数据处理完成!', 'time'=>date('Y-m-d H:i:s')];

        $dateArr = self::genDateArr($split = '', $date_start = '20180101', $date_end = '20180502');
        $tmpInsertData = [];
        foreach ($dateArr as $key=>$date){
            $qihao = ltrim($date_start.'001','20');
            $end_qihao = ltrim($date_start.'120','20');
            for($qihao; $qihao <= $end_qihao; $qihao++){
                # 开奖数据 start
                $kjData = self::getAwardNumberByQihao($qihao);
                $kjDatas = explode(',',$kjData);
                # 开奖数据 end
                if(count($kjDatas) > 4){
                    array_pop($kjDatas);
                    if(count($kjDatas) < 3){
                        continue;
                    }
                }
                array_shift($kjDatas);
                sort($kjDatas);
                $tmpData = [];
                $num3xArr = self::get3x($kjDatas);
                $tmpData = ['qihao'=>$qihao, 'num3xData'=>$num3xArr];
                $tmpInsertData[] = $tmpData;
            }
        }

        $field = ['kj_code','issue','date'];
        $insertData = [];
        foreach ($tmpInsertData as $key=>$num3x){
            $tmpQihao = $num3x['qihao'];
            foreach ($num3xArr['num3xData'] as $tmpKey=>$num3x){
                $insertData[$key][] = $num3x;   // 开奖号码
                $insertData[$key][] = $tmpQihao;   // 开奖期号
            }
        }
        if(!$rst = \Yii::$app->db->createCommand()->batchInsert("{{%ssc_three_num_trend}}",$field,$insertData)->execute()){
            $msg['status'] = 300;
            $msg['msg'] = '数据处理异常';
        }

        return $msg;
    }

    /**
     * @description 按照集合生成三字现组合
     * @param $numArr
     * @return array
     */
    public static function get3x($nums){
        if(count($nums) < 3) return [];
        $datas = [];
        sort($nums);
        foreach ($nums as $key1=>$num1){
            unset($nums[$key1]);
            foreach ($nums as $key2=>$num2){
                if($num1 >= $num2) continue;
                foreach ($nums as $key3=>$num3){
                    if($num1 >= $num3 OR $num3 <= $num2) continue;
                    $datas[] = $num1.$num2.$num3;
                }
            }
        }
        $datas = array_values(array_unique($datas));

        return $datas;
    }

    /**
     * @desc 返回二字现，含双重或者不含，排序过后的三字现，可用于组三、组六的开奖处理
     * @param $codesArr [3, 6, 7, 8] 或者 [3, 4, 4, 6] 或者 [3, 3, 3, 7] 或者 [3,3,3,3] 必须四个号码
     * @return array
     */
    public static function get2n($codesArr, $lottery_type=DEFAULT_LOTTERY_TYPE){
        if(count($codesArr) != 4 && !in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES)) return [];
        if(in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES)){
            $codesArr = [$codesArr[0], $codesArr[1], $codesArr[2]]; # 排列三、福彩3D 支取前面三个
        }
        sort($codesArr);
        if(in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES)){
            $data = [
                $codesArr[0].$codesArr[1],
                $codesArr[0].$codesArr[2],
                $codesArr[1].$codesArr[2],
            ];
        }else{
            $data = [
                $codesArr[0].$codesArr[1],
                $codesArr[0].$codesArr[2],
                $codesArr[0].$codesArr[3],
                $codesArr[1].$codesArr[2],
                $codesArr[1].$codesArr[3],
                $codesArr[2].$codesArr[3],
            ];
        }
        $data = array_values(array_unique($data));

        return $data;
    }

    /**
     * @desc 返回三字现，含双重或者不含，排序过后的三字现，可用于组三、组六的开奖处理
     * @param $codesArr [3, 6, 7, 8] 或者 [3, 4, 4, 6] 或者 [3, 3, 3, 7] 或者 [3,3,3,3] 必须四个号码
     * @return array
     */
    public static function get3n($codesArr, $lottery_type=DEFAULT_LOTTERY_TYPE){
        if(count($codesArr) != 4 && !in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES)) return [];
        if(in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES)){
            $codesArr = [$codesArr[0], $codesArr[1], $codesArr[2]]; # 排列三、福彩3D 支取前面三个
        }
        sort($codesArr);
        if(in_array($lottery_type, CommonBaseService::THIRDD_LOTTERY_TYPES)){
            $data = [$codesArr[0].$codesArr[1].$codesArr[2]];
        }else{
            $data = [
                $codesArr[0] . $codesArr[1] . $codesArr[2],
                $codesArr[0] . $codesArr[1] . $codesArr[3],
                $codesArr[0] . $codesArr[2] . $codesArr[3],
                $codesArr[1] . $codesArr[2] . $codesArr[3],
            ];
        }
        $data = array_values(array_unique($data));

        return $data;
    }

    /**
     * @desc 给定所有投注字符串生成定位所有组合 二字定、三字定、四字定生成
     * @param $codes 投注号码，格式：0,X,8,X@0,X,8,X@1,X,7,X@1,X,7,X@2,X,6,X@2,X,6,X@3,X,5,X@3,X,5,X@4,X,4,X
     * @param string $split 分割字符：@
     * @param string $nullCode 空号码 为X或者空
     * @return array
     */
    public static function genDw($codes, $groupSplit = '@', $nullCode='X'){
        $allGroup = [];
        $preCodes = explode($groupSplit,$codes);
        foreach ($preCodes as $codes){
            $codesArr = explode(',',$codes); // Array ( [0] => 03 [1] => X [2] => 83 [3] => X )
            $nullCodeKey = [];
            foreach ($codesArr as $key1=>$code){
                if(strlen($code) > 1){
                    $forcount = strlen($code);
                    $tmpCodes = [];
                    for ( $i=0; $i<$forcount; $i++ ){
                        $tmpCodes[] = $code[$i];
                    }
                    $codesArr[$key1] = $tmpCodes;
                }else{
                    if($code == $nullCode) $nullCodeKey[] = $key1;
                    $codesArr[$key1] = [ $code ];
                }
            }
            //p(['nullCodeKey'=>$nullCodeKey,'codeKeys'=>$codeKeys,'codesArr'=>$codesArr],0);

            $tmpCodesArr1 = [];
            $tmpArr = [];
            foreach ($codesArr[0] as $key0=>$codes0) {
                foreach ($codesArr[1] as $key1 => $codes1) {
                    $tmpArr = [$codes0, $codes1];
                    $tmpCodesArr1[] = $tmpArr;
                }
            }
            $tmpCodesArr2 = [];
            foreach ($codesArr[2] as $key2 => $codes2) {
                foreach ($tmpCodesArr1 as $tmpKey2=>$tmpCodes2){
                    $tmpCodes2[] = $codes2;
                    $tmpCodesArr2[] = $tmpCodes2;
                }
            }

            $tmpCodesArr3 = [];
            foreach ($codesArr[3] as $key3=>$codes3){
                foreach ($tmpCodesArr2 as $tmpKey3=>$tmpCodes3){
                    $tmpCodes3[] = $codes3;
                    $tmpCodesArr3[] = $tmpCodes3;
                }
            }
            $allCodesGroup = $tmpCodesArr3;

            if(count($codesArr)>4){
                $tmpCodesArr4 = [];
                foreach ($codesArr[4] as $key4=>$codes4){
                    foreach ($tmpCodesArr3 as $tmpKey4=>$tmpCodes4){
                        $tmpCodes4[] = $codes4;
                        $tmpCodesArr4[] = $tmpCodes4;
                    }
                }
                $allCodesGroup = $tmpCodesArr4;
            }
            //p($tmpCodesArr3);
            $allGroup = array_merge($allGroup,$allCodesGroup);
        }

        return $allGroup;
    }

    /**
     * @desc 给定所有投注字符串生成定位所有组合 定位胆
     * @param $codes 投注号码，格式：,,,12357,6@,,345,,8
     * @param string $split 分割字符：@
     * @param string $nullCode 空号码 为X或者空
     * @return array
     */
    public static function genDw10($codes, $groupSplit = '@',$codeSplit = ',', $nullCode=''){
        $preCodes = explode($groupSplit,$codes);
        $allGroup = [];
        foreach ($preCodes as $preCodes){
            $codesArr = explode($codeSplit,$preCodes); // Array ( [0] => 03 [1] => X [2] => 83 [3] => X )
            $nullCodeKey = [];
            foreach ($codesArr as $key1=>$code){
                if(strlen($code) > 1){
                    $forcount = strlen($code);
                    $tmpCodes = [];
                    for ( $i=0; $i<$forcount; $i++ ){
                        $tmpCodes[] = $code[$i];
                    }
                    $codesArr[$key1] = $tmpCodes;
                }else{
                    if(!$code)
                        $codesArr[$key1] = [];
                    else{
                        $codesArr[$key1] = [$code];
                    }
                }
            }
            //p(['nullCodeKey'=>$nullCodeKey,'codesArr'=>$codesArr],0);

            foreach ($codesArr as $key=>$codes) {
                if(empty($codes)) continue;
                foreach ($codes as $key1 => $codes1) {
                    if($key == 0){
                        $allGroup[] = [$codes1,'','','',''];    // 万位
                    }elseif($key == 1){
                        $allGroup[] = ['',$codes1,'','',''];    // 千位
                    }elseif($key == 2){
                        $allGroup[] = ['','',$codes1,'',''];    // 百位
                    }elseif($key == 3){
                        $allGroup[] = ['','','',$codes1,''];    // 十位
                    }elseif($key == 4){
                        $allGroup[] = ['','','','',$codes1];    // 个位
                    }
                }
            }

        }

        return $allGroup;
    }

    /**
     * @description 赔率数据
     * @param int $playway
     * @return mixed
     */
    public static function getOdds($playway = 10, $uid='', $type = 'odds'){
        $playways = [
            1 => ['odds'=>98,'name'=>'两字定'],  // 两字定
            2 => ['odds'=>995,'name'=>'三字定'],   // 三字定
            3 => ['odds'=>9950,'name'=>'四字定'],  // 四字定
            4 => ['odds'=>9.8,'name'=>'一字定'],  // 一字定
            5 => ['odds'=>9.60,'name'=>'二字现'],  // 二字现
            6 => ['odds'=>48.00,'name'=>'三字现'], // 三字现
            10 => ['odds'=>9.8,'name'=>'定位胆'], // 定位胆
            11 => ['odds'=>98,'name'=>'后二'], // 后二
            12 => ['odds'=>980,'name'=>'后三'],  // 后三
            13 => ['odds'=>1.95,'name'=>'大小单双'], // 大小单双
            14 => ['odds'=>98,'name'=>'前二'], // 前二
            15 => ['odds'=>98,'name'=>'前三'],  // 前三
            16 => ['odds'=>300,'name'=>'组三'],  // 组三
            17 => ['odds'=>140,'name'=>'组六'],  // 组六
        ];

        if(!empty($uid)){
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
            if(!empty($TzSystemsUsers)){
                $playways[1] = ['odds'=>$TzSystemsUsers->odds_2d,'name'=>'两字定'];  // 两字定
                $playways[2] = ['odds'=>$TzSystemsUsers->odds_3d,'name'=>'三字定'];  // 三字定
                $playways[3] = ['odds'=>$TzSystemsUsers->odds_4d,'name'=>'四字定'];  // 四字定
            }
        }

        if($playway && $playways[$playway][$type]){
            return $playways[$playway][$type];
        }
        return $playways;
    }

    /**
     * @desc 是否双重 - 四定
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType2($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = array_unique($codesArr);
        if(count($codesArr)<=3) $flag = 1;
        $flag = self::isCodeType4($codes) == 1 ? 1 : $flag; # 四重属于双重

        return $flag;
    }

    /**
     * @desc 是否双重 - 二、三定
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType_2($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = NumService::delByValue($codesArr, 'X');
        if(count($codesArr) == 3){
            if($codesArr[0] == $codesArr[1] OR $codesArr[1] == $codesArr[2] OR $codesArr[0] == $codesArr[2] ) $flag = 1;
        }else{
            if($codesArr[0] == $codesArr[1]) $flag = 1;
        }

        return $flag;
    }

    /**
     * @desc 是否双重 - 二、三定
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType_3($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = NumService::delByValue($codesArr, 'X');
        if(count($codesArr) == 3){
            if($codesArr[0] == $codesArr[1] OR $codesArr[1] == $codesArr[2] OR $codesArr[0] == $codesArr[2] ) $flag = 1;
        }else{
            if($codesArr[0] == $codesArr[1]) $flag = 1;
        }

        return $flag;
    }


    /**
     * @desc 是否双双重
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType22($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = array_unique($codesArr);
        if(count($codesArr)==2) $flag = 1;
        $flag = self::isCodeType3($codes) == 1 ? 0 : $flag; # 三重不属于双双重
        $flag = self::isCodeType4($codes) == 1 ? 1 : $flag; # 四重属于双双重

        return $flag;
    }

    /**
     * @desc 是否三重
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType3($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        if(count($codesArr)==2) $flag = 1;
        $ArrayCountVals = array_count_values($codesArr);
        foreach ($ArrayCountVals as $val){
            if($val >= 3) $flag = 1;
        }

        return $flag;
    }

    /**
     * @desc 是否四重
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType4($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = array_unique($codesArr);
        if(count($codesArr)==1) $flag = 1;

        return $flag;
    }

    /**
     * @desc 是否两兄弟 - 四定
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType2b($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = NumService::delByValue($codesArr, 'X');
        asort($codesArr);
        $codes_str = implode(',', $codesArr);
        $bArrs = [
            '0,1',
            '1,2',
            '2,3',
            '3,4',
            '4,5',
            '5,6',
            '6,7',
            '7,8',
            '8,9',
            '0,9',
        ];
        foreach ($bArrs as $bArr){
            if(strpos($codes_str, $bArr) !== false) $flag = 1;
        }
        if(in_array(0, $codesArr) && in_array(9, $codesArr)) $flag = 1;

        return $flag;
    }

    /**
     * @desc 是否双两兄弟 - 四定
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType22b($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = NumService::delByValue($codesArr, 'X');
        sort($codesArr);
        $code_1 = $codesArr[0].','.$codesArr[1];
        $code_2 = $codesArr[2].','.$codesArr[3];

        $code_3 = $codesArr[0].','.$codesArr[3];
        $code_4 = $codesArr[1].','.$codesArr[2];
        $bArrs = [
            '0,1',
            '1,2',
            '2,3',
            '3,4',
            '4,5',
            '5,6',
            '6,7',
            '7,8',
            '8,9',
            '0,9',
        ];
        if((in_array($code_1, $bArrs) && in_array($code_2, $bArrs)) OR (in_array($code_3, $bArrs) && in_array($code_4, $bArrs))){
            $flag = 1;
        }

        return $flag;
    }

    /**
     * @desc 是否三兄弟
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType3b($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = NumService::delByValue($codesArr, 'X');
        $codesArr = array_unique($codesArr);
        asort($codesArr);
        $codes_str = implode(',', $codesArr);
        $bArrs = [
            '0,1,2',
            '0,1,9',
            '0,8,9',
            '1,2,3',
            '2,3,4',
            '3,4,5',
            '4,5,6',
            '5,6,7',
            '6,7,8',
            '7,8,9',
        ];
        foreach ($bArrs as $bArr){
            if(strpos($codes_str, $bArr) !== false) $flag = 1;
            if(in_array($bArr, ['0,8,9', '0,1,9'])){
                $bArrCodes = explode(',', $bArr);
                if(strpos($codes_str, $bArrCodes[0]) !== false && strpos($codes_str, $bArrCodes[1]) !== false && strpos($codes_str, $bArrCodes[2]) !== false) $flag = 1;
            }
        }

        return $flag;
    }

    /**
     * @desc 是否四兄弟
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType4b($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = NumService::delByValue($codesArr, 'X');
        asort($codesArr);
        $codes_str = implode(',', $codesArr);
        $bArrs = [
            '0,1,2,3',
            '1,2,3,4',
            '2,3,4,5',
            '3,4,5,6',
            '4,5,6,7',
            '5,6,7,8',
            '6,7,8,9',
            '0,7,8,9',
            '0,1,8,9',
            '0,1,2,9',
        ];
        foreach ($bArrs as $bArr){
            if(strpos($codes_str, $bArr) !== false) $flag = 1;
        }

        return $flag;
    }

    /**
     * @desc 四定单双:0保留1四单2四双3两单两双4一单三双5一双三单
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType4ds($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        asort($codesArr);

        $types_1 = [
            0 => 2, # 四双
            1 => 4, # 一单三双
            2 => 3, # 两双两单
            3 => 5, # 一双三单
            4 => 1, # 四单
        ];

        $count_1 = 0; # 单数量
        $count_2 = 0; # 双数量
        /*
        # 双数量判断
        $flag_4d = 1;
        foreach ($codesArr as $code){
            if($code % 2 == 0) {
                $count_2++;
                $flag_4d = 0;
            }
        }
        */

        # 单数量判断
        $flag_4s = 2;
        foreach ($codesArr as $code){
            if($code % 2 == 1) {
                $count_1++;
                $flag_4s = 0;
            }
        }

        $flag = $types_1[$count_1];
        //return max($flag, $flag_4d, $flag_4s);
        return $flag;
    }

    /**
     * @desc 四定单双:0保留1四单2四双3两单两双4一单三双5一双三单
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType4numDs($codes){
        $codesArr = explode(',', $codes);
        asort($codesArr);

        $types_1 = [
            0 => 2, # 四双
            1 => 4, # 一单三双
            2 => 3, # 两双两单
            3 => 5, # 一双三单
            4 => 1, # 四单
        ];

        $count_1 = 0; # 单数量

        # 单数量判断
        foreach ($codesArr as $code){
            if($code % 2 == 1) {
                $count_1++;
            }
        }

        $flag = $types_1[$count_1];
        return $flag;
    }

    /**
     * @desc 是否对数
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeTypeLog($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = NumService::delByValue($codesArr, 'X');
        asort($codesArr);

        if(in_array(0, $codesArr) && in_array(5, $codesArr)){
            $flag = 1;
        }

        if(in_array(1, $codesArr) && in_array(6, $codesArr)){
            $flag = 1;
        }

        if(in_array(2, $codesArr) && in_array(7, $codesArr)){
            $flag = 1;
        }

        if(in_array(3, $codesArr) && in_array(8, $codesArr)){
            $flag = 1;
        }

        if(in_array(4, $codesArr) && in_array(9, $codesArr)){
            $flag = 1;
        }

        return $flag;
    }

    /**
     * @desc 是否双对数
     * @param string $codes 格式 1,2,3,8
     * @return int
     */
    public static function isCodeType2Log(string $codes): int
    {
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = array_unique($codesArr);
        sort($codesArr);
        if(count($codesArr)==4){
            if(($codesArr[2]-$codesArr[0]==5) && ($codesArr[3]-$codesArr[1]==5)){
                $flag = 1;
            }
        }
        if(count($codesArr)==2){
            if(($codesArr[1]-$codesArr[0])==5){
                $flag = 1;
            }
        }

        return $flag;
    }

    /**
     * @desc 是否 三现:双重+兄弟
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType3n2b($codes){
        $flag = 0;
        $m = \Yii::$app->cache;
        $mkey = 'isCodeType3n2b_codes_'.$codes;
        if(!$code3n2nArr = $m->get($mkey)){
            $code = CodeTypes::find()->where(['type'=>1])->one()['codes'];
            $code3n2nArr = explode(',', $code);
            $m->set($mkey, $code3n2nArr, 3600*24);
        }

        $codesArr = explode(',', $codes);
        asort($codesArr);
        $code_3ns = CommonService::get3n($codesArr);

        foreach ($code_3ns as $code_3n){
            if(in_array($code_3n, $code3n2nArr)) $flag = 1;
        }

        return $flag;
    }

    /**
     * @desc 跨度获取
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function getKuaDu($codes){
        $codeArr = explode(',', trim($codes));
        $min = min($codeArr);
        $max = max($codeArr);
        $kd = $max - $min;

        return $kd;
    }

    /**
     * @desc 前三：1组三、2组六、3豹子判断
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeTypeZxBz($codes){
        $m = \Yii::$app->cache;
        $codeArr = explode(',', $codes);
        $mkey = 'isCodeTypeZxBz_codes_'.$codeArr[0].$codeArr[1].$codeArr[2];
        if(true OR !$flag = $m->get($mkey)){
            if($codeArr[0]==$codeArr[1] && $codeArr[1]==$codeArr[2]){
                $flag = MethodMatchService::CODE_TYPE_BAO_ZI;
            }elseif ($codeArr[0]==$codeArr[1] OR $codeArr[0]==$codeArr[2] OR $codeArr[1]==$codeArr[2]){
                $flag = MethodMatchService::CODE_TYPE_ZU_SAN;
            }else{
                $flag = MethodMatchService::CODE_TYPE_ZU_LIU;
            }
            $m->set($mkey, $flag, 86400 * 7);
        }

        return $flag;
    }

    /**
     * @desc 和值
     * @param string $codes 格式 1,2,3
     * @return int
     */
    public static function getHeZhi($codes){
        $codeArr = explode(',', $codes);
        $heZhi = array_sum($codeArr);

        return $heZhi;
    }

    /**
     * @desc 是否双重 - 三字现
     * @param string $codes 格式 1,2,3
     * @return int
     */
    public static function isCodeType2_3z($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = array_unique($codesArr);
        if(count($codesArr)==2) $flag = 1;
        $flag = self::isCodeType3_3z($codes) == 1 ? 1 : $flag; # 三重属于双重

        return $flag;
    }

    /**
     * @desc 是否三重 - 三字现
     * @param string $codes 格式 1,2,3
     * @return int
     */
    public static function isCodeType3_3z($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = array_unique($codesArr);
        if(count($codesArr)==1) $flag = 1;

        return $flag;
    }

    /**
     * @desc 获取大小类型
     * @param string $codes 格式 6,2,7,4,5
     * @return array [3, '1122', '2大2小']
     */
    public static function getTypeDx($codes){

        $codesArr = explode(',', $codes);

        $code1_dx = in_array($codesArr[0], NumService::$MIN_CODES)? '1':'2';
        $code2_dx = in_array($codesArr[1], NumService::$MIN_CODES)? '1':'2';
        $code3_dx = in_array($codesArr[2], NumService::$MIN_CODES)? '1':'2';
        $code4_dx = in_array($codesArr[3], NumService::$MIN_CODES)? '1':'2';
        $code5_dx = in_array($codesArr[4], NumService::$MIN_CODES)? '1':'2';

        $numArr = [$code1_dx, $code2_dx, $code3_dx, $code4_dx];
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
        $type_dx = NumService::getType4dx($type_dx_str);;

        $type_4dx = $code1_dx.$code2_dx.$code3_dx.$code4_dx.$code5_dx;

        return [$type_dx, $type_4dx, $type_dx_str];
    }

    /**
     * @desc 排序号码
     * @param array $codesArr 格式 [12, 43, 796]
     * @return array
     */
    public static function reSortCodes(array $codesArr): array
    {
        if(empty($codesArr)){
            return $codesArr;
        }

        $codeData = [];
        foreach ($codesArr as $code){
            $code = (string)$code;
            $tmpCode = [];
            for ($i=0; $i<strlen($code); $i++){
                $tmpCode[] = $code[$i];
            }
            sort($tmpCode);
            $codeData[] = implode('', $tmpCode);
        }

        return $codeData;
    }

    /**
     * @desc 所有投注系统
     * @param int $status
     * @return array
     */
    public static function getAllSystems($type = 0, $status = 1){

        $where = ['status'=>$status];
        if(!empty($type)){
            $where['type'] = $type;
        }
        $datas = TzSystems::find()->where($where)->asArray()->all();

        $dataArr = [];

        foreach ($datas as $key=>$data){
            $dataArr[$data['id']] = $data['name'];
        }

        return $dataArr;
    }

    /**
     * @desc 是否有激活的正常计划， 主要用于判断是否要开启代理
     * @param int $lottery_type
     * @return int
     */
    public static function hasPlansActiveSys($tz_system_id = 9, $uid=''){
        $where = ['AND', ['=', 'tz_sites', $tz_system_id], ['=', 'uid', $uid], ['=', 'status', 1]];
        $row = UserSysPlans::find()->where($where)->one();

        $flag = !empty($row) ? 1 : 0;

        return $flag;
    }

    public static function hasRealPlansActiveSys($tz_system_id = 9, $uid=''){
        return UserSysPlans::find()->where([
            'uid'=>$uid,
            'status'=>1,
            'is_test'=>0,
            'is_batch_simulate'=>0,
        ])->andWhere('FIND_IN_SET(:tz_system_id, tz_sites)', [
            ':tz_system_id'=>(int)$tz_system_id,
        ])->exists();
    }

    /**
     * @desc 是否有激活的正常计划， 主要用于判断是否要开启代理
     * @param int $lottery_types
     * @return int
     */
    public static function hasPlansActiveLottery($lottery_types = [], $proxy_type=1){
        $where = ['AND', ['IN', 'p.lottery_type', $lottery_types], ['=', 'p.status', 1], ['=', 'u.proxy_type', $proxy_type], ['=', 'p.is_test', 0], ['=', 'u.is_use_proxy', 1]];
        $row = UserSysPlans::find()->alias('p')->select(['p.*'])
            ->leftJoin('{{%tz_systems_users}} u', 'p.uid=u.uid')
            ->where($where)->one();

        $flag = !empty($row) ? 1 : 0;

        return $flag;
    }

    /**
     * @desc 是否有激活的正常计划， 主要用于判断是否要开启代理
     * @param int $lottery_type
     * @return int
     */
    public static function hasPlansActive($lottery_type = DEFAULT_LOTTERY_TYPE){
        $where = ['AND', ['=', 'lottery_type', $lottery_type], ['=', 'status', 1]];
        $row = UserSysPlans::find()->where($where)->one();

        $flag = !empty($row) ? 1 : 0;

        return $flag;
    }

    /**
     * @desc 主要针对 - 九九网 用户网站临时 存储数据
     * @param $uid
     * @param $tz_system_id
     * @return string
     */
    public static function buildXCsrfTokenKey($uid, $tz_system_id){
        return 'buildXCsrfTokenKey_'.$uid.'_'.$tz_system_id;
    }

    /**
     * @desc 所有网盘对应的投注类型
     * @param int $type
     * @param int $status
     * @return array
     */
    public static function getSystemTzTypes($type=1, $status=1){
        $datas = TzSystems::find()->select(['id', 'tz_types'])->where(['status'=>$status, 'type'=>$type])->asArray()->all();

        $dataArr = [];

        foreach ($datas as $key=>$data){
            if(empty($data['tz_types'])) continue;
            $dataArr[$data['id']] = explode(',', $data['tz_types']);
        }

        return $dataArr;
    }

    /**
     * @desc 所有投注类型
     * @param int $status
     * @return array
     */
    public static function getAllTzTypes($play_type = 2, $type = 'lottery'){

        $datas = StaticService::$kArr;

        unset($datas[0],$datas[1],$datas[10],$datas[11]);

        $datas = TzTypes::find()->where(['status'=>1, 'play_type'=>$play_type])->asArray()->all();

        $dataArr = [];

        foreach ($datas as $key=>$data){
            $dataArr[$data['type']] = $data['type_name'];
        }

        return $dataArr;
    }

    /**
     * @desc 所有彩票类型
     * @param int $status
     * @return array
     */
    public static function getAllLotteryTypes(){

        $datas = LotteryType::find()->where(['enable'=>1])->asArray()->all();

        $dataArr = [];

        foreach ($datas as $key=>$data){
            $dataArr[$data['lottery_type']] = $data['title'];
        }

        return $dataArr;
    }

     /**
     * @desc 获取号码类型名称
     * @param int $code_type
     * @return array|mixed
     */
    public static function getCodeTypeName($code_type = 1){
        $codeTypeNameArr = [
            1 => '号码类型',
            2 => '三现带双',
            3 => '三现带双热码',
            4 => '三现三重',
            5 => '四现带双',
            501 => '四现带双热码',
            6 => '四现不带双',
            601 => '四现不带双热码',
            7 => '四兄弟',
            8 => '四单四双',
            9 => '四单带双',
            901 => '四单带双热码',
            10 => '四双带双',
            1001 => '四双带双热码',
        ];

        if(isset($codeTypeNameArr[$code_type]) && $codeTypeNameArr[$code_type]) return $codeTypeNameArr[$code_type];

        return $codeTypeNameArr;
    }

    /**
     * @desc 获取所有彩票名称
     * @param int $lottery_type
     * @return array
     */
    public static function getLotterys(){
        $m = \Yii::$app->cache;
        $mkey = 'getLotterys_data';
        if($data = $m->get($mkey)) return $data;
        $lottery_types = LotteryType::find()->where(['enable'=>1])->asArray()->all();

        $data = [];
        foreach ($lottery_types as $lottery){
            $data[$lottery['lottery_type']] = $lottery['shortName'];
        }
        $m->set($mkey, $data, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);

        return $data;
    }

    /**
     * @desc 获取所有玩法名称
     * @param int $lottery_type
     * @return array
     */
    public static function getPlayMethods(){
        $m = \Yii::$app->cache;
        $mkey = 'getPlayMethods_x0';
        if($data = $m->get($mkey)) return $data;
        $methods = PlayMethod::find()->asArray()->all();

        $data = [];
        foreach ($methods as $method){
            $data[$method['id']] = $method['name'];
        }
        $m->set($mkey, $data, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);

        return $data;
    }

    /**
     * @desc 获取彩票名称
     * @param int $lottery_type
     * @return mixed
     */
    public static function getLotteryName($lottery_type = DEFAULT_LOTTERY_TYPE){
        $lotterys = self::getLotterys();

        $data = $lotterys[$lottery_type];

        return $data;
    }

    /**
     * @desc 返回用户默认的彩种类型
     * @param $uid
     * @param $queryParams
     * @return int
     */
    public static function getIndexLotteryType($uid, $queryParams=[]){
        $m = \Yii::$app->cache;
        $mkey = 'getIndexLotteryType_'.$uid;

        $userDefaultLotteryType = UserService::getUserDefaultLotteryType($uid);
        if(!empty($queryParams)){
            foreach ($queryParams as $queryParam){
                if(isset($queryParam['lottery_type']) && $queryParam['lottery_type']){
                    $lottery_type = $queryParam['lottery_type'];
                }elseif ($lottery_type = $m->get($mkey)){
                    $lottery_type = $lottery_type;
                }else{
                    $lottery_type = $userDefaultLotteryType;
                }
            }
        }else{
            $lottery_type = $m->get($mkey);
            $lottery_type = $lottery_type ? $lottery_type : $userDefaultLotteryType;
        }

        $m->set($mkey, $lottery_type, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);

        return $lottery_type;
    }

    /**
     * @desc 生成用户默认投注方式key
     * @param $uid
     * @return string
     */
    public static function buildMyTzTypes($uid, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $mkey = 'getMyTzTypes_data_'.$lottery_type.'_'.$uid;

        return $mkey;
    }

    /**
     * @desc 删除用户投注记录 - 主要针对新开通用户清除数据
     * @param $uid
     */
    public static function delUserBetRecords($uid){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        try {
            //$rst['data']['flag0'] = BettingRecords::deleteRecord(['uid'=>$uid]);
            //$rst['data']['flag1'] = BetErrorPlansTask::deleteRecord(['uid'=>$uid]);
        }catch (\Exception $e){
            Tool_Common::log('/user/'.__FUNCTION__, 'ERR', '数据删除', ['uid'=>$uid, 'err_msg'=>$e->getMessage()]);
        }

        return $rst;
    }

    /**
     * @desc 删除用户授权的投注方式缓存
     * @param $uid
     */
    public static function delUserTzTypesCache($uid){

        $m = \Yii::$app->cache;
        $lottery_types = StaticService::getLotteryTypes();
        foreach ($lottery_types as $lottery_type) {
            $mkey = CommonService::buildMyTzTypes($uid, $lottery_type);
            $rst[$lottery_type] = $m->delete($mkey);
        }

        return $rst;
    }

    /**
     * @desc status或者是否多选框处理成1个值   数组->单个
     * @param $post
     * @param array $fields
     * @return mixed
     */
    public static function opPreStatusFields($post, $fields = [], $model = ''){
        if(empty($fields)) return $post;

        $data = $post[$model];
        foreach ($fields as $field){
            if(isset($data[$field]) && !empty($data[$field])){
                if(count($data[$field])>1) unset($post[$model][$field]);
                $post[$model][$field] = $post[$model][$field][0];
            }else{
                unset($post[$model][$field]);
            }

        }

        return $post;
    }

    public static function getVoteCode(){

        # 1、获取验证码
        $rand = rand(60000000000000000, 6999999999999999);
        $itemid = '2193389';
        $url = 'http://vote.chkling.com/api/vote/captcha.png.php?rnd=&itemid='.$itemid.'&authType=1';
        $cookie_str = 'UM_distinctid=1732c8825ba22a-05ef0d3e077e67-4e31563f-5e106-1732c8825bb769; Hm_lvt_5aa56b2bef4b65b9c1660a5987b93134=1594179987; czt_openinfo=%257B%2522uid%2522%253A%252211599625%2522%252C%2522token%2522%253A%252299a155fdcbb73305fa201d06f3fcad9b%2522%257D; Hm_lpvt_5aa56b2bef4b65b9c1660a5987b93134=1594287530';
        $common_headers = [
            'Host: vote.chkling.com',
            'Connection: keep-alive',
            'User-Agent: Mozilla/5.0 (Linux; Android 10; VOG-AL10 Build/HUAWEIVOG-AL10; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/78.0.3904.62 XWEB/2469 MMWEBSDK/200601 Mobile Safari/537.36 MMWEBID/3859 MicroMessenger/7.0.16.1700(0x27001039) Process/tools WeChat/arm64 NetType/WIFI Language/zh_CN ABI/arm64',
            'X-Requested-With: com.tencent.mm',
            'Referer: http://m.chkling.com/activity?sid=0aa0562d621d67be&cfrom=UP2CW&from=singlemessage',
            'Accept-Encoding: gunzip, deflate',
            'Accept-Language: zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7',
            //'Cookie: '.$cookie_str,
        ];
        $cookie_headers = [
            'Accept: image/wxpic,image/tpg,image/wxpic,image/tpg,image/webp,image/apng,image/*,*/*;q=0.8',
        ];
        $cookie = CurlService::curlGetCookie($url, array_merge($common_headers, $cookie_headers));
        $acw_tc = explode('=', str_replace(';path=/;HttpOnly;Max-Age=1800', '', $cookie))[1];
        $cookie = CurlService::curlGetCookie($url, array_merge($common_headers, array_merge($cookie_headers, ['Cookie: '.$cookie_str.'; acw_tc='.$acw_tc])));
        //p(['cookie'=>$cookie, array_merge($common_headers, $cookie_headers), $acw_tc]);

        # 2、下载图片
        $filename = CommonService::downLoadVoteImg($uid = 1000, $rand, $acw_tc);

        # 3、验证码接口
        $codeRst = CaptchaCodeService::chaojiying($filename, $codeType = '6001'); # 超级鹰
        //p($codeRst);

        if($codeRst['status'] == 200){
            # 4、投票
            $url = 'http://vote.chkling.com/api/vote/captcha.check.php?rnd='.$rand.'&itemid='.$itemid.'&authType=1&captcha='.$codeRst['code'];
            $vote_headers = [
                'Accept: application/json, text/plain, */*',
                'Origin: http://m.chkling.com',
                'Cookie: '.$cookie_str.'; acw_tc='.$acw_tc.''
                // 2f624a1c15942884560606649e7f64ca606a345e012248b91aa297ba1b192a;
            ];
            $rst = CurlService::getCurl($url, array_merge($common_headers, $vote_headers));
        }
        Tool_Common::log('getVoteCode', 'INFO', '投票', ['filename'=>$filename, 'codeRst'=>$codeRst, 'url'=>$url, 'headers'=>$vote_headers, 'rst'=>$rst]);
        p($rst);
        p('xxxx');

        $cookie = 'czt_openinfo=%257B%2522uid%2522%253A%252211599625%2522%252C%2522token%2522%253A%25224584d583f1730a193e6d0ccc3f8a8cad%2522%257D; UM_distinctid=1732c8825ba22a-05ef0d3e077e67-4e31563f-5e106-1732c8825bb769; Hm_lvt_5aa56b2bef4b65b9c1660a5987b93134=1594179987; Hm_lpvt_5aa56b2bef4b65b9c1660a5987b93134=1594196682; acw_tc=2f624a4815941966830157919e096a214d8921d809bdd348daba87faf9df8b';
    }

    public static function downLoadVoteImg($uid = 1000, $rnd = '', $acw_tc = ''){
        $url = 'http://vote.chkling.com/api/vote/captcha.png.php?rnd='.$rnd.'&itemid=1466789&authType=1';
        $headers = [

        ];
        $imageData = CurlService::httpGet($url, $headers);
        $filename = \Yii::$app->basePath . "/runtime/captcha/".$uid.'_'.$acw_tc.".png";
        //$filename = Yii::$app->basePath . "/runtime/captcha/".$cookie.".png";
        $tp = fopen($filename,"w");
        fwrite($tp, $imageData);
        fclose($tp);
        $logData = ['url'=>$url,'headers'=>$headers, 'filename'=>$filename];
        //p($logData);
        Tool_Common::log('/downLoadCodeImg','INFO','下载图片验证码', $logData);

        return $filename;
    }
}
