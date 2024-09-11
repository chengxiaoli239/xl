<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\service\BaseService;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class DynamicFilterService extends BaseService {

    # 动态过滤2
    const DYNAMIC_FILTER_TYPES = [
        ['type'=>1, 'label'=>'两合上1', 'params'=>['x'=>'']],
        //['type'=>2, 'label'=>'两合上13', 'params'=>['x'=>'', 'y'=>'']],
    ];

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
            switch ($dynamic['type']){
                case 1: # 两合上1
                    $codes = DynamicType2Service::filter1($plan, $dynamic);
                    break;
            }
            $codesArr = array_intersect($codesArr, $codes);
        }
        #p(['counts'=>count($codesArr), 'codesArr'=>$codesArr]);

        return $codesArr;
    }
}
