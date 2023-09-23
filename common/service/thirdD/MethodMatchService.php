<?php

namespace common\service\thirdD;

use common\service\BaseService;
use yii\helpers\Json;

class MethodMatchService extends BaseService
{
    /**
     * 1 直选
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZhiXuan($text='', &$codes=[], &$count=0){
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{3}(?:\s+\d{3})*)/', $text, $matches)) {
            $codes = explode(' ', trim($matches[1][0]));
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        if(empty($codes)){
            throw_info('匹配组选号码为空');
        }
        $methodArr = ['id'=>1, 'name'=>'直选', 'matchName'=>'组选'];
        $count = count($codes);

        return $methodArr;
    }

    /**
     * 2、3 组选
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZuXuan($text='', &$codes=[], &$count=0){
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{3}(?:\s+\d{3})*)/', $text, $matches)) {
            $codes = explode(' ', trim($matches[1][0]));
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        if(empty($codes)){
            throw_info('匹配组选号码为空');
        }
        $method_id = 0;
        foreach ($codes as $code){
            $code = trim($code);
            if(strlen($code) == 3){
                if($code[0]==$code[1] OR $code[1]==$code[2] OR $code[0]==$code[2]){
                    # 组三
                    $methodArr = ['id'=>2, 'name'=>'组三', 'matchName'=>'组选'];
                    $new_method_id = 2;
                }else{
                    # 组六
                    $methodArr = ['id'=>3, 'name'=>'组六', 'matchName'=>'组选'];
                    $new_method_id = 3;
                }
                if($method_id !== 0 && $new_method_id != $method_id){
                    throw_info('组三、组六须分开输入', self::CODE_FOR_USER);
                }else{
                    $method_id = $new_method_id;
                }
            }else{
                throw_info('组选');
            }
        }
        $count = count($codes);

        return $methodArr;
    }

    /**
     * 独胆
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchDuDan($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/胆(\d{1}(?:\s*\d{1})*)/', $text, $matches)) {
            $numbers = str_replace(' ', '', $matches[1])[0];
        }

        if(empty($numbers)){
            throw_info('获取号码异常');
        }
        $codes = [];
        for ($i=0; $i<strlen($numbers); $i++){
            $codes[] = $numbers[$i];
        }
        $count = count($codes);
        $methodArr = ['id'=>4, 'name'=>'独胆', 'matchName'=>$matchName];

        return $methodArr;
    }

    /**
     * 5、双飞
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchShuangFen($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/双飞(\d{2}(?:\s*\d{2})*)/', $text, $matches)) {
            $numbers = $matches[1][0];
        }

        if(empty($numbers)){
            throw_info('获取号码异常');
        }
        $codes = explode(' ', $numbers);
        $count = count($codes);
        $methodArr = ['id'=>5, 'name'=>'双飞', 'matchName'=>$matchName];

        return $methodArr;
    }

    /**
     * 6、对子全拖
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchDuiZiQuanTuo($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/对子全拖(\d{2}(?:\s*\d{2})*)/', $text, $matches)) {
            $numbers = $matches[1][0];
        }

        if(empty($numbers)){
            throw_info('获取号码异常');
        }
        $codes = explode(' ', $numbers);
        $count = count($codes);
        $methodArr = ['id'=>6, 'name'=>'对子全拖', 'matchName'=>$matchName];

        return $methodArr;
    }

    /**
     * 7、一码定位
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchYiMaDing($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配所有单个数字
        $text = str_replace(' ', '', $text);
        if (preg_match_all('/[百十个]位{0,1}(\d+)/u', $text, $matches)) {
            $numbers = str_replace('位', '', $matches[0]);
        }

        if(empty($numbers)){
            throw_info('一码定位获取号码异常');
        }

        $codes = [];
        $count = 0;
        foreach ($numbers as $number){
            if(strpos($number, '百') !== false){
                $code = str_replace('百', '', $number);
                $codes[] = '百:'. $code;
            }elseif(strpos($text, '十') !== false){
                $code = str_replace('十', '', $number);
                $codes[] = '十:'. $code;
            }elseif(strpos($text, '个') !== false){
                $code = str_replace('个', '', $number);
                $codes[] = '个:'. $code;
            }
            $count += strlen($code);
        }
        $methodArr = ['id'=>7, 'name'=>'一码定位', 'matchName'=>$matchName];

        return $methodArr;
    }


    /**
     * 8、二码定位
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchErMaDing($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配所有单个数字
        $text = str_replace(' ', '', $text);
        if (preg_match_all('/[百十个]位{0,1}(\d+)[百十个]位{0,1}(\d+)/u', $text, $matches)) {
            $numbers = str_replace('位', '', $matches[0]);
        }

        if(empty($numbers)){
            throw_info($matchName.'获取号码异常');
        }

        $codes = [];
        $count = 0;
        foreach ($matches[0] as $number){
            $number = str_replace('百', '百:', $number);
            $number = str_replace('十', '十:', $number);
            $number = str_replace('个', '个:', $number);
            $codes[] = $number;

            $count += strlen($matches[1][0]) * strlen($matches[2][0]);
        }
        $methodArr = ['id'=>8, 'name'=>'二码定位', 'matchName'=>$matchName];

        return $methodArr;
    }

    /**
     * 9、豹子全包
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchBaoZi($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/豹子全包/u', $text, $matches)) {
            $numbers = $matches[0];
            $name = '豹子全包';
        }
        #if (empty($codes) && preg_match_all('/豹子((\d+){3})*/u', $text, $matches)) {
        #    $numbers = str_replace('位', '', $matches[0]);
        #}
        #p($codes);

        if(empty($numbers)){
            throw_info($matchName.'获取号码异常');
        }
        $codes = $numbers;
        $count = count($codes);


        $methodArr = ['id'=>9, 'name'=>$name, 'matchName'=>$matchName];

        return $methodArr;
    }

    /**
     * 10-15组六四、五...九码
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZuLiuXMa($text='', &$codes=[], &$count=0, $matchName='', $t='四'){
        $num = ThirdDTypeService::SINGLE_ASSCIATE[$t];
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{'.$num.',}(?:\s+\d{'.$num.',})*)/', $text, $matches)) {
            $codes = explode(' ', trim($matches[1][0]));
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        if(empty($codes)){
            throw_info('匹配组选号码为空');
        }
        foreach ($codes as $code){
            if(strlen($code) != $num){
                throw_info($matchName.'号码数量不匹配['.$num.'!='.strlen($code).']');
            }
        }
        $name = '组六'.$t.'码';
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1, $orignMethod);
        $id = $methods[$name]['id'];

        $count = count($codes);
        $methodArr = ['id'=>$id, 'name'=>$name, 'matchName'=>$matchName];

        return $methodArr;
    }

    /**
     * 16、组六全包
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchZuLiuQuanBao($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/组六全包/u', $text, $matches)) {
            $numbers = $matches[0];
            $name = '组六全包';
        }
        #if (empty($codes) && preg_match_all('/豹子((\d+){3})*/u', $text, $matches)) {
        #    $numbers = str_replace('位', '', $matches[0]);
        #}
        #p($codes);

        if(empty($numbers)){
            throw_info($matchName.'获取号码异常');
        }
        $codes = $numbers;
        $count = count($codes);


        $methodArr = ['id'=>16, 'name'=>$name, 'matchName'=>$matchName];

        return $methodArr;
    }

    /**
     * 17-组三两码
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchLiangMaZuSan($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{2}(?:\s+\d{2})*)/', $text, $matches)) {
            $codes = explode(' ', trim($matches[1][0]));
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        if(empty($codes)){
            throw_info('匹配组选号码为空');
        }
        $count = count($codes);
        $methodArr = ['id'=>17, 'name'=>'组三两码', 'matchName'=>$matchName];

        return $methodArr;
    }

}
