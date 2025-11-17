<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\service\BaseService;
use common\service\cache\CacheKeyService;
use common\service\ssc\QihaoService;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class DynamicFilterService extends BaseService {

    # 动态过滤2
    const DYNAMIC_FILTER_TYPES = [
        ['type'=>1, 'label'=>'两合上1', 'params'=>['x'=>''], 'desc'=>'两数合分为x或该数字x上奖', 'playway'=>[2, 3]],
        ['type'=>2, 'label'=>'过滤近x天直码', 'params'=>['x'=>''], 'desc'=>'过滤近(x)7天的直码', 'playway'=>[2, 3]],
        ['type'=>3, 'label'=>'x(1234)位近y个码最多上z个', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'x(1234)位近y个码最多上z个', 'playway'=>[1, 2, 3]],
        ['type'=>4, 'label'=>'定x位号码y对应位置最少上z个', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'定x位号码y对应位置最少上z个', 'playway'=>[1, 2, 3]],
        ['type'=>5, 'label'=>'过滤1234最近x期开过号码全转', 'params'=>['x'=>''], 'desc'=>'过滤1234最近x期开过号码全转', 'playway'=>[3]],
        ['type'=>6, 'label'=>'过滤1234前第x期同位置号码(6561组)', 'params'=>['x'=>''], 'desc'=>'比如x:1，则往上数1期，假如往上第x开：2473，则：第1位2、第2位过滤4、第3位过滤7、第4位过滤3，最终结果6561组号码', 'playway'=>[3]],
        ['type'=>7, 'label'=>'过滤1234最近x期开过号码全转', 'params'=>['x'=>''], 'desc'=>'过滤1234最近x期开过号码全转', 'playway'=>[1,2]],
        ['type'=>8, 'label'=>'过滤1234前第x-y期同位置号码', 'params'=>['x'=>'', 'y'=>''], 'desc'=>'过滤1234前第x-y期同位置号码', 'playway'=>[3]],
        ['type'=>9, 'label'=>'定位x或定位y,合分为z', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>"<br>比如：x:13、y:24、z:45678 <br>则第1位+第3位合分 或 第2位+第4位的合分，只要有一个合分为：45678就中", 'playway'=>[3]],
        ['type'=>10, 'label'=>'定位x或定位y,合分为z', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>"<br>比如：x:13、y:24、z:45678 <br>则第1位+第3位合分 或 第2位+第4位的合分，只要有一个合分为：45678就中", 'playway'=>[3]],

        ['type'=>11, 'label'=>'x位过滤上上期y位+上期z位合数', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'1位过滤上上期3位+上期的4位合数', 'playway'=>[3], 'img'=>'/statics/img/dynamic_filter/11.jpg'],
        ['type'=>13, 'label'=>'x位过滤上上期y位+上期z位合数', 'params'=>['x'=>'', 'y'=>'', 'z'=>''], 'desc'=>'1位过滤上上期3位+上期的4位合数', 'playway'=>[3], 'img'=>'/statics/img/dynamic_filter/11.jpg'],
        ['type'=>12, 'label'=>'(x位+上期y位)!=(上上期z位+上期h位)合数', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'h'=>''], 'desc'=>'(x位+上期y位)!=(上上期z位+上期h位)合数', 'playway'=>[3], 'img'=>'/statics/img/dynamic_filter/12.png'],
        ['type'=>14, 'label'=>'(x位+上期y位)!=(上上期z位+上期h位)合数', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'h'=>''], 'desc'=>'(x位+上期y位)!=(上上期z位+上期h位)合数', 'playway'=>[3], 'img'=>'/statics/img/dynamic_filter/12.png'],

        ['type'=>15, 'label'=>'定位x排除位置对应对数', 'params'=>['x'=>''], 'desc'=>"定位x排除位置对应对数，比如x：123，上期开：7849。 <br>1、则1位排除2、2位排除3，3位排除9", 'playway'=>[2,3], 'is_show'=>1],
        ['type'=>16, 'label'=>'定位x排除位置对数与对数值合分', 'params'=>['x'=>''], 'desc'=>"定位x排除位置合分以及对数值合分，比如x：123，上期开：7849。 <br>1、123位合分为9(7+8+4=19)，则123位过滤合分：4和9", 'playway'=>[2,3], 'is_show'=>1],
        ['type'=>17, 'label'=>'x位合数不等于上期y位合数', 'params'=>['x'=>'','y'=>''], 'desc'=>"x位合数不等于上期y位合数，<br>1、比如x：23，y：23，上期开：1478(4+7)=11合数为1，则23位过滤合数：1和11<br>2、其中x、y都可以随意搭配，比如：x:13、y:234或x:13、y:24", 'playway'=>[2,3], 'is_show'=>1, 'img'=>'/statics/img/dynamic_filter/17.png'],
        ['type'=>18, 'label'=>'x位合数不等于上期y位合数', 'params'=>['x'=>'','y'=>''], 'desc'=>"x位合数不等于上期y位合数，<br>1、比如x：23，y：23，上期开：1478(4+7)=11合数为1，则23位过滤合数：1和11<br>2、其中x、y都可以随意搭配，比如：x:13、y:234或x:13、y:24", 'playway'=>[2,3], 'is_show'=>1, 'img'=>'/statics/img/dynamic_filter/17.png'],
        ['type'=>19, 'label'=>'定位x或定位y或定位k,合分为z', 'params'=>['x'=>'', 'y'=>'', 'k'=>'', 'z'=>''], 'desc'=>"<br>比如：x:13、y:24、j:34、z:45678 <br>则(第1位+第3位合分)或(第2位+第4位的合分)或(第3位+第4位的合分)，只要有一个合分为：45678就中 <br>x、y、k可以只填其中两个或三个一起填", 'playway'=>[3]],
        ['type'=>20, 'label'=>'定位x或定位y或定位k,合分为z', 'params'=>['x'=>'', 'y'=>'', 'k'=>'', 'z'=>''], 'desc'=>"<br>比如：x:13、y:24、j:34、z:45678 <br>则(第1位+第3位合分)或(第2位+第4位的合分)或(第3位+第4位的合分)，只要有一个合分为：45678就中 <br>x、y、k可以只填其中两个或三个一起填", 'playway'=>[3]],
        ['type'=>25, 'label'=>'定位x或定位y或定位k,合分为z', 'params'=>['x'=>'', 'y'=>'', 'k'=>'', 'z'=>''], 'desc'=>"<br>比如：x:13、y:24、j:34、z:45678 <br>则(第1位+第3位合分)或(第2位+第4位的合分)或(第3位+第4位的合分)，只要有一个合分为：45678就中 <br>x、y、k可以只填其中两个或三个一起填", 'playway'=>[3]],
        ['type'=>26, 'label'=>'定位x或定位y或定位k,合分为z', 'params'=>['x'=>'', 'y'=>'', 'k'=>'', 'z'=>''], 'desc'=>"<br>比如：x:13、y:24、j:34、z:45678 <br>则(第1位+第3位合分)或(第2位+第4位的合分)或(第3位+第4位的合分)，只要有一个合分为：45678就中 <br>x、y、k可以只填其中两个或三个一起填", 'playway'=>[3]],
        ['type'=>21, 'label'=>'过滤同时x个位置配数单双互排及该位置号码', 'params'=>['x'=>''], 'desc'=>'过滤同时存在x个配数单双互排除及该位置号码', 'playway'=>[3]],
        ['type'=>22, 'label'=>'x个位置配数单双互排及该位置号码上奖', 'params'=>['x'=>''], 'desc'=>"同时存在x个位置配数单双互排除及该位置号码则中奖，比如开：2345，x:1，则下一把：<br>第1位开135792<br>第2位开024683<br>第3位开135794<br>第4位开024685<br>以上只要有一个位置中就算中，如x:2，则需要2个位置中才算中，以此类推", 'playway'=>[3]],
        ['type'=>23, 'label'=>'x位或y位或z位上n配数单双互排及该位置号码则中', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'n'=>''], 'desc'=>"配数单双互排及该位置号码，意思是：比如5，则对应的是024685，比如开：2345，x:123、y:234， n：2则下一把：<br>1、第123位或234位，其中只要某个位置组合出现2个对应的号码则中奖<br>2、其中x、y、z可以只填其中一个或多个", 'playway'=>[3]],
        ['type'=>24, 'label'=>'x位或y位或z位上n配数单双互排及该位置号码则不中', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'n'=>''], 'desc'=>"配数单双互排及该位置号码，意思是：比如5，则对应的是024685，比如开：2345，x:123、y:234， n：2则下一把：<br>1、第123位或234位，其中只要某个位置组合出现2个对应的号码则中奖<br>2、其中x、y、z可以只填其中一个或多个", 'playway'=>[3]],
        ['type'=>28, 'label'=>'x位或y位或z位上n配数大小互排及该位置号码则中', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'n'=>''], 'desc'=>"配数大小互排及该位置号码，意思是：比如3，则对应的是567893，比如开：2345，x:123、y:234， n：2则下一把：<br>1、第123位或234位，其中只要某个位置组合出现2个对应的号码则中奖<br>2、其中x、y、z可以只填其中一个或多个", 'playway'=>[3]],
        ['type'=>29, 'label'=>'x位或y位或z位上n配数大小互排及该位置号码则不中', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'n'=>''], 'desc'=>"配数大小互排及该位置号码，意思是：比如3，则对应的是567893，比如开：2345，x:123、y:234， n：2则下一把：<br>1、第123位或234位，其中只要某个位置组合出现2个对应的号码则中奖<br>2、其中x、y、z可以只填其中一个或多个", 'playway'=>[3]],
        ['type'=>27, 'label'=>'排除x位最近n个码的复试', 'params'=>['x'=>'', 'n'=>''], 'desc'=>"排除x位最近n个码的复试：<br>x:1，n:6，则千位取最近6个号码的复试号码过滤掉<br>x:12，n:6，则千位和百位各取最近6个号码的复试号码过滤掉", 'playway'=>[3]],
        ['type'=>30, 'label'=>'过滤x位最新y期直码', 'params'=>['x'=>'', 'y'=>''], 'desc'=>"过滤指定位置最新y期的直码：<br>1、x可以灵活组合位置，如：1234、2345、1345等四位组合，或123、234等三位组合<br>2、y表示要过滤的期数，如y:5表示过滤最近5期的直码<br>3、比如x:123，y:3，则过滤第1、2、3位最近3期开过的直码", 'playway'=>[1, 2, 3]],
        ['type'=>31, 'label'=>'x上期开奖号码每个位置对数的全倒', 'params'=>['x'=>''], 'desc'=>"[x:1取2除]上期开奖的号码每个位置变成对数后全倒：<br>1、对数为：0-5 1-6 2-7 3-8 4-9（相减为5）<br>2、例如上期开：1234，则生成：6234、1734、1284、1239的全倒组合<br>3、最终去重后得到过滤号码", 'playway'=>[1, 2, 3]],
        ['type'=>32, 'label'=>'相邻两个相加合分有且只有x个相等', 'params'=>['x'=>''], 'desc'=>"相邻位置相加的合分与上期相邻位置相加的合分比较，有且只有x个相等<br>1、合分计算：如4+8=12，合分为2和12（个位数和十位数）<br>2、x范围：0~3，表示相等的个数", 'playway'=>[1, 2, 3]],
        ['type'=>33, 'label'=>'相邻两个相加合分至少只有x个相等', 'params'=>['x'=>''], 'desc'=>"相邻位置相加的合分与上期相邻位置相加的合分比较，至少有x个相等<br>1、合分计算：如4+8=12，合分为2和12（个位数和十位数）<br>2、x范围：0~3，表示至少相等的个数", 'playway'=>[1, 2, 3]],
        ['type'=>34, 'label'=>'过滤上x期的和值', 'params'=>['x'=>''], 'desc'=>"过滤上x期的和值，其中x=1则过滤上期的和值，x=2则过滤上上期的和值<br>例如：x=1，上期和值为15，则过滤掉所有和值为15的四定号码", 'playway'=>[3]],
        ['type'=>35, 'label'=>'同位置最近1:x个|2:y个|3:z个|4:n个号码复试排除', 'params'=>['x'=>'', 'y'=>'', 'z'=>'', 'n'=>''], 'desc'=>"排除千百十个位：x、y、z、n个号码的所有复试组合。参数分别为千x、百y、十z、个n。", 'playway'=>[3]],
        ['type'=>36, 'label'=>'指定位置排除期号尾号', 'params'=>['x'=>''], 'desc'=>"指定位置排除期号尾号，比如x：12，期号：250727003，则第1位和第2位排除号码3<br>1、x可以灵活组合位置，如：1234、234、134等<br>2、1234分别代表千百十个位", 'playway'=>[1, 2, 3]],
        ['type'=>37, 'label'=>'指定位置合分不等于上期对应位置合分', 'params'=>['x'=>''], 'desc'=>"指定位置合分不等于上期对应位置合分，比如x：234，上期开：9843，则234位合分：8+4+3=15，过滤：5、15、25、35<br>1、x可以灵活组合位置，如：1234、234、134等<br>2、1234分别代表千百十个位<br>3、过滤和值会根据位置数量自动调整", 'playway'=>[1, 2, 3]],
        ['type'=>38, 'label'=>'筛选昨天早8点到今天凌晨5点重复x次或x次以上的号码全倒', 'params'=>['x'=>''], 'desc'=>"主要为幸运五(lottery_type=8)，筛选昨天早8点到今天凌晨5点重复x次或x次以上的号码，这些号码来全倒，主要为4定号码", 'playway'=>[3], 'lottery_type'=>[8]],
        ['type'=>39, 'label'=>'过滤x范围直码', 'params'=>['x'=>''], 'desc'=>"过滤指定期数范围的直码：<br>1、支持格式：1-2;4~6 则过滤前1、2、4、5、6期的直码<br>2、支持格式：1-100 或 1~100，则过滤最近100期的直码<br>3、支持混合格式：1,2;4~6;10-15，则过滤前1、2、4、5、6、10、11、12、13、14、15期的直码<br>4、格式说明：用分号(;)分隔不同范围，用逗号(,)分隔单个期数，用横线(-)或波浪号(~)表示连续范围", 'playway'=>[1, 2, 3]],
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
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        $md5PlanKey = md5($plan->id.'_'.$plan->hz_Arr.'_'.$plan->update_time.'_'.$nextQiHao);
        $cacheKey = CacheKeyService::getPlanCurrentCodeKey($plan->id, $nextQiHao, $md5PlanKey);
        if($data = commonRedis()->get($cacheKey)){
            //return $data;
        }
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
                case 19: # 定位x或定位y或定位j合分为z
                case 20: # 定位x或定位y或定位j合分为z
                case 25: # 定位x或定位y或定位j合分为z
                case 26: # 定位x或定位y或定位j合分为z
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
                case 17: # x位不等于上期y位合分
                case 18: # x位不等于上期y位合分
                    $codes = DynamicType2Service::filter17($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 21: # 过滤x个配数单双互排除及该位置号码(四定)
                    $codes = DynamicType2Service::filter19($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 22: # x个位置配数单双互排除及该位置号码中奖
                    $codes = DynamicType2Service::filter19($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType], $direct=1);
                    break;
                case 23: # x位或y位或z位上n配数单双互排及该位置号码则中
                case 28: # x位或y位或z位上n配数大小互排及该位置号码则中
                    $opType = ($dynamic['type']==28) ? 'type_dx' : 'type_ds';
                    $codes = DynamicType2Service::filter20($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType], $direct=1, $opType);
                    break;
                case 24: # x位或y位或z位上n配数单双互排及该位置号码则不中
                case 29: # x位或y位或z位上n配数大小互排及该位置号码则不中
                    $opType = ($dynamic['type']==29) ? 'type_dx' : 'type_ds';
                    $codes = DynamicType2Service::filter20($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType], $direct=0, $opType);
                    break;
                case 27: # x位或y位或z位上n配数单双互排及该位置号码则不中
                    $codes = DynamicType2Service::filter21($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 30: # 过滤x位最新y期直码
                    $codes = DynamicType2Service::filter30($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 31: # 上期开奖号码对数全倒
                    $codes = DynamicType2Service::filter31($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 32: # 相邻两个相加合分有且只有x个相等
                    $codes = DynamicType2Service::filter32($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 33: # 相邻两个相加合分至少只有x个相等
                    $codes = DynamicType2Service::filter33($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 34: # 过滤上x期的和值
                    $codes = DynamicType2Service::filter34($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 35: # 排除同位置最近n个号码复试
                    $codes = DynamicType2Service::filter35($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 36: # 指定位置排除期号尾号
                    $codes = DynamicType2Service::filter36($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 37: # 指定位置合分不等于上期对应位置合分
                    $codes = DynamicType2Service::filter37($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 38: # 动态过滤2
                    $codes = DynamicType2Service::filter38($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
                case 39: # 过滤x范围直码
                    $codes = DynamicType2Service::filter39($plan, $dynamic, $filterDesc=self::DYNAMIC_FILTER_TYPES[self::$filterType]);
                    break;
            }
            if(empty($codes)){
                $codes = $allCodes;
            }
            $codesArr = array_intersect($codesArr, $codes);
        }
        if(!empty($codesArr)){
            commonRedis()->setex($cacheKey, 30, $codesArr);
        }
        #p(['counts'=>count($codesArr), 'codesArr'=>$codesArr]);

        return $codesArr;
    }

}
