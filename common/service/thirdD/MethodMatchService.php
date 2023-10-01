<?php

namespace common\service\thirdD;

use common\service\BaseService;
use yii\helpers\Json;

class MethodMatchService extends BaseService
{
    # 组与组之间符号
    const ZU_SPLIT_FLAG = ';';
    /**
     * 1 直选
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZhiXuan($text='', &$codes=[], &$count=0, $match_name=''){
        // 使用正则表达式匹配直选后面的三个数字
        if (preg_match_all('/(\d{3}(?:\s+\d{3})*)/', $text, $matches)) {
            $codes = explode(' ', trim($matches[1][0]));
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        if(empty($codes)){
            throw_info('匹配直选号码为空');
        }
        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>1, 'name'=>'直选', 'codes'=>$codes, 'count'=>$count, 'matchName'=>'组选'];

        return $methodArr;
    }

    /**
     * 2、3 组选
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZuXuan($text='', &$codes=[], &$count=0, $match_name=''){
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{3}(?:\s+\d{3})*)/', $text, $matches)) {
            $codes = explode(' ', trim($matches[1][0]));
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        if(empty($codes)){
            throw_info('匹配组选号码为空');
        }
        $methodArr = [];
        $methodArr3 = [];
        $methodArr6 = [];
        foreach ($codes as $code){
            $code = trim($code);
            if(strlen($code) == 3){
                if( ($match_name=='组六' && ($code[0]==$code[1] OR $code[1]==$code[2] OR $code[0]==$code[2])) OR
                    ($match_name=='组三' && ($code[0]!=$code[1] && $code[1]!=$code[2] && $code[0]!=$code[2]))
                ){
                    throw_info($match_name.'号码输入异常，请重新确认');
                }
                if($code[0]==$code[1] OR $code[1]==$code[2] OR $code[0]==$code[2]){
                    # 组三
                    $methodArr3[] = ['id'=>2, 'name'=>'组三', 'matchName'=>$match_name, 'code'=>$code, 'count'=>1];
                }else{
                    # 组六
                    $methodArr6[] = ['id'=>3, 'name'=>'组六', 'matchName'=>$match_name, 'code'=>$code, 'count'=>1];
                }
            }else{
                throw_info($match_name.'每个号码必须是三位数');
            }
        }
        if(!empty($methodArr3)){
            $count3 = count($methodArr3);
            $codes3 = '';
            foreach ($methodArr3 as $m3){
                $codes3 .= $m3['code'].',';
            }
            $codes3 = trim($codes3, ',');
            $methodArr[] = ['id'=>2, 'name'=>'组三', 'matchName'=>$match_name, 'codes'=>$codes3, 'count'=>$count3];
        }
        if(!empty($methodArr6)){
            $count6 = count($methodArr6);
            $codes6 = '';
            foreach ($methodArr6 as $m6){
                $codes6 .= $m6['code'].',';
            }
            $codes6 = trim($codes6, self::ZU_SPLIT_FLAG);
            $methodArr[] = ['id'=>3, 'name'=>'组六', 'matchName'=>$match_name, 'codes'=>$codes6, 'count'=>$count6];
        }
        $codes = trim($codes3.','.$codes6, self::ZU_SPLIT_FLAG);
        $count = (int)$count3 + (int)$count6;

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

        if(empty($numbers) && $numbers === ''){
            throw_info('['.$matchName.']获取号码异常');
        }
        $codes = [];
        for ($i=0; $i<strlen($numbers); $i++){
            $codes[] = $numbers[$i];
        }
        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>4, 'name'=>'独胆', 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

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
        $text = str_replace(',', ' ', $text);
        if (preg_match_all('/双飞(\d{2}(?:\s*\d{2})*)/', $text, $matches)) {
            $numbers = $matches[1][0];
        }
        #p([$text, $matchName, $numbers]);

        if(empty($numbers) && $numbers === ''){
            throw_info('获取号码异常');
        }
        $codes = explode(' ', $numbers);
        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>5, 'name'=>'双飞', 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

        return $methodArr;
    }

    /**
     * 6、对子全拖
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchDuiZiQuanTuo($text='', &$codes=[], &$count=0, $matchName=''){
        $text = str_replace(',', ' ', $text);
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/对子全拖(\d{2}(?:\s*\d{2})*)/', $text, $matches)) {
            $numbers = $matches[1][0];
        }

        if(empty($numbers) && $numbers === ''){
            throw_info('获取号码异常');
        }
        $codes = explode(' ', $numbers);
        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>6, 'name'=>'对子全拖', 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

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

        if(empty($numbers) && $numbers === ''){
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
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>7, 'name'=>'一码定位', 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

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

        if(empty($numbers) && $numbers === ''){
            throw_info($matchName.'获取号码异常');
        }

        $codes = [];
        $count = 0;
        foreach ($matches[0] as $number){
            $number = str_replace('位', '', $number);
            $number = str_replace('百', ',百:', $number);
            $number = str_replace('十', ',十:', $number);
            $number = str_replace('个', ',个:', $number);
            $codes[] = trim($number, ',');

            $count += strlen($matches[1][0]) * strlen($matches[2][0]);
        }
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>8, 'name'=>'二码定位', 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];
        //p($methodArr);

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

        if(empty($numbers) && $numbers === ''){
            throw_info($matchName.'获取号码异常');
        }
        $codes = $numbers;
        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);

        $methodArr = ['id'=>9, 'name'=>$name, 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

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
        if(empty($codes) && $codes === ''){
            throw_info('匹配组六x码为空');
        }
        foreach ($codes as $code){
            if(strlen($code) != $num){
                throw_info($matchName.'号码['.$code.']数量不匹配['.$num.'!='.strlen($code).']');
            }
        }
        $name = '组六'.$t.'码';
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1, $orignMethod);
        $id = $methods[$name]['id'];

        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>$id, 'name'=>$name, 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

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

        if(empty($numbers) && $numbers === ''){
            throw_info($matchName.'获取号码异常');
        }
        $codes = $numbers;
        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);

        $methodArr = ['id'=>16, 'name'=>$name, 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

        return $methodArr;
    }

    /**
     * 17-24组三两、三、...九码
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZuSanXMa($text='', &$codes=[], &$count=0, $matchName='', $t='两'){
        $num = ThirdDTypeService::SINGLE_ASSCIATE[$t];
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{2,}(?:\s+\d{2,})*)/', $text, $matches)) {
            $codes = explode(' ', trim($matches[1][0]));
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        if(empty($codes) && $codes === ''){
            throw_info('匹配组三x码为空');
        }
        $count = count($codes);
        foreach ($codes as $code){
            if(strlen($code) != $num){
                throw_info($matchName.'号码['.$code.']数量不匹配['.$num.'!='.strlen($code).']');
            }
        }
        #p($codes);

        $name = '组三'.$t.'码';
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1, $orignMethod);
        $id = $methods[$name]['id'];
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>$id, 'name'=>$name, 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

        return $methodArr;
    }

    /**
     * 25、组三全包
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchZuSanQuanBao($text='', &$codes=[], &$count=0, $matchName=''){
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/组三全包/u', $text, $matches)) {
            $numbers = $matches[0];
            $name = '组三全包';
        }

        if(empty($numbers) && $numbers === ''){
            throw_info($matchName.'获取号码异常');
        }
        $codes = $numbers;
        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);

        $methodArr = ['id'=>25, 'name'=>$name, 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

        return $methodArr;
    }

    /**
     * 26-35跨度0-9
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchKuaDuX($text='', &$codes=[], &$count=0, $matchName=''){
        #$text = trim(str_replace(' ', '', $text));
        if (preg_match('/(\d+)元/', $text, $matches)) {
            if(!empty($matches[0])){
                $text = str_replace($matches[0], '', $text);
            }
        }
        #p([$text, $matchName, $matches]);

        $text = explode('各', trim($text))[0];
        #$text = explode(' ', trim($text))[0];
        #$text = str_replace($matchName, $matchName.' ', $text);


        preg_match('/[\p{Han}]{2}/u', $text, $matchesCn);
        $cnTextMatch = $matchesCn[0];
        $text = str_replace(' ', '', $text);
        $text = explode(' ', trim($text))[0];
        if (preg_match_all('/[跨夸]{1}度[零一二三四五六七八九]{1,10}/u', $text, $matches2)) {
            #$codes = str_replace(' ', '', trim($matches2[0][0]));
            $codes = str_replace('零', '0', $codes);
            $codes = str_replace('一', '1', $codes);
            $codes = str_replace('二', '2', $codes);
            $codes = str_replace('三', '3', $codes);
            $codes = str_replace('四', '4', $codes);
            $codes = str_replace('五', '5', $codes);
            $codes = str_replace('六', '6', $codes);
            $codes = str_replace('七', '7', $codes);
            $codes = str_replace('八', '8', $codes);
            $codes = str_replace('九', '9', $codes);

            $codes = str_replace($cnTextMatch, '', $codes);
            #p(['cnTextMatch'=>$cnTextMatch, 'matchName'=>$matchName, 'codes'=>$codes, 'text'=>$text, 'matchesCn'=>$matchesCn]);
        }
        // 使用正则表达式匹配组选后面的三个数字
        if ($codes !== '0' && empty($codes) && preg_match_all('/'.$cnTextMatch.'(\d{1,}(?:\s+\d{1,})*)/u', $text, $matches1)) {
            $codes = str_replace(' ', '', trim($matches1[1][0]));
        }
        # codes : [23]
        if(empty($codes) && $codes === ''){
            throw_info('匹配跨度号码为空');
        }
        $count = strlen($codes);
        #p([$codes, $matches1, $matches2]);

        $methodArr = [];
        for ($i=0; $i<strlen($codes); $i++){
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $code = $codes[$i];
            $name = '跨度'.$code;
            $method = $methods[$name];
            $id = $method['id'];
            $changeNameArr = array_flip(ThirdDTypeService::SINGLE_ASSCIATE); //p($changeNameArr);
            $methodArr[] = ['id'=>$id, 'name'=>$name, 'codes'=>$code, 'matchName'=>$cnTextMatch.$changeNameArr[$codes[$i]], 'count'=>1];
            #p(['codes'=>$codes[0][$i], 'methods'=>$methods]);
        }

        return $methodArr;
    }

    /**
     * 36-51:1码拖.... [组三|组六]
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchYiMaTuo($text='', &$codes=[], &$count=0, $matchName=''){
        $text = explode(' ', trim($text))[0];
        $text = trim(str_replace(' ', '', $text));
        #$text = str_replace($matchName, $matchName.' ', $text);

        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d){1}拖(\d+)/u', $text, $matches1)) {
            $codes1 = trim(str_replace(' ', '', trim($matches1[1][0])));
            $codes2 = trim(str_replace(' ', '', trim($matches1[2][0])));
        }

        $codesArr = [];
        for ($i=0; $i<strlen($codes2); $i++){
            $codesArr[] = $codes2[$i];
        }
        $codesArr = array_unique($codesArr);
        if(strlen($codes2) != count($codesArr)){
            throw_info('拖码有重复：'.$codes2);
        }

        $subMethod = '';
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/组[三六]/u', $text, $matches2)) {
            $subMethod = $matches2[0][0];
        }

        if(empty($subMethod)){
            $subMethod = '组六';
        }
        $len = strlen($codes2);
        if(empty($codes2) && $codes2 === ''){
            throw_info('匹配一码拖号码为空');
        }
        $codes = $codes1.'拖'.$codes2.'_'.$subMethod;
        $count = 1;
        $name = '1码拖'.$len.$subMethod;
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $method = $methods[$name];
        $methodArr = ['id'=>$method['id'], 'name'=>$name, 'codes'=>$codes, 'matchName'=>$name, 'count'=>$count];
        #p([$methodArr, $codes]);

        return $methodArr;
    }

    /**
     * 51-58:复式三 - 九
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchFuShi($text='', &$codes=[], &$count=0, $matchName=''){
        $text = trim(str_replace(',', ' ', $text));
        $text = trim(str_replace(';', ' ', $text));
        #$text = explode(' ', trim($text))[0];
        #$text = trim(str_replace(' ', '', $text));
        #$text = str_replace($matchName, $matchName.' ', $text);
        #p([$text, $matchName, 'ddd']);

        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(复[试式]{1}[三四五六七八九]{0,1})(\d+(?: \d+)*)/u', $text, $matches1)) {
            $matchCodes = explode(' ', trim($matches1[2][0]));
        }
        #p([$codes, $matches1, $matchCodes]);

        if(empty($matchCodes)){
            throw_info('匹配复式号码为空');
        }

        foreach ($matchCodes as $matchCode){
            $len = strlen($matchCode);
            $codesArr = [];
            for ($i=0; $i<$len; $i++){
                $codesArr[] = $matchCode[$i];
            }
            $codesArr = array_unique($codesArr);
            if(strlen($matchCode) != count($codesArr)){
                throw_info('复式有重复：'.$matchCode);
            }

            $changeNameArr = array_flip(ThirdDTypeService::SINGLE_ASSCIATE); //p($changeNameArr);
            $name = '复式'.$changeNameArr[$len];

            $count = 1;
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $method = $methods[$name];
            $methodArr[] = ['id'=>$method['id'], 'name'=>$name, 'codes'=>$matchCode, 'matchName'=>$name, 'count'=>$count];
        }
        #p([$methodArr, $codes]);

        return $methodArr;
    }

    /**
     * 59-86:和值0-27
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchHeZhi($text='', &$codes=[], &$count=0, $matchName=''){
        $text = explode(' ', trim($text))[0];
        $text = trim(str_replace(' ', '', $text));
        //p([$text, $matchName]);

        // 使用正则表达式匹配组选后面的三个数字  -- 和值范围
        if (strpos($text, ',')===false && preg_match_all('/[和合]值(\d+)(?:[-到](\d+))?/u', $text, $matches1)) {
            $num1 = $matches1[1][0];
            $num2 = $matches1[2][0];
        }

        # 指定某几个和值
        if (empty($num2) && preg_match_all('/[和合]值(\d+(?:,\d+)*)/u', $text, $matches2)) {
            $numsArr = explode(',', $matches2[1][0]);
        }
        #p($matches2);

        if($num1===''){
            throw_info('和值不能为空');
        }
        if($num2!=='' && $num2 !== NULL && $num2<=$num1){
            throw_info('和值2必须大于和值1');
        }

        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        if(!empty($numsArr)){
            foreach ($numsArr as $code){
                $name = '和值'.$code;
                $method = $methods[$name];
                $id = $method['id'];
                $methodArr[] = ['id'=>$id, 'name'=>$name, 'codes'=>$code, 'matchName'=>$name, 'count'=>1];
            }
        }elseif($num2 !== ''){
            for ($i=$num1; $i<=$num2; $i++){
                $name = '和值'.$i;
                $method = $methods[$name];
                $id = $method['id'];
                $methodArr[] = ['id'=>$id, 'name'=>$name, 'codes'=>$i, 'matchName'=>$name, 'count'=>1];
            }
        }else{
            $name = '和值'.$num1;
            $method = $methods[$name];
            $id = $method['id'];
            $methodArr[] = ['id'=>$id, 'name'=>$name, 'codes'=>$num1, 'matchName'=>$name, 'count'=>1];
        }
        #p($methodArr);

        return $methodArr;
    }

    /**
     * 59-86:和值0-27
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchHeZhiDaXiaoDanShuang($text='', &$codes=[], &$count=0, $matchName=''){
        $text = explode(' ', trim($text))[0];
        $text = trim(str_replace(' ', '', $text));
        $text = str_replace('合值', '和值', $text);
        //p([$text, $matchName]);

        // 使用正则表达式匹配组选后面的三个数字  -- 和值范围
        if (preg_match_all('/和值[大小单双]{1}?/u', $text, $matches1)) {
            $num = $matches1[0][0];
        }
        $codes = str_replace('和值', '', $num);
        $count = 1;

        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $name = $num;
        $method = $methods[$name];
        $id = $method['id'];
        $methodArr = ['id'=>$id, 'name'=>$name, 'codes'=>$codes, 'matchName'=>$name, 'count'=>$count];

        return $methodArr;
    }

    /**
     *  91 定位直选复式
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchDingWeiZhiXuanFuShi($text='', &$codes=[], &$count=0, $matchName=''){
        $text = trim(str_replace('各', ' ', $text));
        $text = str_replace('值选', '直选', $text);
        $text = str_replace('复试', '复式', $text);
        $text = trim(str_replace(' ', '', $text));
        $text = trim( $text, ',');
        #$text = str_replace($matchName, $matchName.' ', $text);
        //p([$text, $matchName]);

        // 使用正则表达式匹配组选后面的三个数字
        if (
            (strpos($text, '定位') !== false AND strpos($text, '复式') !== false AND strpos($text, '直选') !== false)
            && preg_match_all('/[百十个]{1}(\d+)/u', $text, $matches1)
        ) {
            $m0 = $matches1[0];
            $m1 = $matches1[1];
        }
        //p([$m0, $m1]);

        if(count($m0)!=3 OR count($m1)!=3){
            throw_info('号码匹配异常[百十个]');
        }
        foreach ($m1 as $k=>$code){
            $flag = \common\service\helpers\ThirdD::judgeCodesRepeat($code, $c1); # 判断号码是否有重复
            if($flag){
                throw_info($m0[$k].'号码有重复');
            }
        }
        $codes = implode(',', $m0);

        $count = 1;
        foreach ($m1 as $mCodes){
            $count *= strlen($mCodes);
        }

        $name = '定位直选复式';
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $method = $methods[$name];
        $methodArr = ['id'=>$method['id'], 'name'=>$name, 'codes'=>$codes, 'matchName'=>$name, 'count'=>$count];
        #p([$methodArr, $codes]);

        return $methodArr;
    }

    /**
     *  92 定位直选复式
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchQuanDao($text='', &$codes=[], &$count=0, $matchName=''){
        $text = trim(explode('各', trim($text))[0]);
        $text = trim( $text, ',');

        $text = str_replace('全倒', '', $text);
        //p($text);
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{3})/u', $text, $matches1) ) {
            $codesArr = $matches1[0];
        }
        $code2s = [];
        $code3s = [];
        foreach ($codesArr as $code){
            if(strlen($code)!=3){
                throw_info('号码一定要是三位['.$code.']');
            }
            $tmpCodes = [];
            for($i=0; $i<strlen($code); $i++){
                $tmpCodes[] = $code[$i];
            }
            $tmpCodes = array_unique($tmpCodes);
            if(count($tmpCodes)<3){
                $code2s[] = implode('', $tmpCodes);
            }else{
                $code3s[] = implode('', $tmpCodes);
            }
        }
        $single = 0;
        if(count($code2s)>0){
            //$code2s_str = implode(',', $code2s);
            foreach ($code2s as $code2){
                $single += 6;
            }
        }
        if(count($code3s)>0){
            //$code2s_str = implode(',', $code2s);
            foreach ($code3s as $code3){
                $single += 12;
            }
        }

        $name = '全倒';
        $count = 1; //count($codesArr);
        $codes = implode(',', $codesArr);
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $method = $methods[$name];

        $methodArr = ['id'=>$method['id'], 'name'=>$name, 'codes'=>$codes, 'single'=>$single, 'matchName'=>$name, 'count'=>$count];
        //p([$methodArr, $codes]);

        return $methodArr;
    }

    /**
     *  93 直选复式
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZhiXuanFuShi($text='', &$codes=[], &$count=0, $matchName=''){
        $text = trim(str_replace('各', ' ', $text));
        $text = str_replace('值选', '直选', $text);
        $text = str_replace('复试', '复式', $text);
        //$text = trim(str_replace(' ', '', $text));
        $text = trim( $text, ',');
        #$text = str_replace($matchName, $matchName.' ', $text);

        $pattern = '/[四五六七八九]码/u';
        if (preg_match($pattern, $text, $matches)) {
            $numCn = $matches[0];
        }
        #p([$text, $matchName, $numCn]);
        $numCnTxt = str_replace($numCn, '', $numCn);
        $num = ThirdDTypeService::SINGLE_ASSCIATE[$numCnTxt];

        if (preg_match_all('/(\d{4,})/', $text, $matches1) ) {
            $codesArr = $matches1[0];
        }
        #p([$matches1, $codesArr, $num], 0);
        $all_counts = 0;
        foreach ($codesArr as $codeData){
            $flag = \common\service\helpers\ThirdD::judgeCodesRepeat($codeData);
            if($flag){
                throw_info($codeData.'号码有重复');
            }
            $count = 1;
            for ($i=0; $i<3; $i++){
                $count *= (strlen($codeData)-$i);
            }
            $all_counts += $count;
        }
        $count = $all_counts;
        #p([$count, $all_counts]);

        $name = '直选复式';
        #$codes = $numCn .':'.implode(',', $codesArr);
        $codes = implode(self::ZU_SPLIT_FLAG, $codesArr);
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $method = $methods[$name];
        $methodArr = ['id'=>$method['id'], 'name'=>$name, 'codes'=>$codes, 'single'=>$all_counts, 'matchName'=>$name, 'count'=>$all_counts];
        //p([$methodArr, $codes]);

        return $methodArr;
    }
}
