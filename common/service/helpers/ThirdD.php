<?php

namespace common\service\helpers;

use common\service\BaseService;
use common\service\thirdD\ThirdDTypeService;

class ThirdD extends BaseService
{
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
     * 中文转成数字 - 靠谱
     * @param $string
     * @return float|int|mixed|string
     */
    public static function cn2num($string) {
        if(is_numeric($string)){
            return $string;
        }
        // '仟' => '千','佰' => '百','拾' => '十',
        $string = str_replace('仟', '千', $string);
        $string = str_replace('佰', '百', $string);
        $string = str_replace('拾', '十', $string);
        $num = 0;
        $wan = explode('万', $string);
        if (count($wan) > 1) {
            $num += self::cn2num($wan[0]) * 10000;
            $string = $wan[1];
        }
        $qian = explode('千', $string);
        if (count($qian) > 1) {
            $num += self::cn2num($qian[0]) * 1000;
            $string = $qian[1];
        }
        $bai = explode('百', $string);
        if (count($bai) > 1) {
            $num += self::cn2num($bai[0]) * 100;
            $string = $bai[1];
        }
        $shi = explode('十', $string);
        if (count($shi) > 1) {
            $num += self::cn2num($shi[0] ? $shi[0] : '一') * 10;
            $string = $shi[1] ? $shi[1] : '零';
        }
        $ling = explode('零', $string);
        if (count($ling) > 1) {
            $string = $ling[1];
        }
        $d = array(
            '一' => '1','二' => '2','三' => '3','四' => '4','五' => '5','六' => '6','七' => '7','八' => '8','九' => '9',
            '壹' => '1','贰' => '2','叁' => '3','肆' => '4','伍' => '5','陆' => '6','柒' => '7','捌' => '8','玖' => '9',
            '零' => 0, '0' => 0, 'O' => 0, 'o' => 0,
            '两' => 2
        );
        return $num + @$d[$string];
    }

    /**
     * @param $vDim
     * @return int 判断数组维度
     */
    public static function getMaxDim($vDim): int
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

    /**
     * 判断号码是否有重复
     * @param string $codes 123、223
     * @param array $codesArr 剔重之后的号码
     * @return bool
     */
    public static function judgeCodesRepeat($codes='', &$codesArr=[]){
        if(empty($codes)){
            return false;
        }
        $len = strlen($codes);
        $c1 = [];
        for ($i=0; $i<$len; $i++){
            $c1[] = $codes[$i];
        }
        $codesArr = array_unique($c1);
        if(count($codesArr) < $len){
            //throw_info('该位置号码不能重复');
            return true;
        }

        return false;
    }

    /**
     * 字符串号码转换成数字
     * @param string $codes 234
     * @return array [2, 3, 4]
     */
    public static function getArrayCodesByString($codes=''){
        if(empty($codes)){
            return [];
        }
        $codes = (string)$codes;
        $len = strlen($codes);
        $data = [];
        for ($i=0; $i<$len; $i++){
            $data[] = $codes[$i];
        }

        return $data;
    }

    /**
     * 字符串转换成数字二、三定号码
     * @param array $codes ['234', '34']
     * @return array [23, 24, 33, 34, 43, 44]
     */
    public static function getArrayCodesByArray(array $codes=[]): array
    {
        if(empty($codes)){
            return [];
        }
        $len = count($codes); # 号码尾数
        $data = [];
        if($len==2){
            # 两位数
            $code0 = $codes[0];
            $code1 = $codes[1];
            for ($i=0; $i<strlen($code0); $i++){
                for ($j=0; $j<strlen($code1); $j++){
                    $data[] = $code0[$i].$code1[$j];
                }
            }
        }elseif($len==3){
            # 三位数
            $code0 = $codes[0];
            $code1 = $codes[1];
            $code2 = $codes[2];
            for ($i=0; $i<strlen($code0); $i++){
                for ($j=0; $j<strlen($code1); $j++){
                    for ($k=0; $k<strlen($code2); $k++) {
                        $data[] = $code0[$i] . $code1[$j] . $code2[$k];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 匹配百位置以及号码
     * @param string $text
     * @return array
     */
    public static function getPosAndNums(string $text=''): array
    {
        if(preg_match_all('/\d+/', $text, $matches)){
            switch (true){
                case strpos($text, '千') !== false:
                    $pos = '千';
                    break;
                case strpos($text, '百') !== false:
                    $pos = '百';
                    break;
                case strpos($text, '十') !== false:
                    $pos = '十';
                    break;
                case strpos($text, '个') !== false:
                    $pos = '个';
                    break;
            }
            $num = $matches[0][0];
        }
        if(empty($pos) OR empty($num)){
            throw_info('号码或位置匹配异常');
        }

        return [$pos, $num];
    }
}
