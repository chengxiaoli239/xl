<?php

namespace common\service\thirdD;

use common\service\BaseService;
use common\service\CommonService;
use yii\helpers\Json;

class MethodMatchService extends CommonBaseService
{

    const CODE_TYPE_ZU_SAN = 1; # 组三
    const CODE_TYPE_ZU_LIU = 2; # 组六
    const CODE_TYPE_BAO_ZI = 3; # 豹子

    const METHOD_ID_ZHIXUAN = 1;
    const METHOD_ID_ZUSAN = 2;
    const METHOD_ID_ZULIU = 3;
    const METHOD_ID_DUDAN = 4;
    const METHOD_ID_SHUANGFEN = 5;
    const METHOD_ID_QUANTUO = 6; # 对子全拖、双飞
    const METHOD_ID_YIMADING = 7;
    const METHOD_ID_ERMADING = 8;
    const METHOD_ID_BAOZI_QB = 9; # 豹子全包

    # 组六x码
    const METHOD_ID_ZL_4_MA = 10;
    const METHOD_ID_ZL_5_MA = 11;
    const METHOD_ID_ZL_6_MA = 12;
    const METHOD_ID_ZL_7_MA = 13;
    const METHOD_ID_ZL_8_MA = 14;
    const METHOD_ID_ZL_9_MA = 15;
    const METHOD_ID_ZL_QB = 16;
    # 组六玩法ID集合
    const METHOD_ID_ZL = [
        self::METHOD_ID_ZL_4_MA,
        self::METHOD_ID_ZL_5_MA,
        self::METHOD_ID_ZL_6_MA,
        self::METHOD_ID_ZL_7_MA,
        self::METHOD_ID_ZL_8_MA,
        self::METHOD_ID_ZL_9_MA,
        self::METHOD_ID_ZL_QB,
    ];

    # 组三x码
    const METHOD_ID_ZS_2_MA = 17;
    const METHOD_ID_ZS_3_MA = 18;
    const METHOD_ID_ZS_4_MA = 19;
    const METHOD_ID_ZS_5_MA = 20;
    const METHOD_ID_ZS_6_MA = 21;
    const METHOD_ID_ZS_7_MA = 22;
    const METHOD_ID_ZS_8_MA = 23;
    const METHOD_ID_ZS_9_MA = 24;
    const METHOD_ID_ZS_QB = 25;
    # 组三玩法ID集合
    const METHOD_ID_ZS = [
        self::METHOD_ID_ZS_2_MA,
        self::METHOD_ID_ZS_3_MA,
        self::METHOD_ID_ZS_4_MA,
        self::METHOD_ID_ZS_5_MA,
        self::METHOD_ID_ZS_6_MA,
        self::METHOD_ID_ZS_7_MA,
        self::METHOD_ID_ZS_8_MA,
        self::METHOD_ID_ZS_9_MA,
        self::METHOD_ID_ZS_QB,
    ];

    # 跨度0-9
    const METHOD_ID_KD_0 = 26;
    const METHOD_ID_KD_1 = 27;
    const METHOD_ID_KD_2 = 28;
    const METHOD_ID_KD_3 = 29;
    const METHOD_ID_KD_4 = 30;
    const METHOD_ID_KD_5 = 31;
    const METHOD_ID_KD_6 = 32;
    const METHOD_ID_KD_7 = 33;
    const METHOD_ID_KD_8 = 34;
    const METHOD_ID_KD_9 = 35;

    # 1码拖x - 组六
    const METHOD_ID_YMT_ZL_2 = 36;
    const METHOD_ID_YMT_ZL_3 = 37;
    const METHOD_ID_YMT_ZL_4 = 38;
    const METHOD_ID_YMT_ZL_5 = 39;
    const METHOD_ID_YMT_ZL_6 = 40;
    const METHOD_ID_YMT_ZL_7 = 41;
    const METHOD_ID_YMT_ZL_8 = 42;
    const METHOD_ID_YMT_ZL_9 = 43;

    # 1码拖x - 组三
    const METHOD_ID_YMT_ZS_2 = 44;
    const METHOD_ID_YMT_ZS_3 = 45;
    const METHOD_ID_YMT_ZS_4 = 46;
    const METHOD_ID_YMT_ZS_5 = 47;
    const METHOD_ID_YMT_ZS_6 = 48;
    const METHOD_ID_YMT_ZS_7 = 49;
    const METHOD_ID_YMT_ZS_8 = 50;
    const METHOD_ID_YMT_ZS_9 = 51;

    # 复式x码
    const METHOD_ID_FS_3 = 52;
    const METHOD_ID_FS_4 = 53;
    const METHOD_ID_FS_5 = 54;
    const METHOD_ID_FS_6 = 55;
    const METHOD_ID_FS_7 = 56;
    const METHOD_ID_FS_8 = 57;
    const METHOD_ID_FS_9 = 58;

    # 和值
    const METHOD_ID_HZ_0 = 59;
    const METHOD_ID_HZ_1 = 60;
    const METHOD_ID_HZ_2 = 61;
    const METHOD_ID_HZ_3 = 62;
    const METHOD_ID_HZ_4 = 63;
    const METHOD_ID_HZ_5 = 64;
    const METHOD_ID_HZ_6 = 65;
    const METHOD_ID_HZ_7 = 66;
    const METHOD_ID_HZ_8 = 67;
    const METHOD_ID_HZ_9 = 68;
    const METHOD_ID_HZ_10 = 69;
    const METHOD_ID_HZ_11 = 70;
    const METHOD_ID_HZ_12 = 71;
    const METHOD_ID_HZ_13 = 72;
    const METHOD_ID_HZ_14 = 73;
    const METHOD_ID_HZ_15 = 74;
    const METHOD_ID_HZ_16 = 75;
    const METHOD_ID_HZ_17 = 76;
    const METHOD_ID_HZ_18 = 77;
    const METHOD_ID_HZ_19 = 78;
    const METHOD_ID_HZ_20 = 79;
    const METHOD_ID_HZ_21 = 80;
    const METHOD_ID_HZ_22 = 81;
    const METHOD_ID_HZ_23 = 82;
    const METHOD_ID_HZ_24 = 83;
    const METHOD_ID_HZ_25 = 84;
    const METHOD_ID_HZ_26 = 85;
    const METHOD_ID_HZ_27 = 86;

    # 和值大小单双
    const METHOD_ID_HZ_DA = 87;
    const METHOD_ID_HZ_XIAO = 88;
    const METHOD_ID_HZ_DAN = 89;
    const METHOD_ID_HZ_SHUANG = 90;

    # 其它
    const METHOD_ID_DW_ZX_FS = 91; # 定位直选复式
    const METHOD_ID_QD = 92; # 全倒
    const METHOD_ID_ZX_FS = 93; # 直选复式

    const ZU_SPLIT_FLAG = ';'; # 组与组之间符号
    const CODE_SPLIT_FLAG = ','; # 组内号码之间符号
    const METHOD_SPLIT_FLAG = '|'; # 玩法之间符号

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
        $text = explode('元', $text)[0];
        $text = explode('倍', $text)[0];
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{2,}(?:\s+\d{2,})*)/', $text, $matches)) {
            $codes = explode(' ', trim($matches[1][0]));
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        #p([$text, $matches, $match_name, $codes]);
        if(empty($codes)){
            throw_info('匹配组选号码为空');
        }
        $methodArr = [
            'methodArr3' => [],
            'methodArr6' => [],
            'methodArr32' => [],
            'methodArr33' => [],
            'methodArr34' => [],
            'methodArr35' => [],
            'methodArr36' => [],
            'methodArr37' => [],
            'methodArr38' => [],
            'methodArr39' => [],
            'methodArr64' => [],
            'methodArr65' => [],
            'methodArr66' => [],
            'methodArr67' => [],
            'methodArr68' => [],
            'methodArr69' => [],
        ];
        /**
        {
            "type_3": 0,
            "textx": "744 991 988 244 882 245组六各20",
            "text1": "福组269一倍共10 - 组六",
            "text2": "福组六269一倍共10 - 组六",
            "text3": "福组选269一倍共10 - 组六",
            "text4": "福组选2679一倍共10 - 组六四码",
            "text5": "福组三26一倍共10 - 组三两码",
            "text6": "福组三266一倍共10 - 组三",
            "text7": "福组选266一倍共10 - 组三",
            "text8": "福组三269一倍共10 - 组三三码",
            "text9": "福组三2679一倍共10 - 组三四码"
        }
         */
        foreach ($codes as $code){
            $code = trim($code);
            $reSortCode = CommonService::reSortCodes([$code])[0]; # 排序数据内的号码
            $flag = \common\service\helpers\ThirdD::judgeCodesRepeat($code, $sortCode); # 判断号码是否有重复

            $len = strlen($code);
            if($len == 2) {
                if($match_name != '组三'){
                    throw_info('玩法不确定：'.$code);
                }
                $methodArr['methodArr32'][] = ['id'=>17, 'name'=>'组三两码', 'matchName'=>'组三两码', 'code'=>$code, 'count'=>1]; # 组三两码
            }elseif($len == 3){
                # 三个码的情况分：组三、组六、组三三码，其中组三三码还是组三根据号码是否有重复来定，有重复则为单纯的组三，没重复则为组三三码(多吗组三必须备注组三)
                if( ($match_name=='组六' && $flag)
                    #OR ($match_name=='组三' && ($code[0]!=$code[1] && $code[1]!=$code[2] && $code[0]!=$code[2]))
                ){
                    throw_info($match_name.'号码号码重复:'.$code.'，请重新确认');
                }
                #sort($sortCode); # 先排序号码再入库
                if( (strpos($text, '组') !== false OR strpos($text, '组选')!==false OR strpos($text, '组六') !== false) && !$flag ){
                    # 组六
                    $methodArr['methodArr6'][] = ['id'=>3, 'name'=>'组六', 'matchName'=>$match_name, 'code'=>$reSortCode, 'count'=>1];
                }elseif((strpos($text, '组') !== false OR strpos($text, '组选')!==false OR strpos($text, '组三') !== false) && $flag){
                    # 组三
                    $methodArr['methodArr3'][] = ['id'=>2, 'name'=>'组三', 'matchName'=>$match_name, 'code'=>$reSortCode, 'count'=>1];
                }elseif((strpos($text, '组') !== false OR strpos($text, '组选')!==false OR strpos($text, '组三') !== false) && !$flag){
                    # 组三三码
                    $methodArr['methodArr33'][] = ['id'=>17, 'name'=>'组三三码', 'matchName'=>'组三三码', 'code'=>$reSortCode, 'count'=>1];
                }

            }elseif($len>3) {
                if($flag){
                    throw_info('组选多码号码不能重复');
                }
                if(strpos($text, '组三') !==false && strpos($text, '组六') !==false){
                    throw_info('组选多码号码必须备注组三或组六');
                }
                $changeNameArr = array_flip(ThirdDTypeService::SINGLE_ASSCIATE); //p($changeNameArr);
                $cnNum = $changeNameArr[$len];
                if(strpos($text, '组三') !==false){
                    # 组三多码
                    switch ($len){
                        case 3:
                            $methodArr['methodArr33'][] = ['id'=>17, 'name'=>'组三'.$cnNum.'码', 'matchName'=>'组三'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 4:
                            $methodArr['methodArr34'][] = ['id'=>18, 'name'=>'组三'.$cnNum.'码', 'matchName'=>'组三'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 5:
                            $methodArr['methodArr35'][] = ['id'=>19, 'name'=>'组三'.$cnNum.'码', 'matchName'=>'组三'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 6:
                            $methodArr['methodArr36'][] = ['id'=>20, 'name'=>'组三'.$cnNum.'码', 'matchName'=>'组三'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 7:
                            $methodArr['methodArr37'][] = ['id'=>21, 'name'=>'组三'.$cnNum.'码', 'matchName'=>'组三'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 8:
                            $methodArr['methodArr38'][] = ['id'=>22, 'name'=>'组三'.$cnNum.'码', 'matchName'=>'组三'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 9:
                            $methodArr['methodArr39'][] = ['id'=>23, 'name'=>'组三'.$cnNum.'码', 'matchName'=>'组三'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                    }
                }else{
                    # 组六多码
                    switch ($len){
                        case 4:
                            $methodArr['methodArr64'][] = ['id'=>10, 'name'=>'组六'.$cnNum.'码', 'matchName'=>'组六'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 5:
                            $methodArr['methodArr65'][] = ['id'=>11, 'name'=>'组六'.$cnNum.'码', 'matchName'=>'组六'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 6:
                            $methodArr['methodArr66'][] = ['id'=>12, 'name'=>'组六'.$cnNum.'码', 'matchName'=>'组六'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 7:
                            $methodArr['methodArr67'][] = ['id'=>13, 'name'=>'组六'.$cnNum.'码', 'matchName'=>'组六'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 8:
                            $methodArr['methodArr68'][] = ['id'=>14, 'name'=>'组六'.$cnNum.'码', 'matchName'=>'组六'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                        case 9:
                            $methodArr['methodArr69'][] = ['id'=>15, 'name'=>'组六'.$cnNum.'码', 'matchName'=>'组六'.$cnNum.'码', 'code'=>$reSortCode, 'count'=>1];
                            break;
                    }
                }
            }else{
                throw_info($match_name.'组选匹配异常');
            }
        }
        $allCount = 0;
        $codes = '';
        foreach ($methodArr as $key=>$items){
            if(empty($items)){
                unset($methodArr[$key]);
                continue;
            }
            $countNum = count($methodArr[$key]);
            $allCount += $countNum;
            $codesTmp = '';
            foreach ($methodArr[$key] as $m){
                $codesTmp .= $m['code'] . self::ZU_SPLIT_FLAG;
            }
            $codesTmp = trim($codesTmp, self::ZU_SPLIT_FLAG);
            $codes .= self::ZU_SPLIT_FLAG.$codesTmp;
            $methodArr[$key] = ['id'=>$m['id'], 'name'=>$m['name'], 'matchName'=>$m['matchName'], 'codes'=>$codesTmp, 'count'=>$countNum];
        }
        $methodArr = array_values($methodArr);
        $count = $allCount;
        $codes = trim($codes, self::ZU_SPLIT_FLAG);
        #p($methodArr);
        if(count($methodArr)==1){
            $methodArr = $methodArr[0];
        }

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
        $text = str_replace(',', ' ', trim($text));
        if (strpos($text, '双飞') !==false && preg_match_all('/(\d{2}(?:\s*\d{2})*)/', $text, $matches)) {
            $numbers = $matches[1][0];
        }
        #p([$text, $matchName, $numbers]);

        if(empty($numbers) && $numbers === ''){
            throw_info('获取号码异常');
        }
        $codes = explode(' ', $numbers);
        $count = count($codes);

        $codes = CommonService::reSortCodes($codes); # 排序数据内的号码
        $methodArr1 = []; # 无对子
        $methodArr2 = []; # 对子
        foreach ($codes as $code){
            $code = (string)$code;
            if($code[0] == $code[1]){
                $methodArr2[] = ['id'=>6, 'name'=>'对子全拖', 'code'=>$code, 'matchName'=>$matchName, 'count'=>$count];
            }else{
                $methodArr1[] = ['id'=>5, 'name'=>'双飞', 'code'=>$code, 'matchName'=>$matchName, 'count'=>$count];
            }
        }
        if(!empty($methodArr2)){
            $count2 = count($methodArr2);
            $codes2 = '';
            foreach ($methodArr2 as $m2){
                $codes2 .= $m2['code'] . self::ZU_SPLIT_FLAG;
            }
            $codes2 = trim($codes2, self::ZU_SPLIT_FLAG);
            $methodArr[] = ['id'=>6, 'name'=>'对子全拖', 'codes'=>$codes2, 'matchName'=>$matchName, 'count'=>$count2];
        }
        if(!empty($methodArr1)){
            $count1 = count($methodArr1);
            $codes1 = '';
            foreach ($methodArr1 as $m1){
                $codes1 .= $m1['code'] . self::ZU_SPLIT_FLAG;
            }
            $codes1 = trim($codes1, self::ZU_SPLIT_FLAG);
            $methodArr[] = ['id'=>5, 'name'=>'双飞', 'codes'=>$codes1, 'matchName'=>$matchName, 'count'=>$count1];
        }
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);

        #$methodArr = ['id'=>5, 'name'=>'双飞', 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

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
        #p([$text, $matchName, $matchName], 0);

        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{3,})/u', $text, $matches1)) {
            $matchCodes = explode(' ', trim($matches1[1][0]));
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
        $text = trim(str_replace(' ', ',', $text));
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
     *  92 全倒
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
        if (preg_match_all('/(\d{3,})/u', $text, $matches1) ) {
            $codesArr = $matches1[0];
        }
        #p($codesArr);
        $code2s = [];
        $code3s = [];
        $codeFuShis = [];
        foreach ($codesArr as $code){
            if(strlen($code)!=3){
                #throw_info('号码一定要是三位['.$code.']');
            }
            $tmpCodes = [];
            for($i=0; $i<strlen($code); $i++){
                $tmpCodes[] = $code[$i];
            }
            $tmpCodes = array_unique($tmpCodes);
            $codeLen = count($tmpCodes);
            if($codeLen<3){
                $code2s[] = implode('', $tmpCodes);
            }elseif($codeLen==3){
                $code3s[] = implode('', $tmpCodes);
            }elseif($codeLen>3){
                if($codeLen<strlen($code)){
                    throw_info('复式直选号码不能重复');
                }
                $codeFuShis[] = implode('', $tmpCodes);
            }
        }
        $count = 0;
        if(count($code2s)>0){
            foreach ($code2s as $code2){
                $count += 6;
            }
        }
        if(count($code3s)>0){
            foreach ($code3s as $code3){
                $count += 12;
            }
        }
        if(count($codeFuShis)>0){
            foreach ($codeFuShis as $codeFuShi){
                $fsCount = 1;
                for ($i=0; $i<3; $i++){
                    $fsCount *= (strlen($codeFuShi)-$i);
                }
                $count += $fsCount;
            }
        }

        $name = '全倒';
        $codes = implode(',', $codesArr);
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $method = $methods[$name];

        $methodArr = ['id'=>$method['id'], 'name'=>$name, 'codes'=>$codes, 'matchName'=>$name, 'count'=>$count];
        #p([$methodArr, $codes]);

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
