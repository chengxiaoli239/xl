<?php

namespace common\service\thirdD;

use backend\models\LotteryType;
use common\models\thirdD\BetOrderId;
use common\service\BaseService;
use common\service\CommonService;
use common\service\helpers\ThirdD;
use yii\helpers\Json;

class ThirdDTypeService extends BaseService
{
    const CODE_FOR_USER = 33333;
    # lottery_type:26 福彩3d、27 排列三
    const LOTTERY_TYPE_FUCAI = 26;
    const LOTTERY_TYPE_PL3 = 27;
    const SINGLE_ASSCIATE = [
        '一' => 1,
        '二' => 2,
        '两' => 2,
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
        $name = CommonService::getLotteryName($lottery_type);

        return [$lottery_type, $name, $result];
    }

    /**
     * 判断playMethod 玩法
     * @param string $text
     * @return array
     */
    public static function getPlayMethodAndCodes($text='', &$codes=[]){
        #$methods = PlayMethodService::getMethods();
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1, $orignMethod);
        $methodArr = [];
        foreach ($methods as $method){
            try {
                $method_name = trim($method['name']);
                $result = ThirdD::arrayItemInString($text, [$method_name]);
                if($result){
                    $methodArr = ['id'=>$method['id'], 'name'=>$methods[$result[0]]['name'], 'originName'=>$orignMethod[$method['id']]['name']];
                    if(strpos($text, '码定') === false){ # 非一码、二码定位
                        $text = str_replace($methodArr['matchName'], $methodArr['name'], $text);
                    }
                    #p(['methodArr'=>$methodArr]);
                    break;
                }
            }catch (\Exception $e){
                var_dump($e->getMessage());
            }
        }
        #p(['methodArr'=>$methodArr]);
        $matchMethodAndCodeText = explode('各', $text)[0];
        p([$methodArr, $matchMethodAndCodeText, $text], 0);
        if($methodArr['originName'] == '直选') {
            # 直选
            $methodArr = MethodMatchService::matchZhiXuan($matchMethodAndCodeText, $codes, $count);
        }else if(in_array($methodArr['originName'], ['组选', '组三', '组六'])) {
            # 2、3组选
            $methodArr = MethodMatchService::matchZuXuan($matchMethodAndCodeText, $codes, $count);
        }else if($methodArr['originName'] == '独胆') {
            # 4独胆
            $methodArr = MethodMatchService::matchDuDan($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '双飞') {
            # 5独胆
            $methodArr = MethodMatchService::matchShuangFen($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '对子全拖') {
            # 6对子全拖
            $methodArr = MethodMatchService::matchDuiZiQuanTuo($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '一码定'){
            $methodArr = MethodMatchService::matchYiMaDing($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '二码定'){
            $methodArr = MethodMatchService::matchErMaDing($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }elseif($methodArr['originName'] == '豹子全包'){
            $methodArr = MethodMatchService::matchBaoZi($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
        }else if($methodArr['originName'] == '组六四码') {
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='四');
        }else if($methodArr['originName'] == '组六五码') {
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='五');
        }else if($methodArr['originName'] == '组六六码') {
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='六');
        }else if($methodArr['originName'] == '组六七码') {
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='七');
        }else if($methodArr['originName'] == '组六八码') {
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='八');
        }else if($methodArr['originName'] == '组六九码') {
            $methodArr = MethodMatchService::matchZuLiuXMa($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name'], $t ='九');
        }elseif($methodArr['originName'] == '组六全包'){
            $methodArr = MethodMatchService::matchZuLiuQuanBao($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);

        }else if($methodArr['originName'] == '组三两码') {
            $methodArr = MethodMatchService::matchLiangMaZuSan($matchMethodAndCodeText, $codes, $count, $matchName=$methodArr['name']);
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
    public static function getMoneys($text='', $method_id=0, $matchName=''){
        header('Content-Type: text/html; charset=UTF-8');
        $single = 0;

        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (preg_match('/各\s*(\d+)\s*元/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
        }
        // 使用正则表达式匹配 "各" 或 "共" 后面的数字
        if (empty($single) && preg_match('/各\s*(\d+)\s*/', $text, $matches)) {
            $single_txt = $matches[1];
            $single = $matches[1];
        }

        // 使用正则表达式匹配 "倍" 前面的中文一到九
        if(empty($single) && preg_match('/([\p{Han}一二三四五六七八九十]{1,3})倍/u', $text, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            $s = ThirdD::cnToNums($t); # 中文转数字
            #p([$s, $method_id, $methods[$method_id]]);
            $single = $s * (int)$methods[$matchName]['money'];
        }

        // 使用正则表达式匹配 "倍" 前面的数字
        if(empty($single) && preg_match('/(\d+)倍/', $text, $matches)) {
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $t = $matches[1];
            #p([$s, $method_id, $methods[$method_id]]);
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
    public static function getThirdDAlias($lottery_type=26){

        $datas = [
            ThirdDTypeService::LOTTERY_TYPE_FUCAI => ['福彩3D', '福彩3d', '福彩', '福', '富'],
            ThirdDTypeService::LOTTERY_TYPE_PL3 => ['排三', '体彩', '排3', '体', '排', 'p3'],
        ];
        if(!isset($datas[$lottery_type])){
            return $datas;
        }

        return $datas[$lottery_type];
    }
}
