<?php
namespace backend\service\numbers;

use backend\service\BaseService;
use backend\service\HN0898Service;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class CodeTypeService extends BaseService {
    # keyword type 1 类型过滤
    const KX_ACT_TYPE_FILTER_DOUBLE_TXT = '双重“除”操作';
    const KX_ACT_TYPE_GET_DOUBLE_TXT = '双重“取”操作';
    const KX_ACT_TYPE_FILTER_DOUBLE2_TXT = '双双重“除”操作';
    const KX_ACT_TYPE_GET_DOUBLE2_TXT = '双双重“取”操作';
    const KX_ACT_TYPE_FILTER_DOUBLE3_TXT = '三重“除”操作';
    const KX_ACT_TYPE_GET_DOUBLE3_TXT = '三重“取”操作';
    const KX_ACT_TYPE_FILTER_DOUBLE4_TXT = '四重“除”操作';
    const KX_ACT_TYPE_GET_DOUBLE4_TXT = '四重“取”操作';
    const KX_ACT_TYPE_FILTER_2B_TXT = '二兄弟“除”操作';
    const KX_ACT_TYPE_GET_2B_TXT = '二兄弟“取”操作';
    const KX_ACT_TYPE_FILTER_3B_TXT = '三兄弟“除”操作';
    const KX_ACT_TYPE_GET_3B_TXT = '三兄弟“取”操作';
    const KX_ACT_TYPE_FILTER_4B_TXT = '四兄弟“除”操作';
    const KX_ACT_TYPE_GET_4B_TXT = '四兄弟“取”操作';
    const KX_ACT_TYPE_FILTER_LOG = '对数“除”操作';
    const KX_ACT_TYPE_GET_LOG = '对数“取”操作';

    # keyword type 2 位置过滤  四定位，定位置“取”：千=123，百=3，十=23，个=324，对数“除”数：16，，大数“除”数：第1位，第3位，第4位
    const KX_POS_TYPE_FILTER_ODD = '单数“除”数';
    const KX_POS_TYPE_GET_ODD = '单数“取”数';
    const KX_POS_TYPE_FILTER_EVEN = '双数“除”数';
    const KX_POS_TYPE_GET_EVEN = '双数“取”数';
    const KX_POS_TYPE_FILTER_BIG = '大数“除”数';
    const KX_POS_TYPE_GET_BIG = '大数“取”数';
    const KX_POS_TYPE_FILTER_SMALL = '小数“除”数';
    const KX_POS_TYPE_GET_SMALL = '小数“取”数';

    const KX_KW_2_FIXED_POS = '固定位置';
    const KX_KW_2_FIXED_POS_2_3 = '乘号位置';
    const KX_KW_2_PEISHU_FILTER = '配数“除”';
    const KX_KW_2_PEISHU_GET = '配数“取”';
    const KX_KW_2_LOG_FILTER = '对数“除”数';
    const KX_KW_2_LOG_GET = '对数“取”数';
    const KX_KW_2_FIXED_HF_GET = '固定合分取值';
    const KX_KW_2_FIXED_HF_FILTER = '固定合分除值';
    const KX_KW_2_NOT_FIXED_HF_2NUM = '不定合分值(两数合)';
    const KX_KW_2_NOT_FIXED_HF_3NUM = '不定合分值(三数合)';
    const KX_KW_2_FU_SHI_FILTER = '复式“除”数';
    const KX_KW_2_FU_SHI_GET = '复式“取”数';
    const KX_KW_2_HF_ZHI_ZONE = '合分值范围';
    const KX_KW_2_BAO_HAN_FILTER = '包含“除”数';
    const KX_KW_2_BAO_HAN_GET = '包含“取”数';
    const KX_KW_2_FIXED_GET = '定位置“取”';
    const KX_KW_2_FIXED_FILTER = '定位置“除”';
    const KX_KW_2_EXCLUDE_CODE = '排除数';
    const KX_KW_2_ARISE_CODE = '全转数';
    const KX_KW_2_FIXED_POS_1 = '千=';
    const KX_KW_2_FIXED_POS_2 = '百=';
    const KX_KW_2_FIXED_POS_3 = '十=';
    const KX_KW_2_FIXED_POS_4 = '个=';
    const KX_KW_2_FIXED_POS_5 = '五=';

    # 除取
    public static $keywordsWhere1 = [
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_FILTER_DOUBLE_TXT => ['type_2'=>0],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_GET_DOUBLE_TXT => ['type_2'=>1],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_FILTER_DOUBLE2_TXT => ['type_22'=>0],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_GET_DOUBLE2_TXT => ['type_22'=>1],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_FILTER_DOUBLE3_TXT => ['type_3'=>0],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_GET_DOUBLE3_TXT => ['type_3'=>1],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_FILTER_DOUBLE4_TXT => ['type_4'=>0],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_GET_DOUBLE4_TXT => ['type_4'=>1],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_FILTER_2B_TXT => ['type_2b'=>0],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_GET_2B_TXT => ['type_2b'=>1],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_FILTER_3B_TXT => ['type_3b'=>0],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_GET_3B_TXT => ['type_3b'=>1],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_FILTER_4B_TXT => ['type_4b'=>0],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_GET_4B_TXT => ['type_4b'=>1],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_FILTER_LOG => ['type_log'=>0],
        \backend\service\numbers\CodeTypeService::KX_ACT_TYPE_GET_LOG => ['type_log'=>1],
    ];

    # 匹配 类型 "除" 、"取"
    public static $keywords3 = [
        \backend\service\numbers\CodeTypeService::KX_POS_TYPE_FILTER_ODD => ['odd_sel'=>1, 'key'=>'odd_pos'],
        \backend\service\numbers\CodeTypeService::KX_POS_TYPE_GET_ODD => ['odd_sel'=>2, 'key'=>'odd_pos'],
        \backend\service\numbers\CodeTypeService::KX_POS_TYPE_FILTER_EVEN => ['even_sel'=>1, 'key'=>'even_pos'],
        \backend\service\numbers\CodeTypeService::KX_POS_TYPE_GET_EVEN => ['even_sel'=>2, 'key'=>'even_pos'],
        \backend\service\numbers\CodeTypeService::KX_POS_TYPE_FILTER_BIG => ['big_sel'=>1, 'key'=>'big_pos'],
        \backend\service\numbers\CodeTypeService::KX_POS_TYPE_GET_BIG => ['big_sel'=>2, 'key'=>'big_pos'],
        \backend\service\numbers\CodeTypeService::KX_POS_TYPE_FILTER_SMALL => ['small_sel'=>1, 'key'=>'small_pos'],
        \backend\service\numbers\CodeTypeService::KX_POS_TYPE_GET_SMALL => ['small_sel'=>2, 'key'=>'small_pos'],
    ];

    # 匹配数据
    public static $keywords2 = [
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_POS => [ ],  # '固定位置',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_POS_2_3 => [ ],  # '乘号位置',
        \backend\service\numbers\CodeTypeService::KX_KW_2_PEISHU_FILTER => ['ps_sel'=>1],  # '配数“除”',
        \backend\service\numbers\CodeTypeService::KX_KW_2_PEISHU_GET => ['ps_sel'=>2], # '配数“取”',
        \backend\service\numbers\CodeTypeService::KX_KW_2_LOG_FILTER => ['log_sel'=>1],  # '对数“除”',
        \backend\service\numbers\CodeTypeService::KX_KW_2_LOG_GET => ['log_sel'=>2], # '对数“取”',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_HF_FILTER => ['fixed_pos_hefen_sel'=>1],  #  '固定合分除值',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_HF_GET => ['fixed_pos_hefen_sel'=>2],  #  '固定合分取值',
        \backend\service\numbers\CodeTypeService::KX_KW_2_NOT_FIXED_HF_2NUM => ['no_fix_hefen_pos'=>1],  # '不定合分值(两数合)',
        \backend\service\numbers\CodeTypeService::KX_KW_2_NOT_FIXED_HF_3NUM => ['no_fix_hefen_pos'=>2],  # '不定合分值(三数合)',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FU_SHI_FILTER => ['fushi_sel'=>1],  # '复式“除”数',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FU_SHI_GET => ['fushi_sel'=>2],  # '复式“取”数',
        \backend\service\numbers\CodeTypeService::KX_KW_2_HF_ZHI_ZONE => [ ], # '合分值范围',
        \backend\service\numbers\CodeTypeService::KX_KW_2_BAO_HAN_FILTER => ['arise_in_sel'=>1 ],  # '包含“除”数',
        \backend\service\numbers\CodeTypeService::KX_KW_2_BAO_HAN_GET => ['arise_in_sel'=>2 ],  # '包含“取”数',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_FILTER => ['fixed_pos_sel'=>1 ],  #  '定位取
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_GET => [ 'fixed_pos_sel'=>2 ],  #  '定位置“取”',
        \backend\service\numbers\CodeTypeService::KX_KW_2_EXCLUDE_CODE => [ ],  #  排除数
        \backend\service\numbers\CodeTypeService::KX_KW_2_ARISE_CODE => [ ],  #  全转数
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_POS_1 => [ ],  #  '千=',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_POS_2 => [ ],  #  '百=',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_POS_3 => [ ],  #  '十=',
        \backend\service\numbers\CodeTypeService::KX_KW_2_FIXED_POS_4 => [ ],  #  '个=',
    ];

    /**
     * 定位置 处理
     * @param string $operateStr
     * @return array
     */
    public static function oprateFixedPositionStrCondition($operateStr=''){
        $matchCondition = [];
        preg_match_all('/[千|百|十|个]\=\d+/u', $operateStr, $matches);
        if($m = $matches[0]){
            foreach ($m as $mt){
                if(strpos($mt, CodeTypeService::KX_KW_2_FIXED_POS_1) !== false){
                    $matchCondition['p1'] = str_replace(CodeTypeService::KX_KW_2_FIXED_POS_1, '', trim($mt));
                }
                if(strpos($mt, CodeTypeService::KX_KW_2_FIXED_POS_2) !== false){
                    $matchCondition['p2'] = str_replace(CodeTypeService::KX_KW_2_FIXED_POS_2, '', trim($mt));
                }
                if(strpos($mt, CodeTypeService::KX_KW_2_FIXED_POS_3) !== false){
                    $matchCondition['p3'] = str_replace(CodeTypeService::KX_KW_2_FIXED_POS_3, '', trim($mt));
                }
                if(strpos($mt, CodeTypeService::KX_KW_2_FIXED_POS_4) !== false){
                    $matchCondition['p4'] = str_replace(CodeTypeService::KX_KW_2_FIXED_POS_4, '', trim($mt));
                }
                if(strpos($mt, CodeTypeService::KX_KW_2_FIXED_POS_5) !== false){
                    $matchCondition['p5'] = str_replace(CodeTypeService::KX_KW_2_FIXED_POS_5, '', trim($mt));
                }
            }
        }

        return $matchCondition;
    }

    /**
     * “固定位置” 处理 - 配数全转所属
     * @param string $operateStr
     * @return array
     */
    public static function oprateFixedPosStrCondition($operateStr=''){
        $matcheCondition = [];
        preg_match_all('/\d+/', $operateStr, $matches);
        if($m = $matches[0]){
            $matcheCondition['fixed_sel_pos'] = implode(',', $m);
        }

        return $matcheCondition;
    }

    /**
     * 配数处理
     * @param string $operateStr
     * @return array
     */
    public static function opratePeiShuStrCondition($operateStr=''){
        $matcheCondition = [];
        $operateArr = explode('，', $operateStr);
        foreach ($operateArr as $opStr){
            preg_match_all('/\d+/', $opStr, $matches);
            if($m = $matches[0]){
                $matcheCondition['ps_'.$m[0]] = $m[1];
            }
        }

        return $matcheCondition;
    }

    /**
     * 对数处理
     * @param string $operateStr
     * @return array
     */
    public static function oprateLogStrCondition($operateStr=''){
        $matcheCondition = [];
        $operateArr = explode('，', trim($operateStr));
        foreach ($operateArr as $k=>$opStr){
            $matcheCondition['log_'.($k+1)] = $opStr;
        }

        return $matcheCondition;
    }

    /**
     * 固定合分除、取值
     * @param string $operateStr
     * @return array
     */
    public static function oprateFixedPosHfStrCondition($operateStr=''){
        $matcheCondition = [];
        $operateArr = array_filter(explode('；', $operateStr));
        //p($operateArr, 0);
        foreach ($operateArr as $k=>$opStr){
            $n = $k + 1;
            // 指定编码为 UTF-8
            mb_internal_encoding('UTF-8');
            $matches = [];
            preg_match_all('/第(\d+)位选中/u', $opStr, $matches);
            $content = trim(explode('：', $opStr)[1]);
            #p(['opStr'=>$opStr, 'matches'=>$matches, 'content'=>$content]);
            $matcheCondition['hefen_pos'.$n] = implode(',', $matches[1]);
            $matcheCondition['hefen'.$n] = $content;
        }

        return $matcheCondition;
    }

    /**
     * 不定合分值(两数合)、不定合分值(三数合)
     * @param string $operateStr
     * @return array
     */
    public static function oprateNotFixed2_3Condition($operateStr=''){
        $matcheCondition = ['no_fix_hefen'=>trim($operateStr)];

        return $matcheCondition;
    }

    /**
     * 复式
     * @param string $operateStr
     * @return array
     */
    public static function oprateFuShiCondition($operateStr=''){
        $matcheCondition = ['fushiCodes'=>trim($operateStr)];

        return $matcheCondition;
    }

    /**
     * 合分值范围
     * @param string $operateStr
     * @return array
     */
    public static function oprateHfZoneStrCondition($operateStr=''){
        $matcheCondition = [];
        $hzs = explode('-', trim($operateStr));
        if(isset($hzs[0]) && $hzs[1]){
            $hzsArr = [];
            for($i=$hzs[0]; $i<=$hzs[1]; $i++){
                $hzsArr[] = $i;
            }
            $matcheCondition['hz'] = $hzsArr;
        }

        return $matcheCondition;
    }

    /**
     * 包含“除”、“取”数
     * @param string $operateStr
     * @return array
     */
    public static function oprateBaoHanCondition($operateStr=''){

        $matcheCondition = ['arise_in'=>trim($operateStr)];

        return $matcheCondition;
    }

    /**
     * 全转数
     * @param string $operateStr
     * @return array
     */
    public static function oprateAriseCondition($operateStr=''){

        $matcheCondition = ['arise'=>trim($operateStr)];

        return $matcheCondition;
    }

    /**
     * 排除数
     * @param string $operateStr
     * @return array
     */
    public static function oprateExcludeCodesCondition($operateStr=''){

        $matcheCondition = ['exclude_codes'=>trim($operateStr)];


        return $matcheCondition;
    }

    /**
     * 单、双、大、小、位置过滤
     * @param string $operateStr
     * @return array
     */
    public static function oddEvenBigSmallPosFilter($operateStr='', $keyword3Condition=[]){
        $operateStr = trim($operateStr);
        $matcheCondition = [];
        preg_match_all('/\d+/', $operateStr, $matches);
        //p(['operateStr'=>$operateStr, 'matches'=>$matches]);
        if($m = $matches[0]){
            $matcheCondition[$keyword3Condition['key']] = implode(',', $m);
        }

        return $matcheCondition;
    }

}
