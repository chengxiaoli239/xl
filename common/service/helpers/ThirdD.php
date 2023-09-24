<?php

namespace common\service\helpers;

use common\service\BaseService;
use common\service\thirdD\ThirdDTypeService;

class ThirdD extends BaseService
{
    public static $typeArr = ['福', '福彩', '富', '福彩3D', '福彩3d'];

    /**
     * 判断数组中的元素是否存在于字符串text中
     * @param string $text
     * @param array $typeArr
     * @return array
     */
    public static function arrayItemInString($text='', $typeArr=[]){

        #$flag = array_reduce($typeArr, function($carry, $item) use ($text) {
        #    return $carry || strpos($text, $item) !== false;
        #}, false);
        $matchedItems = array_filter($typeArr, function ($item) use ($text) {
            return strpos($text, $item) !== false;
        });


        return $matchedItems;
    }

    /**
     * 统计字符串中汉字的个数
     *
     * @param string $str 要统计的字符串
     * @return int 汉字个数
     */
    public static function countChineseCharacters($str) {
        preg_match_all('/[\x{4e00}-\x{9fff}]/u', $str, $matches);
        return count($matches[0]);
    }

    /**
     * 多空格替换成单空格
     * @param string $str
     * @return string|string[]|null
     */
    public static function replaceManyNull($str=''){
        $str = preg_replace( '#\s+#', ' ', $str); # 多个空格替换成单个空格

        return $str;
    }


    /**
     *  中文转数字
     * @param $t
     * @return int
     */
    public static function cnToNums($t){
        $count = ThirdD::countChineseCharacters($t);
        if($count>1){
            $singleTxts = mb_str_split($t);
            if(count($singleTxts) == 2){
                if($singleTxts[0] == '十'){
                    $singleN = 10;
                }
                $single = $singleN + ThirdDTypeService::SINGLE_ASSCIATE[$singleTxts[1]];
            }elseif(count($singleTxts)==3){
                $s1 = ThirdDTypeService::SINGLE_ASSCIATE[$singleTxts[0]] * 10;
                $s2 = ThirdDTypeService::SINGLE_ASSCIATE[$singleTxts[2]];
                $single = $s1 + $s2;
            }
        }else{
            $single = ThirdDTypeService::SINGLE_ASSCIATE[$t];
        }

        return $single;
    }


    /**
     * @param $vDim
     * @return int 判断数组维度
     */
    public static function getMaxDim($vDim)
    {
        if(!is_array($vDim)) return 0;
        else
        {
            $max1 = 0;
            foreach($vDim as $item1)
            {
                $t1 = self::getmaxdim($item1);
                if( $t1 > $max1) $max1 = $t1;
            }
            return $max1 + 1;
        }
    }
}
