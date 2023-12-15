<?php
namespace common\service\thirdD\match;

use common\service\thirdD\CommonBaseService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\ThirdDTypeService;
use common\tools\Tool_Common;
use yii\helpers\Json;

class MatchCodeService extends CommonBaseService
{
    /**
     * @var mixed
     */
    private static $gLotteryType;
    /**
     * @var mixed
     */
    private static $gLotteryName;

    public static function getExplainData($text=''): array
    {
        try {
            $params = ['textfield' => $text];
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'INFO', '号码数据匹配0', ['text'=>$text]);
            $domain = \Yii::$app->params['EXPLAIN_CODE_API'];
            $result = \common\open\thirdD\api\MatchCodeApi::push($domain, $params);
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'INFO', '号码数据匹配', ['text'=>$text, 'result'=>$result]);
        }catch (\Exception $e){
            return ['code'=>$e->getCode(), 'msg'=>$e->getMessage()];
        }

        return $result;
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
                #p([$lottery_type, $codes]);
                $allMoney = 0.00;
                foreach ($codes as $code){
                    $localToSiteMethodInfo = CommonBaseService::getSiteToLocalMethods($code['playedId']); #
                    $single = $code['mode'];
                    $count = $code['actionNum'];

                    self::resetMethodInfo($code, $localToSiteMethodInfo);

                    $oneAllMoney = $single * $count;
                    $allMoney += $oneAllMoney;
                    $playMethod[] = [
                        'id' => $localToSiteMethodInfo['id'],
                        'codes' => str_replace(',', ';', $code['actionData']),
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
                p($g);
                $dataGroups['betCodeContents'][$lottery_type][] = $g;
            }
            p($dataGroups);
        }catch (\Exception $e){
            return [CommonBaseService::CODE_FOR_USER, [], '匹配异常，请重新输入'];
        }finally{

        }

        #p(['g'=>$g, 'codeDatas'=>$codeDatas, 'betText'=>$betText], 0);
        if(empty($g['single']) OR empty($g['all_moneys'])){
            return [CommonBaseService::CODE_FOR_USER, [], '匹配倍数或金额异常'];
        }
        //var_dump('========='.$lottery_type.'=======');
        return [0, ['text'=>$betText, 'dataGroups'=>$dataGroups], '接口匹配成功'];
    }

    public static function getCodeData($betText=''): array
    {
        try {
            list($lottery_type, $lottery_name, $matchTexts, $isEmpty) = ThirdDTypeService::getLotteryType($betText);
            #var_dump('1lottery_type:'.$lottery_type, $isEmpty);
            if($isEmpty){
                # 彩种匹配为空则取上次匹配的结果
                $lottery_type = self::$gLotteryType;
                $lottery_name = self::$gLotteryName;
            }
            self::$gLotteryType = $lottery_type;
            self::$gLotteryName = $lottery_name;
            $codeDatas = MatchCodeService::getExplainData($betText);
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'INFO', '号码识别', ['text'=>$betText, 'codeDatas'=>$codeDatas]);
            $playMethod = [];
            foreach ($codeDatas[0] as $k=>$codeData){
                $localToSiteMethodInfo = CommonBaseService::getSiteToLocalMethods($codeData['playedId']); #
                $single = $codeData['mode'];
                $count = $codeData['actionNum'];

                self::resetMethodInfo($codeData, $localToSiteMethodInfo);

                $all_moneys = $single * $count;
                $playMethod[] = [
                    'id' => $localToSiteMethodInfo['id'],
                    'codes' => str_replace(',', ';', $codeData['actionData']),
                    'single' => $single,
                    'count' => $codeData['actionNum'],
                    'all_moneys' => $all_moneys,
                    'name' => $localToSiteMethodInfo['name'],
                ];
                //$playMethod[$k]['playMethod'] = $pm;
            }
            //p($codeDatas);
        }catch (\Exception $e){
            if (($e instanceof \common\exceptions\InfoException)) {
                $result = $e->data;
            }
            return [CommonBaseService::CODE_FOR_USER, [], '匹配异常，请重新输入'];
        }finally{

        }
        $g['lottery_type'] = self::$gLotteryType;
        $g['lottery_name'] = self::$gLotteryName;
        $g['single'] = $single;
        $g['all_moneys'] = $all_moneys;
        $g['playMethod'] = $playMethod;
        $g['apiCodeDatas'] = $codeDatas[0];
        #p(['g'=>$g, 'codeDatas'=>$codeDatas, 'betText'=>$betText], 0);
        if(empty($g['single']) OR empty($g['all_moneys'])){
            return [CommonBaseService::CODE_FOR_USER, [], '匹配倍数或金额异常'];
        }
        //var_dump('========='.$lottery_type.'=======');
        return [0, ['text'=>$betText, 'lottery_type'=>$lottery_type, 'g'=>$g], '接口匹配成功'];
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
        }
        if($codeData['playedId']==201){
            $method = \common\service\thirdD\PlayMethodService::getOneMethod($localToSiteMethodInfo['id']);
            $localToSiteMethodInfo['name'] = '组选'.$method['name'];
        }
        //p(['codeData'=>$codeData, 'localToSiteMethodInfo'=>$localToSiteMethodInfo, 'method'=>$method]);
    }
}
