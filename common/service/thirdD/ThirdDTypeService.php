<?php

namespace common\service\thirdD;

use backend\models\LotteryType;
use common\models\thirdD\BetOrderId;
use common\service\BaseService;
use common\service\CommonService;
use common\service\helpers\ThirdD;
use yii\helpers\Json;

class ThirdDTypeService extends CommonBaseService
{
    # lottery_type:26 福彩3d、27 排列三
    const LOTTERY_TYPE_FUCAI = 26;
    const LOTTERY_TYPE_PL3 = 27;
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
    public static function getLotteryType($text=''){
        $lottery_types = [
            ThirdDTypeService::LOTTERY_TYPE_FUCAI,
            ThirdDTypeService::LOTTERY_TYPE_PL3,
        ];

        foreach ($lottery_types as $lottery_type){
            // 检查$arr1是否有元素存在于$str
            #p([$text, ThirdDTypeService::getThirdDAlias($lottery_type)]);
            $result = ThirdD::arrayItemInString($text, ThirdDTypeService::getThirdDAlias($lottery_type));
            if($result){
                break;
            }
        }
        if(empty($result)){
            # 默认为福彩
            $lottery_type = ThirdDTypeService::LOTTERY_TYPE_FUCAI;
        }
        $lottery_name = CommonService::getLotteryName($lottery_type);

        return [$lottery_type, $lottery_name, $result];
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
    public static function getPlayMethodAndCodes($text='', &$codes=[]){
        #$methods = PlayMethodService::getMethods();
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1, $orignMethod);
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
        $matchMethodAndCodeText = explode('各', $text)[0];
        //p(['methodArr'=>$methodArr, 'text'=>$text, 'matchMethodAndCodeText'=>$matchMethodAndCodeText]);
        if(strpos($matchMethodAndCodeText, $methodArr['name'])===false) $matchMethodAndCodeText = $methodArr['name'].$matchMethodAndCodeText;
        #p([$methodArr, $matchMethodAndCodeText, $text], 0);
        if($methodArr['originName'] == '直选') {
            $methodArr = MethodMatchService::matchZhiXuan($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if(
            (in_array($methodArr['originName'], ['组选', '组三', '组六']) OR strpos($text, '组') !== false)
            && strpos($matchMethodAndCodeText, '拖') === false
        ) { # 2、3组选
            $methodArr = MethodMatchService::matchZuXuan($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '独胆') { # 4独胆
            $methodArr = MethodMatchService::matchDuDan($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '双飞') { # 5双飞
            $methodArr = MethodMatchService::matchShuangFen($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '对子全拖') { # 6对子全拖
            $methodArr = MethodMatchService::matchDuiZiQuanTuo($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '一码定位'){ # 7一码定位
            $methodArr = MethodMatchService::matchYiMaDing($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '二码定位'){ # 8二码定位
            $methodArr = MethodMatchService::matchErMaDing($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '豹子全包'){ # 9豹子全包
            $methodArr = MethodMatchService::matchBaoZi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '组六四码') { # 10组六四码
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='四');
        }else if($methodArr['originName'] == '组六五码') { # 11组六五码
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='五');
        }else if($methodArr['originName'] == '组六六码') { # 12组六六码
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='六');
        }else if($methodArr['originName'] == '组六七码') { # 13组六七码
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='七');
        }else if($methodArr['originName'] == '组六八码') { # 14组六八码
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='八');
        }else if($methodArr['originName'] == '组六九码') { # 15组六九码
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='九');
        }elseif($methodArr['originName'] == '组六全包'){ # 16组六全包
            $methodArr = MethodMatchService::matchZuLiuQuanBao($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '组三两码') { #17组三两码
            $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='两');
        }else if($methodArr['originName'] == '组三三码') { #18组三三码
            $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='三');
        }else if($methodArr['originName'] == '组三四码') { #19组三四码
            $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='四');
        }else if($methodArr['originName'] == '组三五码') { #20组三四码
            $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='五');
        }else if($methodArr['originName'] == '组三六码') { #21组三四码
            $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='六');
        }else if($methodArr['originName'] == '组三七码') { #22组三四码
            $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='七');
        }else if($methodArr['originName'] == '组三八码') { #23组三四码
            $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='八');
        }else if($methodArr['originName'] == '组三九码') { #24组三四码
            $methodArr = MethodMatchService::matchZuSanXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t='九');
        }elseif($methodArr['originName'] == '组三全包'){ # 25组三全包
            $methodArr = MethodMatchService::matchZuSanQuanBao($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if(strpos($methodArr['name'], '夸度') !== false OR strpos($methodArr['originName'], '跨度') !== false) { #26-35跨度0
            $methodArr = MethodMatchService::matchKuaDuX($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if(strpos($matchMethodAndCodeText, '拖') !== false) { #36-51:1码拖.... [组三|组六]
            $methodArr = MethodMatchService::matchYiMaTuo($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        #}else if(in_array($methodArr['originName'], ['复式三', '复式四', '复式五', '复式六', '复式七', '复式八', '复式九']) && strpos($text, '直选')===false) { # 51-58:复式三 - 九
        }else if(strpos($text, '复式')!==false && strpos($text, '直选')===false) { # 51-58:复式三 - 九
            $methodArr = MethodMatchService::matchFuShi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if(
            (strpos($matchMethodAndCodeText, '和值') !== false OR strpos($matchMethodAndCodeText, '合值') !== false) &&
            (
                strpos($matchMethodAndCodeText, '大') === false && strpos($matchMethodAndCodeText, '小') === false &&
                strpos($matchMethodAndCodeText, '单') === false && strpos($matchMethodAndCodeText, '双') === false
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
            $methodArr = MethodMatchService::matchHeZhiDaXiaoDanShuang($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '定位直选复式'){ # 91 定位直选复式
            $methodArr = MethodMatchService::matchDingWeiZhiXuanFuShi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '全倒'){ # 92 定位直选复式
            $methodArr = MethodMatchService::matchQuanDao($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '直选复式'){ # 93 定位直选复式
            $methodArr = MethodMatchService::matchZhiXuanFuShi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else{
            $codes = explode(' ', trim($matchMethodAndCodeText));
        }
        #p(['methodArr'=>$methodArr, 'codes'=>$codes, 'count'=>$count]);

        return [$methodArr, $codes, $count];
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

        // 使用正则表达式匹配 直选复式
        if ($playMethod['name']=='直选复式' && preg_match('/(\d+(?:\.\d+)?)元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
            #$count = $playMethod['count']? $playMethod['count']*$matches[1] : $matches[1];
        }

        // 使用正则表达式匹配 全倒
        if ($playMethod['name']=='全倒' && preg_match('/(\d+(?:\.\d+)?)元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $playMethod['single']? $playMethod['single']*$matches[1] : $matches[1];
        }

        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/各\s*(\d+(?:\.\d+)?)\s*元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
        }
        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/各\s*(\d+)\s*(?!(?:倍))/', $text, $matches)) { # 匹配金额切非倍数,因为 各2倍，会误判的为：各2元
            $single_txt = $matches[1];
            $single = $matches[1];
        }

        // 使用正则表达式匹配 "倍" 前面的中文一到九
        if(empty($single) && preg_match('/([\p{Han}一二三四五六七八九十]{1,3})倍/u', $text, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            $s = ThirdD::cnToNums($t); # 中文转数字
            #p([$s, $method_id, $matchName, $methods[$method_id]]);
            $single = $s * (int)$methods[$matchName]['money'];
        }

        // 使用正则表达式匹配 "倍" 前面的数字
        if(empty($single) && preg_match('/[各]{0,1}(\d+)倍/', $text, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            #p([$s, $method_id, $matchName, $methods[$method_id]]);
            $single = $t * (int)$methods[$matchName]['money'];
        }

        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/\s*(\d+)\s*元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
        }

        if (empty($single) && preg_match('/([\p{Han}一二三四五六七八九十]{1,3})元/u', $text, $matches)) {
            $t = $matches[1];
            $single_txt = $matches[1];
            $single = ThirdD::cnToNums($t); # 中文转数字
        }

        $data = [
            'single'=>$single,
            'text'=>$text,
            'single_txt'=>$single_txt.'元',
        ];
        if(empty($single)){
            # 兼容，在没有输入各x元的情况，这里先匹配获取总共金额，后面在根绝号码的数量反算倍数
            if(preg_match('/共(\d+)/', $text, $matches)){
                $data['all_moneys'] = $matches[1];
            }
        }

        return $data;
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
            ThirdDTypeService::LOTTERY_TYPE_FUCAI => ['福彩3D', '福彩3d', '福彩', '福佳', '福3D', '3D', '3d', '福'],
            ThirdDTypeService::LOTTERY_TYPE_PL3 => ['排三', '体彩', '体家', '排佳', '排3', '体', '排', 'p3'],
        ];
        if(!isset($datas[$lottery_type])){
            return $datas;
        }

        return $datas[$lottery_type];
    }
}
