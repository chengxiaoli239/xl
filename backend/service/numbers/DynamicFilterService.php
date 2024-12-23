<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\service\BaseService;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class DynamicFilterService extends BaseService {

    # 动态过滤2
    const DYNAMIC_FILTER_TYPES = [
        ['type'=>1, 'label'=>'两合上1', 'params'=>['x'=>''], 'desc'=>'两数合分为x或该数字x上奖', 'playway'=>[1, 2, 3]],
        ['type'=>2, 'label'=>'过滤近x天直码', 'params'=>['x'=>''], 'desc'=>'过滤近(x)7天的直码', 'playway'=>[1, 2, 3]],
        ['type'=>3, 'label'=>'x(1234)位近y个码最多上z个', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'x(1234)位近y个码最多上z个', 'playway'=>[1, 2, 3]],
        ['type'=>4, 'label'=>'定x位号码y对应位置最少上z个', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'定x位号码y对应位置最少上z个', 'playway'=>[1, 2, 3]],
        ['type'=>5, 'label'=>'过滤1234最近x期开过号码全转', 'params'=>['x'=>''], 'desc'=>'过滤1234最近x期开过号码全转', 'playway'=>[3]],
        ['type'=>6, 'label'=>'过滤1234前第x期同位置号码(6561组)', 'params'=>['x'=>''], 'desc'=>'过滤1234上x期同位置号码', 'playway'=>[3]],
        ['type'=>7, 'label'=>'过滤1234最近x期开过号码全转', 'params'=>['x'=>''], 'desc'=>'过滤1234最近x期开过号码全转', 'playway'=>[1,2]],
        ['type'=>8, 'label'=>'过滤1234前第x-y期同位置号码', 'params'=>['x'=>'', 'y'=>''], 'desc'=>'过滤1234前第x-y期同位置号码', 'playway'=>[3]],
        ['type'=>9, 'label'=>'定位x或定位y合分为z', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'定位(x)12或(y)34合分为(z)01234', 'playway'=>[3]],
        ['type'=>10, 'label'=>'定位x或定位y合分为z', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'定位(x)12或(y)34合分为(z)01234', 'playway'=>[3]],

        ['type'=>11, 'label'=>'x位过滤上上期y位+上期z位合数', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'1位过滤上上期3位+上期的4位合数', 'playway'=>[3], 'img'=>'/statics/img/dynamic_filter/11.jpg'],
        ['type'=>13, 'label'=>'x位过滤上上期y位+上期z位合数', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'1位过滤上上期3位+上期的4位合数', 'playway'=>[3], 'img'=>'/statics/img/dynamic_filter/11.jpg'],
        ['type'=>12, 'label'=>'(x位+上期y位)!=(上上期z位+上期h位)合数', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'h'=>''], 'desc'=>'(x位+上期y位)!=(上上期z位+上期h位)合数', 'playway'=>[3], 'img'=>'/statics/img/dynamic_filter/12.png'],
        ['type'=>14, 'label'=>'(x位+上期y位)!=(上上期z位+上期h位)合数', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'h'=>''], 'desc'=>'(x位+上期y位)!=(上上期z位+上期h位)合数', 'playway'=>[3], 'img'=>'/statics/img/dynamic_filter/12.png'],

        ['type'=>15, 'label'=>'定位x排除位置对应对数', 'params'=>['x'=>''], 'desc'=>"定位x排除位置对应对数，比如x：123，上期开：7849。 <br>1、则1位排除2、2位排除3，3位排除9", 'playway'=>[2,3], 'is_show'=>1],
        ['type'=>16, 'label'=>'定位x排除位置对数与对数值合分', 'params'=>['x'=>''], 'desc'=>"定位x排除位置合分以及对数值合分，比如x：123，上期开：7849。 <br>1、123位合分为9(7+8+4=19)，则123位过滤合分：4和9", 'playway'=>[2,3], 'is_show'=>1],


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
                case 8: # 过滤1234前第x-y期同位置号码
                    $codes = DynamicType2Service::filter6($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 7: # 过滤最近x期开过号码全转
                    $codes = DynamicType2Service::filter7($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 9: # 定位x或定位y合分为z
                case 10: # 定位x或定位y合分为z
                    $codes = DynamicType2Service::filter9($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 11: # x位过滤上上期的y位+上期z位合数
                case 13: # x位过滤上上期的y位+上期z位合数
                    $codes = DynamicType2Service::filter11($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 12: # (x位+上期y位)!=(上上期z位+上期h位 合数)
                case 14: # (x位+上期y位)!=(上上期z位+上期h位 合数)
                    $codes = DynamicType2Service::filter12($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 15: # 定位x分别排除位置的对数
                    $codes = DynamicType2Service::filter15($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 16: # 定位x排除对应位置的合分与对数值合分
                    $codes = DynamicType2Service::filter16($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
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
