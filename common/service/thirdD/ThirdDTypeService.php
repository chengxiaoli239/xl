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
    public static function getPlayMethod($text=''){
        $methods = PlayMethodService::getMethods();
        $methodArr = [];
        foreach ($methods as $method){
            try {
                $alias_name = trim($method['alias_name']);
                $method_name = trim($method['name']);
                if(!empty($alias_name)){
                    $methodNames = array_merge([$method_name], explode(',', $alias_name));
                }else{
                    $methodNames = [$method_name];
                }
                $result = ThirdD::arrayItemInString($text, $methodNames);
                #print_r('id:'.$method['id']);
                #print_r($result);
                if($result){
                    $methodArr = ['id'=>$method['id'], 'name'=>$method['name'], 'matchName'=>end($result)];
                    break;
                }
            }catch (\Exception $e){
                var_dump(Json::encode($method, 320).'=='.$e->getMessage());
            }
        }

        return $methodArr;
    }

    /**
     * 判断金额：各x元、共x元
     * @param string $text
     * @return array
     */
    public static function getMoneys($text=''){
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

        // 使用正则表达式匹配 "倍" 前面的数字
        if(empty($single) && preg_match('/([\p{Han}一二三四五六七八九十]{1,3})倍/u', $text, $matches)) {
            $t = $matches[1];
            $single_txt = $matches[1];
            $single = ThirdD::cnToNums($t); # 中文转数字
        }else{
            # 匹配不到倍，则总金额为single*号码组数
        }

        if (preg_match('/共\s*(\d+)/', $text, $matches)) {
            $all_moneys = $matches[1];
        }elseif (!empty($single)){
            $all_moneys = $single;
        }

        $data = [
            'single'=>$single,
            'all_moneys'=>$all_moneys,
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
