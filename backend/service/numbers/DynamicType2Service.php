<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\service\BaseService;
use backend\service\NumService;
use common\helpers\Code;
use common\helpers\LotteryType;
use common\service\ssc\QihaoService;
use common\service\ssc\SscKjDataService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class DynamicType2Service extends BaseService {

    # 两合上1
    public static function filter1(object $plan, $dynamic=[]): array
    {
        $lottery_type = $plan->lottery_type;

        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        //$historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        $params = $dynamic['params'];
        $x = $params['x'];

        $hCodeArr = Code::codeStringToArray($x); # 合分数组
        $hfArr = [];
        foreach ($hCodeArr as $filterNum){
            $hfArr = array_merge($hfArr, [$filterNum, $filterNum+10, $filterNum+20, $filterNum+30]);
        }
        //p([$params, $historyKjData, $hCode, $hCodeArr, $hfArr]);
        $where = ['OR'];

        # 两数合
        $orWhere1 = ['OR'];
        foreach (NumService::TWO_NUM_POS as $poss){
            $orWhere1[] = ['IN', "(`code_".$poss[0]."` + `code_".$poss[1]."`)", $hfArr];
        }
        $where[] = $orWhere1;

        $orWhere2 = [
            'OR',
            ['IN', "code_1", $hCodeArr],
            ['IN', "code_2", $hCodeArr],
            ['IN', "code_3", $hCodeArr],
            ['IN', "code_4", $hCodeArr],
        ];
        $where[] = $orWhere2;

        $playway = $plan->playway;

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway+1])
            ->andWhere($where);

        //$sql = $query->createCommand()->getRawSql();p($sql);
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = "两合上1[".$x."]号码:"."合".implode(',', $hfArr)."(四定)";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    # 过滤近x天直码
    public static function filter2(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        //$historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        $params = $dynamic['params'];
        $x = $params['x'];
        $filterField = 'code_4n_str';
        if($playway==1){
            $filterField = [
                'CONCAT(`code1`, ",", `code2`, ",X,X") c1',
                'CONCAT(`code1`, ",X,", `code3`, ",X") c2',
                'CONCAT(`code1`, ",X,X", ",", `code4`) c3',
                'CONCAT("X,", `code2`, ",", `code3`, ",X") c4',
                'CONCAT("X,", `code2`, ",X,", `code4`) c5',
                'CONCAT("X,X,", `code3`, ",", `code4`) c6',
            ];
        }elseif($playway==2){
            $filterField = [
                'CONCAT(`code1`, ",", `code2`, ",", `code3`, ",X") c1',
                'CONCAT(`code1`, ",", `code2`, ",X,", `code4`) c2',
                'CONCAT(`code1`, ",X,", `code3`, ",", `code4`) c3',
                'CONCAT("X,", `code2`, ",", `code3`, ",", `code4`) c4',
            ];
        }
        $filterCodesQuery = SscKjData::find()->select($filterField)->where(['>=', 'created_at', (time()-$x*86400)])->andWhere(['lottery_type'=>$lottery_type]);
        //p($filterCodesQuery->createCommand()->getRawSql());
        $filterCodes = [];
        if($playway == 1){
            $filterCodesData = $filterCodesQuery->asArray()->all();
            $filterCodes = array_merge($filterCodes, array_column($filterCodesData, 'c1'));
            $filterCodes = array_merge($filterCodes, array_column($filterCodesData, 'c2'));
            $filterCodes = array_merge($filterCodes, array_column($filterCodesData, 'c3'));
            $filterCodes = array_merge($filterCodes, array_column($filterCodesData, 'c4'));
        }elseif($playway == 2){
            $filterCodesData = $filterCodesQuery->asArray()->all();
            $filterCodes = array_merge($filterCodes, array_column($filterCodesData, 'c1'));
            $filterCodes = array_merge($filterCodes, array_column($filterCodesData, 'c2'));
            $filterCodes = array_merge($filterCodes, array_column($filterCodesData, 'c3'));
            $filterCodes = array_merge($filterCodes, array_column($filterCodesData, 'c4'));
        }else{
            $filterCodes = $filterCodesQuery->column();
        }
        $where = ['NOT IN', 'code', $filterCodes];

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway+1])
            ->andWhere($where);

        //$sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '过滤两个位置一样的所有号码', [
            'plan_id'=>$plan->id,
            'lottery_type'=>$lottery_type,
            'current_kj_qihao'=>$currentKjQiHao,
            //'sql'=>$sql
        ]);
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc'].":过滤近".$x."天直码";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述
        //p([count($codes)]);

        return $codes;
    }

    # x(1234)位近y个码最多上z个
    public static function filter3(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lotteryType = $plan->lottery_type;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        //$historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        $params = $dynamic['params'];
        $x = $params['x']; # 位置
        $y = $params['y']; # 号码数量
        $z = $params['z']; # 最多上奖数量
        $filterCodes = SscKjDataService::getRecentlyPosCodes($lotteryType, $positions=[$x], $y);

        $codes = DynamicType2Service::getRecordsByCodes($filterCodes, $z);
        //p([$x.'位filterCodes'=>$filterCodes, '最多'=>$z.'个', 'count'=>count($codes)]);
        $betDesc = $filterDesc['desc']."过滤第".$x."位最近".$y."个码:".implode(',', $filterCodes)."最多上奖".$z."个(四定)";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    # 定x位号码y最少上z个
    public static function filter4(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lotteryType = $plan->lottery_type;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType);

        //$historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        $params = $dynamic['params'];
        $x = trim($params['x']); # x位置
        $y = trim($params['y']); # 号码：258
        $z = (int)trim($params['z']); # 最多上奖数量
        $xPos = str_split($x);
        $xCodes = str_split($y);
        //p(['xPos'=>$xPos, 'xCodes'=>$xCodes]);
        // 开奖号码
        $fourCodes = [1=>$historyKjData['code1'], 2=>$historyKjData['code2'], 3=>$historyKjData['code3'], 4=>$historyKjData['code4']];
        $fixedCodes = [];
        foreach ($xPos as $po){
            $fixedCodes[] = $fourCodes[$po];
        }
        $n = 0; // 开奖号码对应147、258、369 上的个数
        foreach ($fixedCodes as $xc){
            if(in_array($xc, $xCodes)){
                $n += 1;
            }
        }
        if($n<2){
            Tool_Common::log('/data/'.__FUNCTION__, "ERR", '147、258、369本期上2个则下期至少上1个', ['plan_id'=>$plan->id, 'params'=>$params, 'code_str'=>$historyKjData['code_str'], 'fixedCodes'=>$fixedCodes, 'n'=>$n]);
            throw_info('不满足条件不投');
        }

        $lottery_type = $plan->lottery_type;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        //p([$historyKjData, $fourCodes]);
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type');
        $query->where(['code_type' => ($plan->playway+1)]);
        $where = ['OR'];
        if($n>=2 && (strlen($x) == 4 OR $plan->playway == 3)){
            # 四定
            $where[] = ['IN', 'code_1', $xCodes];
            $where[] = ['IN', 'code_2', $xCodes];
            $where[] = ['IN', 'code_3', $xCodes];
            $where[] = ['IN', 'code_4', $xCodes];
        }else{
            $diff = array_diff([1,2,3,4], $xPos);
            $currentPos = current($diff);
            $query->andWhere(['=', 'code_'.$currentPos, 'X']);
            if($n>=2){
                foreach ($xPos as $xp){
                    $where[] = ['IN', 'code_'.$xp, $xCodes];
                }
            }
        }

        $query->andWhere($where);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '定x位y码至少上1个', ['plan_id'=>$plan->id, 'current_kj_qihao'=>$currentKjQiHao, 'lottery_type'=>$lottery_type, 'fourCodes'=>$fourCodes, 'sql'=>$sql, 'n'=>$n]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc']."定".$x."位号码".$y.",下期".$x."位至少上".$z."个";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 前x期开过的号码全转
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter5(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($current_kj_qihao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $params = $dynamic['params'];
        $num = trim($params['x']); # x位置
        $positions_str_4 = 'code'.implode(',code', $positions=[1,2,3,4]);
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

        $betDesc = $filterDesc['desc'].'过滤前'.$num.'期开过的号码全转';
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 去除上x期同位置 9 * 9 * 9 * 9 = 81 * 81 = 6561 组
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter6(object $plan, $dynamic=[], $filterDesc = []){
        $playway = $plan->playway;
        $lotteryType = $plan->lottery_type;

        $params = $dynamic['params'];
        $x = trim($params['x']); # x期数
        if(!empty($params['y'])){
            $x .= '-'.trim($params['y']);
        }
        $qiNums = explode('-', $x);
        $codes = [];
        $desc = '';
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);
        foreach ($qiNums as $qiNum){
            $currentKjQiHao = LotteryType::getBeforeNQiHao($currentKjQiHao, $qiNum);
            $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType);

            $query = Num4Type::find()->select(['code'])
                ->where(['AND', ['!=', 'code_1', $historyKjData['code1']], ['!=', 'code_2', $historyKjData['code2']], ['!=', 'code_3', $historyKjData['code3']], ['!=', 'code_4', $historyKjData['code4']]])
                ->andWhere(['=', 'code_type', $playway+1]);
            $sql = $query->createCommand()->getRawSql();
            #p($sql);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '去除上期同位置6561组', ['current_kj_qihao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType, 'historyKjData'=>$historyKjData, 'sql'=>$sql]);
            $NumTypes = $query->asArray()->all();
            #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
            $tmpCodes = ArrayHelper::getColumn($NumTypes, 'code');
            if(empty($codes)){
                $codes = $tmpCodes;
            }
            $codes = array_merge($codes, $tmpCodes);
            $codes = array_unique($codes);

            $desc .= "去除上".$qiNum."期同位置号码：千!={$historyKjData['code1']}百!={$historyKjData['code2']}十!={$historyKjData['code3']}个!={$historyKjData['code4']}";
        }
        $betDesc = $filterDesc['desc'].'：'.$desc;
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 过滤最近x期开过号码全转
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter7(object $plan, $dynamic=[], $filterDesc = []){
        $playway = $plan->playway;
        $lotteryType = $plan->lottery_type;

        $params = $dynamic['params'];
        $qiNum = trim($params['x']); # x位置
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);
        //$historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType);
        $positions = [];
        $allPos = NumService::$ALL_POSES;
        $whereQuery = [];
        if($playway != 3){
            $codeHz = Json::decode($plan->hz_Arr);
            $fixedSelPos = explode(',', $codeHz['fixed_sel_pos']); // 定位位置
            $whereQuery[] = 'AND';
            foreach ($allPos as $k=>$pos){
                if(in_array($pos, $fixedSelPos)){
                    $whereQuery[] = ['=', 'code_'.$pos, 'X'];
                    unset($allPos[$k]);
                }
            }
            $allPos = array_values($allPos);
            $len = count($allPos);
            if($len == 2){
                $positions = [
                    [$allPos[0], $allPos[1]],
                    [$allPos[1], $allPos[0]],
                ];
            }elseif ($len == 3){
                $positions = [
                    [$allPos[0], $allPos[1], $allPos[2]],
                    [$allPos[0], $allPos[2], $allPos[1]],
                    [$allPos[1], $allPos[0], $allPos[2]],
                    [$allPos[1], $allPos[2], $allPos[0]],
                    [$allPos[2], $allPos[0], $allPos[1]],
                    [$allPos[2], $allPos[1], $allPos[0]],
                ];
            }else{
                throw_info('过滤号码异常');
            }
            //p(['allPos'=>$allPos, 'fixedSelPos'=>$fixedSelPos]);
        }else{
            $positions[] = $allPos;
        }
        $selects = [];
        foreach ($positions as $pos){
            //$selects[] = 'CONCAT('.'code'.implode(',","'.',code', $pos).')'; // 含逗号
            $selects[] = 'CONCAT('.'code'.implode(',code', $pos).')';
        }

        $needCodesQuery = SscKjData::find()->select($selects)
            ->where(['lottery_type'=>$lotteryType])
            ->andWhere(['<=', 'qihao', $currentKjQiHao])
            ->orderBy(['id'=>SORT_DESC])->limit($qiNum);
        $sql = $needCodesQuery->createCommand()->getRawSql();//p($sql);
        $needCodes = $needCodesQuery->asArray()->all();
        $filterCodes = []; // 过滤全倒的号码
        foreach ($needCodes as $needCode){
            $filterCodes = array_merge($filterCodes, array_values($needCode));
        }
        $filterCodesStr = implode('","', $filterCodes);
        //p(['needCodes'=>$needCodes, 'filterCodes'=>$filterCodes]);

        $query = Num4Type::find()->alias('n')->select(['code', 'code_type'])
            ->where('CONCAT(`code_'.implode('`,`code_', $allPos).'`) NOT IN("'.$filterCodesStr.'")')
            ->andWhere(['=', 'code_type', $playway+1]);
        if(!empty($whereQuery)){
            $query->andwhere($whereQuery);
        }
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '过滤最近x期开过号码全转', ['plan_id'=>$plan->id, 'currentKjQiHao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType, 'filterDesc'=>$filterDesc, 'sql'=>$sql]);
        //$sql = $query->createCommand()->getRawSql();p($sql);
        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');
        //p(count($codes));

        $betDesc = $filterDesc['desc'].'：过滤前'.$qiNum.'期开过的号码全转';
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }



    /**
     * @param array $nCode
     * @param int $cNum
     * @param int $playWay
     * @return array
     */
    public static function getRecordsByCodes(array $nCode = [], int $cNum = 2, int $playWay=3): array // 默认最多2个号码
    {
        $filterNumKjCodes = $nCode;
        $query = Num4Type::find()->where(['=', 'code_type', $playWay+1]);
        if($cNum == 2) {
            # 排除上三个和上四个情况
            $whereFilterKjCodes = [
                'OR',
                # 三个
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_1<>code_2 and code_1<>code_3 and code_2<>code_3'],
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_2 and code_1<>code_4 and code_2<>code_4'],
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_3 and code_1<>code_4 and code_3<>code_4'],
                ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_2<>code_3 and code_2<>code_4 and code_3<>code_4'],
                # 四个
                ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_2 and code_1<>code_3 and code_1<>code_4 and code_2<>code_3 and code_2<>code_4 and code_3<>code_4'],
            ];
        }elseif ($cNum == 3){
            # 排除四个的情况
            $whereFilterKjCodes = [
                'AND',
                ['IN', 'code_1', $filterNumKjCodes],
                ['IN', 'code_2', $filterNumKjCodes],
                ['IN', 'code_3', $filterNumKjCodes],
                ['IN', 'code_4', $filterNumKjCodes],
                'code_1<>code_2 and code_1<>code_3 and code_1<>code_4 and code_2<>code_3 and code_2<>code_4 and code_3<>code_4',
            ];
        }elseif ($cNum == 1){
            # 排除上两个、三个、四个的情况
            if(false){
                # 双重算一个
                $whereFilterKjCodes = [
                    'OR',
                    # 两个
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], 'code_1<>code_2'],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_1<>code_3'],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_4'],
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_2<>code_3'],
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_2<>code_4'],
                    ['AND', ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_3<>code_4'],
                    # 三个
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], 'code_1<>code_2 and code_1<>code_3 and code_2<>code_3'],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_2 and code_1<>code_4 and code_2<>code_4'],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_3 and code_1<>code_4 and code_3<>code_4'],
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_2<>code_3 and code_2<>code_4 and code_3<>code_4'],
                    # 四个
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes], 'code_1<>code_2 and code_1<>code_3 and code_1<>code_4 and code_2<>code_3 and code_2<>code_4 and code_3<>code_4'],
                ];
            }else{
                # 双重算一个
                $whereFilterKjCodes = [
                    'OR',
                    # 两个
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes]],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes]],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]],
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes]],
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]],
                    ['AND', ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]],
                    # 三个
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes]],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]],
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]],
                    ['AND', ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes], ['IN', 'code_4', $filterNumKjCodes]],
                    # 四个
                    ['AND', ['IN', 'code_1', $filterNumKjCodes], ['IN', 'code_2', $filterNumKjCodes], ['IN', 'code_3', $filterNumKjCodes]],
                ];
            }
        }else{
            $whereFilterKjCodes = '1=1';
        }
        //p($whereFilterKjCodes);
        $query->andWhere(['NOT', $whereFilterKjCodes]);
        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '排除前一期号码剩余号码至少上x个码', [
            'filterNumKjCodes'=>$filterNumKjCodes,
            'sql'=>$sql,
        ]);
        $NumTypes = $query->asArray()->all();
        //p(['count'=>count($NumTypes1), /*'codes2'=>$codes1,*/ 'sql'=>$sql]);

        // 执行查询，并返回结果
        return ArrayHelper::getColumn($NumTypes, 'code');
    }

    /**
     * @param array $nCode
     * @param int $n
     * @param int $playway
     * @return array
     */
    public static function getRecordsByCodesX(array $nCode = [], int $n = 2, $playway = 3): array
    {
        // 创建查询对象
        $query = new \yii\db\Query();

        // 生成符合 $nCode 的 CASE 条件
        $caseCondition = "CASE
        WHEN code_1 IN (" . implode(',', $nCode) . ") THEN code_1
        WHEN code_2 IN (" . implode(',', $nCode) . ") AND code_2 != code_1 THEN code_2
        WHEN code_3 IN (" . implode(',', $nCode) . ") AND code_3 NOT IN (code_1, code_2) THEN code_3
        WHEN code_4 IN (" . implode(',', $nCode) . ") AND code_4 NOT IN (code_1, code_2, code_3) THEN code_4
        ELSE NULL
    END";

        // 构建查询
        $query->select('*')
            ->from(Num4Type::tableName())
            ->andWhere(['=', 'code_type', $playway + 1])
            /*
            ->andWhere([
                'OR',
                ['in', 'code_1', $nCode],
                ['in', 'code_2', $nCode],
                ['in', 'code_3', $nCode],
                ['in', 'code_4', $nCode],
            ])
            */
            ->groupBy('id')
            ->having(['<=', 'COUNT(DISTINCT ' . $caseCondition . ')', $n]);

        // 输出生成的SQL语句（调试用）
        p($query->createCommand()->getRawSql());

        // 执行查询并返回结果
        return $query->all();
    }

}
