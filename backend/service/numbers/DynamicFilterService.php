<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\service\BaseService;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class DynamicFilterService extends BaseService {

    # 动态过滤2
    const DYNAMIC_FILTER_TYPES = [
        ['type'=>1, 'label'=>'两合上1', 'params'=>['x'=>''], 'desc'=>'两数合为该合分或该数字上奖', 'playway'=>[1, 2, 3]],
        ['type'=>2, 'label'=>'过滤近x天直码', 'params'=>['x'=>''], 'desc'=>'过滤近x天的直码', 'playway'=>[1, 2, 3]],
        ['type'=>3, 'label'=>'x(1234)位近y个码最多上z个', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'x(1234)位近y个码最多上z个', 'playway'=>[1, 2, 3]],
        ['type'=>4, 'label'=>'定x位号码y对应位置最少上z个', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'定x位号码y对应位置最少上z个', 'playway'=>[1, 2, 3]],
        ['type'=>5, 'label'=>'过滤1234最近x期开过号码全转', 'params'=>['x'=>''], 'desc'=>'过滤1234最近x期开过号码全转', 'playway'=>[3]],
        ['type'=>6, 'label'=>'过滤1234前第x期同位置号码(6561组)', 'params'=>['x'=>''], 'desc'=>'过滤1234上x期同位置号码', 'playway'=>[3]],
        ['type'=>7, 'label'=>'过滤最近x期开过号码全转', 'params'=>['x'=>''], 'desc'=>'过滤1234最近x期开过号码全转', 'playway'=>[1,2]],
    ];
    public static int $filterType = 0;

    /**
     * 动态过滤
     * @param object $plan
     * @return array
     */
    public static function getFilterDynamic2(object $plan, $dynamicTypes=[]): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        $query = Num4Type::find()->select(['code', 'code_type'])->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $allCodes = ArrayHelper::getColumn($NumTypes, 'code');
        $hzArr = Json::decode($plan->hz_Arr);
        $dynamicTypes = $dynamicTypes ? :$hzArr['filter_dynamic_types2'];

        $codesArr = $allCodes;
        foreach ($dynamicTypes as $dynamic){
            self::$filterType = $dynamic['type'];
            switch ($dynamic['type']){
                case 1: # 两合上1
                    $codes = DynamicType2Service::filter1($plan, $dynamic);
                    break;
                case 2: # 过滤近x天直码
                    $codes = DynamicType2Service::filter2($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 3: # x(1234)位近y个码最多上z个
                    $codes = DynamicType2Service::filter3($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 4: # 定x位号码y上期上两个则下期至少上1个
                    $codes = DynamicType2Service::filter4($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 5: # 过滤1234最近x期开过号码全转
                    $codes = DynamicType2Service::filter5($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 6: # 过滤1234前第x期同位置号码(6561)
                    $codes = DynamicType2Service::filter6($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 7: # 过滤最近x期开过号码全转
                    $codes = DynamicType2Service::filter7($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
            }
            if(empty($codes)){
                $codes = $allCodes;
            }
            $codesArr = array_intersect($codesArr, $codes);
        }
        #p(['counts'=>count($codesArr), 'codesArr'=>$codesArr]);

        return $codesArr;
    }

}
