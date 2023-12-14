<?php
namespace common\service\thirdD\match;

use common\service\thirdD\CommonBaseService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\ThirdDTypeService;
use common\tools\Tool_Common;

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
