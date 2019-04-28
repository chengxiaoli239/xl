<?php

namespace common\service;
use backend\models\BettingRecords;
use backend\models\TzSystems;
use backend\models\TzTypes;
use backend\models\UserFollowData;
use backend\service\SscDataService;
use backend\service\StaticService;
use common\models\User;
use common\tools\Tool_Common;
use backend\service\CurlService;
use backend\service\UserService;
use backend\models\SscKjData;
class  CommonService{


    /**
     * @description 用户设置成代理、删除用户代理时候处理业务
     * @param $admin_user_id
     * @param $action
     * @param $member 默认权限
     * @return bool
     */
    public static function opUser($admin_id, $action, $role = '收费会员'){
        # 1、时时彩用户记录添加
        $rst1 = UserService::opUser($admin_id, $action, $role);
        //$rst1 = UserService::opTzSystemsUsers($admin_id, $action, $role);

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
     * @param string $lottery_type ssc:时时彩、qxc:七星彩
     * @param string $api_type 360:360网站、yiyuan:易源
     * @return bool
     */
    public static function getAwardNumberByQihao($qihao, $lottery_type = 'ssc'){
        if(!$qihao) return false;
        $m = \Yii::$app->cache;

        $mkey = 'KJ_DATA_2_'.$lottery_type.'_'.$qihao;
        if(!$kjData = $m->get($mkey)){
            if($lottery_type == 'ssc'){
                $kjData = SscKjData::findOne(['qihao'=>$qihao])->code_str;

                if(!$kjData){
                    return false;
                }

                if($kjData) $m->set($mkey,$kjData, 2*60*60);
            }elseif($lottery_type == 'qxc'){
                # 1、七星彩抓取
                $url = 'http://route.showapi.com/44-3';
                $post_data = [
                    'showapi_appid'=> \Yii::$app->params['SHOW_API_APPID'],
                    'showapi_sign'=>\Yii::$app->params['SHOW_API_SIGN'],
                    'code' => 'qxc',
                    'expect'=>$qihao
                ];
                $data = CurlService::httpPost($url,$post_data); // {"showapi_res_error":"","showapi_res_code":0,"showapi_res_body":{"result":{"expect":"2018016","timestamp":1517920300,"time":"2018-02-06 20:31:40","name":"七星彩","code":"qxc","openCode":"6,9,3,3,9,4,7"},"ret_code":0}}
                $tmpKjData = $data['showapi_res_body']['result'];
                if($tmpKjData['openCode']) $m->set($mkey,$tmpKjData, 2*60*60);
                $kjData = [
                    'qihao'=>$tmpKjData['expect'],
                    'time' => $tmpKjData['timestamp'],
                    'date_time' =>$tmpKjData['time'],
                    'kj_code' => $tmpKjData['openCode'],
                ];

                $logArr = ['url'=>$url, 'post_data'=>$post_data, 'returnData'=>$data,'lottery_type'=>'qxc'];
                Tool_Common::log('/WORK/LOG/lottery_xl/'.date('Ymd').'/getAwardNumberByQihaoQxc', 'INFO', '开奖日志记录', $logArr);

                /*
                # 2、0898投注网，返回最近开奖号码
                $url = 'https://700056.com/qxc/ajax.aspx?act=getlastkj';
                $data = CurlService::httpGet($url);
                $kjData = $data[0]['code'];
                */
            }
        }
        $m->set($mkey, $kjData,7*24*60*60);

        return $kjData;
   }

    /**
     * @decription 开奖处理
     * @return array
     */
    public static function kj(){
        $rst = ['status'=>200, 'msg'=>'开奖数据处理完成!'];

        $where = ['status'=>0];
        $bettingRecords = BettingRecords::find()->where($where)->all();
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
    public static function getOdds($playway = 10, $type = 'odds'){
        $playways = [
            1 => ['odds'=>97.5,'name'=>'两字定'],  // 两字定
            2 => ['odds'=>975,'name'=>'三字定'],   // 三字定
            3 => ['odds'=>9750,'name'=>'四字定'],  // 四字定
            4 => ['odds'=>9.75,'name'=>'一字定'],  // 一字定
            5 => ['odds'=>9.60,'name'=>'二字现'],  // 二字现
            6 => ['odds'=>48.00,'name'=>'三字现'], // 三字现
            10 => ['odds'=>9.75,'name'=>'定位胆'], // 定位胆
            11 => ['odds'=>97.5,'name'=>'后二'], // 后二
            12 => ['odds'=>975,'name'=>'后三'],  // 后三
            13 => ['odds'=>1.95,'name'=>'大小单双'], // 大小单双
            14 => ['odds'=>97.5,'name'=>'前二'], // 前二
            15 => ['odds'=>975,'name'=>'前三'],  // 前三
            16 => ['odds'=>300,'name'=>'组三'],  // 组三
            17 => ['odds'=>140,'name'=>'组六'],  // 组六
        ];

        if($playway && $playways[$playway][$type])
            return $playways[$playway][$type];
        return $playways;
    }

    /**
     * @desc 是否双重
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType2($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        $codesArr = array_unique($codesArr);
        if(count($codesArr)<=3) $flag = 1;

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
     * @desc 是否两兄弟
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType2b($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
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
        ];
        foreach ($bArrs as $bArr){
            if(strpos($codes_str, $bArr) !== false) $flag = 1;
        }
        if(in_array(0, $codesArr) && in_array(9, $codesArr)) $flag = 1;

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
        asort($codesArr);
        $codes_str = implode(',', $codesArr);
        $bArrs = [
            '0,1,2',
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
        ];
        foreach ($bArrs as $bArr){
            if(strpos($codes_str, $bArr) !== false) $flag = 1;
        }

        return $flag;
    }

    /**
     * @desc 是否四单四双
     * @param string $codes 格式 1,2,3,4
     * @return int
     */
    public static function isCodeType4ds($codes){
        $flag = 0;
        $codesArr = explode(',', $codes);
        asort($codesArr);

        # 四单判断
        $flag_4d = 1;
        foreach ($codesArr as $code){
            if($code % 2 == 0) $flag_4d = 0;
        }

        # 四双判断
        $flag_4s = 2;
        foreach ($codesArr as $code){
            if($code % 2 == 1) $flag_4s = 0;
        }

        return max($flag, $flag_4d, $flag_4s);
    }

    /**
     * @desc 所有投注系统
     * @param int $status
     * @return array
     */
    public static function getAllSystems($status = 1){

        $datas = TzSystems::find()->where(['status'=>$status])->asArray()->all();

        $dataArr = [];

        foreach ($datas as $key=>$data){
            $dataArr[$data['id']] = $data['name'];
        }

        return $dataArr;
    }

    /**
     * @desc 所有投注类型
     * @param int $status
     * @return array
     */
    public static function getAllTzTypes(){

        $datas = StaticService::$kArr;

        unset($datas[0],$datas[1],$datas[10],$datas[11]);

        $datas = TzTypes::find()->where(['status'=>1])->asArray()->all();

        $dataArr = [];

        foreach ($datas as $key=>$data){
            $dataArr[$data['type']] = $data['type_name'];
        }

        return $dataArr;
    }
}