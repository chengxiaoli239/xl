<?php

namespace common\service\thirdD;

use backend\models\LotteryType;
use common\models\thirdD\BetOrderId;
use common\service\BaseService;
use common\service\chat\Tool_Common;
use common\service\CommonService;
use common\service\helpers\ThirdD;
use yii\helpers\Json;

class ThirdDTypeService extends CommonBaseService
{
    const SINGLE_ASSCIATE = [
        '零' => 0,
        '两' => 2,
        '一' => 1,
        '二' => 2,
        '三' => 3,
        '四' => 4,
        '五' => 5,
        '六' => 6,
        '七' => 7,
        '八' => 8,
        '九' => 9,
        '十' => 10,
    ];

    /**
     * 判断lottery_type
     * @param string $text
     * @return array
     */
    public static function getLotteryType(string $text=''): array
    {
        $lottery_types = CommonBaseService::THIRDD_LOTTERY_TYPES;

        foreach ($lottery_types as $lottery_type){
            // 检查$arr1是否有元素存在于$str
            #p([$text, ThirdDTypeService::getThirdDAlias($lottery_type)]);
            $result = ThirdD::arrayItemInString($text, ThirdDTypeService::getThirdDAlias($lottery_type));
            if($result){
                break;
            }
        }
        $isEmpty=false;
        if(empty($result)){
            $isEmpty = true;
            # 默认为福彩
            $lottery_type = CommonBaseService::LOTTERY_TYPE_FUCAI;
        }
        $lottery_name = CommonService::getLotteryName($lottery_type);

        return [$lottery_type, $lottery_name, $result??[], $isEmpty];
    }

    /**
     * 匹配彩种个数
     * @param $text
     * @param $datas
     * @return array
     */
    public static function getLotteryTypes($text, $datas) {
        $matches = [];
        foreach ($datas as $lottery_type=>$data){
            foreach ($data as $d){
                if (strpos($text, $d) !== false) {
                    $matches[$lottery_type] = $d;
                    break;
                }
            }
        }

        return $matches;
    }


    /**
     * 判断playMethod 玩法
     * @param string $text
     * @return array
     */
    public static function getPlayMethodAndCodes(string $text='', &$codes=[]): array
    {
        #$methods = PlayMethodService::getMethods();
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        //p([$text, $methods]);
        $methodArr = [];
        foreach ($methods as $key=>$method){
            try {
                $method_name = trim($method['name']);
                $result = ThirdD::arrayItemInString($text, [$method_name]);
                #if($result OR ($key==$method_name && $key!=$method['originName'] && empty($method['originName']))){
                if($result){
                    //p([$text, $result, $key, $method_name, $method['originName'], $method, $methods]);
                    $name = (!empty($result) && isset($methods[$result[0]])) ? $methods[$result[0]]['name'] : $key;
                    $methodArr = ['id'=>$method['id'], 'name'=>$name, 'originName'=>$method['originName']];
                    if(strpos($text, '码定') === false){ # 非一码、二码定位
                        $text = str_replace($methodArr['matchName'], $methodArr['name'], $text);
                    }
                    #p(['methodArr'=>$methodArr]);
                    if(in_array($methodArr['originName'], ['复式三', '复式四', '复式五', '复式六', '复式七', '复式八', '复式九']) && strpos($text, '直选')!==false){
                        continue;
                    }
                    break;
                }
            }catch (\Exception $e){
                var_dump($e->getMessage());
            }
        }

        $matchMethodAndCodeText = $text;
        switch (true){
            case strpos($text, '全包')!==false OR strpos($text, '豹子') !== false:
                $mType = 1;
                $methodArr = MethodMatchService::matchQuanBao($matchMethodAndCodeText, $codes, $count);
                break;
            case strpos($matchMethodAndCodeText, '拖') !== false: #36-51:1码拖.... [组三|组六]
                $mType = 9;
                $methodArr = MethodMatchService::matchYiMaTuo($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case $methodArr['originName'] == '独胆' OR strpos($text, '独') !== false:
                $mType = 2;
                $methodArr = MethodMatchService::matchDuDan($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case $methodArr['originName'] == '双飞' OR $methodArr['originName'] == '对子全拖' OR strpos($text, '对') !== false:
                $mType = 3;
                $methodArr = MethodMatchService::matchShuangFei($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case $methodArr['originName'] == '一码定位':
                $mType = 4;
                $methodArr = MethodMatchService::matchYiMaDing($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case $methodArr['originName'] == '二码定位':
                $mType = 5;
                $methodArr = MethodMatchService::matchErMaDing($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case (strpos($text, '百倍')===false && strpos($text, '十倍')===false && strpos($text, '个倍')===false ) &&
                    (strpos($text, '百') !== false OR strpos($text, '十') !== false OR strpos($text, '个') !== false):# 一、二定位、定位直选复式
                $mType = 6;
                $methodArr = MethodMatchService::matchDingWei($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case (strpos($text, '和值') !== false OR strpos($text, '合值') !== false) && (
                    strpos($text, '大') !== false OR strpos($text, '小') !== false OR strpos($text, '单') !== false OR strpos($text, '双') !== false ):
                $mType = 12;
                $methodArr = MethodMatchService::matchHeZhiDaXiaoDanShuang($matchMethodAndCodeText, $codes, $count);
                break;
            case strpos($text, 'X') !== false OR (
                    strpos($text, '*') !== false && empty($methodArr)
                ): # 带X的定位
                $mType = 6.1;
                $methodArr = MethodMatchService::matchXDingWei($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case (
                    strpos($text, '复式')===false && (
                        (strpos($text, '直')!==false && strpos($text, '组三')===false && strpos($text, '组六')===false) OR # 直选
                        (strpos($text, '组')!==false && strpos($text, '组三')===false && strpos($text, '组六')===false) # 组选
                    )) OR ( strpos($matchMethodAndCodeText, '拖') === false && (
                        strpos($text, '组三') !== false OR strpos($text, '组六') !== false OR # 组三或组六
                        (strpos($text, '组三') !== false && strpos($text, '组六') !== false) OR # 组三组六 组合
                        (strpos($text, '直') !== false && strpos($text, '组') !== false) OR # 直组
                        (strpos($text, '单') !== false && strpos($text, '组') !== false) OR # 单选组选  组合
                        (strpos($text, '组选') !== false) OR # 组选
                        (strpos($text, '组') !== false) OR   # 组选
                        (strpos($text, '单') !== false) OR   # 单选
                        (strpos($text, '直') !== false && strpos($text, '复式')===false) # 直选
                )): # 1、2、3组选
                $mType = 7;
                list($matchMethodAndCodeText, $singleArr) = ThirdDTypeService::getTwoMethodAndSingle($text, $matchMethodAndCodeText);
                //p([$matchMethodAndCodeText, $singleArr]);
                $methodArr = MethodMatchService::matchZhiZuOrZuSanOrZuLiuXMa($matchMethodAndCodeText, $codes, $count, $singleArr);
                break;
            case strpos($text, '跨') !== false:  #26-35 跨度0
                $mType = 8;
                $methodArr = MethodMatchService::matchKuaDuX($matchMethodAndCodeText, $codes, $count);
                break;
            case strpos($text, '复式')!==false && strpos($text, '直选')===false:
                $mType = 10;
                $methodArr = MethodMatchService::matchFuShi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case (strpos($text, '和值') !== false OR strpos($text, '合值') !== false) && (
                    strpos($text, '大') === false && strpos($text, '小') === false &&
                    strpos($text, '单') === false && strpos($text, '双') === false
                ): #36-51:1码拖.... [组三|组六]
                $mType = 11;
                $methodArr = MethodMatchService::matchHeZhi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case strpos($text, '复式') !== false:
                $mType = 13;
                $methodArr = MethodMatchService::matchDingWeiFuShi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            case $methodArr['originName'] == '全倒':
                $mType = 14;
                $methodArr = MethodMatchService::matchQuanDao($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
                break;
            default:
                $mType = 15;
                $codes = explode(' ', trim($matchMethodAndCodeText));
                break;
        }
        Tool_Common::log('/match/'.__FUNCTION__, 'INFO', '匹配玩法号码及ID', ['text'=>$text, 'mType'=>$mType, 'methodArr'=>$methodArr]);
        //p(['methodArr'=>$methodArr, 'codes'=>$codes, 'count'=>$count]);

        return [$methodArr, $codes, $count];
    }

    /**
     * 获取两种玩法：直组、组三组六
     * @param string $text
     * @param string $matchMethodAndCodeText
     * @param array $singleArr
     * @return array
     */
    public static function getTwoMethodAndSingle(string $text='', string $matchMethodAndCodeText='', array &$singleArr=[]): array
    {
        try {
            $cnSingleTxt = MethodMatchService::CN_SINGLE_TEXT;
            $text = str_replace('倍组选', '倍组', $text);
            $matchType = 0; # 匹配逻辑跟进
            $pattern36 = '/(组六|组三)\s*各\s*([一二两三四五六七八九十]{1,3}\s*倍|(\d+)\s*元|[一二两三四五六七八九十]{1,3}\s*元|(\d)+\s*倍)/u';
            $patternZhiZu = '/(直|单|组三|组六|组)\s*各\s*([一二两三四五六七八九十0-9]{1,3}\s*倍|(\d+)\s*元|[一二两三四五六七八九十0-9]{1,3}\s*元|(\d)+\s*倍)/u';
            $patternZhiZuNum = '/((\d+)元直|(\d+)元组)/';
            #$patternZhiZuNum = '/(直|单|组三|组六|组)\s*各\s*([0-9]{1,3}\s*倍|(\d+)\s*元|[0-9]{1,3}\s*元|(\d)+\s*倍)/u';
            $patternZhiZuNotBei = '/(直|单|直选|组三|组六|组选|组)\s*([一二两三四五六七八九十]{1,3}倍|(\d+)\s*元|[一二两三四五六七八九十]{1,3}\s*元|(\d)+倍)/u';
            $patternNotAndYuanBei0 = '/(直|单|直选|组三|组六|组选|组)([一二两三四五六七八九十0-9]){1,3}倍/u'; # 一直一组、直二组三

            $patternDanZhi = '/(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}单|['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}组)/u'; # 两单一直
            //p($patternDanZhi);
            #$patternNotAndYuanBeiCn2 = '/(([一二两三四五六七八九十]{1,3})(直|单|组|直选|组三|组六|组选))/u'; # 一直一组、直二组三
            $patternNotAndYuanBeiCn1 = '/(直([一二两三四五六七八九十]{1,3})组([一二两三四五六七八九十]{1,3}))/u'; # 直一组一、直二组三
            $patternNotAndYuanBeiCn2 = '/(组([一二两三四五六七八九十]{1,3})直([一二两三四五六七八九十]{1,3}))/u'; # 一直一组、直二组三

            $patternBei21 = '/(直\s*\D*\d+倍|组\s*\D*\d+倍)/';
            $patternBei22 = '/(直\s*['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}倍|组\s*['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}倍)/';
            //p($patternBei22);
            //$patternBei22 = '/(\s*\D*\d+倍直|\s*\D*\d+倍组)/';
            $patternBei31 = '/(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})直\s*(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})组/';
            //$patternBei32 = '/(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})单(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})组/';

            $patternNotAndYuanBeiNum = '/(\d+){1,3}(直|单|直选|组三|组六|组选|组)/u'; # 一直一组、直二组三
            $pattern36_01 = '/(直|单|直选|组三|组六|组选|组)([0-9]{1,3})/u'; # 一直一组、直二组三
            $perSingle = 0;
            $perSinglesArr = [];
            if(preg_match_all('/各(\d+)/', $text, $m)){
                if(count($m[1])==1){
                    $perSingle = $m[1][0];
                }else{
                    $perSinglesArr = $m[1];
                }
            }
            switch (true){
                case strpos($text, '组三') !== false && strpos($text, '组六') !== false:
                    # 组六 且 组三
                    switch (true){
                        case preg_match_all('/(['.$cnSingleTxt.']{1,3}元{0,1}组三)(['.$cnSingleTxt.']{1,3}元{0,1}组六)/u', $text, $ms) && $m=1: # 一直一组
                        case preg_match_all('/(['.$cnSingleTxt.']{1,3}元{0,1}组六)(['.$cnSingleTxt.']{1,3}元{0,1}组三)/u', $text, $ms) && $m=2: # 一组一直
                        case preg_match_all('/(组三['.$cnSingleTxt.']{1,3}元{0,1})(组六['.$cnSingleTxt.']{1,3}元{0,1})/u', $text, $ms) && $m=3: # 直一组一
                        case preg_match_all('/(组六['.$cnSingleTxt.']{1,3}元{0,1})(组三['.$cnSingleTxt.']{1,3}元{0,1})/u', $text, $ms) && $m=4: # 组一直一
                            $matchType = 3.11;
                            $matcheSingles = array_merge($ms[1], $ms[2]);
                            $singleOne = 2;
                            if(preg_match('/\d+/', $text, $ms)){
                                $singleOne = (strlen($ms[0])>3) ? 10 : $singleOne; # 多码情况为10元一倍
                            }

                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles, $singleOne); # 没有
                            //p([$text, $matchType, $ms, $singleArr, $matcheSingles, 'm'=>$m]);
                            break;
                        case preg_match_all('/(['.$cnSingleTxt.']{1,3}倍{0,1}组三)(['.$cnSingleTxt.']{1,3}倍{0,1}组六)/u', $text, $ms) && $m=1: # 一倍直一倍组
                        case preg_match_all('/(['.$cnSingleTxt.']{1,3}倍{0,1}组六)(['.$cnSingleTxt.']{1,3}倍{0,1}组三)/u', $text, $ms) && $m=2: # 一倍组一倍直
                        case preg_match_all('/(组三['.$cnSingleTxt.']{1,3}倍{0,1})(组六['.$cnSingleTxt.']{1,3}倍{0,1})/u', $text, $ms) && $m=3: # 直一倍组一倍
                        case preg_match_all('/(组六['.$cnSingleTxt.']{1,3}倍{0,1})(组三['.$cnSingleTxt.']{1,3}倍{0,1})/u', $text, $ms) && $m=4: # 组一倍直一倍
                            $matchType = 3.02;
                            $matcheSingles = array_merge($ms[1], $ms[2]);
                            $singleOne = 2;
                            if(preg_match('/\d+/', $text, $ms)){
                                $singleOne = (strlen($ms[0])>3) ? 10 : $singleOne; # 多码情况为10元一倍
                            }
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles, $singleOne); # 没有
                            //p([$text, $matchType, $ms, $singleArr, $matcheSingles, 'm'=>$m]);
                            break;
                        case preg_match_all('/(\d+元{0,1}组三)(\d+元{0,1}组六)/u', $text, $ms) && $m=1: # 1直1组
                        case preg_match_all('/(\d+元{0,1}组三)(\d+元{0,1}组三)/u', $text, $ms) && $m=2: # 1组1直
                        case preg_match_all('/(组三\d+元{0,1})(组三\d+元{0,1})/u', $text, $ms) && $m=3: # 直1组1
                        case preg_match_all('/(组六\d+元{0,1})(组三\d+元{0,1})/u', $text, $ms) && $m=4: # 组1直1
                            $matchType = 3.03;
                            $matcheSingles = array_merge($ms[1], $ms[2]);
                            $singleOne = 2;
                            if(preg_match('/\d+/', $text, $ms)){
                                $singleOne = (strlen($ms[0])>3) ? 10 : $singleOne; # 多码情况为10元一倍
                            }
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles, $singleOne); # 没有
                            //p([$text, $matchType, $ms, $matcheSingles, $singleArr, 'm'=>$m]);
                            break;
                        case preg_match_all('/(组三\d+倍{0,1})(组六\d+倍{0,1})/u', $text, $ms) && $m=1: # 1倍直1倍组
                        case preg_match_all('/(组六\d+倍{0,1})(组三\d+倍{0,1})/u', $text, $ms) && $m=2: # 1倍组1倍直
                        case preg_match_all('/(\d+倍{0,1}组三)(\d+倍{0,1}组六)/u', $text, $ms) && $m=3: # 直1倍组1倍
                        case preg_match_all('/(\d+倍{0,1}组六)(\d+倍{0,1}组三)/u', $text, $ms) && $m=4: # 组1倍直1倍
                            $matchType = 3.04;
                            $singleOne = 2;
                            if(preg_match('/\d+/', $text, $ms)){
                                $singleOne = (strlen($ms[0])>3) ? 10 : $singleOne; # 多码情况为10元一倍
                            }
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles = array_merge($ms[1], $ms[2]), $singleOne); # 没有
                            #p([$text, $matchType, $ms, $singleArr, 'm'=>$m]);
                            break;
                        case (strpos($text, '组六组三') !== false OR strpos($text,'组三组六')!==false) && preg_match_all('/各(\d+(元|倍){0,1})/', $text, $matcheSingles):
                            $matchType = 3.05;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle1($matcheSingles[0]); # 没有
                            if(count($singleArr)==1){
                                $singleArr = [ '直' => current($singleArr), '组' => current($singleArr)];
                            }
                            //p([$text, $matchType, $matcheSingles, current($singleArr), $singleArr]);
                            break;
                        case preg_match_all('/各(\d+(元|倍){0,1})/', $text, $matcheSingles):
                            $matchType = 3.09;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle1($matcheSingles[0]); # 没有
                            if(count($singleArr)==1){
                                $singleArr = [ '组三' => current($singleArr), '组六' => current($singleArr)];
                            }
                            //p([$text, $matchType, $matcheSingles, current($singleArr), $singleArr]);
                            break;
                        //case preg_match_all('/组三组六(各|)(\d*)(倍|元|)/u', $text, $matcheSingles) && $mType=1: # 数字倍数
                        case preg_match_all('/组三组六(各|)(['.MethodMatchService::CN_SINGLE_TEXT.'])(倍|元|)/u', $text, $matcheSingles) && $mType=2: # 数字倍数
                            $matchType = 3.10;
                            $singleOne = 2;
                            if(preg_match('/\d+/', $text, $ms)){
                                $singleOne = (strlen($ms[0])>3) ? 10 : $singleOne;
                            }
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0], $singleOne);
                            p([$text, $matchType, $matcheSingles, $singleArr,  $mType]);
                            break;
                        case preg_match_all('/组三(各|)(\d*)(倍|元|)|组六(各|)(\d*)(倍|元|)/u', $text, $matcheSingles) && $mType=1: # 数字倍数
                        case preg_match_all('/组三(各|)(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})(倍|元|))|(组六(各|)(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})(倍|元|)/u', $text, $matcheSingles) && $mType=1: # 中文倍数
                            $matchType = 3.01;
                            $singleOne = 2;
                            if(preg_match('/\d+/', $text, $ms)){
                                $singleOne = (strlen($ms[0])>3) ? 10 : $singleOne;
                            }
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0], $singleOne);
                            //p([$text, $matchType, $matcheSingles, $singleArr,  $mType]);
                            break;
                        case preg_match_all($pattern36, $text, $matcheSingles):
                            $matchType = 3.02;
                            //p([$matchMethodAndCodeText, $text, $matcheSingles]);
                            # $matcheSingles Array ( [0] => Array ( [0] => 组六各4倍 [1] => 组三各20元 ) [1] => Array ( [0] => 组六 [1] => 组三 ) [2] => Array ( [0] => 倍 [1] => 元 ) )
                            $zu3Key = $matcheSingles[1][0]? : '组三';
                            $zu6Key = $matcheSingles[1][1]? : '组六';
                            if(!empty($perSingle)){
                                $singleArr[$zu3Key] = $perSingle;
                                $singleArr[$zu6Key] = $perSingle;
                            }else if(!empty($perSinglesArr)){
                                $singleArr[$zu3Key] = $perSinglesArr[0];
                                $singleArr[$zu6Key] = $perSinglesArr[1];
                            }else {
                                foreach ($matcheSingles[0] as $matcheSingle) {
                                    $sData = explode('各', $matcheSingle);
                                    if (strpos($sData[1], '倍') !== false) {
                                        # 倍
                                        $singleTxt = str_replace('倍', '', $sData[1]);
                                        if (is_numeric($singleTxt)) {
                                            $tmpSingle = $singleTxt * 10; #  转换成元
                                        } else {
                                            # 中文
                                            $tmpSingle = ThirdD::cn2num($singleTxt) * 10; #  # 中文转数字  转换成元
                                        }
                                    } else {
                                        $singleTxt = str_replace('元', '', $sData[1]);
                                        if (is_numeric($singleTxt)) {
                                            $tmpSingle = $singleTxt; #  转换成元
                                        } else {
                                            # 中文
                                            $tmpSingle = ThirdD::cn2num($singleTxt); #  # 中文转数字  转换成元
                                        }
                                        # 元
                                    }
                                    $singleArr[$sData[0]] = $tmpSingle; # 倍数转换成：元
                                }
                                #p($singleArr);
                                if (!empty($singleArr)) {
                                    if (count($singleArr) == 1) {
                                        $singleArr = [
                                            '组三' => current($singleArr),
                                            '组六' => current($singleArr),
                                        ];
                                    }
                                    $matchMethodAndCodeText = $text;
                                    foreach ($matcheSingles[0] as $matcheSingle) {
                                        $matchMethodAndCodeText = str_replace($matcheSingle, '', $matchMethodAndCodeText);
                                    }
                                    $matchMethodAndCodeText = str_replace('组三', '', $matchMethodAndCodeText);
                                    $matchMethodAndCodeText = str_replace('组六', '', $matchMethodAndCodeText);
                                    $matchMethodAndCodeText .= '组三组六';
                                }
                            }
                            break;
                        case preg_match_all($pattern36_01, $text, $matcheSingles):
                            $matchType = 1.03;
                            $matcheCn = $matcheSingles[1];
                            $matcheCnSingles = $matcheSingles[2];
                            if(!empty($perSingle)){
                                $singleArr[trim($matcheCn[0])] = $perSingle;
                                $singleArr[trim($matcheCn[1])] = $perSingle;
                            }else{
                                if(!empty($perSinglesArr)){
                                    $singleArr[trim($matcheCn[0])] = $perSinglesArr[0];
                                    $singleArr[trim($matcheCn[1])] = $perSinglesArr[1];
                                }else{
                                    $singleArr[trim($matcheCn[0])] = $matcheCnSingles[0];
                                    $singleArr[trim($matcheCn[1])] = $matcheCnSingles[1];
                                }
                            }
                            break;
                    }
                    break;
                case strpos($text, '直') !== false && strpos($text, '组') !== false && $text = str_replace('单', '直', $text):
                case strpos($text, '单') !== false && strpos($text, '组') !== false && $text = str_replace('单', '直', $text):
                    # 直 且 组
                    switch (true){
                        case preg_match_all('/(['.$cnSingleTxt.']{1,3}元{0,1}直)(['.$cnSingleTxt.']{1,3}元{0,1}组)/u', $text, $ms) && $m=1: # 一直一组
                        case preg_match_all('/(['.$cnSingleTxt.']{1,3}元{0,1}组)(['.$cnSingleTxt.']{1,3}元{0,1}直)/u', $text, $ms) && $m=2: # 一组一直
                        case preg_match_all('/(直['.$cnSingleTxt.']{1,3}元{0,1})(组['.$cnSingleTxt.']{1,3}元{0,1})/u', $text, $ms) && $m=3: # 直一组一
                        case preg_match_all('/(组['.$cnSingleTxt.']{1,3}元{0,1})(直['.$cnSingleTxt.']{1,3}元{0,1})/u', $text, $ms) && $m=4: # 组一直一
                            $matchType = 2.01;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles = array_merge($ms[1], $ms[2])); # 没有
                            #p([$text, $matchType, $ms, $singleArr, 'm'=>$m]);
                            break;
                        case preg_match_all('/(['.$cnSingleTxt.']{1,3}倍{0,1}直)(['.$cnSingleTxt.']{1,3}倍{0,1}组)/u', $text, $ms) && $m=1: # 一倍直一倍组
                        case preg_match_all('/(['.$cnSingleTxt.']{1,3}倍{0,1}组)(['.$cnSingleTxt.']{1,3}倍{0,1}直)/u', $text, $ms) && $m=2: # 一倍组一倍直
                        case preg_match_all('/(直['.$cnSingleTxt.']{1,3}倍{0,1})(组['.$cnSingleTxt.']{1,3}倍{0,1})/u', $text, $ms) && $m=3: # 直一倍组一倍
                        case preg_match_all('/(组['.$cnSingleTxt.']{1,3}倍{0,1})(直['.$cnSingleTxt.']{1,3}倍{0,1})/u', $text, $ms) && $m=4: # 组一倍直一倍
                            $matchType = 2.02;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles = array_merge($ms[1], $ms[2])); # 没有
                            //p([$text, $matchType, $ms, $singleArr, 'm'=>$m]);
                            break;
                        case preg_match_all('/(\d+元{0,1}直)(\d+元{0,1}组)/u', $text, $ms) && $m=1: # 1直1组
                        case preg_match_all('/(\d+元{0,1}组)(\d+元{0,1}直)/u', $text, $ms) && $m=2: # 1组1直
                        case preg_match_all('/(直\d+元{0,1})(组\d+元{0,1})/u', $text, $ms) && $m=3: # 直1组1
                        case preg_match_all('/(组\d+元{0,1})(直\d+元{0,1})/u', $text, $ms) && $m=4: # 组1直1
                            $matchType = 2.03;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles = array_merge($ms[1], $ms[2])); # 没有
                            #p([$text, $matchType, $ms, $singleArr, 'm'=>$m]);
                            break;
                        case preg_match_all('/(直\d+倍{0,1})(组\d+倍{0,1})/u', $text, $ms) && $m=1: # 1倍直1倍组
                        case preg_match_all('/(组\d+倍{0,1})(直\d+倍{0,1})/u', $text, $ms) && $m=2: # 1倍组1倍直
                        case preg_match_all('/(\d+倍{0,1}直)(\d+倍{0,1}组)/u', $text, $ms) && $m=3: # 直1倍组1倍
                        case preg_match_all('/(\d+倍{0,1}组)(\d+倍{0,1}直)/u', $text, $ms) && $m=4: # 组1倍直1倍
                            $matchType = 2.04;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles = array_merge($ms[1], $ms[2])); # 没有
                            #p([$text, $matchType, $ms, $singleArr, 'm'=>$m]);
                            break;
                        case (strpos($text, '组直') !== false OR strpos($text,'直组')!==false) && preg_match_all('/各(\d+(元|倍){0,1})/', $text, $matcheSingles):
                            $matchType = 2.05;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle1($matcheSingles[0]); # 没有
                            if(count($singleArr)==1){
                                $singleArr = [ '直' => current($singleArr), '组' => current($singleArr)];
                            }
                            //p([$text, $matchType, $matcheSingles, current($singleArr), $singleArr]);
                            break;
                        case preg_match_all('/各(\d+(元|倍){0,1})/', $text, $matcheSingles):
                            $matchType = 2.06;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle1($matcheSingles[0]); # 没有
                            if(count($singleArr)==1){
                                $singleArr = [ '直' => current($singleArr), '组' => current($singleArr)];
                            }
                            //p([$text, $matchType, $matcheSingles, current($singleArr), $singleArr]);
                            break;
                        case preg_match_all('/(直|组)(各|)(\d+(元|倍){0,1})/', $text, $matcheSingles):
                            $matchType = 2.11;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle1($matcheSingles[0]); # 没有
                            //p([$text, $matchType, $matcheSingles, $singleArr]);
                            break;
                        case preg_match('/各(\d+(元|倍){0,1})/', $text, $matcheSingles):
                            $matchType = 2.12;
                            if(preg_match('/\d+/', $matcheSingles[0], $ms)){
                                $single = (strpos($matcheSingles[0], '倍') !== false) ? $ms[0] * 2 : $ms[0];
                                $singleArr['直'] = $single;
                                $singleArr['组'] = $single;
                            }
                            //p([$text, $matchType, $matcheSingles, $singleArr, $ms]);
                            break;
                        case preg_match_all('/((直选|直)(\d+){1,3}倍)|((组选|组)(\d+){1,3}倍)/', $text, $matcheSingles):
                            $matchType = 2.13;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0]);
                            //p([$text, $matchType, $matcheSingles, $singleArr]);
                            break;
                        case preg_match_all('/((直选|直)(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})倍)|((组选|组)(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})倍)/', $text, $matcheSingles):
                            $matchType = 2.14;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0]);
                            //p([$text, $matchType, $matcheSingles, $singleArr]);
                            break;
                        case preg_match_all('/(直(\d+){1,3}倍)|(组(\d+){1,3}倍)/', $text, $matcheSingles):
                            $matchType = 2.15;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0]);
                            //p([$text, $matchType, $matcheSingles, $singleArr]);
                            break;
                        case preg_match_all('/(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}直)|(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}组)/', $text, $matcheSingles):
                            $matchType = 2.16;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0]);
                            //p([$text, $matchType, $matcheSingles, $patternZhiZu, $singleArr]);
                            break;
                        case preg_match_all('/((直(\d)*元)|(组(\d+)*元))/', $text, $matcheSingles):
                            $matchType = 2.17;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0]);
                            //p([$text, $matcheSingles, $patternZhiZuNum]);
                            break;
                        case preg_match_all($patternZhiZuNum, $text, $matcheSingles):
                            //p([$text, $matcheSingles, $patternZhiZuNum]);
                            $matchType = 2.18;
                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0]);
                            break;
                        case preg_match_all($patternZhiZu, $text, $matcheSingles):
                            $matchType = 2.19;
                            //p([$text, $matcheSingles, $patternZhiZu]);
                            foreach ($matcheSingles[0] as $matcheSingle){
                                $sData = explode('各', $matcheSingle);
                                if(strpos($sData[1], '倍') !== false){ # 倍
                                    $singleTxt = str_replace(['倍', '组选', '组三', '组六', '组', '直选', '直', '单'], '', $sData[1]);
                                    if(is_numeric($singleTxt)){
                                        $tmpSingle = $singleTxt * 2; #  转换成元
                                    }else{
                                        # 中文
                                        $tmpSingle = ThirdD::cn2num($singleTxt) * 2; #  # 中文转数字  转换成元
                                    }
                                }else{
                                    $singleTxt = str_replace('元', '', $sData[1]);
                                    if(is_numeric($singleTxt)){
                                        $tmpSingle = $singleTxt; #  转换成元
                                    }else{
                                        # 中文
                                        $tmpSingle = ThirdD::cn2num($singleTxt); #  # 中文转数字  转换成元
                                    }
                                    # 元
                                }
                                $singleArr[trim($sData[0])] = $tmpSingle; # 倍数转换成：元
                            }
                            if(!empty($singleArr)){
                                if(count($singleArr)==1){
                                    $singleArr = [
                                        '直' => current($singleArr),
                                        '组三' => current($singleArr),
                                        '组六' => current($singleArr),
                                    ];
                                }
                                $matchMethodAndCodeText = $text;
                                foreach ($matcheSingles[0] as $matcheSingle){
                                    $matchMethodAndCodeText = str_replace($matcheSingle, '', $matchMethodAndCodeText);
                                }
                                $matchMethodAndCodeText = str_replace('组三', '', $matchMethodAndCodeText);
                                $matchMethodAndCodeText = str_replace('组六', '', $matchMethodAndCodeText);
                                $matchMethodAndCodeText .= '直组';
                            }
                            #p([$text, $matcheSingles, $singleArr]);
                            break;
                        case preg_match_all($patternBei31, $text, $matcheSingles):
                            $matchType = 2.20;
                            $singleArr['直'] = ThirdD::cn2num($matcheSingles[1][0]) * 2;
                            $singleArr['组'] = ThirdD::cn2num($matcheSingles[2][0]) * 2;
                            break;
                        case preg_match_all($patternNotAndYuanBeiCn1, $text, $matcheSingles): #  && (count($matcheSingles1[0][0])==2)
                        case preg_match_all($patternNotAndYuanBeiCn2, $text, $matcheSingles): #  && (count($matcheSingles2[0][0])==2)
                            $matchType = 2.22;
                            #p([$text, $matcheSingles1, $matcheSingles2]);

                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0]);
                            break;
                        case preg_match_all($patternBei21, $text, $matcheSingles): # 组1倍直1倍、直1倍组1倍
                        case preg_match_all($patternBei22, $text, $matcheSingles):
                            $matchType = 2.23;
                            foreach ($matcheSingles[0] as $item){
                                if(preg_match('/[直组](['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}|\d+)倍/', $item, $m)){
                                    if(is_numeric($m[1])){
                                        $tmpSingle = $m[1] * 2; #  转换成元
                                    }else{
                                        # 中文
                                        $tmpSingle = ThirdD::cn2num($m[1]) * 2; #  # 中文转数字  转换成元
                                    }

                                    if(strpos($item, '组') !== false){
                                        $singleArr['组'] = $tmpSingle;
                                    }else{
                                        $singleArr['直'] = $tmpSingle;
                                    }
                                }
                            }
                            //p($singleArr);
                            break;
                        case preg_match_all($patternNotAndYuanBeiNum, $text, $matcheSingles):
                            $matchType = 2.24; #

                            $singleArr = ThirdDTypeService::getMatchTwoSingle($matcheSingles[0]);
                            //p([$matchType, $singleArr, $text, $matcheSingles]);
                            break;
                        case preg_match_all($patternZhiZuNotBei, $text, $matcheSingles):
                            $matchType = 2.25; #
                            foreach ($matcheSingles[0] as $matcheSingle){
                                $patternBei = '/(直选|直|组三|组六|组选|组)\s*([一二两三四五六七八九十]{1,3}倍|(\d+)\s*元|[一二两三四五六七八九十]{1,3}\s*元|(\d)+倍)/u';
                                if(preg_match_all($patternBei, $matcheSingle, $ms)){
                                    if(strpos($ms[2][0], '倍') !== false){ # 倍
                                        $singleOne = (
                                            strpos($ms[1][0], '直选') !== false OR
                                            strpos($ms[1][0], '直') !== false OR
                                            strpos($ms[1][0], '组') !== false OR
                                            strpos($ms[1][0], '组选') !== false
                                        ) ? 2 : 10;
                                        $singleTxt = str_replace(['倍', '组选', '组三', '组六', '组', '直选', '直'], '', $ms[2][0]);
                                        if(is_numeric($singleTxt)){
                                            $tmpSingle = $singleTxt * $singleOne; #  转换成元
                                        }else{
                                            # 中文
                                            $tmpSingle = ThirdD::cn2num($singleTxt) * $singleOne; #  # 中文转数字  转换成元
                                        }
                                    }else{
                                        $singleTxt = str_replace('元', '', $ms[2][0]);
                                        if(is_numeric($singleTxt)){
                                            $tmpSingle = $singleTxt; #  转换成元
                                        }else{
                                            # 中文
                                            $tmpSingle = ThirdD::cn2num($singleTxt); #  # 中文转数字  转换成元
                                        }
                                        # 元
                                    }
                                    $singleArr[trim($ms[1][0])] = $tmpSingle; # 倍数转换成：元
                                }
                            }
                            break;
                    }
                    break;
                default:
                    $matchType = 99;
                    break;
            }
        }catch (\Exception $e){
            $matchType = $e->getMessage();
        }
        Tool_Common::log('/matchSingle/'.__FUNCTION__, 'INFO', '多倍匹配条件', ['text'=>$text, 'matchType'=>$matchType, 'singleArr'=>$singleArr]);
        #p([$matchMethodAndCodeText, $text, $matcheSingles, $methodArr, $singleArr]);
        # 玩法类型组合：组三、组六、组三&组六、直&组（一直一组，二直三组，直组）、组选、组、直||直选

        #p([$matchMethodAndCodeText, $matcheSingles, $text, $singleArr]);

        return [$matchMethodAndCodeText, $singleArr];
    }

    /**
     * 匹配倍数 单种玩法的倍数
     * @param string $matchStr - 格式 : 各2倍、各2、各2元
     * @param $type 1直组(2元一倍)、2其它(10元一倍)
     * @return float|int|string
     * @throws \common\exceptions\InfoException     * @return int|float
     */
    public static function getMatchOneSingle(string $matchStr='', $type=1)
    {
        $baseSingle = ($type==2)?10:2;
        if(preg_match('/\d+/', $matchStr, $ms)) {
            $single = strpos($matchStr, '倍') !== false ? ($ms[0] * $baseSingle) : $ms[0];
        }elseif (preg_match('/(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})/u', $matchStr, $ms)){
            $toNum = ThirdD::cn2num($ms[0]);
            $single = strpos($matchStr, '倍') !== false ? ($toNum * $baseSingle) : $toNum;
        }else{
            throw_info('倍数匹配错误');
        }

        return $single;
    }

    /**
     * 匹配倍数 没有关键字"倍"字的默认为：元
     * @param array $matchArr - 格式 : Array( [0] => 直2倍 [1] => 组1倍 )
     * @return array
     */
    public static function getMatchTwoSingle1(array $matchArr=[]): array
    {
        $singleArr = [];
        foreach ($matchArr as $matcheSingle){
            $cnKey = ThirdDTypeService::getCnKey($matcheSingle);
            if(in_array($cnKey, ['组三', '组六'])){
                # 组三组六除了匹配导倍，其它一般都是元
                if(preg_match('/\d+/', $matcheSingle, $ms)){
                    $singleArr[$cnKey] = strpos($matcheSingle, '倍') !== false ? ($ms[0] * 2) : $ms[0];
                }else if(preg_match('/['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}/u', $matcheSingle, $ms)){
                    $singleArr[$cnKey] = strpos($matcheSingle, '倍') !== false ? (ThirdD::cn2num($ms[0]) * 2) : ThirdD::cn2num($ms[0]);
                }
            }else{
                if(preg_match('/\d+/', $matcheSingle, $ms)){
                    $singleArr[$cnKey] = strpos($matcheSingle, '倍') !== false ? ($ms[0] * 2) : $ms[0];
                }else if(preg_match('/['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}/u', $matcheSingle, $ms)){
                    $singleArr[$cnKey] = strpos($matcheSingle, '倍') !== false ? (ThirdD::cn2num($ms[0]) * 2) : ThirdD::cn2num($ms[0]);
                }
            }
        }

        return $singleArr;
    }

    /**
     * 匹配倍数 直组 没有关键字"元"字的默认为：倍    * @param array $matchArr - 格式 : Array( [0] => 直2倍 [1] => 组1倍 )
     * @return array
     */
    public static function getMatchTwoSingle(array $matchArr=[], $oneSingle=0): array
    {
        $singleArr = [];
        foreach ($matchArr as $matcheSingle){
            $cnKey = ThirdDTypeService::getCnKey($matcheSingle);
            var_dump('cnKey:'.$cnKey);
            if(in_array($cnKey, ['组三', '组六'])){
                if(isset($singleArr[$cnKey])){
                    continue;
                }
                $matcheSingle = str_replace('组三组六', '', $matcheSingle);
                $matcheSingle = str_replace('组六组三', '', $matcheSingle);
                $matcheSingle = str_replace($cnKey, '', $matcheSingle);
                $oneSingle = !empty($oneSingle) ? $oneSingle : 2; # 一倍金额，组选组三组六2元一倍，组三组六多码则为10元一倍
                if(preg_match('/\d+/', $matcheSingle, $ms)){
                    $currentSingle = strpos($matcheSingle, '倍') !== false ? ($ms[0] * $oneSingle) : $ms[0];
                }else if(preg_match('/['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}/u', $matcheSingle, $ms)){
                    $currentSingle = strpos($matcheSingle, '倍') !== false ? (ThirdD::cn2num($ms[0]) * $oneSingle) : ThirdD::cn2num($ms[0]);
                }
                $singleArr[$cnKey] = $currentSingle;
            }else{
                if(preg_match('/\d+/', $matcheSingle, $ms)){
                    $singleArr[$cnKey] = strpos($matcheSingle, '元') !== false ? $ms[0] : ($ms[0] * 2);
                }else if(preg_match('/['.MethodMatchService::CN_SINGLE_TEXT.']{1,3}/u', $matcheSingle, $ms)){
                    $singleArr[$cnKey] = strpos($matcheSingle, '元') !== false ? ThirdD::cn2num($ms[0]) : (ThirdD::cn2num($ms[0]) * 2);
                }
            }
        }

        return $singleArr;
    }

    /**
     * @param string $text
     * @return string
     */
    public static function getCnKey(string $text=''): string
    {
        if(strpos($text, '组三') !== false){
            $cnKey = '组三';
        }elseif (strpos($text, '组六') !== false) {
            $cnKey = '组六';
        }else{
            $cnKey = (strpos($text, '组') !== false) ? '组' : '直';
        }

        return $cnKey;
    }

    /**
     * 判断金额：各x元、共x元
     * @param string $text
     * @return array
     */
    public static function getMoneys(string $text='', $matchName='', $playMethod=[]): array
    {
        #header('Content-Type: text/html; charset=UTF-8');
        $single = 0;
        $text = trim($text);

        $t=0; # 匹配倍数走进哪个逻辑标识
        $single_cn_text = '元'; # 倍数中文关键字，元或倍
        // 使用正则表达式匹配 直选复式
        if ($playMethod['name']=='直选复式' && preg_match('/(\d+(?:\.\d+)?)元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
            $t = 1;
            #$count = $playMethod['count']? $playMethod['count']*$matches[1] : $matches[1];
        }

        // 使用正则表达式匹配 全倒
        if ($playMethod['name']=='全倒' && preg_match('/(\d+(?:\.\d+)?)元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $playMethod['single']? $playMethod['single']*$matches[1] : $matches[1];
            $t = 2;
        }

        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/各\s*(\d+(?:\.\d+)?)\s*元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
            $t = 3;
        }
        // 使用正则表达式匹配 "各" 和 "倍" 中间的数字
        if (empty($single) && preg_match('/各\s*(\d+)\s*倍/', $text, $matches)) { # 匹配金额切非倍数,因为 各2倍，会误判的为：各2元
            $single_cn_text = '倍';
            $single_cn = $matches[1];
            $t = 4;
        }

        $cnSingleText = ThirdDTypeService::getTextCnSingle($text);
        // 使用正则表达式匹配 "倍" 前面的中文一到九
        if(empty($single) && preg_match('/(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})倍/u', $cnSingleText, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            $s = ThirdD::cn2num($t); # 中文转数字，一=>1、二=>2.。。。
            $single = $s * (int)$methods[$matchName]['money'];
            //p([$s, $single, $methods[$matchName]]);
            $single_cn_text = '倍';
            $single_cn = $s;
            $t = 5;
        }

        // 使用正则表达式匹配 "倍" 前面的数字
        if(empty($single) && preg_match('/各\s*(\d+)倍/u', $text, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            #p([$t, $s, $matchName, $matches, $methods]);
            $single = $t * (int)$methods[$matchName]['money'];
            //p([$text, $single, $t, $methods[$matchName], $matches]);
            #p([$single, $methods[$matchName]]);
            $single_cn_text = '';
            $single_cn = '';
            $t = 6;
        }
        // 使用正则表达式匹配 "倍" 前面的数字
        if(empty($single) && preg_match('/\s*(\d+)\s*倍/u', $text, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            $single = $t * (int)$methods[$matchName]['money'];
            $single_cn_text = '元';
            $single_cn = '';
            $t = 7;
        }

        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/\s*(\d+)\s*元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
            $t = 8;
        }
        //$text = '福136  139  346   001各2组';
        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && (preg_match('/各\s*(\d+)[直|组]/', $text, $matches) OR
                preg_match('/各\s*(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})[直|组]/', $text, $matches))) {
            $single = (is_numeric($matches[1]) ? $matches[1] : ThirdD::cn2num($matches[1])) * 2;
            $single_txt = $single;
            $t = 9.1;
        }

        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/各\s*(\d+)/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
            $t = 9;
        }

        if (empty($single) && preg_match('/([一二两三四五六七八九十百千万]{1,3})元/u', $cnSingleText, $matches)) {
            $t = $matches[1];
            $single = ThirdD::cn2num($t); # 中文转数字
            $single_txt = $matches[1];
            $t = 10;
        }
        if (empty($single) && preg_match('/(?:组选|组六|组三)?([一二两三四五六七八九十百千万]{1,3})\s*倍/u', $text, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            $s = ThirdD::cn2num($t); # 中文转数字，一=>1、二=>2.。。。
            $single = $s * (int)$methods[$matchName]['money'];
            $single_cn_text = '倍';
            $single_cn = $s;
            $t = 11;
        }
        # 福0324578组六40
        if(empty($single) && preg_match('/(\d{1,2})$/', $text, $matches)){
            $single = $matches[1];
        }
        //p($single);

        $data = [
            'single'=>$single,
            'text'=>$text,
            'single_txt'=>$single_txt.'元',
            'single_cn' => $single_cn,
            'single_cn_text' => $single_cn_text,
        ];
        if(empty($single)){
            # 兼容，在没有输入各x元的情况，这里先匹配获取总共金额，后面在根绝号码的数量反算倍数
            if(preg_match('/共(\d+)/', $text, $matches)){
                $data['all_moneys'] = $matches[1];
            }
        }
        Tool_Common::log('/matchSingle/'.__FUNCTION__, 'INFO', '匹配倍数01', ['text'=>$text,  't'=>$t, 'data'=>$data]);

        return $data;
    }

    /**
     * 获取中文倍数之前先剔除组三、组六的情况，比如：福234组三五十倍，最终返回：组234五十倍
     * @param string $text
     * @return array|mixed|string|string[]
     */
    public static function getTextCnSingle(string $text=''){
        $cnTextSingle = str_replace(['组三', '组六'], '', $text);

        return $cnTextSingle;
    }

    /**
     * 下注单号
     * @return bool|mixed|null
     */
    public static function getOrderId(){
        $BetOrderId = new BetOrderId();
        $BetOrderId->created_at = time();
        $BetOrderId->updated_at = time();
        $r = $BetOrderId->save();
        if(empty($r)){
            return false;
        }

        return $BetOrderId->bet_order_id;
    }

    /**
     * 彩票种类别名
     * @param int $lottery_type
     * @return string[]|\string[][]
     */
    public static function getThirdDAlias($lottery_type=''){

        $datas = [
            CommonBaseService::LOTTERY_TYPE_FUCAI => ['福彩3D', '福彩3d', '福彩', '福佳', '福3D', '三地', '3D', '3d', '福'],
            CommonBaseService::LOTTERY_TYPE_PL3 => ['排列三', '排三', '体彩', '体家', '排佳', /*'排3',*/ '体', '排', 'p3'],
        ];
        if(!isset($datas[$lottery_type])){
            return $datas;
        }

        return $datas[$lottery_type];
    }
}
