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
    public static function getLotteryType(string $text='', &$isEmpty=false): array
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
        if(empty($result)){
            $isEmpty = true;
            # 默认为福彩
            $lottery_type = CommonBaseService::LOTTERY_TYPE_FUCAI;
        }
        $lottery_name = CommonService::getLotteryName($lottery_type);

        return [$lottery_type, $lottery_name, $result??[]];
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

        $countY = mb_substr_count($text, '元'); # 元
        $countB = mb_substr_count($text, '倍'); # 倍
        $matchMethodAndCodeText = $text;
        #if($countB>1 OR $countY>1){
        #    $matchMethodAndCodeText = $text;
        #}else{
        #    $matchMethodAndCodeText = explode('各', $text)[0];
        #}
        #p(['methodArr'=>$methodArr, 'text'=>$text, 'matchMethodAndCodeText'=>$matchMethodAndCodeText]);
        #if(strpos($matchMethodAndCodeText, $methodArr['name'])===false) $matchMethodAndCodeText = $methodArr['name'].$matchMethodAndCodeText;
        if(strpos($text, '全包')!==false) { # 9豹子全包
            $methodArr = MethodMatchService::matchQuanBao($matchMethodAndCodeText, $codes, $count);
        }else if( false && strpos($text, '复式')===false && (
                (strpos($text, '直')!==false && strpos($text, '组三')===false && strpos($text, '组六')===false) OR # 直选
                (strpos($text, '组')!==false && strpos($text, '组三')===false && strpos($text, '组六')===false) # 组选
            )
        ) { # 直、组（原先的组三组六）
            #$methodArr = MethodMatchService::matchZhiZu($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
            $methodArr = MethodMatchService::matchZhiZuOrZuSanOrZuLiuXMa($matchMethodAndCodeText, $codes, $count);
        }else if($methodArr['originName'] == '独胆') { # 4独胆
            $methodArr = MethodMatchService::matchDuDan($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '双飞' OR $methodArr['originName'] == '对子全拖') { # 5双飞
            $methodArr = MethodMatchService::matchShuangFei($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '一码定位'){ # 7一码定位
            $methodArr = MethodMatchService::matchYiMaDing($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '二码定位'){ # 8二码定位
            $methodArr = MethodMatchService::matchErMaDing($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        #}elseif(strpos($text, '全包')!==false){ # 9豹子全包
        #    $methodArr = MethodMatchService::matchQuanBao($matchMethodAndCodeText, $codes, $count);
        }else if(
            (
                strpos($text, '复式')===false && (
                    (strpos($text, '直')!==false && strpos($text, '组三')===false && strpos($text, '组六')===false) OR # 直选
                    (strpos($text, '组')!==false && strpos($text, '组三')===false && strpos($text, '组六')===false) # 组选
                )
            ) OR (
            strpos($matchMethodAndCodeText, '拖') === false &&
            (
                strpos($text, '组三') !== false OR
                strpos($text, '组六') !== false OR
                (strpos($text, '组三') !== false && strpos($text, '组六') !== false) OR
                (strpos($text, '直') !== false && strpos($text, '组') !== false) OR
                (strpos($text, '组选') !== false) OR
                (strpos($text, '组') !== false) OR
                (strpos($text, '直') !== false && strpos($text, '复式')===false)
            ))
        ) { # 1、2、3组选
            list($matchMethodAndCodeText, $singleArr) = ThirdDTypeService::getTwoMethodAndSingle($text, $matchMethodAndCodeText);
            #p([$matchMethodAndCodeText, $text, $singleArr]);
            $methodArr = MethodMatchService::matchZhiZuOrZuSanOrZuLiuXMa($matchMethodAndCodeText, $codes, $count, $singleArr);

        #}else if($methodArr['originName'] == '组六四码') { # 10组六四码
        #    $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='四');
        #}else if($methodArr['originName'] == '组六五码') { # 11组六五码
        #    $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='五');
        #}else if($methodArr['originName'] == '组六六码') { # 12组六六码
        #    $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='六');
        #}else if($methodArr['originName'] == '组六七码') { # 13组六七码
        #    $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='七');
        #}else if($methodArr['originName'] == '组六八码') { # 14组六八码
        #    $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='八');
        #}else if($methodArr['originName'] == '组六九码') { # 15组六九码
        #    $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='九');
        #}elseif($methodArr['originName'] == '组六全包'){ # 16组六全包
        #    $methodArr = MethodMatchService::matchZuLiuQuanBao($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        #}else if($methodArr['originName'] == '组三两码') { #17组三两码
        #    $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='两');
        #}else if($methodArr['originName'] == '组三三码') { #18组三三码
        #    $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='三');
        #}else if($methodArr['originName'] == '组三四码') { #19组三四码
        #    $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='四');
        #}else if($methodArr['originName'] == '组三五码') { #20组三四码
        #    $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='五');
        #}else if($methodArr['originName'] == '组三六码') { #21组三四码
        #    $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='六');
        #}else if($methodArr['originName'] == '组三七码') { #22组三四码
        #    $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='七');
        #}else if($methodArr['originName'] == '组三八码') { #23组三四码
        #    $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='八');
        #}else if($methodArr['originName'] == '组三九码') { #24组三四码
        #    $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='九');
        #}elseif($methodArr['originName'] == '组三全包'){ # 25组三全包
        #    $methodArr = MethodMatchService::matchZuSanQuanBao($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if(strpos($text, '跨度') !== false) { #26-35跨度0
            $methodArr = MethodMatchService::matchKuaDuX($matchMethodAndCodeText, $codes, $count);
        }else if(strpos($matchMethodAndCodeText, '拖') !== false) { #36-51:1码拖.... [组三|组六]
            $methodArr = MethodMatchService::matchYiMaTuo($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        #}else if(in_array($methodArr['originName'], ['复式三', '复式四', '复式五', '复式六', '复式七', '复式八', '复式九']) && strpos($text, '直选')===false) { # 51-58:复式三 - 九
        }else if(strpos($text, '复式')!==false && strpos($text, '直选')===false) { # 51-58:复式三 - 九
            $methodArr = MethodMatchService::matchFuShi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if(
            (strpos($text, '和值') !== false OR strpos($text, '合值') !== false) &&
            (
                strpos($text, '大') === false && strpos($text, '小') === false &&
                strpos($text, '单') === false && strpos($text, '双') === false
            )
        ) { #36-51:1码拖.... [组三|组六]
            $methodArr = MethodMatchService::matchHeZhi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if(
            (strpos($matchMethodAndCodeText, '和值') !== false OR strpos($matchMethodAndCodeText, '合值') !== false) &&
            (
                strpos($matchMethodAndCodeText, '大') !== false OR strpos($matchMethodAndCodeText, '小') !== false OR
                strpos($matchMethodAndCodeText, '单') !== false OR strpos($matchMethodAndCodeText, '双') !== false
            )
        ) { #36-51:1码拖.... [组三|组六]
            $methodArr = MethodMatchService::matchHeZhiDaXiaoDanShuang($matchMethodAndCodeText, $codes, $count);
        }elseif(strpos($text, '复式') !== false){ # 91 定位直选复式
            $methodArr = MethodMatchService::matchDingWeiFuShi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '全倒'){ # 92 全倒
            $methodArr = MethodMatchService::matchQuanDao($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        #}elseif($methodArr['originName'] == '直选复式'){ # 93 定位直选复式
        #    p('kkkk');
        #    $methodArr = MethodMatchService::matchZhiXuanFuShi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else{
            $codes = explode(' ', trim($matchMethodAndCodeText));
        }
        #p(['methodArr'=>$methodArr, 'codes'=>$codes, 'count'=>$count]);

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
        $pattern36 = '/(组六|组三)\s*各\s*([一二两三四五六七八九十]{1,3}\s*倍|(\d+)\s*元|[一二两三四五六七八九十]{1,3}\s*元|(\d)+\s*倍)/u';
        $patternZhiZu = '/(直|组三|组六|组)\s*各\s*([一二两三四五六七八九十]{1,3}\s*倍|(\d+)\s*元|[一二两三四五六七八九十]{1,3}\s*元|(\d)+\s*倍)/u';
        $patternZhiZuNotBei = '/(直|直选|组三|组六|组选|组)\s*([一二两三四五六七八九十]{1,3}倍|(\d+)\s*元|[一二两三四五六七八九十]{1,3}\s*元|(\d)+倍)/u';
        switch (true){
            # 匹配组三组六
            case strpos($text, '组三') !== false && strpos($text, '组六') !== false && preg_match_all($pattern36, $text, $matcheSingles):
                #p([$matchMethodAndCodeText, $text, $matcheSingles]);
                # $matcheSingles Array ( [0] => Array ( [0] => 组六各4倍 [1] => 组三各20元 ) [1] => Array ( [0] => 组六 [1] => 组三 ) [2] => Array ( [0] => 倍 [1] => 元 ) )
                //p($matcheSingles);
                foreach ($matcheSingles[0] as $matcheSingle){
                    $sData = explode('各', $matcheSingle);
                    if(strpos($sData[1], '倍') !== false){
                        # 倍
                        $singleTxt = str_replace('倍', '', $sData[1]);
                        if(is_numeric($singleTxt)){
                            $tmpSingle = $singleTxt * 10; #  转换成元
                        }else{
                            # 中文
                            $tmpSingle = ThirdD::cn2num($singleTxt) * 10; #  # 中文转数字  转换成元
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
                    $singleArr[$sData[0]] = $tmpSingle; # 倍数转换成：元
                }
                #p($singleArr);
                if(!empty($singleArr)){
                    if(count($singleArr)==1){
                        $singleArr = [
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
                    $matchMethodAndCodeText .= '组三组六';
                }
                break;
            case strpos($text, '直') !== false && strpos($text, '组') !== false && preg_match_all($patternZhiZu, $text, $matcheSingles):
                foreach ($matcheSingles[0] as $matcheSingle){
                    $sData = explode('各', $matcheSingle);
                    if(strpos($sData[1], '倍') !== false){ # 倍
                        $singleTxt = str_replace(['倍', '组选', '组三', '组六', '组', '直选', '直'], '', $sData[1]);
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
                    $singleArr[$sData[0]] = $tmpSingle; # 倍数转换成：元
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
            case strpos($text, '直') !== false && strpos($text, '组') !== false && preg_match_all($patternZhiZuNotBei, $text, $matcheSingles):
                foreach ($matcheSingles[0] as $matcheSingle){
                    $patternBei = '/(直选|直|组三|组六|组选|组)\s*([一二两三四五六七八九十]{1,3}倍|(\d+)\s*元|[一二两三四五六七八九十]{1,3}\s*元|(\d)+倍)/u';
                    if(preg_match_all($patternBei, $matcheSingle, $ms)){
                        if(strpos($ms[2][0], '倍') !== false){ # 倍
                            $singleOne = (
                                strpos($ms[1][0], '直选') !== false OR
                                strpos($ms[1][0], '直') !== false OR
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
                        $singleArr[$ms[1][0]] = $tmpSingle; # 倍数转换成：元
                    }
                }
                break;
            default:
                break;
        }
        #p([$matchMethodAndCodeText, $text, $matcheSingles, $methodArr, $singleArr]);
        # 玩法类型组合：组三、组六、组三&组六、直&组（一直一组，二直三组，直组）、组选、组、直||直选

        #p([$matchMethodAndCodeText, $matcheSingles, $text, $singleArr]);

        return [$matchMethodAndCodeText, $singleArr];
    }

    /**
     * 判断金额：各x元、共x元
     * @param string $text
     * @return array
     */
    public static function getMoneys($text='', $matchName='', $playMethod=[]){
        #header('Content-Type: text/html; charset=UTF-8');
        $single = 0;
        $text = trim($text);

        $t=0; # 匹配倍数走进哪个逻辑标识
        $single_cn_text = '元';
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
        if(empty($single) && preg_match('/\s*(\d+)倍/u', $text, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            $single = $t * (int)$methods[$matchName]['money'];
            //p([$text, $single, $t, $methods[$matchName], $matches]);
            $single_cn_text = '';
            $single_cn = '';
            $t = 7;
        }

        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/\s*(\d+)\s*元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
            $t = 8;
        }
        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/各\s*(\d+)/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
            $t = 9;
        }

        if (empty($single) && preg_match('/([一二两三四五六七八九十百千万]{1,3})元/u', $cnSingleText, $matches)) {
            $t = $matches[1];
            $single_txt = $matches[1];
            $single = ThirdD::cn2num($t); # 中文转数字
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
        Tool_Common::log('/matchSingle/'.__FUNCTION__, 'INFO', '匹配倍数', ['text'=>$text,  't'=>$t, 'data'=>$data]);

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
            CommonBaseService::LOTTERY_TYPE_FUCAI => ['福彩3D', '福彩3d', '福彩', '福佳', '福3D', '3D', '3d', '福'],
            CommonBaseService::LOTTERY_TYPE_PL3 => ['排列三', '排三', '体彩', '体家', '排佳', /*'排3',*/ '体', '排', 'p3'],
        ];
        if(!isset($datas[$lottery_type])){
            return $datas;
        }

        return $datas[$lottery_type];
    }
}
