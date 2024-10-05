<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\service\BaseService;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class DynamicFilterService extends BaseService {

    # 动态过滤2
    const DYNAMIC_FILTER_TYPES = [
        ['type'=>1, 'label'=>'两合上1', 'params'=>['x'=>''], 'desc'=>'两数合为该合分或该数字上奖'],
        ['type'=>2, 'label'=>'过滤近x天直码', 'params'=>['x'=>''], 'desc'=>'过滤近x天的直码'],
        ['type'=>3, 'label'=>'x(1234)位近y个码最多上z个', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'x(1234)位近y个码最多上z个'],
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
            }
            $codesArr = array_intersect($codesArr, $codes);
        }
        #p(['counts'=>count($codesArr), 'codesArr'=>$codesArr]);

        return $codesArr;
    }

}
