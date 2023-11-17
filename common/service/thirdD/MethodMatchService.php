<?php

namespace common\service\thirdD;

use common\service\BaseService;
use common\service\CommonService;
use common\service\helpers\ThirdD;
use yii\helpers\Json;

class MethodMatchService extends CommonBaseService
{
    const CN_SINGLE_TEXT = '一二两三四五六七八九十百千万';
    const CODE_TYPE_ZU_SAN = 1; # 组三
    const CODE_TYPE_ZU_LIU = 2; # 组六
    const CODE_TYPE_BAO_ZI = 3; # 豹子

    # 赔率类型表的id
    const METHOD_ID_ZHIXUAN = 1;
    const METHOD_ID_ZUSAN = 2;
    const METHOD_ID_ZULIU = 3;
    const METHOD_ID_DUDAN = 4;
    const METHOD_ID_SHUANGFEI = 5; # 双飞
    const METHOD_ID_QUANTUO = 6; # 对子全拖
    const METHOD_ID_DUIZI_QB = 94; # 对子全包
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
    const METHOD_SPLIT_FLAG = '#'; # 玩法、或规则之间符号（井号#或句号。）

    /**
     * 为了避免干扰，匹配前倍数字符用空字符串先替换
     * @param string $text
     * @return array
     */
    public static function replaceSingleText(string &$text=''): array
    {
        $originText = $text;
        if (preg_match_all('/(\d+)元/', $text, $matches1)) {
            $singleCnTxt = $matches1[0][0];
            $text = str_replace($singleCnTxt, '', $text);
        }
        if (preg_match_all('/共(\d+)/', $text, $matches2)) {
            $singleCnTxt = $matches2[0][0];
            $text = str_replace($singleCnTxt, '', $text);
        }
        if (preg_match_all('/各(\d+)/', $text, $matches3)) {
            $singleCnTxt = $matches3[0][0];
            $text = str_replace($singleCnTxt, '', $text);
        }
        if (preg_match_all('/(\d+)块/', $text, $matches4)) {
            $singleCnTxt = $matches4[0][0];
            $text = str_replace($singleCnTxt, '', $text);
        }
        if (preg_match_all('/(['.MethodMatchService::CN_SINGLE_TEXT.'0-9]{1,3})倍/', $text, $matches5)) {
            $singleCnTxt = $matches5[0][0];
            $text = str_replace($singleCnTxt, '', $text);
        }
        //p($text);

        return [$originText, $singleCnTxt];
    }

    /**
     * 1 直选、2/3组选
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZhiZu($text='', &$codes=[], &$count=0, $match_name=''){
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
     * 直：1
     * 组、组选：2、3
     * 组三多码：10-15组六四、五...九码、
     * 组六多码：17-24组三两、三、...九码
     * @param string $text
     * @param array $codes
     * @param array $singleArr Array ( [组六] => 4倍元 [组三] => 20倍/元 )
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchZhiZuOrZuSanOrZuLiuXMa(string $text='', array &$codes=[], &$count=0, array $singleArr=[]): array
    {
        #$text = explode('元', $text)[0];
        #$text = explode('倍', $text)[0];
        // 使用正则表达式匹配组选后面的三个数字
        $matcheCodeText = $text;
        list($originText, $singleCnTxt) = MethodMatchService::replaceSingleText($matcheCodeText); # 匹配号码前倍数字符先替换为空
        #if (preg_match_all('/(\d{2,}(?:\s+\d{2,})*)/', $text, $matches)) {
        if (preg_match_all('/(\d{2,})+/', $matcheCodeText, $matches)) {
            #$codes = explode(' ', trim($matches[1][0]));
            $codes = $matches[1]; # 多组号码，每组一个个元素
        } else {
            throw_info('组选未匹配到号码,text:'.$text);
        }
        //p([$text, $matches, $codes, $singleArr]);
        if(empty($codes)){
            throw_info('匹配组选号码为空');
        }
        $methodArr = [
            'methodArrZhi' => [],
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
        ###  第一步、玩法的匹配
        # 玩法类型组合：组三、组六、组三&组六、直&组（一直一组，二直三组，直组）、组选、组、直||直选
        foreach ($codes as $code){
            $code = trim($code);
            $len = strlen($code);
            $reSortCode = CommonService::reSortCodes([$code])[0]; # 排序数据后的号码
            $flag = \common\service\helpers\ThirdD::judgeCodesRepeat($code, $sortCode); # 判断号码是否有重复，重复则为组三
            switch (true){
                case ((strpos($text, '直') !== false OR strpos($text, '单') !== false) && strpos($text, '组') !== false):
                    if($len != 3) {
                        #throw_info('直选或组选号码必须三个：'.$code, self::CODE_FOR_USER);
                        break;
                    }
                    if(preg_match_all('/[直|组]/u', $text, $matcheTypes)){ # 匹配直组顺序：Array ( [0] => 直 [1] => 组 )
                        $mTypes = $matcheTypes; # Array ( [0] => 直 [1] => 组 )
                        if(
                            preg_match_all('/([一二两三四五六七八九])\s*直([一二三四五六七八九])组/u', $text, $matches) OR
                            preg_match_all('/([一二两三四五六七八九])\s*组([一二三四五六七八九])直/u', $text, $matches) OR
                            preg_match_all('/直([一两二三四五六七八九])\s*组([一二三四五六七八九])/u', $text, $matches) OR
                            preg_match_all('/组([一两二三四五六七八九])\s*直([一二三四五六七八九])/u', $text, $matches) OR
                            preg_match_all('/直(?:选)?([一两二三四五六七八九0-9])?倍\s*(?:组|组选|组六|组三)?([一两二三四五六七八九0-9])?倍/u', $text, $matches) OR
                            preg_match_all('/(?:组|组选|组六|组三)?([一两二三四五六七八九0-9])?倍\s*直(?:选)([一两二三四五六七八九0-9])?倍/u', $text, $matches) OR

                            preg_match_all('/(\d+)倍直\s*(\d+)倍组/u', $text, $matches) OR
                            preg_match_all('/(\d+)倍组\s*(\d+)倍直/u', $text, $matches) OR
                            preg_match_all('/直(\d+)倍\s*组(\d+)倍/u', $text, $matches) OR
                            preg_match_all('/组(\d+)倍\s*直(\d+)倍/u', $text, $matches)
                        ){
                            $singleD = [];
                            foreach ($mTypes as $key=>$mType){
                                $singleD[$key] = [
                                    $mType[0]=>is_numeric($matches[1][0])?(int)$matches[1][0] : ThirdD::cn2num($matches[1][0]) * 2, # 直、组都是两元一倍，所以这里 *2
                                    $mType[1]=>is_numeric($matches[2][0])?(int)$matches[2][0] : ThirdD::cn2num($matches[2][0]) * 2, # 直、组都是两元一倍，所以这里 *2
                                ];
                            }
                            #p([$matcheTypes, $text, $matches, $singleD], 0);
                        }
                    }

                    $zhi = ['id'=>self::METHOD_ID_ZHIXUAN, 'name'=>'直选', 'code'=>$code, 'count'=>1]; # 直选
                    if(!empty($singleD[0]['直'])){
                        $zhi['single'] = $singleD[0]['直'];
                    }
                    if(!empty($singleArr['直'])){
                        $zhi['single'] = $singleArr['直'];
                    }
                    $methodArr['methodArrZhi'][] = $zhi;
                    if(!$flag){
                        # 组六
                        $zuliu = ['id'=>self::METHOD_ID_ZULIU, 'name'=>'组六', 'code'=>$reSortCode, 'count'=>1];
                        if(!empty($singleD[0]['组'])){
                            $zuliu['single'] = $singleD[0]['组'];
                        }
                        if(!empty($singleArr['组六'])){
                            $zuliu['single'] = $singleArr['组六'];
                        }
                        if(!empty($singleArr['组'])){
                            $zuliu['single'] = $singleArr['组'];
                        }
                        $methodArr['methodArr6'][] = $zuliu;
                    }else{
                        # 组三
                        $zusan = ['id'=>self::METHOD_ID_ZUSAN, 'name'=>'组三', 'code'=>$reSortCode, 'count'=>1];
                        if(!empty($singleD[0]['组'])){
                            $zusan['single'] = $singleD[0]['组'];
                        }
                        if(!empty($singleArr['组三'])){
                            $zusan['single'] = $singleArr['组三'];
                        }
                        if(!empty($singleArr['组'])){
                            $zusan['single'] = $singleArr['组'];
                        }
                        $methodArr['methodArr3'][] = $zusan;
                    }
                    break;
                case (strpos($text, '组三') !== false && strpos($text, '组六') !== false):
                    if($len<=2){
                        //throw_info('组六号码至少为三个：'.$code);
                        break;
                    }
                    if($len==3){
                        if($flag){
                            break;
                            //throw_info('组六号码不能有重复：'.$code);
                        }

                        # 组六
                        $zuliu = ['id' => self::METHOD_ID_ZULIU, 'name' => '组六', 'code' => $reSortCode, 'count' => 1];
                        if(!empty($singleArr)){
                            $zuliu['single'] = $singleArr['组六'];
                        }
                        $methodArr['methodArr6'][] = $zuliu;

                        # 组三三码
                        $zusan = ['id' => self::METHOD_ID_ZS_3_MA, 'name' => '组三三码', 'code' => $reSortCode, 'count' => 1];
                        if(!empty($singleArr)){
                            $zusan['single'] = $singleArr['组三'];
                        }
                        $methodArr['methodArr3'][] = $zusan;
                    }else{
                        # len>3
                        if($flag){
                            break;
                            //throw_info('组三组六多码号码不能有重复：'.$code);
                        }
                        MethodMatchService::getMethodArrDatas($reSortCode, '组三', $methodArr, $singleArr);
                        MethodMatchService::getMethodArrDatas($reSortCode, '组六', $methodArr, $singleArr);
                    }
                    break;
                case strpos($text, '组三') !== false:
                    if($flag){
                        if($len==3){ # 常规的组三
                            $methodArr['methodArr3'][] = ['id'=>self::METHOD_ID_ZUSAN, 'name'=>'组三', 'code'=>$reSortCode, 'count'=>1];
                        }else{
                            break;
                            //throw_info('组三号码不能重复：'.$code);
                        }
                    }else{
                        # 组三多码
                        MethodMatchService::getMethodArrDatas($reSortCode, '组三', $methodArr);
                    }
                    break;
                case strpos($text, '组六') !== false:
                    if($len==2){
                        #throw_info('组六号码至少3个号码：'.$code);
                        break;
                    }
                    if($flag){
                        break;
                        //throw_info('组六号码不能重复：'.$code.'_'.$text);
                    }
                    if($len==3){ # 常规的组六
                        $methodArr['methodArr6'][] = ['id'=>self::METHOD_ID_ZULIU, 'name'=>'组六', 'code'=>$reSortCode, 'count'=>1];
                    }else{
                        # 组六多码
                        MethodMatchService::getMethodArrDatas($reSortCode, '组六', $methodArr);
                    }
                    break;
                case strpos($text, '组') !== false:
                #case strpos($text, '组选') !== false:
                    # 组三或组六 根据号码类型决定
                    if($len<3){
                        #throw_info('组选号码至少3个号码：'.$code);
                        break;
                    }
                    if($len==3){
                        if($flag){
                            # 组三
                            $methodArr['methodArr3'][] = ['id'=>self::METHOD_ID_ZUSAN, 'name'=>'组三', 'code'=>$reSortCode, 'count'=>1];
                        }else{
                            # 组六
                            $methodArr['methodArr6'][] = ['id'=>self::METHOD_ID_ZULIU, 'name'=>'组六', 'code'=>$reSortCode, 'count'=>1];
                        }
                    }else{
                        if($flag){
                            throw_info('多码情况号码不允许重复：'.$code);
                        }
                        # 不备注组六组三默认为：组六多码
                        MethodMatchService::getMethodArrDatas($reSortCode, '组六', $methodArr);
                    }
                    break;
                case strpos($text, '直') !== false OR strpos($text, '单选') !== false:
                    if($len != 3) {
                        break;
                    }
                    $zhi = ['id'=>self::METHOD_ID_ZHIXUAN, 'name'=>'直选', 'code'=>$code];
                    if(preg_match_all('/(['.MethodMatchService::CN_SINGLE_TEXT.'0-9]{1,3})\s*直/u', $text, $matches)){
                        $zhi['single'] = is_numeric($matches[1]) ? :ThirdD::cn2num($matches[1][0]) * 2; # 中文转数字，一=>1、二=>2.。。。
                    }
                    $methodArr['methodArrZhi'][] = $zhi;
                    break;
                default:
                    throw_info('玩法匹配异常...');
            }
        }
        /**
            {
                "type_3":0,
                "text":"福组三组六2345 234 4567 35790各10元",
                "text0":"福组三2345 234 4567 35790各10元",
                "text1":"福组六2345 234 4567 35790各10元",
                "text2":"福组三组六2345 234 4567 35790各10元",
                "text3":"福组选2345 234 456 357各10元",
                "text4":"福一直一组345 234 456 357各10元"
            }
         */

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
                $single_s = ''; # 类似一直一组情况，在本方法匹配倍数，外面判断优先用此处倍数
                $codesTmp .= $m['code'] . self::ZU_SPLIT_FLAG;
                if(!empty($m['single'])){
                    $single_s = $m['single'];
                }
            }
            $codesTmp = trim($codesTmp, self::ZU_SPLIT_FLAG);
            $codes .= self::ZU_SPLIT_FLAG.$codesTmp;
            $methodArrD = ['id'=>$m['id'], 'name'=>$m['name'], 'codes'=>$codesTmp, 'count'=>$countNum];
            if(!empty($single_s)){
                $methodArrD['single'] = $single_s;
            }
            $methodArr[$key] = $methodArrD;
        }
        $methodArr = array_values($methodArr);
        $count = $allCount;
        $codes = trim($codes, self::ZU_SPLIT_FLAG);
        //p($methodArr);
        if(count($methodArr)==1){
            $methodArr = $methodArr[0];
        }

        return $methodArr;
    }

    /**
     * 组三组六多码数据获取
     * @param string $code 号码必须去重拦截之后才调用此方法
     * @param $name
     * @param $methodArr
     * @return mixed
     */
    public static function getMethodArrDatas($code, $name, &$methodArr, $singleArr=[]){
        $len = strlen($code);
        $changeNameArr = array_flip(ThirdDTypeService::SINGLE_ASSCIATE); //p($changeNameArr);
        $cnNum = $changeNameArr[$len];
        if(strpos($name, '组三') !==false){
            # 组三多码
            switch ($len){
                case 2:
                    $methodArr32 = ['id'=>17, 'name'=>'组三两码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组三'])) && ($methodArr32['single'] = $singleArr['组三']);
                    $methodArr['methodArr32'][] = $methodArr32;
                    break;
                case 3:
                    $methodArr33 = ['id'=>18, 'name'=>'组三'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组三'])) && ($methodArr33['single'] = $singleArr['组三']);
                    $methodArr['methodArr33'][] = $methodArr33;
                    break;
                case 4:
                    $methodArr34 = ['id'=>19, 'name'=>'组三'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组三'])) && ($methodArr34['single'] = $singleArr['组三']);
                    $methodArr['methodArr34'][] = $methodArr34;
                    break;
                case 5:
                    $methodArr35 = ['id'=>20, 'name'=>'组三'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组三'])) && ($methodArr35['single'] = $singleArr['组三']);
                    $methodArr['methodArr35'][] = $methodArr35;
                    break;
                case 6:
                    $methodArr36 = ['id'=>21, 'name'=>'组三'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组三'])) && ($methodArr36['single'] = $singleArr['组三']);
                    $methodArr['methodArr36'][] = $methodArr36;
                    break;
                case 7:
                    $methodArr37 = ['id'=>22, 'name'=>'组三'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组三'])) && ($methodArr37['single'] = $singleArr['组三']);
                    $methodArr['methodArr37'][] = $methodArr37;
                    break;
                case 8:
                    $methodArr38 = ['id'=>23, 'name'=>'组三'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组三'])) && ($methodArr38['single'] = $singleArr['组三']);
                    $methodArr['methodArr38'][] = $methodArr38;
                    break;
                case 9:
                    $methodArr39 = ['id'=>24, 'name'=>'组三'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组三'])) && ($methodArr39['single'] = $singleArr['组三']);
                    $methodArr['methodArr39'][] = $methodArr39;
                    break;
            }
        }else{
            #p(['singleArr'=>$singleArr]);
            (!empty($singleArr) && isset($singleArr['组三'])) && ($methodArr32['single'] = $singleArr['组三']);
            # 组六多码
            switch ($len){
                case 4:
                    $methodArr64 = ['id'=>10, 'name'=>'组六'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组六'])) && ($methodArr64['single'] = $singleArr['组六']);
                    $methodArr['methodArr64'][] = $methodArr64;
                    break;
                case 5:
                    $methodArr65 = ['id'=>11, 'name'=>'组六'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组六'])) && ($methodArr65['single'] = $singleArr['组六']);
                    $methodArr['methodArr65'][] = $methodArr65;
                    break;
                case 6:
                    $methodArr66 = ['id'=>12, 'name'=>'组六'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组六'])) && ($methodArr66['single'] = $singleArr['组六']);
                    $methodArr['methodArr66'][] = $methodArr66;
                    break;
                case 7:
                    $methodArr67 = ['id'=>13, 'name'=>'组六'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组六'])) && ($methodArr67['single'] = $singleArr['组六']);
                    $methodArr['methodArr67'][] = $methodArr67;
                    break;
                case 8:
                    $methodArr68 = ['id'=>14, 'name'=>'组六'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组六'])) && ($methodArr68['single'] = $singleArr['组六']);
                    $methodArr['methodArr68'][] = $methodArr68;
                    break;
                case 9:
                    $methodArr69 = ['id'=>15, 'name'=>'组六'.$cnNum.'码', 'code'=>$code, 'count'=>1];
                    (!empty($singleArr['组六'])) && ($methodArr69['single'] = $singleArr['组六']);
                    $methodArr['methodArr69'][] = $methodArr69;
                    break;
            }
        }

        return $methodArr;
    }

    /**
     * 独胆
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchDuDan(string $text='', array &$codes=[], &$count=0, $matchName=''): array
    {
        list($originText, $singleCnTxt) = MethodMatchService::replaceSingleText($text); # 匹配号码前倍数字符先替换为空
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/胆\s*(\d{1}(?:\s*\d{1})*)/', $text, $matches2)) {
            $numbers = str_replace(' ', '', $matches2[1])[0];
        }
        #p([$text, $matchName]);

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
    public static function matchShuangFei(string $text='', array &$codes=[], &$count=0, $matchName=''): array
    {
        list($originText, $singleCnTxt) = MethodMatchService::replaceSingleText($text); # 匹配号码前倍数字符先替换为空
        // 使用正则表达式匹配所有单个数字
        $text = str_replace(',', ' ', trim($text));
        if ((strpos($text, '双飞') !==false OR strpos($text, '飞') !==false) && preg_match_all('/(\d{2})/', $text, $matches)) {
            $codes = $matches[1];
        }

        if(empty($codes)){
            throw_info('获取号码异常');
        }
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
        $matcheCodeText = $text;
        list($originText, $singleCnTxt) = MethodMatchService::replaceSingleText($matcheCodeText); # 匹配号码前倍数字符先替换为空
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
    public static function matchErMaDing(string $text='', array &$codes=[], &$count=0, $matchName=''): array
    {
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
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $id = $methods[$name]['id'];

        $count = count($codes);
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>$id, 'name'=>$name, 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

        return $methodArr;
    }

    /**
     * 9豹子全包 16组六全包 25组三全包
     * @param string $text
     * @param array $codes
     * @return array
     */
    public static function matchQuanBao(string $text='', array &$codes=[], &$count=0): array
    {
        $methodArr = [];
        // 使用正则表达式匹配所有单个数字
        if (preg_match_all('/组三/u', $text, $matches)) {
            $numbers = $matches[0];
            $name = '组三全包';
            $methodArr[] = ['id'=>MethodMatchService::METHOD_ID_ZS_QB, 'name'=>$name, 'codes'=>$name, 'count'=>1];
        }
        if (preg_match_all('/组六/u', $text, $matches)) {
            $numbers = $matches[0];
            $name = '组六全包';
            $methodArr[] = ['id'=>MethodMatchService::METHOD_ID_ZL_QB, 'name'=>$name, 'codes'=>$name, 'count'=>1];
        }
        if (preg_match_all('/组六/u', $text, $matches)) {
            $numbers = $matches[0];
            $name = '豹子全包';
            $methodArr[] = ['id'=>MethodMatchService::METHOD_ID_BAOZI_QB, 'name'=>$name, 'codes'=>$name, 'count'=>1];
        }
        if (preg_match_all('/对子/u', $text, $matches)) {
            $numbers = $matches[0];
            $name = '对子全包';
            $methodArr[] = ['id'=>MethodMatchService::METHOD_ID_DUIZI_QB, 'name'=>$name, 'codes'=>$name, 'count'=>1];
        }
        if(empty($methodArr)){
            throw_info('匹配玩法异常');
        }

        $codes = $numbers;
        $count = count($methodArr);
        $codes = implode(self::ZU_SPLIT_FLAG, array_column($methodArr, 'codes'));
        //p($methodArr);

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
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $id = $methods[$name]['id'];
        $codes = implode(self::ZU_SPLIT_FLAG, $codes);
        $methodArr = ['id'=>$id, 'name'=>$name, 'codes'=>$codes, 'matchName'=>$matchName, 'count'=>$count];

        return $methodArr;
    }

    /**
     * 26-35跨度0-9
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchKuaDuX($text='', &$codes=[], &$count=0){
        #$text = trim(str_replace(' ', '', $text));
        #if (preg_match('/(\d+)元/', $text, $matches)) {
        #    if(!empty($matches[0])){
        #        $text = str_replace($matches[0], '', $text);
        #    }
        #}
        #//p([$text, $matches]);

        #$text = explode('各', trim($text))[0];
        #$matcheCodeText = $text;
        list($originText, $singleCnTxt) = MethodMatchService::replaceSingleText($text); # 匹配号码前倍数字符先替换为空

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
        // 使用正则表达式匹配跨度 3-6、3到6，即：3、4、5、6
        if ($codes !== '0' && empty($codes) && preg_match_all('/(\d+)\s*[-|到|至]\s*(\d+)/u', $text, $matches0)) {
            $codes = '';
            for ($i=$matches0[1][0]; $i<=$matches0[2][0]; $i++){
                $codes .= $i;
            }
        }
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
        $changeNameArr = array_flip(ThirdDTypeService::SINGLE_ASSCIATE); //p($changeNameArr);
        for ($i=0; $i<strlen($codes); $i++){
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $code = $codes[$i];
            $name = '跨度'.$code;
            $method = $methods[$name];
            $id = $method['id'];
            $methodArr[] = ['id'=>$id, 'name'=>$name, 'codes'=>$code, 'matchName'=>$cnTextMatch.$changeNameArr[$codes[$i]], 'count'=>1];
            #p(['codes'=>$codes[0][$i], 'methods'=>$methods]);
        }
        #p($methodArr);

        return $methodArr;
    }

    /**
     * 36-51:1码拖.... [组三|组六]
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchYiMaTuo(string $text='', array &$codes=[], &$count=0, $matchName=''): array
    {
        $text = explode(' ', trim($text))[0];
        $text = trim(str_replace(' ', '', $text));
        #$text = str_replace($matchName, $matchName.' ', $text);

        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d){1}拖\s*(\d+)/u', $text, $matches1)) {
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

        # 和值范围
        if (strpos($text, ',')===false && preg_match_all('/(\d+)\s*[-|到|至]\s*(\d+)/u', $text, $matches0)) {
            $numsArr = [];
            for ($i=$matches0[1][0]; $i<=$matches0[2][0]; $i++){
                $numsArr[] = $i;
            }
        }
        # 指定某几个和值
        if (empty($numsArr) && preg_match_all('/[和合]值(\d+(?:,\d+)*)/u', $text, $matches2)) {
            $numsArr = explode(',', $matches2[1][0]);
        }

        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        if(!empty($numsArr)){
            foreach ($numsArr as $code){
                $name = '和值'.$code;
                $method = $methods[$name];
                $id = $method['id'];
                $methodArr[] = ['id'=>$id, 'name'=>$name, 'codes'=>$code, 'matchName'=>$name, 'count'=>1];
            }
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
    public static function matchHeZhiDaXiaoDanShuang($text='', &$codes=[], &$count=0){
        $text = trim($text);
        $text = str_replace('合值', '和值', $text);
        //p([$text, $matchName]);

        // 使用正则表达式匹配组选后面的三个数字  -- 和值范围
        if (preg_match_all('/(大|小|单|双)/u', $text, $matches1)) {
            $nums = $matches1[0];
        }
        $codes = implode(';', $nums);
        $count = count($nums);

        foreach ($nums as $num){
            $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
            $name = '和值'.$num;
            $method = $methods[$name];
            $id = $method['id'];
            $methodArr[] = ['id'=>$id, 'name'=>$name, 'codes'=>$num, 'matchName'=>$name, 'count'=>1];
        }
        #p($methodArr);

        return $methodArr;
    }

    /**
     *  91 定位直选复式、93 直选复式
     * @param string $text
     * @param array $codes
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchDingWeiFuShi($text='', &$codes=[], &$count=0){
        $text = trim(str_replace('各', ' ', $text));
        $text = str_replace('值选', '直选', $text);
        $text = str_replace('复试', '复式', $text);
        #$text = trim(str_replace(' ', '', $text));
        $text = trim( $text, ',');
        #$text = str_replace($matchName, $matchName.' ', $text);
        //p([$text, $matchName]);

        // 使用正则表达式匹配组选后面的三个数字
        if ( (strpos($text, '定位') !== false) && preg_match_all('/[百十个]{1}(\d+)/u', $text, $matches1) ) {
            # 定位复式直选
            $m0 = $matches1[0];
            $m1 = $matches1[1];
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
            $codes = str_replace('百', '百:', $codes);
            $codes = str_replace('十', '十:', $codes);
            $codes = str_replace('个', '个:', $codes);

            $count = 1;
            foreach ($m1 as $mCodes){
                $count *= strlen($mCodes);
            }

            $name = '定位直选复式';
        }else{
            if (preg_match_all('/(\d{4,})+/', $text, $matches1) ) {
                $codesArr = $matches1[0];
            }
            # 复式直选
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

            $name = '直选复式';
            $codes = implode(self::ZU_SPLIT_FLAG, $codesArr);
        }

        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $method = $methods[$name];
        $methodArr[] = ['id'=>$method['id'], 'name'=>$name, 'codes'=>$codes, 'matchName'=>$name, 'count'=>$count];
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
    public static function matchQuanDao(string $text='', array &$codes=[], &$count=0, $matchName=''): array
    {
        $text = trim(explode('各', trim($text))[0]);
        $text = trim( $text, ',');

        $text = str_replace('全倒', '', $text);
        // 使用正则表达式匹配组选后面的三个数字
        if (preg_match_all('/(\d{3,})/u', $text, $matches1) ) {
            $codesArr = $matches1[0];
        }
        if(empty($codesArr)){
            throw_info('匹配号码为空');
        }

        //p($matches1);
        $code2s = [];
        $code3s = [];
        $codeFuShis = [];
        foreach ($codesArr as $code){
            if(strlen($code)!=3){
                throw_info('号码一定要是三位['.$code.']');
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
                $count += 3;
            }
        }
        if(count($code3s)>0){
            foreach ($code3s as $code3){
                $count += 6;
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
        $codes = implode(self::ZU_SPLIT_FLAG, $codesArr);
        $methods = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1);
        $method = $methods[$name];

        $methodArr[] = ['id'=>$method['id'], 'name'=>$name, 'codes'=>$codes, 'matchName'=>$name, 'count'=>$count];
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
