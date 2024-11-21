<?php
namespace backend\service\numbers;
use backend\models\BettingRecords;
use backend\models\DataDealStatus;
use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\models\statics\Ssc1numsYl;
use backend\service\BaseService;
use backend\service\HN0898Service;
use backend\service\NumService;
use backend\service\SscDataService;
use common\service\cache\CacheKeyService;
use common\service\ssc\QihaoService;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;
use yii\helpers\Json;

class NumCodeService extends BaseService
{
    const CODE_LR_TYPE_COLD = 1;
    const CODE_LR_TYPE_HOT = 2;
    const CODE_LR_TYPE_YL = 3;
    const CODE_LR_TYPE_OPTIONS = [
        self::CODE_LR_TYPE_COLD => '冷码',
        self::CODE_LR_TYPE_HOT => '热码',
        self::CODE_LR_TYPE_YL => '遗漏',
    ];
    /**
     * 开奖数据
     * @param string $qiHao
     * @param int $lottery_type
     * @return array|SscKjData|mixed|null
     */
    public static function getKjData(string $qiHao='', int $lottery_type=DEFAULT_LOTTERY_TYPE)
    {
        $mKey = CacheKeyService::lotteryKjInfo($lottery_type, $qiHao);
        $kjData = commonRedis()->get($mKey);
        if(empty($kjData)){
            $historyWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', 'qihao', $qiHao]];
            $select = ['code1', 'code2', 'code3', 'code4', 'code5', 'code_str', 'qihao', 'code_str',
                'type_ds'=>'code_1_2_3_4', 'code_1_2_3_4',
                'type_4dx'=>'LEFT(type_4dx,4)',
                'hz'=>'SUM(code1 + code2 + code3 + code4)',
                'codes_hz'=>'codes_4nums_hz',
            ];
            $historyKjDataQuery = SscKjData::find()->select($select)->where($historyWhere)->limit(1)->orderBy(['id'=>SORT_DESC]);
            $sql = $historyKjDataQuery->createCommand()->getRawSql();//p($sql);
            $kjData = $historyKjDataQuery->asArray()->one();
            Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '开奖数据', ['lottery_type'=>$lottery_type, 'qiHao'=>$qiHao, 'kjData'=>$kjData]);
            if(empty($kjData['qihao'])){
                return []; # 开奖数据为空
            }
            commonRedis()->setex($mKey, 120, $kjData);
        }

        return $kjData;
    }

    /**
     * 过滤类型号码 - 至少1小1大、排除前一期号码剩余号码至少上2个码
     * @param object $plan
     * @param int $lottery_type
     * @param int $cNum 至少上cNum个
     * @return array
     */
    public static function getBeforeKjCodesDynamic1(object $plan, int $lottery_type=DEFAULT_LOTTERY_TYPE, int $cNum=3): array
    {
        $filterNum1 = NumService::$MIN_CODES;  # 至少上一个
        $filterNum2 = NumService::$MAX_CODES;  # 至少上一个

        $playway = $plan->playway;

        list($current_kj_qihao, $nextQihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $NewCodes = array_unique([$historyKjData['code1'], $historyKjData['code2'], $historyKjData['code3'], $historyKjData['code4']]);
        $filterNumKjCodes = array_diff(NumService::$ALL_CODES, $NewCodes); # 剔除上期开奖号码之后的号码
        $query = Num4Type::find()
            ->where(['OR', ['IN', 'code_1', $filterNum1], ['IN', 'code_2', $filterNum1], ['IN', 'code_3', $filterNum1], ['IN', 'code_4', $filterNum1]])
            ->andWhere(['=', 'code_type', $playway+1]);
        if($cNum == 3) {
            # 上三个
            $whereFilterKjCodes = [
                'OR',
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_1<>code_2 and code_2<>code_3'],
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_2 and code_2<>code_4'],
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_3 and code_3<>code_4'],
                ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_2<>code_3 and code_3<>code_4'],
            ];
            $query->andWhere(['=', 'type_3', 0]);
        }elseif ($cNum ==1){
            # 上一个
            $whereFilterKjCodes = [
                'OR',
                ['IN', 'code_1', $filterNumKjCodes],
                ['IN', 'code_2', $filterNumKjCodes],
                ['IN', 'code_3', $filterNumKjCodes],
                ['IN', 'code_4', $filterNumKjCodes],
            ];
        }else{
            # 默认上两个
            $whereFilterKjCodes = [
                'OR',
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], 'code_1<>code_2'],
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_1<>code_3'],
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_4'],
                ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_2<>code_3'],
                ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_2<>code_4'],
                ['AND', ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_3<>code_4'],
            ];
        }
        $query->andWhere($whereFilterKjCodes)
            ->andWhere(['OR', ['IN', 'code_1', $filterNum2], ['IN', 'code_2', $filterNum2], ['IN', 'code_3', $filterNum2], ['IN', 'code_4', $filterNum2]]);
        $sql = $query->createCommand()->getRawSql();
        $NumTypes = $query->asArray()->all();
        #p(['kjCode'=>$NewCodes, 'count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = '剔除上期号码,'.implode('', $filterNumKjCodes)."上{$cNum}个";
        NumCodeService::addBetDescRand($plan->id, $nextQihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 头尾去除当期期号最后两位相加(支持二三四定，主要针对四定)
     * @param object $plan
     * @param int $lottery_type
     * @return array
     */
    public static function getBeforeKjCodesDynamic3(object $plan, int $lottery_type=DEFAULT_LOTTERY_TYPE): array
    {
        $playway = $plan->playway;
        if($plan->is_batch_simulate){
            $endBettedRecord = BettingRecords::find()->select(['qihao'])
                ->where(['lottery_type'=>$lottery_type, 'plan_id'=>$plan->id])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            if(empty($endBettedRecord)){
                $endQihao = SscKjData::find()->where(['lottery_type'=>$lottery_type])->limit(1)->asArray()->one()['qihao'];
            }else{
                $endQihao = $endBettedRecord['qihao'];
            }
            $nextQihao = KjDataGet::getNextQihaoByQihao($endQihao, $lottery_type);
        }else{
            list($current_kj_qihao, $nextQihao) = QihaoService::getKjQiHao($lottery_type);
        }
        $last2Nums = [substr($nextQihao, -1, 1), substr($nextQihao, -2, 1)];
        #p([$DataDealStatus['next_qihao'], $last2Nums, array_sum($last2Nums)]);
        $last2NumsPlus = substr(array_sum($last2Nums), -1, 1);
        #p($last2NumsPlus);

        $query = Num4Type::find()->select(['code', 'code_type'])
            ->where(['AND', ['!=', 'code_1', $last2NumsPlus], ['!=', 'code_4', $last2NumsPlus]])
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        #p(['count'=>count($NumTypes), 'NumTypes'=>$NumTypes, 'sql'=>$query->createCommand()->getRawSql()]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = '头尾去除当期号尾号'.implode($last2Nums).'相加的码：'.$last2NumsPlus;
        NumCodeService::addBetDescRand($plan->id, $nextQihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 头去除当期期号最后两位相加(支持二三四定，主要针对四定)
     * @param object $plan
     * @param int $lottery_type
     * @return array
     */
    public static function getBeforeKjCodesDynamic4(object $plan, int $lottery_type=DEFAULT_LOTTERY_TYPE): array
    {
        $playway = $plan->playway;
        if($plan->is_batch_simulate){
            $endBettedRecord = BettingRecords::find()->select(['qihao'])
                ->where(['lottery_type'=>$lottery_type, 'plan_id'=>$plan->id])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            if(empty($endBettedRecord)){
                $endQihao = SscKjData::find()->where(['lottery_type'=>$lottery_type])->limit(1)->asArray()->one()['qihao'];
            }else{
                $endQihao = $endBettedRecord['qihao'];
            }
            $nextQihao = KjDataGet::getNextQihaoByQihao($endQihao, $lottery_type);
        }else{
            list($current_kj_qihao, $nextQihao) = QihaoService::getKjQiHao($lottery_type);
        }
        $last2Nums = [substr($nextQihao, -1, 1), substr($nextQihao, -2, 1)];
        $last2NumsPlus = substr(array_sum($last2Nums), -1, 1);

        $query = Num4Type::find()->select(['code', 'code_type'])
            ->where(['!=', 'code_1', $last2NumsPlus])
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = '头去除当期号尾号'.implode($last2Nums).'相加的码：'.$last2NumsPlus;
        NumCodeService::addBetDescRand($plan->id, $nextQihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 尾去除当期期号最后两位相加(支持二三四定，主要针对四定)
     * @param object $plan
     * @param int $lottery_type
     * @return array
     */
    public static function getBeforeKjCodesDynamic5(object $plan, int $lottery_type=DEFAULT_LOTTERY_TYPE): array
    {
        $playway = $plan->playway;
        if($plan->is_batch_simulate){
            $endBettedRecord = BettingRecords::find()->select(['qihao'])
                ->where(['lottery_type'=>$lottery_type, 'plan_id'=>$plan->id])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            if(empty($endBettedRecord)){
                $endQihao = SscKjData::find()->where(['lottery_type'=>$lottery_type])->limit(1)->asArray()->one()['qihao'];
            }else{
                $endQihao = $endBettedRecord['qihao'];
            }
            $next_qihao = KjDataGet::getNextQihaoByQihao($endQihao, $lottery_type);
        }else{
            list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        }
        $last2Nums = [substr($next_qihao, -1, 1), substr($next_qihao, -2, 1)];
        $last2NumsPlus = substr(array_sum($last2Nums), -1, 1);

        $query = Num4Type::find()->select(['code', 'code_type'])
            ->where(['!=', 'code_4', $last2NumsPlus])
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = '尾去除当期号尾号'.implode($last2Nums).'相加的码：'.$last2NumsPlus;
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 头尾相加不等于期号最后两位相加(支持二三四定，主要针对四定)
     * @param object $plan
     * @param int $lottery_type
     * @return array
     */
    public static function getBeforeKjCodesDynamic6(object $plan, int $lottery_type=DEFAULT_LOTTERY_TYPE): array
    {
        $playway = $plan->playway;
        if($plan->is_batch_simulate){
            $endBettedRecord = BettingRecords::find()->select(['qihao'])
                ->where(['lottery_type'=>$lottery_type, 'plan_id'=>$plan->id])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            if(empty($endBettedRecord)){
                $endQihao = SscKjData::find()->where(['lottery_type'=>$lottery_type])->limit(1)->asArray()->one()['qihao'];
            }else{
                $endQihao = $endBettedRecord['qihao'];
            }
            $next_qihao = KjDataGet::getNextQihaoByQihao($endQihao, $lottery_type);
        }else{
            list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        }
        $last2Nums = [substr($next_qihao, -1, 1), substr($next_qihao, -2, 1)];
        $last2NumsPlus_1 = substr(array_sum($last2Nums), -1, 1);
        if($last2NumsPlus_1<10){
            $last2NumsPlus_2 = $last2NumsPlus_1 + 10;
        }else{
            $last2NumsPlus_2 = $last2NumsPlus_1 - 10;
        }

        $query = Num4Type::find()->select(['code', 'code_type'])
            ->where('(code_1+code_4)!='.$last2NumsPlus_1)
            ->andWhere('(code_1+code_4)!='.$last2NumsPlus_2)
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = '头尾相加不等于期号最后两位相加：'.$last2NumsPlus_1.','.$last2NumsPlus_2;
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 前200期开过的号码全转
     * @param object $plan
     * @param int $lottery_type
     * @return array
     */
    public static function getBeforeKjCodesDynamic7(object $plan, $positions=[1,2,3,4], $num=200): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);

        $positions_str_4 = 'code'.implode(',code', $positions);
        $needCodesQuery = SscKjData::find()->select($positions_str_4)
            ->where(['lottery_type'=>$lottery_type])->andWhere(['<=', 'qihao', $current_kj_qihao])
            ->orderBy(['id'=>SORT_DESC])->limit($num);
        //$sql = $needCodesQuery->createCommand()->getRawSql();
        $needCodes = $needCodesQuery->asArray()->all();
        $filterCodes = array_values($needCodes);
        $code4nArr = [];
        foreach ($filterCodes as $filterCode){
            sort($filterCode);
            $code4nArr[] = implode('', $filterCode);
        }
        $filterCodesStr = implode('","', $code4nArr);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code_str NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        #$sql = $query->createCommand()->getRawSql();p($sql);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');
        //p(count($codes));

        $betDesc = '过滤前'.$num.'期开过的号码全转';
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 千十相加不等于期号最后两位相加(定位，主要针对四定)
     * @param object $plan
     * @return array
     */
    public static function getBeforeKjCodesDynamic8(object $plan): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);

        if($plan->is_batch_simulate){
            $endBettedRecord = BettingRecords::find()->select(['qihao'])
                ->where(['lottery_type'=>$lottery_type, 'plan_id'=>$plan->id])->andWhere(['<=', 'qihao', $current_kj_qihao])
                ->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            if(empty($endBettedRecord)){
                $endQihao = SscKjData::find()->where(['lottery_type'=>$lottery_type])->limit(1)->asArray()->one()['qihao'];
            }else{
                $endQihao = $endBettedRecord['qihao'];
            }
            $next_qihao = KjDataGet::getNextQihaoByQihao($endQihao, $lottery_type);
        }else{
            $DataDealStatus = DataDealStatus::find()->where(['AND', ['=', 'lottery_type', $lottery_type], ['IS NOT', 'next_qihao', NULL]])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
            $next_qihao = $DataDealStatus['next_qihao'];
        }
        $last2Nums = [substr($next_qihao, -1, 1), substr($next_qihao, -2, 1)];
        $last2NumsPlus_1 = substr(array_sum($last2Nums), -1, 1);
        if($last2NumsPlus_1<10){
            $last2NumsPlus_2 = $last2NumsPlus_1 + 10;
        }else{
            $last2NumsPlus_2 = $last2NumsPlus_1 - 10;
        }

        $query = Num4Type::find()->select(['code', 'code_type'])
            ->where('(code_1+code_3)!='.$last2NumsPlus_1)
            ->andWhere('(code_1+code_3)!='.$last2NumsPlus_2)
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "千十相加不等于期号最后两位相加：$last2NumsPlus_1, $last2NumsPlus_2";
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 随机9000组(主要针对四定)
     * @param int $playway
     * @param int $limit
     * @return array
     */
    public static function getBeforeKjCodesDynamic9(object $plan, $limit=9000){

        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($plan->lottery_type);
        $playway = $plan->playway;
        $query = Num4Type::find()->select(['code', 'code_type'])
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->orderBy('RAND()')->asArray()->limit($limit)->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "随机9000组";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 过滤前200期开过2次以上号码的全转(四定)
     * @param object $plan
     * @param int $lottery_type
     * @param int $num
     * @return array
     */
    public static function getBeforeKjCodesDynamic11(object $plan, $lottery_type=DEFAULT_LOTTERY_TYPE, $num=200){
        $playway = $plan->playway;
        $hzArr = yii\helpers\Json::decode($plan->hz_Arr);
        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $needCodes = SscKjData::find()->select(['code_4n', 'qihao'=>'MAX(qihao)'])
            ->where(['lottery_type'=>$lottery_type])->andWhere(['<=', 'qihao', $current_kj_qihao])
            ->groupBy(['code_4n_str'])->having('COUNT(id)>1')->orderBy(['MAX(qihao)'=>SORT_DESC])->limit($num)->asArray()->all();
        $filterCodes = ArrayHelper::getColumn($needCodes, 'code_4n');
        $filterCodesStr = implode('","', $filterCodes);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code_str NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "过滤前200期开过2次以上号码的全转";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 过滤最近10000期重复2次以上的直码(四定)
     * @param object $plan
     * @param int $lottery_type
     * @param int $num
     * @return array
     */
    public static function getBeforeKjCodesDynamic13(object $plan, $lottery_type=DEFAULT_LOTTERY_TYPE, $num=10000){
        $playway = $plan->playway;
        $hzArr = yii\helpers\Json::decode($plan->hz_Arr);

        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $max_index_id = SscKjData::find()->select(['max_index_id'=>'index_id'])
            ->where(['lottery_type'=>$lottery_type])->andWhere(['<=', 'qihao', $current_kj_qihao])
            ->asArray()->limit(1)->one()['max_index_id'];
        $min_index_id = $max_index_id - $num;

        $query = SscKjData::find()->select(['code_4n_str', 'qihao'=>'MAX(qihao)'])->where(['lottery_type'=>$lottery_type])
            ->andWhere(['>', 'index_id', $min_index_id])->andWhere(['<=', 'qihao', $current_kj_qihao])
            ->groupBy(['code_4n_str'])->having('COUNT(id)>1')->orderBy(['MAX(qihao)'=>SORT_DESC])->limit($num);
        $needCodes = $query->asArray()->all();

        $filterCodes = ArrayHelper::getColumn($needCodes, 'code_4n_str');
        $filterCodesStr = implode('","', $filterCodes);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "过滤最近10000期重复2次以上的直码";//.$filterCodesStr;
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 过滤前num期开过的号码
     * @param object $plan
     * @param int $lottery_type
     * @param int $num
     * @return array
     */
    public static function getBeforeKjCodesDynamic14(object $plan, $lottery_type=DEFAULT_LOTTERY_TYPE, $positions=[1,2,3,4], $num=2880){
        $playway = $plan->playway;
        $hzArr = yii\helpers\Json::decode($plan->hz_Arr);

        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $positions_str = 'code'.implode(',",",code', $positions);
        $query = SscKjData::find()->select(['code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')', 'qihao'=>'MAX(qihao)'])
            ->where(['lottery_type'=>$lottery_type])->andWhere(['<=', 'qihao', $current_kj_qihao])
            ->groupBy(['CONCAT('.$positions_str.')'])->orderBy(['MAX(qihao)'=>SORT_DESC])->limit($num);
        $sql = $query->createCommand()->getRawSql();
        #p($sql);
        $needCodes = $query->asArray()->all();
        $filterCodes = ArrayHelper::getColumn($needCodes, 'code_4n_str');
        $filterCodesStr = implode('","', $filterCodes);

        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤最新'.$num.'期开过的号码', ['plan_id'=>$plan->id, 'lottery_type'=>$lottery_type , 'current_kj_qihao'=>$current_kj_qihao, 'sql'=>$sql]);
        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = implode('', $positions)."位过滤前{$num}期开过的号码";//.$filterCodesStr;
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 取前四最近8000期开过的号码，不够往后搜集够8000组
     * @param object $plan
     * @param int $lottery_type
     * @param int $num
     * @return array
     */
    public static function getBeforeKjCodesDynamic17(object $plan, $lottery_type=DEFAULT_LOTTERY_TYPE, $num=8000){
        $playway = $plan->playway;
        $hzArr = yii\helpers\Json::decode($plan->hz_Arr);

        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $query = SscKjData::find()->select(['code_4n_str'=>'LEFT(code_str, 7)', 'qihao'=>'MAX(qihao)'])
            ->where(['lottery_type'=>$lottery_type])->andWhere(['<=', 'qihao', $current_kj_qihao])
            ->groupBy(['LEFT(code_str, 7)'])->orderBy(['MAX(qihao)'=>SORT_DESC])->limit($num);
        $sql = $query->createCommand()->getRawSql();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '取前四最近8000期开过的号码1', ['plan_id'=>$plan->id, 'current_kj_qihao'=>$current_kj_qihao, 'sql'=>$sql]);
        $needCodes = $query->asArray()->all();
        $filterCodes = ArrayHelper::getColumn($needCodes, 'code_4n_str');
        #p(count($filterCodes));
        $filterCodesStr = implode('","', $filterCodes);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $sql = $query->createCommand()->getRawSql();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '取前四最近8000期开过的号码2', ['plan_id'=>$plan->id, 'lottery_type'=>$lottery_type, 'current_kj_qihao'=>$current_kj_qihao]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');
        #p($codes);

        $betDesc = "取前四最近8000期开过的号码";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 取后四最近8000期开过的号码
     * @param object $plan
     * @param int $lottery_type
     * @param int $num
     * @return array
     */
    public static function getBeforeKjCodesDynamic18(object $plan, $lottery_type=DEFAULT_LOTTERY_TYPE, $num=8000){
        $playway = $plan->playway;
        $hzArr = yii\helpers\Json::decode($plan->hz_Arr);

        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $needCodes = SscKjData::find()->select(['code_4n_str'=>'RIGHT(code_str, 7)', 'qihao'=>'MAX(qihao)'])
            ->where(['lottery_type'=>$lottery_type])->andWhere(['<=', 'qihao', $current_kj_qihao])
            ->groupBy(['RIGHT(code_str, 7)'])->orderBy(['MAX(qihao)'=>SORT_DESC])->limit($num)->asArray()->all();
        $filterCodes = ArrayHelper::getColumn($needCodes, 'code_4n_str');
        #p(count($filterCodes));
        $filterCodesStr = implode('","', $filterCodes);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');
        #p($codes);

        $betDesc = "取后四最近8000期开过的号码";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 过滤两个位置一样的所有号码
     * @param object $plan
     * @return array
     */
    public static function getBeforeKjCodesDynamic19(object $plan){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;
        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        $short_current_kj_qihao = $current_kj_qihao;

        if(substr($current_kj_qihao, 0, 2) != '20'){
            $current_kj_qihao = '20'.$current_kj_qihao;
        }
        $query = SscKjData::find()->select(['qihao', 'code1', 'code2', 'code3', 'code4'])
            ->where(['lottery_type'=>$lottery_type, 'qihao'=>[$current_kj_qihao, $short_current_kj_qihao]])
            ->orderBy(['id'=>SORT_DESC])->limit(1);
        $sql1 = $query->createCommand()->getRawSql();
        $planCurrentKjData = $query->asArray()->one();
        #p($planCurrentKjData);

        $where = [
            'OR',
            ['AND', ['=', 'code_1', $planCurrentKjData['code1']], ['=', 'code_2', $planCurrentKjData['code2']]],
            ['AND', ['=', 'code_1', $planCurrentKjData['code1']], ['=', 'code_3', $planCurrentKjData['code3']]],
            ['AND', ['=', 'code_1', $planCurrentKjData['code1']], ['=', 'code_4', $planCurrentKjData['code4']]],
            ['AND', ['=', 'code_2', $planCurrentKjData['code2']], ['=', 'code_3', $planCurrentKjData['code3']]],
            ['AND', ['=', 'code_2', $planCurrentKjData['code2']], ['=', 'code_4', $planCurrentKjData['code4']]],
            ['AND', ['=', 'code_3', $planCurrentKjData['code3']], ['=', 'code_4', $planCurrentKjData['code4']]],
        ];
        $query = Num4Type::find()->alias('n')->select(['id', 'code', 'code_type'])
            ->where($where)
            ->andWhere(['=', 'code_type', $playway+1]);
        $sql = $query->createCommand()->getRawSql();
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '过滤两个位置一样的所有号码', ['plan_id'=>$plan->id, 'lottery_type'=>$lottery_type, 'short_current_kj_qihao'=>$short_current_kj_qihao, 'current_kj_qihao'=>$current_kj_qihao, 'sql1'=>$sql1, 'sql'=>$sql]);
        #p();
        $NumTypes = $query->asArray()->all();
        $filterIds = ArrayHelper::getColumn($NumTypes, 'id');

        $query = Num4Type::find()->alias('n')->select(['id', 'code', 'code_type'])
            ->where(['NOT IN', 'id', $filterIds])
            ->andWhere(['=', 'code_type', $playway+1]);
        #p($query->createCommand()->getRawSql());
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "过滤两个位置一样的所有号码";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 去除上期同位置 9 * 9 * 9 * 9 = 81 * 81 = 6561 组
     * @param int $lottery_type
     * @param int $playway
     * @return array
     */
    public static function getBeforeKjCodesDynamic26(object $plan, $lottery_type=DEFAULT_LOTTERY_TYPE){
        $playway = $plan->playway;

        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);
        #p($historyKjData);

        $query = Num4Type::find()->select(['code'])
            ->where(['AND', ['!=', 'code_1', $historyKjData['code1']], ['!=', 'code_2', $historyKjData['code2']], ['!=', 'code_3', $historyKjData['code3']], ['!=', 'code_4', $historyKjData['code4']]])
            ->andWhere(['=', 'code_type', $playway+1]);
        $sql = $query->createCommand()->getRawSql();
        #p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '去除上期同位置6561组', ['current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'historyKjData'=>$historyKjData, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "去除上期同位置号码：千!={$historyKjData['code_1']}百!={$historyKjData['code_2']}十!={$historyKjData['code_3']}个!={$historyKjData['code_4']}";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤期号尾号一致历史直码(四定)
     * @param int $lottery_type
     * @param int $playway
     * @param int[] $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic28(object $plan, $positions=[1,2,3,4], $limit=1000){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;
        #$nextQuery = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC]);

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $lastQihaoNum = substr($next_qihao, -1); # 即将下注期号最后一位，126期，则为：6
        #p([$next_qihao, substr($next_qihao, -1)]);

        $historyWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', 'RIGHT(qihao, 1)', $lastQihaoNum]];
        $positions_str = 'code'.implode(',",",code', $positions);
        $historyKjDatasQuery = SscKjData::find()->select(['code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')'])
            ->where($historyWhere)->groupBy(['CONCAT('.$positions_str.')'])->orderBy(['id'=>SORT_DESC]);
        $sql = $historyKjDatasQuery->createCommand()->getRawSql();  //p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤期号尾号一致', ['positions'=>$positions,  'lottery_type'=>$lottery_type, 'next_qihao'=>$next_qihao, 'plan_id'=>$plan->id, 'sql'=>$sql]);
        $historyKjDatas = $historyKjDatasQuery->asArray()->limit($limit)->all();
        $filterCodes = ArrayHelper::getColumn($historyKjDatas, 'code_4n_str');
        $filterCodesStr = implode('","', $filterCodes);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "过滤期号尾号一致历史直码"; //.$filterCodesStr;
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 排除前一期号码剩余号码至少上x个码
     * @param object $plan
     * @param int $cNum 至少上cNum个
     * @param int $is_get_double
     * @return array
     */
    public static function getBeforeKjCodesDynamic29(object $plan, $cNum=3, $is_get_double=1){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $NewCodes = array_unique([$historyKjData['code1'], $historyKjData['code2'], $historyKjData['code3'], $historyKjData['code4']]);
        $filterNumKjCodes = array_diff(NumService::$ALL_CODES, $NewCodes); # 剔除上期开奖号码之后的号码
        $query = Num4Type::find()->where(['=', 'code_type', $playway+1]);
        if($cNum == 3) {
            # 上三个
            $whereFilteKjCodes = [
                'OR',
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_1<>code_2 and code_2<>code_3'],
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_2 and code_2<>code_4'],
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_3 and code_3<>code_4'],
                ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_2<>code_3 and code_3<>code_4'],
            ];
            $query->andWhere(['=', 'type_3', 0]);
        }elseif ($cNum ==1){
            # 上一个
            $whereFilteKjCodes = [
                'OR',
                ['IN', 'code_1', $filterNumKjCodes],
                ['IN', 'code_2', $filterNumKjCodes],
                ['IN', 'code_3', $filterNumKjCodes],
                ['IN', 'code_4', $filterNumKjCodes],
            ];
        }else{
            if(!$is_get_double){
                # 默认上两个 - 剔除上期号码之后上两个 - 双重不算
                $whereFilteKjCodes = [
                    'OR',
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], 'code_1<>code_2'],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_1<>code_3'],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_4'],
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_2<>code_3'],
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_2<>code_4'],
                    ['AND', ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_3<>code_4'],
                ];
            }else{
                # 默认上两个 - 剔除上期号码之后上两个 - 双重算
                $whereFilteKjCodes = [
                    'OR',
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes]], #, 'code_1<>code_2' 剔除两个号码不一样之后双重的也算
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes]], #, 'code_1<>code_3'
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]], #, 'code_1<>code_4'
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes]], #, 'code_2<>code_3'
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]], #, 'code_2<>code_4'
                    ['AND', ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]], #, 'code_3<>code_4'
                ];
            }
        }
        $query->andWhere($whereFilteKjCodes);
        $sql = $query->createCommand()->getRawSql();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '排除前一期号码剩余号码至少上x个码', ['current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'filterNumKjCodes'=>$filterNumKjCodes, 'plan_id'=>$plan->id, 'NewCodes'=>$NewCodes, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        #p(['kjCode'=>$NewCodes, 'count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "排除前一期号码剩余号码".implode('',$filterNumKjCodes)."至少上{$cNum}个码";
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 过滤positions x期内一致的直码
     * @param object $plan
     * @param int[] $positions
     * @param int $num
     * @return array
     */
    public static function getBeforeKjCodesDynamic31(object $plan, $positions=[1,2,3], $num=350): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        list($lastQihao, $lastIndexId, $lastId) = SscDataService::getKjDataLastIndexId($lottery_type);
        $startIndexId = $lastIndexId - $num;

        $positions_str = 'code'.implode(',",",code', $positions);
        $query = SscKjData::find()->select(['qihao', 'code_str', 'codes'=>'CONCAT('.$positions_str.')', 'qihao'=>'MAX(qihao)'])
            ->where(['lottery_type'=>$lottery_type])->andWhere(['<=', 'qihao', $current_kj_qihao])->andWhere(['>', 'index_id', $startIndexId])
            ->groupBy(['CONCAT('.$positions_str.')'])->orderBy(['MAX(qihao)'=>SORT_DESC])->limit($num);
        $sql0 = $query->createCommand()->getRawSql();
        #p($sql0);
        $needCodes = $query->asArray()->all();
        $filterCodes = ArrayHelper::getColumn($needCodes, 'codes');
        $filterCodesStr = implode('","', $filterCodes);
        #p($filterCodesStr);

        $num_positions_str = 'code_'.implode(',",",code_', $positions);
        $query = Num4Type::find()->select(['code', 'code_type'])
            ->where('CONCAT('.$num_positions_str.') NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $sql = $query->createCommand()->getRawSql();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤最近'.$num.'期内三个位置一致的号码', ['plan_id'=>$plan->id, 'positions'=>$positions, 'lottery_type'=>$lottery_type, 'current_kj_qihao'=>$current_kj_qihao, 'sql0'=>$sql0, 'sql'=>$sql]);
        #p($query->createCommand()->getRawSql());
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "过滤".implode('',$positions)."位{$num}期内一致的直码";//.$filterCodesStr;
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 过滤期号一致历史号码全倒(四定)
     * @param int $lottery_type
     * @param int $playway
     * @return array
     */
    public static function getBeforeKjCodesDynamic35(object $plan): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $lastQihaoNum = substr($next_qihao, -3); # 即将下注期号最后三位，126期，则为：126
        #p([$next_qihao, substr($next_qihao, -3)]);

        $historyWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', 'RIGHT(qihao, 3)', $lastQihaoNum]];
        $historyKjDatasQuery = SscKjData::find()->select(['code_4n_str', 'code_4n'])->where($historyWhere);
        #p($historyKjDatasQuery->createCommand()->getRawSql());
        $historyKjDatas = $historyKjDatasQuery->asArray()->all();
        $filterCodes = ArrayHelper::getColumn($historyKjDatas, 'code_4n');
        $filterCodesStr = implode('","', $filterCodes);
        #p($filterCodesStr);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code_str NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        #p($query->createCommand()->getRawSql());
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "过滤期号{$lastQihaoNum}一致历史号码全倒";//.$filterCodesStr;
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤1234大小类型一致近1500组(四定)
     * @param object $plan
     * @param int[] $positions
     * @param int $cNum
     * @param int $type 1:大小 2:单双
     * @return array
     */
    public static function getBeforeKjCodesDynamic36(object $plan, array $positions=[1,2,3,4], int $cNum=1500, int $type=1): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;
        #$nextQuery = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC]);

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $filter_field = ($type==2) ? 'type_4ds' : 'type_dx';# 过滤大小 # 过滤单双

        $currentWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', 'qihao', $current_kj_qihao]];
        $positions_str = 'code'.implode(',",",code', $positions);
        $currentKjDatasQuery = SscKjData::find()->select(['code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')', $filter_field])
            ->where($currentWhere);
        $sql1 = $currentKjDatasQuery->createCommand()->getRawSql();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤1234大小类型一致近2500组(四定)', ['positions'=>$positions, 'lottery_type'=>$lottery_type, 'current_kj_qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'sql1'=>$sql1]);
        $currentKjDatas = $currentKjDatasQuery->limit(1)->asArray()->one();
        #p($currentKjDatas);

        $historyWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['<=', 'qihao', $current_kj_qihao], ['=', $filter_field, $currentKjDatas[$filter_field]]];
        $positions_str = 'code'.implode(',",",code', $positions);
        $currentKjDatasQuery = SscKjData::find()->select(['code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')', $filter_field, 'qihao'=>'MAX(qihao)'])
            ->where($historyWhere)->groupBy(['CONCAT('.$positions_str.')'])->limit($cNum)->orderBy(['MAX(qihao)'=>SORT_DESC]);
        $sql2 = $currentKjDatasQuery->createCommand()->getRawSql();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤1234大小类型一致近2500组(四定)', ['positions'=>$positions, 'lottery_type'=>$lottery_type, 'current_kj_qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'sql2'=>$sql2]);
        $historyKjDatas = $currentKjDatasQuery->asArray()->all();

        $filterCodes = ArrayHelper::getColumn($historyKjDatas, 'code_4n_str');
        $filterCodesStr = implode('","', $filterCodes);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = implode('', $positions)."过滤大小类型一致近{$cNum}组";//.$filterCodesStr;
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 过滤1234前期大小或单双类型分别都不一致号码
     * @param object $plan
     * @param int $filterType  1大小和单双2大小3单双
     * @return array
     */
    public static function getBeforeKjCodesDynamic41(object $plan, int $filterType=1): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $NewKjCodes = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        # 1、大小类型过滤
        $filterDxCode1 = NumService::getDxTypeFanByCode($NewKjCodes['code1']);
        $filterDxCode2 = NumService::getDxTypeFanByCode($NewKjCodes['code2']);
        $filterDxCode3 = NumService::getDxTypeFanByCode($NewKjCodes['code3']);
        $filterDxCode4 = NumService::getDxTypeFanByCode($NewKjCodes['code4']);

        #p($filterCodes1);

        $query = Num4Type::find()->select(['code']);
        $query->where(['=', 'code_type', $playway+1]);
        if(in_array($filterType, [1, 2])){
            $filterQuery1 = Num4Type::find()->select(['code'])
                ->andWhere(['AND', ['IN', 'code_1', $filterDxCode1], ['IN', 'code_2', $filterDxCode2], ['IN', 'code_3', $filterDxCode3], ['IN', 'code_4', $filterDxCode4]])
                ->andWhere(['=', 'code_type', $playway+1]);
            $filterNumTypes1 = $filterQuery1->asArray()->all();
            $filterCodes = ArrayHelper::getColumn($filterNumTypes1, 'code'); # 大小
            $query->andWhere(['NOT IN', 'code', $filterCodes]);
        }
        if(in_array($filterType, [1, 3])){
            # 2、单双类型过滤
            $filterDsCode1 = NumService::getDsTypeFanByCode($NewKjCodes['code1']);
            $filterDsCode2 = NumService::getDsTypeFanByCode($NewKjCodes['code2']);
            $filterDsCode3 = NumService::getDsTypeFanByCode($NewKjCodes['code3']);
            $filterDsCode4 = NumService::getDsTypeFanByCode($NewKjCodes['code4']);
            $filterQuery2 = Num4Type::find()->select(['code'])
                ->where(['AND', ['IN', 'code_1', $filterDsCode1], ['IN', 'code_2', $filterDsCode2], ['IN', 'code_3', $filterDsCode3], ['IN', 'code_4', $filterDsCode4]])
                ->andWhere(['=', 'code_type', $playway+1]);
            #p($filterQuery2->createCommand()->getRawSql(), 0);
            $filterNumTypes2 = $filterQuery2->asArray()->all();
            $filterCodes = ArrayHelper::getColumn($filterNumTypes2, 'code');
            #p($filterCodes2);
            $query->andWhere(['NOT IN', 'code', $filterCodes]);
        }

        //$sql = $query->createCommand()->getRawSql(); p($sql);
        $NumTypes = $query->asArray()->all();
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "过滤1234前期大小或单双类型分别都不一致号码";
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤最近x组大小类型(四定)
     * @param object $plan
     * @param string $type_field
        `type_dx` tinyint(1) DEFAULT '0' COMMENT '四定大小:0保留1全大2三大一小3两大两小4一大三小5全小',
        `type_4ds` tinyint(1) DEFAULT NULL COMMENT '四定单双:0保留1四单2四双3两单两双4一单三双5一双三单',
     * @param int $type_val
     * @param int[] $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic42(object $plan, $type_field='type_ds', $type_val=1, $positions=[1,2,3,4], $filterNums=1000){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);

        $historyWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', $type_field, $type_val]];
        $positions_str = 'code'.implode(',",",code', $positions);
        $historyKjDatasQuery = SscKjData::find()->select(['code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')', 'qihao'=>'MAX(qihao)'])
            ->where($historyWhere)->groupBy(['CONCAT('.$positions_str.')'])->limit($filterNums)->orderBy(['MAX(qihao)'=>SORT_DESC]);
        $sql = $historyKjDatasQuery->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤两单两双、两大两小1000组号码', ['positions'=>$positions, 'lottery_type'=>$lottery_type, 'next_qihao'=>$next_qihao, 'plan_id'=>$plan->id, 'sql'=>$sql]);
        $historyKjDatas = $historyKjDatasQuery->asArray()->all();
        $filterCodes = ArrayHelper::getColumn($historyKjDatas, 'code_4n_str');
        $filterCodesStr = implode('","', $filterCodes);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = implode('', $positions)."位过滤最近{$filterNums}组{$type_field}类型一致的号码";
        NumCodeService::addBetDescRand($plan->id, $next_qihao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤期号一致历史直码(四定)
     * @param object $plan
     * @param int[] $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic43(object $plan, array $positions=[1,2,3,4], $limit=1000): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;
        #$nextQuery = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC]);
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $lastQihaoNum = substr($next_qihao, -3); # 即将下注期号最后一位，126期，则为：126

        $historyWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', 'RIGHT(qihao, 3)', $lastQihaoNum]];
        $positions_str = 'code'.implode(',",",code', $positions);
        $historyKjDatasQuery = SscKjData::find()->select(['code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')'])
            ->where($historyWhere)->groupBy(['CONCAT('.$positions_str.')'])->orderBy(['id'=>SORT_DESC]);
        $sql = $historyKjDatasQuery->createCommand()->getRawSql();  //p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤期号一致直码', ['positions'=>$positions, 'lottery_type'=>$lottery_type, 'next_qihao'=>$next_qihao, 'plan_id'=>$plan->id, 'sql'=>$sql]);
        $historyKjDatas = $historyKjDatasQuery->asArray()->limit($limit)->all();
        $filterCodes = ArrayHelper::getColumn($historyKjDatas, 'code_4n_str');
        $filterCodesStr = implode('","', $filterCodes);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('n.code NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - 取1234位置0123路[或]
     * @param int $lottery_type
     * @param int $playway
     * @return array
     */
    public static function getBeforeKjCodesDynamic60(object $plan, $positions=[1,2,3,4], $lottery_type=DEFAULT_LOTTERY_TYPE){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        $positions_str = 'code'.implode(',",",code', $positions);
        $beforeQuery = SscKjData::find()->select(['code1','code2','code3','code4', 'code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')', 'qihao'])->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC]);

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $beforeQuery->andWhere(['<=', 'qihao', $current_kj_qihao]);
        //p($beforeQuery->createCommand()->getRawSql());
        $currentKjCodes = $beforeQuery->limit(1)->asArray()->one(); # 最新一期
        //p([$currentKjCodes, $positions], 0);
        $where = ['OR'];
        foreach ($positions as $p){
            $where[] = ['IN', 'code_'.$p, NumService::getCodeLine1($currentKjCodes['code'.$p])];
        }

        //p($where);
        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere($where);
        $sql = $query->createCommand()->getRawSql();
        //p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '去除上期同位置6561组', ['plan_id'=>$plan->id, 'current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'currentKjCodes'=>$currentKjCodes, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - 杀千、百、十、个或期号尾号码
     * @param int $lottery_type
     * @param int $playway
     * @return array
     */
    public static function getBeforeKjCodesDynamic61(object $plan, $positions=[1]): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        $positions_str = 'code'.implode(',",",code', $positions);
        $beforeQuery = SscKjData::find()->select(['code1','code2','code3','code4', 'code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')', 'qihao'])->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC]);
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $filterCodes = [];

        foreach ($positions as $p){
            if(!in_array($p, NumService::DW_POSES)){
                # 期号尾数
                $lastQihaoNum = substr($next_qihao, -1); # 即将下注期号最后一位，126期，则为：126
                $filterCodes[] = $lastQihaoNum;
            }else{
                $beforeQuery->andWhere(['<=', 'qihao', $current_kj_qihao]);
                //p($beforeQuery->createCommand()->getRawSql());
                $currentKjCodes = $beforeQuery->limit(1)->asArray()->one(); # 最新一期
                $filterCodes[] = $currentKjCodes['code'.$p];
            }
        }

        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['AND', ['NOT IN', 'code_1', $filterCodes], ['NOT IN', 'code_2', $filterCodes], ['NOT IN', 'code_3', $filterCodes], ['NOT IN', 'code_4', $filterCodes]]);
        $sql = $query->createCommand()->getRawSql();
        //p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '杀x位码6561组', ['current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'currentKjCodes'=>$currentKjCodes, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - 取千、百、十、个 最近9个码
     * @param object $plan
     * @param int[] $positions
     * @param int $cNum
     * @return array
     */
    public static function getBeforeKjCodesDynamic62(object $plan, $positions=[1], $cNum=9){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        $positions_str = 'code'.implode(',",",code', $positions);
        $groupByPos = [];
        foreach ($positions as $pp){
            $groupByPos[] = 'code'.$pp;
        }
        $beforeQuery = SscKjData::find()->select(['codes_str'=>'CONCAT('.$positions_str.')', 'qihao'=>'MAX(qihao)'])
            ->where(['lottery_type'=>$lottery_type])->groupBy($groupByPos)->orderBy(['MAX(qihao)'=>SORT_DESC])->limit($cNum);
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $filterCodes = [];
        foreach ($positions as $p){
            $beforeQuery->andWhere(['<=', 'qihao', $currentKjQiHao]);
            //p($beforeQuery->createCommand()->getRawSql());
            $currentKjCodes = $beforeQuery->asArray()->all(); # 最新一期
            $filterCodes = ArrayHelper::getColumn($currentKjCodes, 'codes_str');
        }

        $positions_str_4 = 'code_'.implode(',",",code_', $positions);
        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['AND', ['IN', 'CONCAT('.$positions_str_4.')', $filterCodes]]);
        $sql = $query->createCommand()->getRawSql();
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '取x位最近n个码', ['currentKjQiHao'=>$currentKjQiHao, 'lottery_type'=>$lottery_type, 'currentKjCodes'=>$currentKjCodes, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        //p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - 取1234位置0123路同路最多两路
     * @param object $plan
     * @param int[] $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic63(object $plan, $positions=[1,2,3,4]){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        $positions_str = 'code'.implode(',",",code', $positions);
        $beforeQuery = SscKjData::find()->select(['code1','code2','code3','code4', 'code_str', 'code_4n_str'=>'CONCAT('.$positions_str.')', 'qihao'])->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC]);
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $beforeQuery->andWhere(['<=', 'qihao', $current_kj_qihao]);
        //p($beforeQuery->createCommand()->getRawSql());
        $currentKjCodes = $beforeQuery->limit(1)->asArray()->one(); # 最新一期
        //p($currentKjCodes, 0);
        $line1Codes = NumService::getCodeLine2($currentKjCodes['code1']);
        $line2Codes = NumService::getCodeLine2($currentKjCodes['code2']);
        $line3Codes = NumService::getCodeLine2($currentKjCodes['code3']);
        $line4Codes = NumService::getCodeLine2($currentKjCodes['code4']);

        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['NOT',[
                'OR',
                ['AND', ['code_1'=>$line1Codes], ['code_2'=>$line2Codes], ['code_3'=>$line3Codes], ['NOT', ['code_4'=>$line4Codes]]],
                ['AND', ['code_1' => $line1Codes], ['code_2' => $line2Codes], ['NOT', ['code_3' => $line3Codes]], ['code_4' => $line4Codes]],
                ['AND', ['code_1' => $line1Codes], ['NOT', ['code_2' => $line2Codes]], ['code_3' =>$line3Codes], ['code_4' => $line4Codes]],
                ['AND', ['NOT', ['code_1' => $line1Codes]], ['code_2' =>$line2Codes], ['code_3' => $line3Codes], ['code_4' => $line4Codes]],
                ['AND', ['code_1' => $line1Codes], ['code_2' => $line2Codes], ['code_3' => $line3Codes], ['code_4' => $line4Codes]],
            ]]);
        $sql = $query->createCommand()->getRawSql();
        //p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '去除上期同位置6561组', ['current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'currentKjCodes'=>$currentKjCodes, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤同单双类型+双重(四定)
     * @param object $plan
     * @param string $type_field
     * @param int[] $positions
     * @param int $filterNums
     * @return array
     */
    public static function getBeforeKjCodesDynamic64(object $plan, string $type_field='type_ds', $positions=[1,2,3,4], $filterNums=1000){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qiHao) = QihaoService::getKjQiHao($lottery_type);
        $CurrentKjDatas = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $type_dd = ($type_field=='type_4dx') ? substr($CurrentKjDatas['type_4dx'], 0, 4) : $CurrentKjDatas['type_ds'];
        # 全大全小，全单全双才过滤双重，其它情况过滤对数
        if(in_array($type_dd, ['1111', '2222'])){
            $andWhere = ['=', 'n.type_2', 1];
        }else{
            $andWhere = ['=', 'n.type_log', 1];
        }
        $andWhere = ['=', 'n.type_2', 1]; // # 调整：只过滤双重

        //$type_dd = '2222';

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where(['=', 'n.'.$type_field, $type_dd])
            ->andWhere($andWhere)
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤单双、大小+双重', ['positions'=>$positions, 'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'sql'=>$sql]);

        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $positions_str_4 = 'code_'.implode(',",",code_', $positions);
        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['NOT IN', 'CONCAT('.$positions_str_4.')', $codes]);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤单双、大小+双重2', ['current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        //p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - 杀上期同位置号码+三兄(四定)
     * @param int $lottery_type
     * @param int $playway
     * @param int $type 1同位置2冷码
     * @return array
     */
    public static function getBeforeKjCodesDynamic74(object $plan, $positions=[1,2,3,4], $c_type='type_3b', $type=1){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);

        $filterCodes = [];
        if($type==2){
            # 冷码 + c_type
            $groupByPos = [];
            foreach ($positions as $pp){
                $groupByPos[] = 'code'.$pp;
                $positions_str = 'code'.implode(',",",code', $positions);
                $beforeQuery = SscKjData::find()->select(['code'=>'code'.$pp, 'qihao'=>'MAX(qihao)', 'c'=>'COUNT(id)'])
                    ->where(['lottery_type'=>$lottery_type])->groupBy($groupByPos)->orderBy(['count(id)'=>SORT_DESC, 'MAX(qihao)'=>SORT_DESC])->limit(10);
                $CurrentKjDatas = $beforeQuery->asArray()->all(); # 最新一期
                $filterCodes['code_'.$pp] = end($CurrentKjDatas)['code'];
                //p(['beforeQuery'=>$CurrentKjDatas]);
            }
        }else{
            # 同位置
            $CurrentKjDatas = SscKjData::find()->where(['lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
            foreach ($positions as $position){
                $filterCodes['code_'.$position] = $CurrentKjDatas['code'.$position];
            }
        }
        //p($filterCodes);
        $notConditions = ['OR'];
        foreach ($filterCodes as $k=>$fCode){
            $notConditions[] = ['AND', ['=', $k, $fCode], ['=', $c_type, 1]];
        }

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['=', $c_type, 1])
            ->andWhere($notConditions);
        $sql = $query->createCommand()->getRawSql();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '杀同位置/冷码位码6561组+3兄弟 1', ['current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'notConditions'=>$notConditions, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $positions_str_4 = 'code_'.implode(',",",code_', $positions);
        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['NOT IN', 'CONCAT('.$positions_str_4.')', $codes]);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '杀同位置/冷码位码6561组+3兄弟 2', ['current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        //p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
    * 过滤类型号码 - # 过滤最近x组大小类型(四定)
    * @param object $plan
    * @param string $type_field
    * @param int $type_val
    * @param int[] $positions
    * @return array
    */
    public static function getBeforeKjCodesDynamic76(object $plan, $type_field='type_4ds', $positions=[1,2,3,4], $filterNums=1000){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);
        #p($historyKjData);

        $filterWhere = ['AND', ['=', $type_field, $historyKjData[$type_field]], ];
        $filterWhere[] = ['OR',
            ['=', 'code_1', $historyKjData['code1']],
            ['=', 'code_2', $historyKjData['code2']],
            ['=', 'code_3', $historyKjData['code3']],
            ['=', 'code_4', $historyKjData['code4']],
        ] ;
        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where(['NOT', $filterWhere])
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤昨日同期[千百-十个]跨度(四定)
     * @param object $plan
     * @param int[] $positions1
     * @param int[] $positions2
     * @param int $beforeType 前期类型：1昨日同期 2今日前一期
     * @return array
     */
    public static function getBeforeKjCodesDynamic78(object $plan, $positions1=[1,2,3,4], $positions2=[1,2,3,4], $beforeType=1){
        $lottery_type = $plan->lottery_type;
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        if($beforeType == 2) {
            $beforeQihao = $current_kj_qihao;
        }else{
            if ($beforeType==3){
                # 最近期数同尾号
                $lastQihaoNum = substr($next_qihao, -1);
            }else{
                # 昨日同期
                $beforeQihao = date('Ymd', strtotime('-1 day')). substr($next_qihao, -3);
            }
        }
        $historyWhere = ['AND', ['=', 'lottery_type', $lottery_type]];
        if($beforeType==3){
            $historyWhere[] = ['LIKE', 'qihao', '%'.$lastQihaoNum, false];
        }else{
            $historyWhere[] = ['=', 'qihao', $beforeQihao];
        }

        $positions_str_11 = 'code'.implode('+code', $positions1);
        $positions_str_22 = 'code'.implode('+code', $positions2);
        $historyKjDatasQuery = SscKjData::find()->select(['code1', 'code2', 'code3', 'code4', 'x1'=>'RIGHT('.$positions_str_11.', 1)', 'x2'=>'RIGHT('.$positions_str_22.', 1)'])
            ->where($historyWhere)->orderBy(['id'=>SORT_DESC])->limit(1);
        #$sql = $historyKjDatasQuery->createCommand()->getRawSql();p($sql);
        $historyKjData = $historyKjDatasQuery->asArray()->one();
        if($historyKjData['x2']<$historyKjData['x1']){
            $kuaDu = 10 + $historyKjData['x2'] - $historyKjData['x1'];
        }else{
            $kuaDu = $historyKjData['x2'] - $historyKjData['x1'];
        }
        #p([$positions1, $positions2, $historyKjData, $kuaDu]);

        $p1_str_Arr = [];
        foreach ($positions1 as $p1){
            $p1_str_Arr[] = 'CAST(code_'.$p1.' AS SIGNED)';
        }
        $p2_str_Arr = [];
        foreach ($positions2 as $p2){
            $p2_str_Arr[] = 'CAST(code_'.$p2.' AS SIGNED)';
        }

        # 原生sql
        #$sql = "SELECT code, kd1, kd2, code_type, IF(kd2 >= kd1, kd2-kd1, 10 + kd2 - kd1) AS kd
        #    FROM (
        #        SELECT code, code_type,
        #            RIGHT(CAST(code_1 AS SIGNED)+CAST(code_2 AS SIGNED), 1) AS kd1,
        #            RIGHT(CAST(code_3 AS SIGNED)+CAST(code_4 AS SIGNED), 1) AS kd2
        #        FROM lt_num4_type
        #        WHERE code_type = 4
        #    ) AS subquery WHERE IF(kd2 >= kd1, kd2-kd1, 10 + kd2 - kd1) NOT in(3)
        #";
        #$results = Yii::$app->db->createCommand($sql)->queryAll();


        $subquery = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->addSelect(['kd1' => new \yii\db\Expression('RIGHT('.implode('+', $p1_str_Arr).', 1)')])
            ->addSelect(['kd2' => new \yii\db\Expression('RIGHT('.implode('+', $p2_str_Arr).', 1)')])
            ->from('lt_num4_type')
            ->where(['code_type' => 4]);

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->addSelect(['kd' => new \yii\db\Expression('IF(kd2 >= kd1, kd2, 10 + kd2)')])
            ->from(['n' => $subquery])
            ->where(['NOT IN', 'IF(kd2 >= kd1, kd2-kd1, 10 + kd2 - kd1)', [$kuaDu]]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        #p(['count'=>count($codes), 'kd'=>$kuaDu,'beforeQihao'=>$beforeQihao, 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤昨日同期[千百-十个]跨度(四定)
     * @param object $plan
     * @param int[] $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic79(object $plan, array $positions=[1]): array
    {
        $lottery_type = $plan->lottery_type;
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        # 昨日同期
        $beforeQihao = date('Ymd', strtotime('-1 day')). substr($next_qihao, -3);
        $historyKjData = NumCodeService::getKjData($beforeQihao, $lottery_type);

        $filterNum = $historyKjData['code'.$positions[0]];
        $andWhere = [
            'OR',
            ['AND', ['code_1'=>$filterNum, 'code_2'=>$filterNum]],
            ['AND', ['code_1'=>$filterNum, 'code_3'=>$filterNum]],
            ['AND', ['code_1'=>$filterNum, 'code_4'=>$filterNum]],
            ['AND', ['code_2'=>$filterNum, 'code_3'=>$filterNum]],
            ['AND', ['code_2'=>$filterNum, 'code_4'=>$filterNum]],
            ['AND', ['code_3'=>$filterNum, 'code_4'=>$filterNum]],
        ];

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => 4])
            ->andWhere(['NOT', $andWhere]);
        #$sql = $query->createCommand()->getRawSql();p($sql);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        #p(['count'=>count($codes), 'kd'=>$kuaDu,'beforeQihao'=>$beforeQihao, 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤[千]位号码及对数近x天(四定)
     * @param object $plan
     * @param int[] $positions
     * @param int $dateNum dateNums 天数
     * @return array
     */
    public static function getBeforeKjCodesDynamic80(object $plan, $positions=[1], $dateNum=10){
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $filterNum_code_field = 'code'.$positions[0];
        $filterNum = $historyKjData[$filterNum_code_field];
        $filterLogNum = $filterNum>4 ? ($filterNum-5) : ($filterNum+5);

        $before10Date = date('Y-m-d', strtotime('-'.$dateNum.' days'));
        # 号码
        $filterNumWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', $filterNum_code_field, $filterNum], ['>=', 'date', $before10Date]];
        $filterNumQuery = SscKjData::find()->select(['code_4n_str'])->where($filterNumWhere)->groupBy(['code_4n_str'])->orderBy(['id'=>SORT_DESC]);
        $sql1 = $filterNumQuery->createCommand()->getRawSql();
        $filterNumCodes = $filterNumQuery->asArray()->all();
        $numCodes = ArrayHelper::getColumn($filterNumCodes, 'code_4n_str');

        # 号码对数
        #$filterNumWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', $filterNum_code_field, $filterLogNum], ['>=', 'date', $before10Date]];
        #$filterLogNumQuery = SscKjData::find()->select(['code_4n_str'])->where($filterNumWhere)->groupBy(['code_4n_str'])->orderBy(['id'=>SORT_DESC]);
        #$sql2 = $filterLogNumQuery->createCommand()->getRawSql();
        #$filterLogNumCodes = $filterLogNumQuery->asArray()->all();
        #$LogNumCodes = ArrayHelper::getColumn($filterLogNumCodes, 'code_4n_str');
        ##p([$sql1, $sql2, $numCodes, $LogNumCodes]);

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => 4])
            ->andWhere(['NOT IN', 'code', $numCodes]);
            #->andWhere(['NOT IN', 'code', $LogNumCodes]); 对数
        #$sql = $query->createCommand()->getRawSql();p($sql);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        #p(['count'=>count($codes), 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤[千]位号码及合分(四定)
     * @param object $plan
     * @param int[] $positions
     * @dateNums int 天数
     * @return array
     */
    public static function getBeforeKjCodesDynamic81(object $plan, $positions=[1], $num=300){
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $filterNum_kjcode_field = 'code'.$positions[0];
        $filterNum = $historyKjData[$filterNum_kjcode_field];
        $filterNums = [$filterNum, $filterNum+10, $filterNum+20, $filterNum+30];

        $filterNum_code_field = 'code_'.$positions[0];
        $notWhere = ['NOT', ['AND', ['=',$filterNum_code_field, $filterNum], ['IN', 'codes_hz', $filterNums]]];
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => 4])
            ->andWhere($notWhere);
        #$sql = $query->createCommand()->getRawSql();p($sql);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        #p(['count'=>count($codes), 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 头尾剔除上期和值后一位号码(四定)
     * @param object $plan
     * @param int[] $positions
     * @dateNums int 天数
     * @return array
     */
    public static function getBeforeKjCodesDynamic82(object $plan, $positions=[1], $num=300){

        $lottery_type = $plan->lottery_type;
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $filterCode = substr($historyKjData['codes_hz'], -1, 1);
        #p([$historyKjData, $filterCode]);

        $notWhere = ['AND', ['!=', 'code_1', $filterCode], ['!=', 'code_4', $filterCode]];
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => 4])
            ->andWhere($notWhere);
        #$sql = $query->createCommand()->getRawSql();p($sql);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        #p(['count'=>count($codes), 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤上期每两个号码及对数(四定)
     * @param object $plan
     * @param int $date_num 0为前期1昨天2前天...以此类推
     * @param int $d_type 过滤的号码类型
     * @return array
     */
    public static function getBeforeKjCodesDynamic83(object $plan, int $date_num=0, int $d_type=1): array
    {
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $filterQihao = ($date_num>0)?date('Ymd', strtotime('-'.$date_num.' day')). substr($next_qihao, -3):$current_kj_qihao;
        $historyKjData = NumCodeService::getKjData($filterQihao, $lottery_type);

        if(empty($historyKjData)){
            $mkey = __FUNCTION__.'_X2_'.$plan->id;
            $num = \Yii::$app->redis->incr($mkey);
            if($num<3){
                throw_info('号码为空');
            }
        }
        //p(['historyKjData'=>$historyKjData], 0);

        $fixedPos = [[1,2], [1,3], [1,4], [2,3], [2,4], [3,4]];
        $notWhere = ['OR'];
        foreach ($fixedPos as $pos){
            $tmpNotWhere = ['AND',
                ['=', 'code_'.$pos[0], $historyKjData['code'.$pos[0]]],
                ['=', 'code_'.$pos[1], $historyKjData['code'.$pos[1]]],
            ];
            switch ($d_type){
                case 2: # 双重
                    $tmpNotWhere[] = ['=', 'type_2', 1];
                    break;
                case 20: # 同合分配双重
                    $tmpNotWhere[] = ['=', 'type_2', 1];
                    $tmpNotWhere[] = ['=', 'type_2', 1];
                    break;
                default: # 默认对数
                    $tmpNotWhere[] = ['=', 'type_log', 1];
                    break;
            }

            $notWhere[] = $tmpNotWhere;
        }

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => 4])
            ->andWhere(['NOT', $notWhere]);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤昨日同期/上期每两个号码及对数', ['lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'sql'=>$sql]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        #p(['count'=>count($codes), 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤三分离号码(四定)
     * @param object $plan
     * @param int[] $positions
     * @dateNums int 天数
     * @return array
     */
    public static function getBeforeKjCodesDynamic102(object $plan, $positions=[3,4,5]){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);
        #p($historyKjData);

        $positionCodes = [];
        foreach ($positions as $p){
            $positionCodes[] = $historyKjData['code'.$p];
        }

        $positions = NumService::get4Len($positionCodes);
        $t = implode('', $positions);
        $codesArrTmps = NumService::getAllCombination4($t);

        $notWhere = ['OR'];
        foreach($codesArrTmps as $fixedPos){
            $notWhere[] = ['LIKE', 'CONCAT(code_1, ",", code_2, ",", code_3, ",", code_4)', $fixedPos, false];
        }

        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['NOT', $notWhere]);
        #$sql = $query->createCommand()->getRawSql();p($sql);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 杀同位置大小加配上期两位同位置号码(四定)
     * @param object $plan
     * @param string $type_field
     * @param int $type_val
     * @param int[] $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic112(object $plan, $type_field='type_4ds'){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $filterWhere = ['AND', ['=', $type_field, $historyKjData[$type_field]]];
        $fixedPos = [[1,2], [1,3], [1,4], [2,3], [2,4], [3,4]];
        $notWhere = ['OR'];
        foreach ($fixedPos as $pos){
            $notWhere[] = ['AND',
                ['IN', 'code_'.$pos[0], ${'filterCode'.$pos[0]}],
                ['IN', 'code_'.$pos[1], ${'filterCode'.$pos[1]}],
            ];
        }
        $filterWhere[] = $notWhere;

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where(['NOT', $filterWhere])
            ->andWhere(['=', 'code_type', $playway+1]);
        #$sql = $query->createCommand()->getRawSql(); p($sql);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤千位最近1个冷码+三兄弟(四定)
     * @param object $plan
     * @param string $type_field
     * @param int $type_val 当type_field=type_3b则type_val=1表示三兄弟，当type_field=type_4ds则type_val=0非四单四双1四单2四双3两单两双4一单三双5一双三单
     * @param int $pos
     * @param int $type_log
     * @return array
     */
    public static function getBeforeKjCodesDynamic114(object $plan, string $type_field='type_ds', int $type_val=1, $pos=1, $type_log=0){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);

        $latelyCode = NumService::getPosLatelyCode($pos, $num=9, $lottery_type); # 最近9个热码
        $filterCodes = array_diff(\backend\service\NumService::$ALL_CODES, $latelyCode); # 一个冷码
        #p([\backend\service\NumService::$ALL_CODES, $latelyCode,  $filterCodes]);
        $andWhere = ['AND'];
        $andWhere[] = ['=', $type_field, $type_val];
        if($type_log > 0){
            if($type_log==2){
                $andWhere[] = ['=', 'type_2', 1];
            }else{
                $andWhere[] = ['=', 'type_log', 1];
            }
        }

        $pos_field = 'code_'.$pos;
        $query = Num4Type::find()->alias('n')->select(['code', $pos_field, 'code_type'])
            ->where(['IN', 'n.'.$pos_field, $filterCodes])
            ->andWhere($andWhere)
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $sql = $query->createCommand()->getRawSql();//p($sql, 0);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤单双、大小+双重', ['pos'=>$pos, 'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'latelyCode'=>$latelyCode, 'filterCodes'=>$filterCodes, 'sql'=>$sql]);
        $filterCodes = ArrayHelper::getColumn($NumTypes, 'code');
        #p(count($filterCodes));

        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['NOT IN', 'code', $filterCodes]);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        $NumTypes = $query->asArray()->all();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤单双、大小+双重2', ['plan_id'=>$plan->id, 'current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'sql'=>$sql, 'count'=>count($NumTypes)]);
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤千位最近1个冷码+合分
     * @param object $plan
     * @param string $type_field
     * @param int $type_val 当type_field=type_3b则type_val=1表示三兄弟，当type_field=type_4ds则type_val=0非四单四双1四单2四双3两单两双4一单三双5一双三单
     * @param int $positions
     * @param string $type  0不过滤1过滤对数2过滤双重，空则不过滤
     * @return array
     */
    public static function getBeforeKjCodesDynamic115(object $plan, $pos=1){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $latelyCode = NumService::getPosLatelyCode($pos, $num=9, $lottery_type);
        $filterCodes = array_values(array_diff(\backend\service\NumService::$ALL_CODES, $latelyCode)); # 过滤1冷码

        $filterNum = (int)current($filterCodes);
        $filterNums = [$filterNum, $filterNum+10, $filterNum+20, $filterNum+30]; # 合分
        #p([\backend\service\NumService::$ALL_CODES, $latelyCode,  $filterCodes]);

        $filterNum_code_field = 'code_'.$pos;

        $notWhere = ['NOT', ['AND', ['IN',$filterNum_code_field, $filterCodes], ['IN', 'codes_hz', $filterNums]]];
        $pos_field = 'code_'.$pos;
        $query = Num4Type::find()->alias('n')->select(['code', $pos_field, 'code_type'])
            ->where($notWhere)
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $sql = $query->createCommand()->getRawSql();//p($sql, 0);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤冷码+合分', ['pos'=>$pos, 'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'latelyCode'=>$latelyCode, 'filterCodes'=>$filterCodes, 'filterNums'=>$filterNums, 'historyKjData'=>$historyKjData, 'sql'=>$sql]);
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤千位+其它位置一起合分是千位
     * @param object $plan
     * @param int $positions
     * @param array $filterTypes # lt_num4_type 号码类型字段，假如：type_4ds pos位置大就过滤大且pos号码，type_dx pos位置是小 则过滤小&pos号码
     * @return array
     */
    public static function getBeforeKjCodesDynamic116(object $plan, $pos=1, $filterTypes=[]){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);
        #p($historyKjData, 0);

        $filterNum = $historyKjData['code'.$pos];
        $filterHf = [$filterNum, $filterNum+10, $filterNum+20, $filterNum+30]; # 合分
        #p([\backend\service\NumService::$ALL_CODES, $latelyCode,  $filterCodes]);
        $otherPos = array_diff([1,2,3,4], [$pos]);
        #$otherHf = 'CONCAT(code_'.implode(',",",code_', $otherPos).')';
        $otherHf = '(code_'.implode('+code_', $otherPos).')';

        $filterNum_code_field = 'code_'.$pos;

        $andNotWhere = ['AND', ['=',$filterNum_code_field, $filterNum]];
        $notWhere = ['NOT' ];
        if(!empty($filterTypes)){
            foreach ($filterTypes as $filterType){
                if($filterType == 'type_4ds'){
                    # type_4ds 单双
                    $filterVal = in_array($filterNum, NumService::$SINGLE_CODES) ? 1 : 2; # 单双：0非四单四双1四单2四双3两单两双4一单三双5一双三单
                }else{
                    # type_dx # 大小
                    $filterVal = in_array($filterNum, NumService::$MAX_CODES) ? 1 : 5; # type_dx:四定大小:0保留1全大2三大一小3两大两小4一大三小5全小
                }
                $andNotWhere[] = ['=', $filterType, $filterVal];
            }
            $notWhere[] = $andNotWhere;
        }else{
            $andNotWhere[] = ['IN', $otherHf, $filterHf];
            $notWhere[] = $andNotWhere;
        }

        //p($notWhere);
        $pos_field = 'code_'.$pos;
        $query = Num4Type::find()->alias('n')->select(['code', $pos_field, 'code_type'])
            ->where($notWhere)
            ->andWhere(['=', 'code_type', $playway+1]);
        #$sql = $query->createCommand()->getRawSql();p($sql);
        $NumTypes = $query->asArray()->all();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤位置+其它位置合分是该位置的', ['pos'=>$pos,  'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'filterHf'=>$filterHf, 'historyKjData'=>$historyKjData]);
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤x位且全双全小
     * @param object $plan
     * @param int $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic117(object $plan, $pos=1){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);
        #p($historyKjData, 0);

        $filterNum = $historyKjData['code'.$pos];
        $filterHf = [$filterNum, $filterNum+10, $filterNum+20, $filterNum+30]; # 合分
        #p([\backend\service\NumService::$ALL_CODES, $latelyCode,  $filterCodes]);
        $otherPos = array_diff([1,2,3,4], [$pos]);
        #$otherHf = 'CONCAT(code_'.implode(',",",code_', $otherPos).')';
        $otherHf = '(code_'.implode('+code_', $otherPos).')';

        $filterNum_code_field = 'code_'.$pos;

        $notWhere = ['NOT', ['AND', ['=',$filterNum_code_field, $filterNum], ['IN', $otherHf, $filterHf]]];
        $pos_field = 'code_'.$pos;
        $query = Num4Type::find()->alias('n')->select(['code', $pos_field, 'code_type'])
            ->where($notWhere)
            ->andWhere(['=', 'code_type', $playway+1]);
        #$sql = $query->createCommand()->getRawSql();p($sql);
        $NumTypes = $query->asArray()->all();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤位置+其它位置合分是该位置的', ['pos'=>$pos, 'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'filterHf'=>$filterHf, 'historyKjData'=>$historyKjData]);
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤x期同合分及双重(四定)
     * @param object $plan
     * @param int $date_num 0为前期1昨天2前天...以此类推
     * @param int $d_type 过滤的号码类型
     * @return array
     */
    public static function getBeforeKjCodesDynamic118(object $plan, int $date_num=0, int $d_type=1): array
    {
        $hzArr = yii\helpers\Json::decode($plan->hz_Arr, true);
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $filterQihao = ($date_num>0)?date('Ymd', strtotime('-'.$date_num.' day')). substr($next_qihao, -3):$current_kj_qihao;
        $historyKjData = NumCodeService::getKjData($filterQihao, $lottery_type);
        //p(['historyKjData'=>$historyKjData]);

        ####################
        $tmpNotWhere = ['AND'];
        $hf = substr(array_sum([$historyKjData['code1'], $historyKjData['code2'], $historyKjData['code3'], $historyKjData['code4']]), -1);
        $hfs = [$hf, $hf+10, $hf+20, $hf+30];
        $tmpNotWhere[] = ['IN', '(`code_1`+`code_2`+`code_3`+`code_4`)', $hfs];
        $tmpNotWhere[] = ['=', 'type_2', 1];
        ####################

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => 4])
            ->andWhere(['NOT', $tmpNotWhere]);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤昨日同期/上期每两个号码及对数', ['date_num'=>$date_num,'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'sql'=>$sql]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        #p(['count'=>count($codes), 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 胆码2跨1-2个(四定)# 0的2跨是2、1的2跨就只是3、8的2跨是6、9的2跨是7
     * @param object $plan
     * @param int $kd 跨度
     * @param int $kdNumType 跨度数量  1:1-2各
     * @return array
     */
    public static function getBeforeKjCodesDynamic119(object $plan, int $kd=2, int $kdNumType=1): array
    {
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);
        $kjCodes = [$historyKjData['code1'], $historyKjData['code2'], $historyKjData['code3'], $historyKjData['code4']];
        $kdCodes = NumService::getKuduCodes($kjCodes, $kd);
        //p([$kjCodes, $kdCodes, $kd]);
        $kdWhere = ['OR'];
        # 跨度x，出现1个情况：
        $kdWhere[] = ['AND', ['IN', 'code_1', $kdCodes], ['NOT IN', 'code_2', $kdCodes], ['NOT IN', 'code_3', $kdCodes], ['NOT IN', 'code_4', $kdCodes]];
        $kdWhere[] = ['AND', ['IN', 'code_2', $kdCodes], ['NOT IN', 'code_1', $kdCodes], ['NOT IN', 'code_3', $kdCodes], ['NOT IN', 'code_4', $kdCodes]];
        $kdWhere[] = ['AND', ['IN', 'code_3', $kdCodes], ['NOT IN', 'code_1', $kdCodes], ['NOT IN', 'code_2', $kdCodes], ['NOT IN', 'code_4', $kdCodes]];
        $kdWhere[] = ['AND', ['IN', 'code_4', $kdCodes], ['NOT IN', 'code_1', $kdCodes], ['NOT IN', 'code_2', $kdCodes], ['NOT IN', 'code_3', $kdCodes]];
        # 跨度x，出现2个情况：
        $kdWhere[] = ['AND', ['IN', 'code_1', $kdCodes], ['IN', 'code_2', $kdCodes], ['NOT IN', 'code_3', $kdCodes], ['NOT IN', 'code_4', $kdCodes]];
        $kdWhere[] = ['AND', ['IN', 'code_1', $kdCodes], ['IN', 'code_3', $kdCodes], ['NOT IN', 'code_2', $kdCodes], ['NOT IN', 'code_4', $kdCodes]];
        $kdWhere[] = ['AND', ['IN', 'code_1', $kdCodes], ['IN', 'code_4', $kdCodes], ['NOT IN', 'code_2', $kdCodes], ['NOT IN', 'code_3', $kdCodes]];
        $kdWhere[] = ['AND', ['IN', 'code_2', $kdCodes], ['IN', 'code_3', $kdCodes], ['NOT IN', 'code_1', $kdCodes], ['NOT IN', 'code_4', $kdCodes]];
        $kdWhere[] = ['AND', ['IN', 'code_2', $kdCodes], ['IN', 'code_4', $kdCodes], ['NOT IN', 'code_1', $kdCodes], ['NOT IN', 'code_3', $kdCodes]];
        $kdWhere[] = ['AND', ['IN', 'code_3', $kdCodes], ['IN', 'code_4', $kdCodes], ['NOT IN', 'code_1', $kdCodes], ['NOT IN', 'code_2', $kdCodes]];

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => 4])
            ->andWhere($kdWhere);
        #$sql = $query->createCommand()->getRawSql();p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '跨度'.$kd.'_'.$kdNumType, ['lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        #p(['count'=>count($codes), 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 随机对数1对、合分9个(四定)
     * @param object $plan
     * @param int $filter_type 随机类型0：对数+合分、1：对数、2：合分
     * @return array
     */
    public static function getBeforeKjCodesDynamic120(object $plan, int $filter_type=0): array
    {
        $lottery_type = $plan->lottery_type;
        list($currentKjQihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        $hzArr = Json::decode($plan->hz_Arr);
        # 随机内容添加："log_sel":1,"log_1":"05","fixed_pos_hefen_sel":2,"hefen_pos1":"1,2,3","hefen1":"012356789"
        //p($hzArr, 0);
        $betDesc = '随机过滤';
        list($log1, $hefen1) = NumCodeService::getRandCode($plan->id, $currentKjQihao, $filter_type);
        if($filter_type==1){
            //$log1 = ['05', '16', '27', '38', '49'][rand(0,4)];
            $betDesc .= '对数：'.$log1;
            $hzArr = array_merge((array)$hzArr, [
                'log_sel' => 1,
                'log_1' => $log1,
                'fixed_pos_hefen_sel' => 2,
            ]);
        }elseif ($filter_type==2){
            //$hefen1 = str_replace(rand(0, 9), '', '0123456789');
            $betDesc .= '合分：'.$hefen1;
            $hzArr = array_merge((array)$hzArr, [
                'fixed_pos_hefen_sel' => 2,
                'hefen_pos1' => '1,2,3',
                'hefen1' => $hefen1,
            ]);
        }elseif (in_array($filter_type, [3, 4])){
            $betDesc .= '两数同上：'.$log1;
            $hzArr = array_merge((array)$hzArr, [
                'log_sel' => 1,
                'log_1' => $log1,
                'fixed_pos_hefen_sel' => 2,
            ]);
        }else{
            //$log1 = ['05', '16', '27', '38', '49'][rand(0,4)];
            //$hefen1 = str_replace(rand(0, 9), '', '0123456789');
            $betDesc .= '对数：'.$log1;
            $betDesc .= '&合分：'.$hefen1;
            $hzArr = array_merge((array)$hzArr, [
                'log_sel' => 1,
                'log_1' => $log1,
                'fixed_pos_hefen_sel' => 2,
                'hefen_pos1' => '1,2,3',
                'hefen1' => $hefen1,
            ]);
        }
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '随机对数或合分', ['plan_id'=>$plan->id, 'qihao'=>$currentKjQihao, 'hzArr'=>$hzArr, 'filter_type'=>$filter_type, 'betDesc'=>$betDesc]);
        # {"ps_sel":2,"ps_2":"34689","ps_3":"01257","log_sel":1,"log_1":"05","fixed_pos_hefen_sel":2,"hefen_pos1":"1,2,3","hefen1":"012356789","arise_in_sel":2,"arise_in":"02356","filters":{"playway":"3","start_qihao":"20231226281","lottery_type":"8"}}
        $codes = NumService::getCodesKuaiXuan($hzArr, $plan->playway+1);

        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述
        #p(['count'=>count($codes), 'historyKjData'=>$historyKjData, 'codes'=>$codes]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 配数单双互排除及该位置号码
     * @param object $plan
     * @param array $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic121(object $plan, array $positions=[]): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        $hzArr = Json::decode($plan->hz_Arr, true);
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);

        $historyWhere = ['AND', ['=', 'lottery_type', $lottery_type], ['=', 'qihao', $current_kj_qihao]];
        $historyKjDataQuery = SscKjData::find()->select(['code1', 'code2', 'code3', 'code4', 'code5', 'code_str', 'qihao'])
            ->where($historyWhere)->limit(1)->orderBy(['id'=>SORT_DESC]);
        $sql = $historyKjDataQuery->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤上期每两个号码及对数', ['positions'=>$positions, 'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'sql'=>$sql]);
        $historyKjData = $historyKjDataQuery->asArray()->one();
        $p1 = implode('', NumService::getDsTypeFanByCode($historyKjData['code1'])).$historyKjData['code1'];
        $p2 = implode('', NumService::getDsTypeFanByCode($historyKjData['code2'])).$historyKjData['code2'];
        $p3 = implode('', NumService::getDsTypeFanByCode($historyKjData['code3'])).$historyKjData['code3'];
        $p4 = implode('', NumService::getDsTypeFanByCode($historyKjData['code4'])).$historyKjData['code4'];

        $hzArr = array_merge((array)$hzArr, [
            'fixed_pos_sel' => 1,
            'p1' => $p1,
            'p2' => $p2,
            'p3' => $p3,
            'p4' => $p4
        ]);

        $codes = NumService::getCodesKuaiXuan($hzArr);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤位置+其它位置合分是该位置的', ['positions'=>$positions,  'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'historyKjData'=>$historyKjData, 'sql'=>$sql]);
        //p(['count'=>count($codes)]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 123路配数-除-X位定
     * @param object $plan
     * @param array $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic122(object $plan, array $positions=[]): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        $hzArr = Json::decode($plan->hz_Arr, true);
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $p1 = implode('', NumService::getCodeLine1($historyKjData['code1']));
        $p2 = implode('', NumService::getCodeLine1($historyKjData['code2']));
        $p3 = implode('', NumService::getCodeLine1($historyKjData['code3']));
        $p4 = implode('', NumService::getCodeLine1($historyKjData['code4']));
        //p([$p1, $p2, $p3, $p4]);
        $psData = [1=>$p1, 2=>$p2, 3=>$p3, 4=>$p4];
        foreach ($positions as $position){
            unset($psData[$position]);
        }

        $hzArr = array_merge((array)$hzArr, [
            'ps_sel' => NumService::PEI_SHU_EXCLUDE,
            #'ps_1' => $p1,
            #'ps_2' => $p2,
            #'ps_3' => $p3,
            'fixed_sel_pos' => implode(',', $positions),
        ]);
        if($playway == 3){
            # 四定
            for ($i=1; $i<=4; $i++){
                $hzArr['ps_'.$i] = array_shift($psData);
            }
        }else{
            for ($i=1; $i<=(4-count($positions)); $i++){
                $hzArr['ps_'.$i] = array_shift($psData);
            }
        }
        //p($hzArr);

        $codes = NumService::getCodesKuaiXuan($hzArr, (int)($playway+1));
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤位置+其它位置合分是该位置的', ['positions'=>$positions,  'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'historyKjData'=>$historyKjData, 'hzArr'=>$hzArr, 'counts'=>count($codes)]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 两数合分-除-上期千百、千十...位置
     * @param object $plan
     * @param array $positions
     * @return array
     */
    public static function getBeforeKjCodesDynamic123(object $plan, array $positions=[1,2]): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        $hzArr = Json::decode($plan->hz_Arr, true);
        list($current_kj_qihao, $next_qihao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($current_kj_qihao, $lottery_type);

        $p1 = implode('', NumService::getCodeLine1($historyKjData['code1']));
        $p2 = implode('', NumService::getCodeLine1($historyKjData['code2']));
        $p3 = implode('', NumService::getCodeLine1($historyKjData['code3']));
        $p4 = implode('', NumService::getCodeLine1($historyKjData['code4']));
        //p([$line1Codes, $line2Codes , $line3Codes , $line4Codes , $p1, $p2, $p3, $p4]);

        $hzArr = array_merge((array)$hzArr, [
            'ps_sel' => NumService::PEI_SHU_EXCLUDE,
            'ps_1' => $p1,
            'ps_2' => $p2,
            'ps_3' => $p3,
            'fixed_sel_pos' => implode(',', $positions),
        ]);

        $codes = NumService::getCodesKuaiXuan($hzArr, (int)($playway+1));
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤位置+其它位置合分是该位置的', ['positions'=>$positions,  'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'historyKjData'=>$historyKjData, 'hzArr'=>$hzArr]);

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤两个位置的冷码
     * @param object $plan
     * @param int[] $positions
     * @param int $type 1冷码2遗漏
     * @return array
     */
    public static function getBeforeKjCodesDynamic124(object $plan, array $positions=[1,2], int $type=1): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        if($type == NumCodeService::CODE_LR_TYPE_YL){
            $Ssc1numsYl1 = Ssc1numsYl::find()->where(['position'=>$positions[0]])->asArray()->orderBy(['today_current'=>SORT_ASC])->limit(2)->all();
            $latelyCode1Other = array_column($Ssc1numsYl1, 'code');

            $Ssc1numsYl2 = Ssc1numsYl::find()->where(['position'=>$positions[1]])->asArray()->orderBy(['today_current'=>SORT_ASC])->limit(2)->all();
            $latelyCode2Other = array_column($Ssc1numsYl2, 'code');
            //p(['Ssc1numsYl1'=>$Ssc1numsYl1, 'Ssc1numsYl2'=>$Ssc1numsYl2]);
        }else{
            $latelyCode1 = NumService::getPosLatelyCode($positions[0], $num=8, $lottery_type); # 最近8个热码
            $latelyCode2 = NumService::getPosLatelyCode($positions[1], $num=8, $lottery_type); # 最近8个热码

            $latelyCode1Other = array_values(array_diff(NumService::$ALL_CODES, $latelyCode1));
            $latelyCode2Other = array_values(array_diff(NumService::$ALL_CODES, $latelyCode2));
        }

        $filterCodes = [$latelyCode1Other[0], $latelyCode2Other[0], $latelyCode1Other[1], $latelyCode2Other[1]];
        $filterCodes = array_values(array_unique($filterCodes));
        $filterCodes = [$filterCodes[0], $filterCodes[1]];

        //p([\backend\service\NumService::$ALL_CODES, $latelyCode,  $filterCodes]);
        $andWhere = ['AND'];
        foreach ($positions as $position){
            $andWhere[] = ['NOT IN', 'code_'.$position, array_merge($filterCodes, ['X'])];
            if($position==5){
                $playway = 4; # 五位code_type=5
            }
        }
        //p([$positions, $andWhere]);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where($andWhere)
            ->andWhere(['=', 'code_type', $playway+1]);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        $NumTypes = $query->asArray()->all();
        //p([$NumTypes, $playway]);

        $codes = ArrayHelper::getColumn($NumTypes, 'code');
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '过滤某两个位置各一个冷码', ['lottery_type'=>$lottery_type, 'qiHao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'filterCodes'=>$filterCodes, 'sql'=>$sql, 'count'=>count($codes)]);

        $betDesc = implode('', $positions)."位置过滤冷码:".implode('', $filterCodes);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤x位最近n个冷码
     * @param object $plan
     * @param int $pos
     * @param $cNum - 冷吗个数
     * @return array
     */
    public static function getBeforeKjCodesDynamic125(object $plan, $pos=1, $cNum=1){
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;

        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $num = 10 - $cNum;
        $latelyCode = NumService::getPosLatelyCode($pos, $num, $lottery_type); # 最近9个热码
        $filterCode = array_diff(\backend\service\NumService::$ALL_CODES, $latelyCode); # cNum个冷码
        #p([\backend\service\NumService::$ALL_CODES, $latelyCode,  $filterCodes]);

        $pos_field = 'code_'.$pos;
        $query = Num4Type::find()->alias('n')->select(['code', $pos_field, 'code_type'])
            ->where(['IN', 'n.'.$pos_field, $filterCode])
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $sql = $query->createCommand()->getRawSql();//p($sql, 0);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤单双、大小+双重', ['pos'=>$pos, 'lottery_type'=>$lottery_type, 'qihao'=>$current_kj_qihao, 'plan_id'=>$plan->id, 'latelyCode'=>$latelyCode, 'filterCode'=>$filterCode, 'sql'=>$sql]);
        $filterCodes = ArrayHelper::getColumn($NumTypes, 'code');
        #p(count($filterCodes));

        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['NOT IN', 'code', $filterCodes]);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        $NumTypes = $query->asArray()->all();
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤x位n个冷码', ['plan_id'=>$plan->id, 'current_kj_qihao'=>$current_kj_qihao, 'lottery_type'=>$lottery_type, 'sql'=>$sql, 'count'=>count($NumTypes)]);
        #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        $betDesc = "{$pos}位过滤最近{$cNum}个冷码:".implode('', $filterCode);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - # 过滤[千百十、千百个...]位号码合分(四定)
     * @param object $plan
     * @param int[] $positions
     * @dateNums int 天数
     * @return array
     */
    public static function getBeforeKjCodesDynamic126(object $plan, array $positions=[1, 2, 3]): array
    {
        $lottery_type = $plan->lottery_type;

        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);

        $sumHz = [];
        foreach ($positions as $position){
            $sumHz[] = $historyKjData['code'.$position];
        }
        $hz = (string)array_sum($sumHz);

        $filterNum = (int)($hz[strlen($hz)-1]);
        $filterNums = [$filterNum, $filterNum+10, $filterNum+20, $filterNum+30];
        //p([$sumHz, $historyKjData, $hz, $hz[strlen($hz)-1], strlen($hz), $filterNum]);
        $playway = $plan->playway;

        $otherHf = '(code_'.implode('+code_', $positions).')';
        $notWhere = ['NOT', ['IN', $otherHf, $filterNums]]; # '(`code_1`+`code_2`+`code_3`+`code_4`)'
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway+1])
            ->andWhere($notWhere);
        $difference = array_diff([1,2,3,4], $positions);
        if($playway != 3 && !empty($difference)){
            foreach ($difference as $diffPos){
                $query->andWhere(['=', 'code_'.$diffPos, 'X']);
            }
        }
        //$sql = $query->createCommand()->getRawSql();p($sql);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = "过滤[".implode('', $positions)."]位号码:".implode(',', $sumHz)."合分".$filterNum."(四定)";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - # 取上期号码4个码(不重复)必须上1个
     * @param object $plan
     * @param int $qiHaoType 1上期2上上期
     * @param int $cNum
     * @return array
     */
    public static function getBeforeKjCodesDynamic127(object $plan, $qiHaoType=1, $cNum=1): array
    {
        $lottery_type = $plan->lottery_type;

        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        if($qiHaoType == 2){
            # 上上期
            $currentKjQiHao = KjDataGet::getBeforeQiHaoByQiHao($currentKjQiHao, $lottery_type);
        }
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);

        $qiHaoTypeDesc = ($qiHaoType==2) ? '取上上期' : '取上期';
        $fourCodes = array_unique([$historyKjData['code1'], $historyKjData['code2'], $historyKjData['code3'], $historyKjData['code4']]);
        if(count($fourCodes)<4){
            $fourCodes[] = $historyKjData['code5'];
            $fourCodes = array_unique($fourCodes);
        }
        if(count($fourCodes)<4){
            Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', $qiHaoTypeDesc.'号码4个码上1个', ['plan_id'=>$plan->id, 'current_kj_qihao'=>$currentKjQiHao, 'lottery_type'=>$lottery_type, 'fourCodes'=>$fourCodes]);
            throw_info('号码数量不足4个不下注');
        }
        //p([$historyKjData, $fourCodes]);

        $notWhere = [
            'OR',
            ['IN', 'code_1', $fourCodes],
            ['IN', 'code_2', $fourCodes],
            ['IN', 'code_3', $fourCodes],
            ['IN', 'code_4', $fourCodes],
        ];
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => 4])
            ->andWhere($notWhere);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '取上期号码4个码上1个', ['plan_id'=>$plan->id, 'current_kj_qihao'=>$currentKjQiHao, 'lottery_type'=>$lottery_type, 'fourCodes'=>$fourCodes, 'sql'=>$sql]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $qiHaoTypeDesc."号码4个码(".implode('', $fourCodes).")上{$cNum}个";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 添加计划动态过滤描述
     * @param $planId
     * @param $qiHao
     * @param $desc
     * @return mixed
     */
    public static function addBetDescRand($planId=0, $qiHao='', $desc='')
    {
        try {
            if(empty($desc)){
                return false;
            }
            $mKey = CacheKeyService::getBetRandDescKey($planId, $qiHao);

            $r = commonRedis()->sadd($mKey, $desc);
            commonRedis()->expire($mKey, 300);
        }catch (\Exception $e){
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '添加描述异常', ['planId'=>$planId, 'qihao'=>$qiHao, 'desc'=>$desc, 'err_msg'=>$e->getMessage()]);
        }

        return $r;
    }

    /**
     * 获取计划动态过滤描述
     * @param int $planId
     * @param string $qiHao
     * @param int $flag 返回1拼接2原集合成员
     * @return mixed
     */
    public static function getRandBetDesc($planId=0, $qiHao='', $flag=1)
    {
        $str = '';
        try {
            $mKey = CacheKeyService::getBetRandDescKey($planId, $qiHao);
            $members = commonRedis()->smembers($mKey);

            $str = implode('; ', array_unique($members));
        }catch (\Exception $e){}

        return $flag?$str:$members;
    }

    /**
     * 随机合分、对数
     * @param $planId
     * @param $qiHao
     * @param $filterType
     * @return array|mixed
     */
    public static function getRandCode($planId, $qiHao, $filterType)
    {
        $mKey = CacheKeyService::getRangeCode($planId, $qiHao, $filterType);
        if(!$randData = commonRedis()->get($mKey)){
            if(in_array($filterType, [3, 4])){
                $randNumbers = self::getRandNumGroups();

                $k = CacheKeyService::getRangeCodeKey($planId, $qiHao);
                $newLogData = commonRedis()->get($k);
                if(empty($newLogData)){
                    $rnt = rand(0, 4);
                    $log = $randNumbers[$rnt];
                    unset($randNumbers[$rnt]);
                    $newLogData = $randNumbers;
                    commonRedis()->setex($k, 300, $newLogData);
                }else{
                    $log = end($newLogData);
                }
            }else{
                $log = ['05', '16', '27', '38', '49'][rand(0,4)];
            }
            $heFen = str_replace(rand(0, 9), '', '0123456789');
            $randData = [$log, $heFen];

            commonRedis()->setex($mKey,300, $randData);
        }

        return $randData;
    }

    /**
     * 随机两位号码
     * @param bool $isRand
     * @return string[]
     */
    public static function getRandNumGroups(bool $isRand=false): array
    {
        $randNumbers = [];
        if($isRand){
            $numbers = range(0, 9); // 创建包含 0 到 9 的数组
            shuffle($numbers); // 打乱数组顺序

            $groups = array_chunk($numbers, 2); // 将数组分成五个组，每组包含5个数字
            foreach ($groups as $group){
                $randNumbers[] = implode('', $group);
            }
        }else{
            $groupsData = [
                ['01','02','03','04','06','07','08','09'],
                ['12','13','14','15','17','18','19'],
                ['23','24','25','26','28','29'],
                ['34','35','36','37','39'],
                ['45','46','47','48'],
                ['56','57','58','59'],
                ['67','68','69'],
                ['78','79'],
                ['89']
            ];
            foreach ($groupsData as $groupsDatum){
                $randNumbers[] = $groupsDatum[rand(0, count($groupsDatum)-1)];
            }
        }

        return $randNumbers;
    }
}
