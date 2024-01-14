<?php
namespace common\service\thirdD\match;

use backend\service\BetService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\OperateApiDataService;
use common\tools\Tool_Common;
use yii\helpers\Json;

class MatchCodeService extends CommonBaseService
{
    public static function getExplainData($text=''): array
    {
        try {
            $start_time = microtime(true);
            $params = ['textfield' => $text];
            //Tool_Common::log('/matchCode/'.__FUNCTION__, 'INFO', '号码数据匹配0', ['text'=>$text]);
            $domain = BetService::getConfig('EXPLAIN_CODE_API')?:\Yii::$app->params['EXPLAIN_CODE_API'];
            $result = \common\open\thirdD\api\MatchCodeApi::push($domain, $params);
            $end_time = microtime(true);
            $time_consume = ($end_time-$start_time).'s';
            $resultData = $result[0] ?? [];
            if($resultData['code'] != 200 OR empty($resultData['data'])){
                throw_info($resultData['msg']??'识别错误.', $resultData['code']);
            }
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'INFO', '号码数据匹配', ['text'=>$text, 'result'=>$result, 'time_consume'=>$time_consume]);
            $resultData = $resultData['data'];
            #p($resultData);
        }catch (\Exception $e){
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'ERR', '号码数据匹配-异常', ['text'=>$text, 'result'=>$result, 'time_consume'=>$time_consume]);
            throw_info($e->getMessage(),  $resultData['code']??self::CODE_FOR_USER);
        }

        return $resultData;
    }

    /**
     * 一次文本
     * @param string $betText
     * @return array
     */
    public static function getCodeDatas(string $betText=''): array
    {
        try {
            $codeDatas = MatchCodeService::getExplainData($betText);
            //p([$betText,$codeDatas]);
            #$codeDatasStr = '[{"caiid":10,"code":[{"playedId":220,"playedName":"组三(七 码)","actionData":"1346789","bonusProp":7.9,"actionNum":1,"isZ6":false,"mode":"100 "},{"playedId":211,"playedName":"组六(七 码)","actionData":"1346789","bonusProp":4.75,"actionNum":1,"isZ6":true,"mode":"300 "}]},{"caiid":20,"code":[{"playedId":200,"playedName":"u76f4u9009","actionData":"130","bonusProp":1000,"ac tionNum":1,"mode":"4"}]},{"caiid":10,"code":[{"playedId":211,"playedName":"组六(七 码)","actionData":"1236789","bonusProp":4.75,"actionNum":1,"isZ6":true,"mode":"200 "}]},{"caiid":10,"code":[{"playedId":200,"playedName":"u76f4u9009","actionData":"103","bonusProp":1000,"ac tionNum":1,"mode":"6"}]},{"caiid":10,"code":[{"playedId":201,"playedName":"组选组 六","actionData":"013","bonusProp":"164.4","actionNum":1,"isZ6":true,"mode":"10"}]}]';
            #$codeDatas = Json::decode($codeDatasStr);
            #p($codeDatas);
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'INFO', '号码识别', ['text'=>$betText, 'codeDatas'=>$codeDatas]);
            $dataGroups = ['betCodeContents'=>[]];
            foreach ($codeDatas as $k=>$codeData){
                $lottery_type = $codeData['caiid']==20 ? CommonBaseService::LOTTERY_TYPE_PL3 : CommonBaseService::LOTTERY_TYPE_FUCAI;

                if(!isset($dataGroups['betCodeContents'][$lottery_type])){
                    $dataGroups['betCodeContents'][$lottery_type] = [];
                }

                $playMethod = [];
                $codes = $codeData['code'];
                //p([$lottery_type, $codeData]);
                $allMoney = 0.00;
                foreach ($codes as $code){
                    list($localToSiteMethodInfo, $codeData) = MatchCodeService::apiMethodDataToLocalMethodData($code);
                    //p([$localToSiteMethodInfo, $codeData]);
                    $single = $code['mode'];
                    $count = $code['actionNum'];

                    $oneAllMoney = $single * $count;
                    $allMoney += $oneAllMoney;
                    $playMethod[] = [
                        'id' => $localToSiteMethodInfo['id'],
                        'codes' => $codeData,
                        'single' => $single,
                        'count' => $code['actionNum'],
                        'all_moneys' => $oneAllMoney,
                        'name' => $localToSiteMethodInfo['name'],
                    ];
                }
                $g['lottery_type'] = $lottery_type;
                $g['lottery_name'] = CommonBaseService::THIRDD_LOTTERY_OPTIONS[$lottery_type];
                $g['single'] = $single;
                $g['all_moneys'] = $allMoney;
                $g['playMethod'] = $playMethod;
                $g['apiCodeDatas'] = $codes;
                //$playMethod[$k]['playMethod'] = $pm;
                //p($g);
                $dataGroups['betCodeContents'][$lottery_type][] = $g;
            }
            //p($dataGroups);
        }catch (\Exception $e){
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'ERR', '匹配错误11', ['text'=>$betText, 'codeDatas'=>$codeDatas, 'err_msg'=>$e->getMessage()]);
            return [CommonBaseService::CODE_FOR_USER, [], '匹配异常，请重新输入'];
        }finally{
            //return [CommonBaseService::CODE_FOR_USER, [], '匹配异常，请重新输入00'];
        }

        #p(['g'=>$g, 'codeDatas'=>$codeDatas, 'betText'=>$betText], 0);
        if(empty($g['single']) OR empty($g['all_moneys'])){
            return [CommonBaseService::CODE_FOR_USER, [], '匹配倍数或金额异常'];
        }
        //var_dump('========='.$lottery_type.'=======');
        return [0, ['text'=>$betText, 'dataGroups'=>$dataGroups], '接口匹配成功'];
    }

    public static function resetMethodInfo($codeData=[], &$localToSiteMethodInfo=[]){
        //p([$codeData['playedId'], $codeData['playedName'], 'codeData'=>$codeData, 'localToSiteMethodInfo'=>$localToSiteMethodInfo], 0);
        switch (true){
            case $codeData['playedId'] == 201 && strpos($codeData['playedName'], '组三') !== false:
                $localToSiteMethodInfo['id'] = MethodMatchService::METHOD_ID_ZUSAN;
                break;
            case $codeData['playedId'] == 201 && strpos($codeData['playedName'], '组六') !== false:
                $localToSiteMethodInfo['id'] = MethodMatchService::METHOD_ID_ZULIU;
                break;
            case $codeData['playedId'] == 200:
                $localToSiteMethodInfo['id'] = MethodMatchService::METHOD_ID_ZHIXUAN;
                break;
        }
        if($codeData['playedId']==201){
            $method = \common\service\thirdD\PlayMethodService::getOneMethod($localToSiteMethodInfo['id']);
            $localToSiteMethodInfo['name'] = '组选'.$method['name'];
        }
        if($codeData['playedId']==200){
            $method = \common\service\thirdD\PlayMethodService::getOneMethod($localToSiteMethodInfo['id']);
            $localToSiteMethodInfo['name'] = $method['name'];
        }

        //p(['codeData'=>$codeData, 'localToSiteMethodInfo'=>$localToSiteMethodInfo, 'method'=>$method]);
    }

    /**
     * @param array $codeData
     * @return array
     */
    public static function apiMethodDataToLocalMethodData($codeData=[]): array
    {
        //todo 入库前接口数据转换本地数据
        $localToSiteMethodInfo = CommonBaseService::getSiteToLocalMethods($codeData['playedId']); #
        self::resetMethodInfo($codeData, $localToSiteMethodInfo);
        $code = str_replace(',', ';', $codeData['actionData']);

        try {
            $method_id = $localToSiteMethodInfo['id'];
            switch ($method_id){
                case MethodMatchService::METHOD_ID_ZHIXUAN:
                    OperateApiDataService::runZhiXuan($code);
                    break;
                case MethodMatchService::METHOD_ID_ZULIU: # 组六
                case MethodMatchService::METHOD_ID_ZUSAN: # 组三
                    OperateApiDataService::runZuXuan($code);
                    break;
                case MethodMatchService::METHOD_ID_DUDAN: # 独胆
                    OperateApiDataService::runDuDan($code);
                    break;
                case MethodMatchService::METHOD_ID_SHUANGFEI: # 双飞
                case MethodMatchService::METHOD_ID_QUANTUO: # 对子全拖
                    OperateApiDataService::runShuangFen($code);
                    break;
                case MethodMatchService::METHOD_ID_DUIZI_QB: # 对子全包
                    OperateApiDataService::runDuiZiQB($code);
                    break;
                case MethodMatchService::METHOD_ID_YIMADING: # 一码定
                    OperateApiDataService::runYiMaDing($code);
                    break;
                case MethodMatchService::METHOD_ID_ERMADING: # 二码定
                    OperateApiDataService::runErMaDing($code);
                    break;
                case MethodMatchService::METHOD_ID_BAOZI_QB: # 豹子全包
                    OperateApiDataService::runBaoZiQB($code);
                    break;
                case MethodMatchService::METHOD_ID_ZL_4_MA: # 组六4码
                case MethodMatchService::METHOD_ID_ZL_5_MA: # 组六5码
                case MethodMatchService::METHOD_ID_ZL_6_MA: # 组六6码
                case MethodMatchService::METHOD_ID_ZL_7_MA: # 组六7码
                case MethodMatchService::METHOD_ID_ZL_8_MA: # 组六8码
                case MethodMatchService::METHOD_ID_ZL_9_MA: # 组六9码
                    OperateApiDataService::runZuLiuXMa($code); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_ZS_2_MA: # 组三2码
                case MethodMatchService::METHOD_ID_ZS_3_MA: # 组三2码
                case MethodMatchService::METHOD_ID_ZS_4_MA: # 组三4码
                case MethodMatchService::METHOD_ID_ZS_5_MA: # 组三5码
                case MethodMatchService::METHOD_ID_ZS_6_MA: # 组三6码
                case MethodMatchService::METHOD_ID_ZS_7_MA: # 组三7码
                case MethodMatchService::METHOD_ID_ZS_8_MA: # 组三8码
                case MethodMatchService::METHOD_ID_ZS_9_MA: # 组三9码
                    OperateApiDataService::runZuSanXMa($code); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_ZL_QB: # 组六全包
                case MethodMatchService::METHOD_ID_ZS_QB: # 组三全包
                    OperateApiDataService::runZuXuanQuanBao($code); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_KD_0: # 跨度0
                case MethodMatchService::METHOD_ID_KD_1: # 跨度1
                case MethodMatchService::METHOD_ID_KD_2: # 跨度2
                case MethodMatchService::METHOD_ID_KD_3: # 跨度3
                case MethodMatchService::METHOD_ID_KD_4: # 跨度4
                case MethodMatchService::METHOD_ID_KD_5: # 跨度5
                case MethodMatchService::METHOD_ID_KD_6: # 跨度6
                case MethodMatchService::METHOD_ID_KD_7: # 跨度7
                case MethodMatchService::METHOD_ID_KD_8: # 跨度8
                case MethodMatchService::METHOD_ID_KD_9: # 跨度9
                    OperateApiDataService::runKuaDu($code); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_YMT_ZL_2: # 一码拖2_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_3: # 一码拖3_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_4: # 一码拖4_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_5: # 一码拖5_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_6: # 一码拖6_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_7: # 一码拖7_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_8: # 一码拖8_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_9: # 一码拖9_组六
                    OperateApiDataService::runYiTuoZuLiu($code); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_YMT_ZS_2: # 一码拖2_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_3: # 一码拖3_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_4: # 一码拖4_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_5: # 一码拖5_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_6: # 一码拖6_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_7: # 一码拖7_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_8: # 一码拖8_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_9: # 一码拖9_组三
                    OperateApiDataService::runYiTuoZuSan($code);
                    break;
                case MethodMatchService::METHOD_ID_FS_3: # 复式三
                case MethodMatchService::METHOD_ID_FS_4: # 复式四
                case MethodMatchService::METHOD_ID_FS_5: # 复式五
                case MethodMatchService::METHOD_ID_FS_6: # 复式六
                case MethodMatchService::METHOD_ID_FS_7: # 复式七
                case MethodMatchService::METHOD_ID_FS_8: # 复式八
                case MethodMatchService::METHOD_ID_FS_9: # 复式九
                    OperateApiDataService::runFuShiX($code); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_HZ_0: # 和值0
                case MethodMatchService::METHOD_ID_HZ_1: # 和值1
                case MethodMatchService::METHOD_ID_HZ_2: # 和值2
                case MethodMatchService::METHOD_ID_HZ_3: # 和值3
                case MethodMatchService::METHOD_ID_HZ_4: # 和值4
                case MethodMatchService::METHOD_ID_HZ_5: # 和值5
                case MethodMatchService::METHOD_ID_HZ_6: # 和值6
                case MethodMatchService::METHOD_ID_HZ_7: # 和值7
                case MethodMatchService::METHOD_ID_HZ_8: # 和值8
                case MethodMatchService::METHOD_ID_HZ_9: # 和值9
                case MethodMatchService::METHOD_ID_HZ_10: # 和值10
                case MethodMatchService::METHOD_ID_HZ_11: # 和值11
                case MethodMatchService::METHOD_ID_HZ_12: # 和值12
                case MethodMatchService::METHOD_ID_HZ_13: # 和值13
                case MethodMatchService::METHOD_ID_HZ_14: # 和值14
                case MethodMatchService::METHOD_ID_HZ_15: # 和值15
                case MethodMatchService::METHOD_ID_HZ_16: # 和值16
                case MethodMatchService::METHOD_ID_HZ_17: # 和值17
                case MethodMatchService::METHOD_ID_HZ_18: # 和值18
                case MethodMatchService::METHOD_ID_HZ_19: # 和值19
                case MethodMatchService::METHOD_ID_HZ_20: # 和值10
                case MethodMatchService::METHOD_ID_HZ_21: # 和值21
                case MethodMatchService::METHOD_ID_HZ_22: # 和值22
                case MethodMatchService::METHOD_ID_HZ_23: # 和值23
                case MethodMatchService::METHOD_ID_HZ_24: # 和值24
                case MethodMatchService::METHOD_ID_HZ_25: # 和值25
                case MethodMatchService::METHOD_ID_HZ_26: # 和值26
                case MethodMatchService::METHOD_ID_HZ_27: # 和值27
                    OperateApiDataService::runHeZhi($code); # 和值
                    break;
                case MethodMatchService::METHOD_ID_HZ_DA: # 和值大
                case MethodMatchService::METHOD_ID_HZ_XIAO: # 和值小
                case MethodMatchService::METHOD_ID_HZ_DAN: # 和值单
                case MethodMatchService::METHOD_ID_HZ_SHUANG: # 和值双
                    OperateApiDataService::runHeZhiDxDs($code); # 和值
                    break;
                case MethodMatchService::METHOD_ID_DW_ZX_FS: # 定位直选复式
                    OperateApiDataService::runHeZhiXuanFuShiDw($code); # 直选复式定位
                    break;
                case MethodMatchService::METHOD_ID_QD: # 全倒
                    OperateApiDataService::runQuanDao($code); # 全倒
                    break;
                case MethodMatchService::METHOD_ID_ZX_FS: # 直选复式
                    OperateApiDataService::runZhiXuanFuShi($code); # 直选复式
                    break;
                default:
                    $err_msg = '未知玩法ID:'.$method_id;
                    $logArr = ['code'=>$code, 'err_msg'=>$err_msg];
                    Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理异常10', $logArr);
                    return [10003, $logArr, $err_msg];
                    break;
            }
            $resultData = ['code'=>$code, 'method_id'=>$method_id, 'err_msg'=>'处理结束'];
            //var_dump($codeData,'：', $resultData);
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理结束99', $resultData);
        }catch (\Exception $e){
            $logArr = ['code'=>$code, 'codeData'=>$codeData, 'method_id'=>$method_id, 'err_msg'=>$e->getMessage()];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理异常11', $logArr);
            var_dump($e->getMessage());
            return [10004, $logArr, $e->getMessage()];
        }

        return [$localToSiteMethodInfo, $code];
    }


}
