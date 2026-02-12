<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\service\BaseService;
use backend\service\NumService;
use backend\service\numbers\NumCodeService;
use common\helpers\Code;
use common\helpers\LotteryType;
use common\service\ssc\QihaoService;
use common\service\ssc\SscKjDataService;
use common\tools\KjDataGet;
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
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc'].":过滤近".$x."天直码";
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '过滤两个位置一样的所有号码', [
            'plan_id'=>$plan->id,
            'lottery_type'=>$lottery_type,
            'current_kj_qihao'=>$currentKjQiHao,
            'count' => count($codes),
            'bet_desc'=>$betDesc,
            //'sql'=>$sql
        ]);
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
        }else{
            $x .= '-'.trim($params['x']);
        }
        $qiNums = explode('-', $x);
        $codes = [];
        $desc = '';
        list($currentQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);
        for($i=$qiNums[0]; $i<=$qiNums[1]; $i++){
            $currentKjQiHao = LotteryType::getBeforeNQiHao($currentQiHao, $i);
            $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType);
            //print_r(['i'=>'::::'.$i.'_'.$currentKjQiHao, 'historyKjData'=>$historyKjData]);

            $query = Num4Type::find()->select(['code'])
                ->where(['AND', ['!=', 'code_1', $historyKjData['code1']], ['!=', 'code_2', $historyKjData['code2']], ['!=', 'code_3', $historyKjData['code3']], ['!=', 'code_4', $historyKjData['code4']]])
                ->andWhere(['=', 'code_type', $playway+1]);
            $sql = $query->createCommand()->getRawSql();
            //p($sql);
            Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '去除上期同位置6561组', ['current_kj_qihao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType, 'historyKjData'=>$historyKjData, 'sql'=>$sql]);
            $NumTypes = $query->asArray()->all();
            #p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
            $tmpCodes = ArrayHelper::getColumn($NumTypes, 'code');
            if(empty($codes)){
                $codes = $tmpCodes;
            }
            $codes = array_intersect($codes, $tmpCodes);
            $codes = array_unique($codes);

            $desc .= "去除上".$i."期同位置号码：千!={$historyKjData['code1']}百!={$historyKjData['code2']}十!={$historyKjData['code3']}个!={$historyKjData['code4']}";
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
     * 过滤类型号码 - 定位x或定位y合分为z
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter9(object $plan, $dynamic=[], $filterDesc = []){
        $lotteryType = $plan->lottery_type;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType);

        $params = $dynamic['params'];
        $pos1 = trim($params['x']); # x位置
        $pos1s = [];
        for ($i=0; $i<strlen($pos1); $i++){
            $pos1s[] = $pos1[$i];
        }
        $pos2 = trim($params['y']); # y位置
        $pos2s = [];
        for ($i=0; $i<strlen($pos2); $i++){
            $pos2s[] = $pos2[$i];
        }
        $pos3 = trim($params['k']); # k位置
        $pos3s = [];
        for ($i=0; $i<strlen($pos3); $i++){
            $pos3s[] = $pos3[$i];
        }
        $hefen = trim($params['z']); # 合分
        $filterHfs = [];
        for($i=0; $i<strlen($hefen); $i++){
            $filterHf = $hefen[$i];
            $filterHfs = array_merge($filterHfs, [$filterHf, $filterHf+10, $filterHf+20, $filterHf+30]);
        }

        $positions = [$pos1s, $pos2s, $pos3s];
        $where = ["OR"];
        foreach ($positions as $position){
            if(empty($position)) continue;
            $otherHf = '(code_'.implode('+code_', $position).')';

            $where[] = ['IN', $otherHf, $filterHfs]; # '(`code_1`+`code_2`+`code_3`+`code_4`)'
        }
        //p([$sumHz, $historyKjData, $hz, $hz[strlen($hz)-1], strlen($hz), $filterNum]);

        $playway = $plan->playway;
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway+1])
            ->andWhere($where);
        //$sql = $query->createCommand()->getRawSql();p($sql);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        $txt = $filterDesc['desc']."定位".implode('', $pos1s)."或定位".implode('', $pos2s);
        if(!empty($pos3)){
            $txt .= "或定位".implode('', $pos3s);
        }
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $txt.", 合分为:".$hefen;
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - x位过滤上上期的y位+上期z位合数
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter11(object $plan, $dynamic=[], $filterDesc = []){
        $lotteryType = $plan->lottery_type;

        $params = $dynamic['params'];
        $pos1 = trim($params['x']); # x位
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType); # 上期号码
        $history2KjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType, true); # 上上期

        $before1pos = trim($params['z']); # 上期z位
        $beforeCode = $historyKjData['code'.$before1pos];

        # 上上期
        $before2pos = trim($params['y']); # 上上期y位
        $before2Code = $history2KjData['code'.$before2pos];

        # 过滤合分
        $hf = $before2Code + $beforeCode;
        $lastCode = substr((string)$hf, -1);

        $logArr = [
            'before1pos' => $before1pos,
            'before1code' => $beforeCode,
            'historyKjData' => $historyKjData,
            'before2pos' => $before2pos,
            'before2Code' => $before2Code,
            'history2KjData' => $history2KjData,
            'hf' => $hf,
            'lastCode' => $lastCode,
        ];

        $where = ['!=', 'code_'.$pos1, $lastCode];
        $query = self::getBaseCodesQuery($where, $plan->playway);
        //$sql = $query->createCommand()->getRawSql();p($sql);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', 'x位过滤上上期的y位+上期z位合数', ['plan_id'=>$plan->id, 'currentKjQiHao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType, 'filterDesc'=>$filterDesc, 'd'=>$logArr/* 'sql'=>$sql*/]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc'].$pos1."位过滤上上期的".$before2pos."位[".$before2Code."]+上期".$before1pos."位[".$beforeCode."]合数：".$lastCode;
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }


    /**
     * 过滤类型号码 - (x位+上期y位)!=(上上期z位+上期h位 合数)
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter12(object $plan, $dynamic=[], $filterDesc = []){
        $lotteryType = $plan->lottery_type;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType); # 上期号码
        $history2KjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType, true); # 上上期

        $params = $dynamic['params'];
        //p(['currentKjQiHao'=>$currentKjQiHao, 'historyKjData'=>$historyKjData, 'history2KjData'=>$history2KjData, 'params'=>$params], 0);

        # 下期x位
        $pos1 = trim($params['x']); # x位 - 要过滤的位置

        # 上期y位
        $before1pos = trim($params['y']);
        $before1Code = $historyKjData['code'.$before1pos];

        # 相加的数
        # 上上期z位
        $before2pos = trim($params['z']); # 上上期z位
        $before2poss = str_split($before2pos);
        $pzCodes = []; // z所有位置对应号码
        foreach ($before2poss as $b2pos){
            $pzCodes[] = $history2KjData['code'.$b2pos];
        }

        # 上期h位
        $before1pos2 = trim($params['h']);
        $before1pos2s = str_split($before1pos2);
        $phCodes = [];
        foreach ($before1pos2s as $b3pos2){
            $phCodes[] = $historyKjData['code'.$b3pos2];
        }
        $p1Codes = array_merge($pzCodes, $phCodes);

        # 和值
        $hz = array_sum($p1Codes);
        $hz = ($hz>=10) ? ($hz-10) : $hz;
        # 过滤号码， 根据hz和上期的h对比大小决定
        $xFilterCode = ($hz>=$before1Code) ? ($hz-$before1Code) : ($hz+10-$before1Code);
        //p(['pzCodes'=>$pzCodes, 'phCodes'=>$phCodes, 'p1Codes'=>$p1Codes, 'hz'=>$hz, 'before1Code'=>$before1Code, 'xFilterCode'=>$xFilterCode]);

        $logArr = [
            'before1pos' => $before1pos,
            'historyKjData' => $historyKjData,
            'before2pos' => $before2pos,
            'history2KjData' => $history2KjData,
            'pzCodes'=>$pzCodes,
            'phCodes'=>$phCodes,
            'p1Codes'=>$p1Codes,
            'hz' => $hz,
            '上期y'.$before1pos.'位' => $before1Code,
            'xFilterCode' => $xFilterCode,
        ];
        //p($logArr, 0);

        $where = ['!=', 'code_'.$pos1, $xFilterCode]; # $otherHf : '(`code_1`+`code_2`+`code_3`+`code_4`)'
        $query = self::getBaseCodesQuery($where, $plan->playway);
        //$sql = $query->createCommand()->getRawSql();p($sql);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '(x位+上期y位)!=(上上期z位+上期h位 合数)', ['plan_id'=>$plan->id, 'currentKjQiHao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType, 'filterDesc'=>$filterDesc, 'd'=>$logArr/* 'sql'=>$sql*/]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc']."(".$pos1."位[过滤".$xFilterCode."]+上期".$before1pos."位:".$before1Code.")!=(上上期".$before2pos."位:".implode('',$pzCodes)."+上期h".$before1pos2."位".implode('', $phCodes).")=".$hz;
        //p($betDesc);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 定位x分别排除位置的对数
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter15(object $plan, $dynamic=[], $filterDesc = []){
        $lotteryType = $plan->lottery_type;

        $params = $dynamic['params'];
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType); # 上期号码

        $x = trim($params['x']); # 位置
        $positions = str_split($x);
        $filterCodes = [];
        foreach ($positions as $p){
            $posCode = $historyKjData['code'.$p] >4 ? ($historyKjData['code'.$p]-5) : ($historyKjData['code'.$p]+5);
            $filterCodes[$p] = $posCode;
        }

        $where = ['AND'];
        foreach ($filterCodes as $pos=>$code){
            $where[] = ['!=', 'code_'.$pos, $code];
        }
        if($plan->playway!=3){
            $xpos = array_diff(NumService::$ALL_POSES, $positions);
            foreach ($xpos as $pos1){
                $where[] = ['=', 'code_'.$pos1, 'X'];
            }
        }

        $logArr = [
            'p'=>$positions,
            'historyKjData'=>$historyKjData,
            'filterCodes'=>$filterCodes,
            'where'=>$where,
        ];
        //p($logArr, 0);

        $query = self::getBaseCodesQuery($where, $plan->playway);
        //$sql = $query->createCommand()->getRawSql();p($sql);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '定位x分别排除位置的对数', ['plan_id'=>$plan->id, 'currentKjQiHao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType, 'filterDesc'=>$filterDesc, 'd'=>$logArr/* 'sql'=>$sql*/]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc']."[上期号码：".$historyKjData['code_str']."]定位".$params['x']."对数分别排除(".implode('',$filterCodes).")";
        //p($betDesc);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 定位x排除对应位置的合分与对数值合分
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter16(object $plan, $dynamic=[], $filterDesc = []){
        $lotteryType = $plan->lottery_type;

        $params = $dynamic['params'];
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType); # 上期号码

        $x = trim($params['x']); # 位置
        $positions = str_split($x);
        $filterCodes = [];
        foreach ($positions as $p){
            $posCode = $historyKjData['code'.$p] >4 ? ($historyKjData['code'.$p]-5) : ($historyKjData['code'.$p]+5);
            $filterCodes[$p] = $posCode;
        }

        $where = ['AND'];

        $sumHz = array_sum($filterCodes);
        $firstHf = substr((string)$sumHz, -1);
        $secondHf = ($firstHf>4) ? ($firstHf-5) : ($firstHf+5);
        $hzs = [
            $firstHf, $firstHf+10, $firstHf+20, $firstHf+30,
            $secondHf, $secondHf+10, $secondHf+20, $secondHf+30,
        ];

        $where[] = ['NOT IN', '(code_'.implode('+code_', $positions).')', $hzs];

        $logArr = [
            'p'=>$positions,
            'historyKjData'=>$historyKjData,
            'secondHf'=>$secondHf,
            'hzs'=>$hzs,
            'filterCodes'=>$filterCodes,
            'sumHz'=>$sumHz,
            'where'=>$where,
        ];
        //p($logArr, 0);

        $query = self::getBaseCodesQuery($where, $plan->playway);
        //$sql = $query->createCommand()->getRawSql();p($sql);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '定位x排除对应位置的合分与对数值合分', ['plan_id'=>$plan->id, 'currentKjQiHao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType, 'filterDesc'=>$filterDesc, 'd'=>$logArr/* 'sql'=>$sql*/]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc']."[上期号码：".$historyKjData['code_str']."]定位".$params['x']."排除合分以及合分对数的合分:". $firstHf.$secondHf;
        //p($betDesc);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - x位不等于上期y位合分
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter17(object $plan, $dynamic=[], $filterDesc = []){
        $lotteryType = $plan->lottery_type;

        $params = $dynamic['params'];
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType); # 上期号码
        $y = trim($params['y']); # 位置
        $beforePositions = str_split($y);
        $beforeCodes = [];
        foreach ($beforePositions as $beforePosition){
            $beforeCodes[] = $historyKjData['code'.$beforePosition];
        }
        $sumHz = array_sum($beforeCodes);
        $beforeHf = substr((string)$sumHz, -1); // 合分
        //p(['historyKjData'=>$historyKjData, 'beforeHz'=>$beforeHz]);
        $hzs = [$beforeHf, $beforeHf+10, $beforeHf+20, $beforeHf+30];

        $x = trim($params['x']); # 位置
        $positions = str_split($x);

        $where = ['AND'];
        $where[] = ['NOT IN', '(code_'.implode('+code_', $positions).')', $hzs];

        $query = self::getBaseCodesQuery($where, $plan->playway);
        //$sql = $query->createCommand()->getRawSql();p($sql);

        $logArr = [
            'p'=>$positions,
            'historyKjData'=>$historyKjData,
            'hzs'=>$hzs,
            'beforeCodes'=>$beforeCodes,
            'sumHz'=>$sumHz,
            'where'=>$where,
        ];
        //p($logArr, 0);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '定位x排除对应位置的合分与对数值合分', ['plan_id'=>$plan->id, 'currentKjQiHao'=>$currentKjQiHao, 'lottery_type'=>$lotteryType, 'filterDesc'=>$filterDesc, 'd'=>$logArr/* 'sql'=>$sql*/]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc']."[上期".$historyKjData['qihao']."：".$historyKjData['code_str']."],上期y:".$params['y']."位,定位x:".$params['x']."排除合分:". implode('、', $hzs);
        //p($betDesc);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    # 过滤x个配数单双互排除及该位置号码(四定)
    public static function filter19(object $plan, $dynamic=[], $filterDesc = [], $direct=0): array
    {
        $lotteryType = $plan->lottery_type;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType);

        $params = $dynamic['params'];
        $x = $params['x']; # x个位置
        $filterPoss = NumService::$ALL_POSES;
        if($x==2){
            $filterPoss = NumService::TWO_NUM_POS;
        }elseif ($x==3){
            $filterPoss = NumService::THIRD_NUM_POS;
        }elseif ($x==4){
            $filterPoss = NumService::$ALL_POSES;
        }
        if($x == 1){
            $where = ['OR'];
            foreach ($filterPoss as $pos){
                $filterCode = array_merge(NumService::getDsTypeFanByCode($historyKjData['code'.$pos]), [$historyKjData['code'.$pos]]);
                if($direct){
                    $where[] = ['IN', 'code_'.$pos, $filterCode];
                }else{
                    $where[] = ['NOT IN', 'code_'.$pos, $filterCode];
                }
            }
        }elseif ($x == 2){
            $where = ['OR'];
            foreach ($filterPoss as $poss){
                $subWhere = ['AND'];
                foreach ($poss as $pos){
                    $filterCode = array_merge(NumService::getDsTypeFanByCode($historyKjData['code'.$pos]), [$historyKjData['code'.$pos]]);
                    $subWhere[] = ['IN', 'code_'.$pos, $filterCode];
                }
                $where[] = $subWhere;
            }
        }elseif ($x == 3){
            $where = ['OR'];
            foreach ($filterPoss as $poss){
                $subWhere = ['AND'];
                foreach ($poss as $pos){
                    $filterCode = array_merge(NumService::getDsTypeFanByCode($historyKjData['code'.$pos]), [$historyKjData['code'.$pos]]);
                    $subWhere[] = ['IN', 'code_'.$pos, $filterCode];
                }
                $where[] = $subWhere;
            }
        }elseif ($x == 4){
            $where = ['AND'];
            foreach ($filterPoss as $pos){
                $filterCode = array_merge(NumService::getDsTypeFanByCode($historyKjData['code'.$pos]), [$historyKjData['code'.$pos]]);
                $where[] = ['IN', 'code_'.$pos, $filterCode];
            }
        }
        $txt = '';
        if($direct==1){
            $query = self::getBaseCodesQuery($where, $plan->playway);
            $txt = '-反';
        }else{
            $query = self::getBaseCodesQuery(['NOT', $where], $plan->playway);
        }
        $sql = $query->createCommand()->getRawSql();
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        $logArr = ['plan_id'=>$plan->id, 'direct'=>$direct, 'historyKjData'=>$historyKjData, 'params'=>$params, 'where'=>$where, 'count'=>count($codes), 'sql'=>$sql];
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '定位x排除对应位置的合分与对数值合分', $logArr);

        $betDesc = $filterDesc['desc']."[上期".$historyKjData['qihao']."：".$historyKjData['code_str']."]过滤".$x."个配数单双互排除及该位置号码".$txt."(四定)-组数：".count($codes);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    # x位或y位或z位上n配数单双互排及该位置号码，则中或者不中
    public static function filter20(object $plan, $dynamic=[], $filterDesc = [], $direct=0, $type='type_ds'): array
    {
        $lotteryType = $plan->lottery_type;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lotteryType);

        $params = $dynamic['params'];
        $x = trim($params['x']); # x位123、234、23等
        $y = trim($params['y']); # y位123、234、23等
        $z = trim($params['z']); # z位123、234、23等
        $n = $params['n']; # n个

        $filterPossData = [];
        if(!empty($x)){
            $filterPossData[] = str_split($x);
        }
        if(!empty($y)){
            $filterPossData[] = str_split($y);
        }
        if(!empty($z)){
            $filterPossData[] = str_split($z);
        }

        $where = ['OR'];
        foreach ($filterPossData as $filterPoss){ // $filterPoss = [1,2,3]
            # [1,2,3] n=1、2
            $n = min(count($filterPoss), $n);
            //p([$n, count($filterPoss)]);
            $subWhere = ['OR'];
            if($n == 1){
                foreach ($filterPoss as $pos) {
                    if($type == 'type_dx'){
                        $filterCode = array_merge(NumService::getDxTypeFanByCode($historyKjData['code' . $pos]), [$historyKjData['code' . $pos]]);
                    }else{
                        $filterCode = array_merge(NumService::getDsTypeFanByCode($historyKjData['code' . $pos]), [$historyKjData['code' . $pos]]);
                    }
                    $subWhere[] = ['IN', 'code_' . $pos, $filterCode]; # 正
                }
            }elseif($n == 2){
                # 正
                for($i=0; $i<count($filterPoss); $i++){
                    for($j=0; $j<count($filterPoss); $j++){
                        if($i>=$j) continue;
                        $posI = $filterPoss[$i];
                        $posJ = $filterPoss[$j];

                        if($type == 'type_dx') {
                            $filterCodeI = array_merge(NumService::getDxTypeFanByCode($historyKjData['code' . $posI]), [$historyKjData['code' . $posI]]);
                        }else{
                            $filterCodeI = array_merge(NumService::getDsTypeFanByCode($historyKjData['code' . $posI]), [$historyKjData['code' . $posI]]);
                        }
                        if($type == 'type_dx') {
                            $filterCodeJ = array_merge(NumService::getDxTypeFanByCode($historyKjData['code' . $filterPoss[$j]]), [$historyKjData['code' . $filterPoss[$j]]]);
                        }else{
                            $filterCodeJ = array_merge(NumService::getDsTypeFanByCode($historyKjData['code' . $filterPoss[$j]]), [$historyKjData['code' . $filterPoss[$j]]]);
                        }
                        $subTwoWhere = [
                            'AND',
                            ['IN', 'code_' . $posI, $filterCodeI], # 正
                            ['IN', 'code_' . $posJ, $filterCodeJ], # 正
                        ];
                        $subWhere[] = $subTwoWhere;
                    }
                }
            }elseif($n == 3){
                $subThirdWhere = ['OR'];
                # 正
                for ($i = 0; $i < count($filterPoss); $i++) {
                    for ($j = 0; $j < count($filterPoss); $j++) {
                        if ($i >= $j) continue;
                        for ($k = 0; $k < count($filterPoss); $k++) {
                            if ($j >= $k) continue;
                            $posI = $filterPoss[$i];
                            $posJ = $filterPoss[$j];
                            $posK = $filterPoss[$k];

                            if($type == 'type_dx') {
                                $filterCodeI = array_merge(NumService::getDxTypeFanByCode($historyKjData['code' . $posI]), [$historyKjData['code' . $posI]]);
                            }else{
                                $filterCodeI = array_merge(NumService::getDsTypeFanByCode($historyKjData['code' . $posI]), [$historyKjData['code' . $posI]]);
                            }
                            if($type == 'type_dx') {
                                $filterCodeJ = array_merge(NumService::getDxTypeFanByCode($historyKjData['code' . $posJ]), [$historyKjData['code' . $posJ]]);
                            }else{
                                $filterCodeJ = array_merge(NumService::getDsTypeFanByCode($historyKjData['code' . $posJ]), [$historyKjData['code' . $posJ]]);
                            }
                            if($type == 'type_dx') {
                                $filterCodeK = array_merge(NumService::getDxTypeFanByCode($historyKjData['code' . $posK]), [$historyKjData['code' . $posK]]);
                            }else{
                                $filterCodeK = array_merge(NumService::getDsTypeFanByCode($historyKjData['code' . $posK]), [$historyKjData['code' . $posK]]);
                            }

                            $subThirdWhere[] = [
                                'AND',
                                ['IN', 'code_' . $posI, $filterCodeI],
                                ['IN', 'code_' . $posJ, $filterCodeJ],
                                ['IN', 'code_' . $posK, $filterCodeK],
                            ];
                        }
                    }
                }
                $subWhere[] = $subThirdWhere;
            }elseif($n==4){
                $where = ['AND'];
                # 正
                foreach ($filterPoss as $pos) {
                    if ($type == 'type_dx') {
                        $filterCode = array_merge(NumService::getDxTypeFanByCode($historyKjData['code' . $pos]), [$historyKjData['code' . $pos]]);
                    } else {
                        $filterCode = array_merge(NumService::getDsTypeFanByCode($historyKjData['code' . $pos]), [$historyKjData['code' . $pos]]);
                    }
                    $where[] = ['IN', 'code_'.$pos, $filterCode];
                }
            }
            $where[] = $subWhere;
        }
        //p(['params'=>$params, 'filterPossData'=>$filterPossData], 0); p(['where'=>$where], 0);

        $txt = '';
        if($direct==1){
            $query = self::getBaseCodesQuery($where, $plan->playway);
            $txt = '-反';
        }else{
            $query = self::getBaseCodesQuery(['NOT', $where], $plan->playway);
        }
        $sql = $query->createCommand()->getRawSql();
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        $logArr = ['plan_id'=>$plan->id, 'direct'=>$direct, 'historyKjData'=>$historyKjData, 'params'=>$params, 'where'=>$where, 'count'=>count($codes), 'sql'=>$sql];
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', 'x位或y位或z位上n配数单双互排及该位置号码', $logArr);

        $betDesc = $filterDesc['desc']."[上期".$historyKjData['qihao']."：".$historyKjData['code_str']."]过滤".$x."位".($y?"或".$y."位":'').($z?"或".$z."位":'')."上".$n."配数单双互排及该位置上".$n."个号码".$txt."(四定)-组数：".count($codes);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

    /**
     * 过滤类型号码 - 取千、百、十、个 最近9个码
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter21(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $playway = $plan->playway;
        $lottery_type = $plan->lottery_type;
        $lotteryType = $plan->lottery_type;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);

        $params = $dynamic['params'];
        $x = $params['x']; # x位
        $n = $params['n']; # n个码
        $k = $params['k']; # 0不除双重，1除双重

        $positions = str_split(trim($x));
        $where = ['OR'];
        $desc = '';
        foreach ($positions as $p){
            $beforeQuery = SscKjData::find()
                ->select(['code'=>'code'.$p, 'qihao'=>'MAX(qihao)'])
                ->where(['lottery_type'=>$lottery_type])
                ->groupBy('code'.$p)
                ->orderBy(['MAX(qihao)'=>SORT_DESC])
                ->limit($n);
            $beforeQuery->andWhere(['<=', 'qihao', $currentKjQiHao]);
            //$sql = $beforeQuery->createCommand()->getRawSql();p($sql);
            $currentKjCodes = $beforeQuery->asArray()->all(); # 最新一期
            $filterCodes = ArrayHelper::getColumn($currentKjCodes, 'code');
            if($k){
                $where[] = [
                    'AND',
                    ['AND', ['IN', 'code_1', $filterCodes], ['<>', 'type_2', 1]],
                    ['AND', ['IN', 'code_2', $filterCodes], ['<>', 'type_2', 2]],
                    ['AND', ['IN', 'code_3', $filterCodes], ['<>', 'type_2', 3]],
                    ['AND', ['IN', 'code_4', $filterCodes], ['<>', 'type_2', 4]],
                ];
            }else{
                $where[] = [
                    'AND',
                    ['IN', 'code_1', $filterCodes],
                    ['IN', 'code_2', $filterCodes],
                    ['IN', 'code_3', $filterCodes],
                    ['IN', 'code_4', $filterCodes],
                ];
            }
            $desc .= ' '.$p.'位近'.$n.'个码:'.implode($filterCodes);
        }


        $query = Num4Type::find()->select(['code'])
            ->where(['=', 'code_type', $playway+1])
            ->andWhere(['NOT', $where]);
        $sql = $query->createCommand()->getRawSql(); //p($sql);
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '过滤x位最近n个码的复试', ['currentKjQiHao'=>$currentKjQiHao, 'lottery_type'=>$lottery_type, 'currentKjCodes'=>$currentKjCodes, 'sql'=>$sql]);
        $NumTypes = $query->asArray()->all();
        //p(['count'=>count($NumTypes), 'sql'=>$sql, 'NumTypes'=>$NumTypes]);
        $betDesc = $filterDesc['desc'].'：过滤'.$desc." 复试，最终组数：".count($NumTypes);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return ArrayHelper::getColumn($NumTypes, 'code');
    }

    /**
     * 获取查询对象
     * @param $where
     * @param $playway
     * @return \yii\db\Query
     */
    public static function getBaseCodesQuery($where, $playway=3): \yii\db\Query
    {
        return (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway+1])
            ->andWhere($where);
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

    /**
     * 过滤x位最新y期直码
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter30(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $params = $dynamic['params'];
        $positions = str_split($params['x']); // 将位置字符串转换为数组
        $periods = (int)$params['y']; // 要过滤的期数

        // 获取最近y期的开奖数据
        $recentDraws = SscKjData::find()
            ->where(['lottery_type' => $lottery_type])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($periods)
            ->asArray()
            ->all();

        // 构建要过滤的号码数组
        $filterCodes = [];
        foreach ($recentDraws as $draw) {
            $codeParts = [];
            foreach ($positions as $pos) {
                $codeParts[] = $draw['code'.$pos];
            }
            $filterCodes[] = implode(',', $codeParts); // 使用逗号连接数字
        }

        // 构建查询条件
        $where = ['NOT IN', 'code', $filterCodes];

        // 根据玩法类型构建查询
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway+1]);

        // 如果是定位玩法，需要特殊处理
        if ($playway == 1 || $playway == 2) {
            $where = ['OR'];
            foreach ($filterCodes as $code) {
                $codeParts = explode(',', $code);
                $condition = ['AND'];
                foreach ($positions as $index => $pos) {
                    $condition[] = ['=', 'code_'.$pos, $codeParts[$index]];
                }
                $where[] = $condition;
            }
            $query->andWhere(['NOT', $where]);
        } else {
            $query->andWhere($where);
        }

        // 执行查询
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        // 添加下注描述
        $betDesc = $filterDesc['desc'].":过滤第".$params['x']."位最近".$periods."期直码";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        return $codes;
    }

    /**
     * 上期开奖号码对数全倒
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter31(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        $x = $dynamic['params']['x']??1; // 1取2除

        // 获取上期开奖数据
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        /*
        print_r($historyKjData);
        # 测试数据
        $historyKjData = [
            'code1' => 1,
            'code2' => 2,
            'code3' => 2,
            'code4' => 2,
            'code5' => 5,
        ];
        */
        if (empty($historyKjData)) {
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '上期开奖数据为空', ['plan_id'=>$plan->id, 'lottery_type'=>$lottery_type, 'currentKjQiHao'=>$currentKjQiHao]);
            return [];
        }

        // 上期开奖号码
        $lastCodes = [
            1 => $historyKjData['code1'],
            2 => $historyKjData['code2'],
            3 => $historyKjData['code3'],
            4 => $historyKjData['code4']
        ];

        // 对数映射：0-5 1-6 2-7 3-8 4-9（相减为5）
        $duiShuMap = [
            0 => 5, 1 => 6, 2 => 7, 3 => 8, 4 => 9,
            5 => 0, 6 => 1, 7 => 2, 8 => 3, 9 => 4
        ];

        // 生成所有可能的对数组合
        $allCombinations = [];

        // 对每个位置生成对数
        for ($pos = 1; $pos <= 4; $pos++) {
            $originalCode = $lastCodes[$pos];
            $duiShuCode = $duiShuMap[$originalCode];

            // 创建新的号码组合，只改变当前位置
            $newCombination = $lastCodes;
            $newCombination[$pos] = $duiShuCode;

            // 生成全倒组合
            $quanDaoCombinations = self::generateQuandaoCombinations($newCombination, $playway);
            //p(['quanDaoCombinations单次'=>$quanDaoCombinations, 'count'=>count($quanDaoCombinations)], 0);
            $allCombinations = array_merge($allCombinations, $quanDaoCombinations);
            //p(['allCombinations累计'=>$allCombinations, 'count'=>count($allCombinations)], 0);
        }

        //p(['去重之前'=>$allCombinations, '去重前数量'=>count($allCombinations)], 0);
        // 去重
        $allCombinations = array_unique($allCombinations);
        //p(['allCombinations'=>$allCombinations, '总数量'=>count($allCombinations)]);

        // 构建查询条件，排除这些号码
        if($x == 1){
            $where = ['IN', 'code', $allCombinations];
        }else{
            $where = ['NOT IN', 'code', $allCombinations];
        }

        // 根据玩法类型构建查询
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway+1]);

        // 如果是定位玩法，需要特殊处理
        if ($playway == 1 || $playway == 2) {
            $where = ['OR'];
            foreach ($allCombinations as $code) {
                $codeParts = explode(',', $code);
                $condition = ['AND'];
                foreach ($codeParts as $index => $part) {
                    $condition[] = ['=', 'code_'.($index+1), $part];
                }
                $where[] = $condition;
            }
            $query->andWhere(['NOT', $where]);
        } else {
            $query->andWhere($where);
        }

        // 执行查询
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        // 添加下注描述
        $lastCodeStr = implode('', array_values($lastCodes));
        $betDesc = $filterDesc['desc'].":上期开奖".$lastCodeStr."对数全倒过滤";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '上期开奖号码对数全倒过滤', [
            'plan_id'=>$plan->id,
            'lottery_type'=>$lottery_type,
            'last_codes'=>$lastCodes,
            'filter_combinations_count'=>count($allCombinations),
            'result_codes_count'=>count($codes)
        ]);

        return $codes;
    }

    /**
     * 生成全倒组合
     * @param array $combination 号码组合
     * @param int $playway 玩法类型
     * @return array
     */
    private static function generateQuandaoCombinations(array $combination, int $playway): array
    {
        $combinations = [];

        if ($playway == 3) {
            // 四定：生成所有排列组合
            $codes = array_values($combination);
            $permutations = self::generatePermutations($codes);
            foreach ($permutations as $perm) {
                $combinations[] = implode(',', $perm);
            }
        } elseif ($playway == 2) {
            // 三定：选择3个位置的所有组合
            $positions = [
                [1, 2, 3], [1, 2, 4], [1, 3, 4], [2, 3, 4]
            ];
            foreach ($positions as $pos) {
                $codes = [];
                foreach ($pos as $p) {
                    $codes[] = $combination[$p];
                }
                $permutations = self::generatePermutations($codes);
                foreach ($permutations as $perm) {
                    $combinations[] = implode(',', $perm);
                }
            }
        } elseif ($playway == 1) {
            // 二定：选择2个位置的所有组合
            $positions = [
                [1, 2], [1, 3], [1, 4], [2, 3], [2, 4], [3, 4]
            ];
            foreach ($positions as $pos) {
                $codes = [];
                foreach ($pos as $p) {
                    $codes[] = $combination[$p];
                }
                $permutations = self::generatePermutations($codes);
                foreach ($permutations as $perm) {
                    $combinations[] = implode(',', $perm);
                }
            }
        }

        return $combinations;
    }

    /**
     * 生成排列组合
     * @param array $codes
     * @return array
     */
    private static function generatePermutations(array $codes): array
    {
        if (count($codes) <= 1) {
            return [$codes];
        }

        $permutations = [];
        for ($i = 0; $i < count($codes); $i++) {
            $current = $codes[$i];
            $remaining = array_merge(array_slice($codes, 0, $i), array_slice($codes, $i + 1));

            foreach (self::generatePermutations($remaining) as $perm) {
                $permutations[] = array_merge([$current], $perm);
            }
        }

        return $permutations;
    }

    /**
     * 相邻两个相加合分有且只有x个相等
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter32(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        // 获取上期开奖数据
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        $params = $dynamic['params'];
        $x = (int)$params['x']; // 相等的个数，范围0-3

        if ($x < 0 || $x > 3) {
            throw_info('参数x必须在0-3之间');
        }

        // 计算上期相邻位置的合分
        $lastSum12 = $historyKjData['code1'] + $historyKjData['code2']; // 1,2位
        $lastSum23 = $historyKjData['code2'] + $historyKjData['code3']; // 2,3位
        $lastSum34 = $historyKjData['code3'] + $historyKjData['code4']; // 3,4位

        // 生成上期各位置合分的个位数和十位数
        $lastSum12Types = [$lastSum12 % 10, 10+($lastSum12%10)]; // 个十位
        $lastSum23Types = [$lastSum23 % 10, 10+($lastSum23%10)]; // 十百位
        $lastSum34Types = [$lastSum34 % 10, 10+($lastSum34%10)]; // 百千位

        // 构建SQL条件 - 每个位置只与对应的上期位置比较
        $conditions = [];

        if ($x == 0) {
            // 有且只有0个相等，即所有都不相等
            $conditions[] = "((code_1 + code_2) NOT IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) NOT IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) NOT IN (" . implode(',', $lastSum34Types) . "))";
        } elseif ($x == 1) {
            // 有且只有1个相等
            $conditions[] = "((code_1 + code_2) IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) NOT IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) NOT IN (" . implode(',', $lastSum34Types) . "))";
            $conditions[] = "((code_1 + code_2) NOT IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) NOT IN (" . implode(',', $lastSum34Types) . "))";
            $conditions[] = "((code_1 + code_2) NOT IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) NOT IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) IN (" . implode(',', $lastSum34Types) . "))";
        } elseif ($x == 2) {
            // 有且只有2个相等
            $conditions[] = "((code_1 + code_2) IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) NOT IN (" . implode(',', $lastSum34Types) . "))";
            $conditions[] = "((code_1 + code_2) IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) NOT IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) IN (" . implode(',', $lastSum34Types) . "))";
            $conditions[] = "((code_1 + code_2) NOT IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) IN (" . implode(',', $lastSum34Types) . "))";
        } elseif ($x == 3) {
            // 有且只有3个相等
            $conditions[] = "((code_1 + code_2) IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) IN (" . implode(',', $lastSum34Types) . "))";
        }

        $where = ['OR'];
        foreach ($conditions as $condition) {
            $where[] = $condition;
        }

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway + 1])
            ->andWhere($where);
        //$sql = $query->createCommand()->getRawSql();p($sql);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '相邻合分有且只有' . $x . '个相等', ['plan_id'=>$plan->id, 'lottery_type'=>$lottery_type, 'qihao'=>$nextQiHao, 'sql'=>$sql]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        $betDesc = $filterDesc['desc'] . ":上期开奖" . $historyKjData['code_str'] . "，相邻合分有且只有" . $x . "个相等";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        return $codes;
    }

    /**
     * 相邻两个相加合分至少只有x个相等
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter33(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        // 获取上期开奖数据
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        $params = $dynamic['params'];
        $x = (int)$params['x']; // 至少相等的个数，范围0-3

        if ($x < 0 || $x > 3) {
            throw_info('参数x必须在0-3之间');
        }

        // 计算上期相邻位置的合分
        $lastSum12 = $historyKjData['code1'] + $historyKjData['code2']; // 1,2位
        $lastSum23 = $historyKjData['code2'] + $historyKjData['code3']; // 2,3位
        $lastSum34 = $historyKjData['code3'] + $historyKjData['code4']; // 3,4位

        // 生成上期各位置合分的个位数和十位数
        $lastSum12Types = [$lastSum12 % 10, 10+($lastSum12%10)]; // 个十位
        $lastSum23Types = [$lastSum23 % 10, 10+($lastSum23%10)]; // 十百位
        $lastSum34Types = [$lastSum34 % 10, 10+($lastSum34%10)]; // 百千位

        // 构建SQL条件 - 至少x个相等，每个位置只与对应的上期位置比较
        $conditions = [];

        if ($x == 0) {
            // 至少0个相等，即所有号码都符合
            $conditions[] = "1=1";
        } elseif ($x == 1) {
            // 至少1个相等
            $conditions[] = "(code_1 + code_2) IN (" . implode(',', $lastSum12Types) . ")";
            $conditions[] = "(code_2 + code_3) IN (" . implode(',', $lastSum23Types) . ")";
            $conditions[] = "(code_3 + code_4) IN (" . implode(',', $lastSum34Types) . ")";
        } elseif ($x == 2) {
            // 至少2个相等
            $conditions[] = "((code_1 + code_2) IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) IN (" . implode(',', $lastSum23Types) . "))";
            $conditions[] = "((code_1 + code_2) IN (" . implode(',', $lastSum12Types) . ") AND (code_3 + code_4) IN (" . implode(',', $lastSum34Types) . "))";
            $conditions[] = "((code_2 + code_3) IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) IN (" . implode(',', $lastSum34Types) . "))";
        } elseif ($x == 3) {
            // 至少3个相等
            $conditions[] = "((code_1 + code_2) IN (" . implode(',', $lastSum12Types) . ") AND (code_2 + code_3) IN (" . implode(',', $lastSum23Types) . ") AND (code_3 + code_4) IN (" . implode(',', $lastSum34Types) . "))";
        }

        $where = ['OR'];
        foreach ($conditions as $condition) {
            $where[] = $condition;
        }

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway + 1])
            ->andWhere($where);

        // 添加SQL日志
        $sql = $query->createCommand()->getRawSql();
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '相邻合分至少' . $x . '个相等', ['plan_id'=>$plan->id, 'lottery_type'=>$lottery_type, 'qihao'=>$nextQiHao, 'sql'=>$sql]);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        $betDesc = $filterDesc['desc'] . ":上期开奖" . $historyKjData['code_str'] . "，相邻合分至少" . $x . "个相等";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        return $codes;
    }

    /**
     * 过滤上x期的和值
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter34(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        // 只支持四定类型
        if ($playway != 3) {
            throw_info('过滤上x期的和值只支持四定类型（playway:3）');
        }

        $params = $dynamic['params'];
        $x = (int)$params['x']??1; // 期数，x=1则过滤上期，x=2则过滤上上期

        if ($x < 1) {
            throw_info('参数x必须大于等于1');
        }

        // 获取指定期数的开奖数据
        $targetQiHao = '';
        // 获取上x期期号
        $targetQiHao = QihaoService::getLastQiHao($lottery_type, $nextQiHao, $x);

        if (empty($targetQiHao)) {
            throw_info('无法获取上' . $x . '期开奖数据');
        }
        $targetKjData = NumCodeService::getKjData($targetQiHao, $lottery_type);
        if (empty($targetKjData) || empty($targetKjData['codes_hz'])) {
            throw_info('上' . $x . '期开奖数据和值数据不存在');
        }

        $targetSum = (int)$targetKjData['codes_hz']; // 目标期数的和值

        // 过滤掉和值等于目标期数和值的号码
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway + 1])
            ->andWhere(['!=', 'codes_hz', $targetSum]);

        //p($query->createCommand()->getRawSql());
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        $betDesc = $filterDesc['desc'] . ":过滤上" . $x . "期和值" . $targetSum . "，期号" . $targetQiHao;
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '过滤上x期的和值', [
            'plan_id' => $plan->id,
            'lottery_type' => $lottery_type,
            'current_kj_qihao' => $currentKjQiHao,
            'target_qihao' => $targetQiHao,
            'target_sum' => $targetSum,
            'filtered_count' => count($codes)
        ]);

        return $codes;
    }

    /**
     * 排除同位置最近n个号码复试
     * @param $plan
     * @param $dynamic
     * @param $filterDesc
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function filter35($plan, $dynamic, $filterDesc) {
        $params = $dynamic['params'];
        $lottery_type = $plan['lottery_type'];
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
        // 千百十个
        $x = intval($params['x'] ?? 0);
        $y = intval($params['y'] ?? 0);
        $z = intval($params['z'] ?? 0);
        $n = intval($params['n'] ?? 0);

        if ($x < 1 || $y < 1 || $z < 1 || $n < 1) {
            throw_info('参数错误，千百十个位都必须大于0');
        }

        // 获取每个位最近N个号码
        $qArr = NumService::getPosLatelyCode(1, $x, $lottery_type);
        $bArr = NumService::getPosLatelyCode(2, $y, $lottery_type);
        $sArr = NumService::getPosLatelyCode(3, $z, $lottery_type);
        $gArr = NumService::getPosLatelyCode(4, $n, $lottery_type);

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $plan['playway'] + 1])
            ->andWhere(['in', 'code_1', $qArr])
            ->andWhere(['in', 'code_2', $bArr])
            ->andWhere(['in', 'code_3', $sArr])
            ->andWhere(['in', 'code_4', $gArr]);

        $excludeCodes = array_column($query->all(), 'code');
        // 排除这些号码
        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $plan['playway'] + 1]); // 四定

        if (!empty($excludeCodes)) {
            $query->andWhere(['not in', 'code', $excludeCodes]);
        }

        $results = $query->all();

        $betDesc = $filterDesc['desc'] . ":排除千/百/十/个位最近" . $n . "个号码的所有复试组合。参数分别为千" . $x . "、百" . $y . "、十" . $z . "、个" . $n;
        NumCodeService::addBetDescRand($plan['id'], $nextQiHao, $betDesc);

        return array_column($results, 'code');
    }

    /**
     * 指定位置排除期号尾号
     * @param $plan
     * @param $dynamic
     * @param $filterDesc
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function filter36($plan, $dynamic, $filterDesc) {
        $params = $dynamic['params'];
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $x = $params['x'];
        if (empty($x)) {
            throw_info('参数x不能为空');
        }

        // 获取期号尾号
        $qihaoTail = substr($nextQiHao, -1);

        // 解析位置参数，1234分别代表千百十个位
        $positions = str_split($x);
        $excludeConditions = [];

        foreach ($positions as $pos) {
            if (!in_array($pos, ['1', '2', '3', '4'])) {
                throw_info('位置参数错误，只能包含1、2、3、4，分别代表千百十个位');
            }
            $excludeConditions[] = ['!=', 'code_' . $pos, $qihaoTail];
        }

        // 构建查询条件
        $where = ['AND'];
        $where[] = ['code_type' => $playway + 1];
        $where = array_merge($where, $excludeConditions);

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where($where);

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        $positionNames = [];
        foreach ($positions as $pos) {
            $positionNames[] = ['1'=>'千', '2'=>'百', '3'=>'十', '4'=>'个'][$pos] . '位';
        }

        $betDesc = $filterDesc['label'] . ":期号" . $nextQiHao . "尾号为" . $qihaoTail . "，排除" . implode('、', $positionNames) . "的号码" . $qihaoTail;
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '指定位置排除期号尾号', [
            'plan_id' => $plan->id,
            'lottery_type' => $lottery_type,
            'next_qihao' => $nextQiHao,
            'qihao_tail' => $qihaoTail,
            'positions' => $positions,
            'filtered_count' => count($codes)
        ]);

        return $codes;
    }

    /**
     * 指定位置合分不等于上期对应位置合分
     * @param $plan
     * @param $dynamic
     * @param $filterDesc
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function filter37($plan, $dynamic, $filterDesc) {
        $params = $dynamic['params'];
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $x = $params['x'];
        if (empty($x)) {
            throw_info('参数x不能为空');
        }

        // 获取上期开奖数据
        $historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        if (empty($historyKjData)) {
            throw_info('上期开奖数据不存在');
        }

        // 解析位置参数，1234分别代表千百十个位
        $positions = str_split($x);
        $positionNames = [];
        $positionSum = 0;

        foreach ($positions as $pos) {
            if (!in_array($pos, ['1', '2', '3', '4'])) {
                throw_info('位置参数错误，只能包含1、2、3、4，分别代表千百十个位');
            }
            $positionNames[] = ['1'=>'千', '2'=>'百', '3'=>'十', '4'=>'个'][$pos] . '位';

            // 获取上期对应位置的号码
            $positionSum += (int)$historyKjData['code' . $pos];
        }

        // 计算上期指定位置的合分
        $lastSum = $positionSum % 10; // 个位数
        $lastSumTens = $positionSum; // 十位数（如果有的话）

        // 根据位置数量确定过滤的和值范围
        $filterSums = [];
        $positionCount = count($positions);

        // 根据位置数量调整过滤范围
        if ($positionCount == 1) {
            // 1个位置：过滤个位数和十位数
            $filterSums = [$lastSum, $lastSumTens];
        } elseif ($positionCount == 2) {
            // 2个位置：过滤个位数和十位数
            $filterSums = [$lastSum, $lastSumTens];
        } elseif ($positionCount == 3) {
            // 3个位置：过滤个位数、十位数、二十位数
            $filterSums = [$lastSum, $lastSumTens, $lastSumTens + 10];
        } else {
            // 4个位置：过滤个位数、十位数、二十位数、三十位数
            $filterSums = [$lastSum, $lastSumTens, $lastSumTens + 10, $lastSumTens + 20];
        }

        // 去重并过滤掉0值
        $filterSums = array_unique(array_filter($filterSums));

        // 构建查询条件
        $where = ['AND'];
        $where[] = ['code_type' => $playway + 1];

        // 构建位置相加的条件
        $positionFields = [];
        foreach ($positions as $pos) {
            $positionFields[] = "code_{$pos}";
        }
        $sumExpression = "(" . implode(" + ", $positionFields) . ")";

        // 排除上期合分的和值
        $where[] = ['NOT IN', $sumExpression, $filterSums];

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where($where);

        $sql = $query->createCommand()->getRawSql();
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        $betDesc = $filterDesc['label'] . ":上期" . implode('、', $positionNames) . "合分为" . $lastSum . "，过滤和值" . implode('、', $filterSums);
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '指定位置合分不等于上期对应位置合分', [
            'plan_id' => $plan->id,
            'lottery_type' => $lottery_type,
            'sql' => $sql,
            'current_kj_qihao' => $currentKjQiHao,
            'next_qihao' => $nextQiHao,
            'positions' => $positions,
            'position_names' => $positionNames,
            'last_sum' => $lastSum,
            'last_sum_tens' => $lastSumTens,
            'filter_sums' => $filterSums,
            'filtered_count' => count($codes)
        ]);

        return $codes;
    }

    /**
     * 动态过滤2 - 幸运五重复号码过滤
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter38($plan, $dynamic, $filterDesc) {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $params = $dynamic['params'];
        $x = $params['x'];
        if (empty($x)) {
            throw_info('参数x不能为空');
        }

        // 检查是否为幸运五
        if ($lottery_type != 8) {
            throw_info('动态过滤2仅支持幸运五(lottery_type=8)');
        }

        // 生成缓存key，包含x参数
        $cacheKey = "dynamic_filter_2_{$lottery_type}_{$x}_{$plan->id}_" . date('Y-m-d');

        // 检查缓存是否存在
        $codesData = commonRedis()->get($cacheKey);
        if ($codesData !== false) {
            $filteredCodeList = $codesData['filteredCodeList'];
            $allCodes = $codesData['allCodes'];
            $betDesc = $filterDesc['label'] . "[大于等于{$x}次]：筛选出号码：".implode(',',$filteredCodeList).'，' . count($filteredCodeList) . "个重复号码，生成" . count($allCodes) . "个过滤号码";
            NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);
            return $allCodes;
        }

        // 计算时间范围：根据用户习惯，早上8点到第二天凌晨5点为一个区间
        $currentTime = time();
        $today = date('Y-m-d');

        // 如果当前时间在08:00到23:59之间，取昨天早8点到今天凌晨5点
        // 如果当前时间过了23:59，取前天早8点到昨天凌晨5点
        if ($currentTime >= strtotime($today . ' 08:00:00') && $currentTime <= strtotime($today . ' 23:59:59')) {
            // 今天08:00到23:59，取昨天早8点到今天凌晨5点
            $startTime = strtotime(date('Y-m-d', strtotime('-1 day')) . ' 08:00:00');
            $endTime = strtotime($today . ' 05:00:00');
        } else {
            // 过了23:59，取前天早8点到昨天凌晨5点
            $startTime = strtotime(date('Y-m-d', strtotime('-2 day')) . ' 08:00:00');
            $endTime = strtotime(date('Y-m-d', strtotime('-1 day')) . ' 05:00:00');
        }
        //p([date('Y-m-d H:i:s', $startTime), date('Y-m-d H:i:s', $endTime)]);

        // 使用SQL GROUP BY直接统计出现次数大于等于x的号码
        $filteredCodesQuery = SscKjData::find()
            ->select(['code_4n', 'COUNT(id) as count'])
            ->where(['lottery_type' => $lottery_type])
            ->andWhere(['>=', 'created_at', $startTime])
            ->andWhere(['<=', 'created_at', $endTime])
            ->groupBy(['code_4n'])
            ->having(['>=', 'COUNT(id)', $x]);

        $filteredCodes = $filteredCodesQuery->asArray()->all();

        if (empty($filteredCodes)) {
            // 如果没有符合条件的号码，返回所有号码
            $query = Num4Type::find()
                ->select(['code', 'code_4n', 'code_type'])
                ->where(['code_type' => $playway + 1]);
            $results = $query->asArray()->all();
            $codes = ArrayHelper::getColumn($results, 'code');

            $betDesc = $filterDesc['label'] . "[{$x}次]：无重复号码，返回所有号码";
            NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);
            return $codes;
        }

        // 提取符合条件的号码
        $filteredCodeList = ArrayHelper::getColumn($filteredCodes, 'code_4n');

        // 直接查询号码表中code_str等于过滤出来号码的所有记录
        $query = Num4Type::find()
            ->select(['code', 'code_type'])
            ->where(['code_type' => $playway + 1])
            ->andWhere(['IN', 'code_str', $filteredCodeList]);

        $sql = $query->createCommand()->getRawSql();
        $results = $query->asArray()->all();
        $allCodes = ArrayHelper::getColumn($results, 'code');

        // 计算缓存剩余时间（到第二天凌晨5点）
        $tomorrow5am = strtotime(date('Y-m-d', strtotime('+1 day')) . ' 05:00:00');
        $cacheExpire = $tomorrow5am - time();

        $codesData = [
            'allCodes' => $allCodes,
            'filteredCodeList' => $filteredCodeList,
        ];
        // 设置缓存
        commonRedis()->setex($cacheKey, 3600, $codesData);

        $betDesc = $filterDesc['label'] . "[大于等于{$x}次]：筛选出号码：".implode(',',$filteredCodeList).'，' . count($filteredCodeList) . "个重复号码，生成" . count($allCodes) . "个过滤号码";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '动态过滤2', [
            'plan_id' => $plan->id,
            'lottery_type' => $lottery_type,
            'x' => $x,
            'sql' => $sql,
            'start_time' => date('Y-m-d H:i:s', $startTime),
            'end_time' => date('Y-m-d H:i:s', $endTime),
            'filtered_codes' => $filteredCodeList,
            'filtered_count' => count($filteredCodeList),
            'result_count' => count($allCodes),
            'cache_key' => $cacheKey,
            'cache_expire' => $cacheExpire
        ]);

        return $allCodes;
    }

    /**
     * 过滤x范围直码
     * 支持格式：1-2;4~6 则过滤前1、2、4、5、6期的直码
     * 支持格式：1-100 或 1~100，则过滤最近100期的直码
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter39(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $params = $dynamic['params'];
        $x = trim($params['x']);

        if (empty($x)) {
            // 如果参数为空，返回所有号码
            $query = Num4Type::find()
                ->select(['code', 'code_type'])
                ->where(['code_type' => $playway + 1]);
            $results = $query->asArray()->all();
            return ArrayHelper::getColumn($results, 'code');
        }

        // 获取当前期号的index_id
        $currentKjData = SscKjData::find()
            ->where(['AND', ['=', 'qihao', $currentKjQiHao], ['=', 'lottery_type', $lottery_type]])
            ->limit(1)
            ->asArray()
            ->one();

        if (empty($currentKjData) || !isset($currentKjData['index_id'])) {
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '获取当前期号index_id失败', [
                'plan_id' => $plan->id,
                'lottery_type' => $lottery_type,
                'currentKjQiHao' => $currentKjQiHao
            ]);
            // 返回所有号码
            $query = Num4Type::find()
                ->select(['code', 'code_type'])
                ->where(['code_type' => $playway + 1]);
            $results = $query->asArray()->all();
            return ArrayHelper::getColumn($results, 'code');
        }

        $index_id = $currentKjData['index_id'];
        $filter_index_ids = [];

        // 解析期数范围：支持 1-2;4~6 或 1,2;4~6 等格式
        $tmp_filter_index_Arrs = explode(';', $x);
        foreach ($tmp_filter_index_Arrs as $tmp_filter_index_Arr) {
            $tmp_filter_index_Arr = trim($tmp_filter_index_Arr);
            if (empty($tmp_filter_index_Arr)) continue;

            if (strpos($tmp_filter_index_Arr, ',') !== false) {
                // 处理逗号分隔的单个期数：1,2
                $tmp_filter_index_Arr2 = explode(',', $tmp_filter_index_Arr);
                foreach ($tmp_filter_index_Arr2 as $tmp_index) {
                    $tmp_index = trim($tmp_index);
                    if (is_numeric($tmp_index)) {
                        $filter_index_ids[] = $index_id - (int)$tmp_index + 1;
                    }
                }
            } elseif (strpos($tmp_filter_index_Arr, '~') !== false || strpos($tmp_filter_index_Arr, '-') !== false) {
                // 处理范围：4~6 或 1-100
                $separator = (strpos($tmp_filter_index_Arr, '~') !== false) ? '~' : '-';
                $tmp_filter_index_Arr2 = explode($separator, $tmp_filter_index_Arr);
                if (empty($tmp_filter_index_Arr2) || count($tmp_filter_index_Arr2) < 2) continue;

                $start = (int)trim($tmp_filter_index_Arr2[0]);
                $end = (int)trim($tmp_filter_index_Arr2[1]);

                // 确保范围正确
                if ($start > $end) {
                    $temp = $start;
                    $start = $end;
                    $end = $temp;
                }

                // 生成范围内的所有期数
                for ($i = $start; $i <= $end; $i++) {
                    $filter_index_ids[] = $index_id - $i + 1;
                }
            } else {
                // 单个期数
                if (is_numeric($tmp_filter_index_Arr)) {
                    $filter_index_ids[] = $index_id - (int)$tmp_filter_index_Arr + 1;
                }
            }
        }

        // 去重并排序
        $filter_index_ids = array_unique($filter_index_ids);
        sort($filter_index_ids);

        if (empty($filter_index_ids)) {
            // 如果没有有效的期数，返回所有号码
            $query = Num4Type::find()
                ->select(['code', 'code_type'])
                ->where(['code_type' => $playway + 1]);
            $results = $query->asArray()->all();
            return ArrayHelper::getColumn($results, 'code');
        }

        // 获取要过滤的期号开奖数据
        $SscKjDatas = SscKjData::find()
            ->where(['AND', ['IN', 'index_id', $filter_index_ids], ['=', 'lottery_type', $lottery_type]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->all();

        if (empty($SscKjDatas)) {
            // 如果没有找到开奖数据，返回所有号码
            $query = Num4Type::find()
                ->select(['code', 'code_type'])
                ->where(['code_type' => $playway + 1]);
            $results = $query->asArray()->all();
            return ArrayHelper::getColumn($results, 'code');
        }

        // 构建要过滤的直码数组
        $filterCodes = [];
        foreach ($SscKjDatas as $sscKjData) {
            // 根据玩法类型构建直码
            if ($playway == 3) {
                // 四定：使用 code_4n_str
                if (!empty($sscKjData['code_4n_str'])) {
                    $filterCodes[] = $sscKjData['code_4n_str'];
                }
            } elseif ($playway == 2) {
                // 三定：构建所有三定组合
                $codeParts = [
                    $sscKjData['code1'] . ',' . $sscKjData['code2'] . ',' . $sscKjData['code3'] . ',X',
                    $sscKjData['code1'] . ',' . $sscKjData['code2'] . ',X,' . $sscKjData['code4'],
                    $sscKjData['code1'] . ',X,' . $sscKjData['code3'] . ',' . $sscKjData['code4'],
                    'X,' . $sscKjData['code2'] . ',' . $sscKjData['code3'] . ',' . $sscKjData['code4'],
                ];
                $filterCodes = array_merge($filterCodes, $codeParts);
            } elseif ($playway == 1) {
                // 二定：构建所有二定组合
                $codeParts = [
                    $sscKjData['code1'] . ',' . $sscKjData['code2'] . ',X,X',
                    $sscKjData['code1'] . ',X,' . $sscKjData['code3'] . ',X',
                    $sscKjData['code1'] . ',X,X,' . $sscKjData['code4'],
                    'X,' . $sscKjData['code2'] . ',' . $sscKjData['code3'] . ',X',
                    'X,' . $sscKjData['code2'] . ',X,' . $sscKjData['code4'],
                    'X,X,' . $sscKjData['code3'] . ',' . $sscKjData['code4'],
                ];
                $filterCodes = array_merge($filterCodes, $codeParts);
            }
        }

        // 去重
        $filterCodes = array_unique($filterCodes);

        if (empty($filterCodes)) {
            // 如果没有过滤号码，返回所有号码
            $query = Num4Type::find()
                ->select(['code', 'code_type'])
                ->where(['code_type' => $playway + 1]);
            $results = $query->asArray()->all();
            return ArrayHelper::getColumn($results, 'code');
        }

        // 构建查询条件
        $query = Num4Type::find()
            ->select(['code', 'code_type'])
            ->where(['code_type' => $playway + 1]);

        // 根据玩法类型构建过滤条件
        if ($playway == 3) {
            // 四定：直接使用 code 字段
            $query->andWhere(['NOT IN', 'code', $filterCodes]);
        } else {
            // 二定、三定：需要匹配位置
            $where = ['OR'];
            foreach ($filterCodes as $code) {
                $codeParts = explode(',', $code);
                $condition = ['AND'];
                foreach ($codeParts as $index => $part) {
                    $pos = $index + 1;
                    if ($part === 'X') {
                        $condition[] = ['=', 'code_' . $pos, 'X'];
                    } else {
                        $condition[] = ['=', 'code_' . $pos, (int)$part];
                    }
                }
                $where[] = $condition;
            }
            $query->andWhere(['NOT', $where]);
        }

        // 执行查询
        $results = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        // 添加下注描述
        $periodsDesc = implode(',', array_unique(array_map(function($id) use ($index_id) {
            return ($index_id - $id + 1);
        }, $filter_index_ids)));
        $betDesc = $filterDesc['label'] . "[范围:{$x}]：过滤前{$periodsDesc}期直码，共" . count($filterCodes) . "个过滤号码，剩余" . count($codes) . "个号码";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '过滤x范围直码', [
            'plan_id' => $plan->id,
            'lottery_type' => $lottery_type,
            'playway' => $playway,
            'x' => $x,
            'index_id' => $index_id,
            'filter_index_ids' => $filter_index_ids,
            'filter_codes_count' => count($filterCodes),
            'result_count' => count($codes),
        ]);

        return $codes;
    }

    # 随机x组号码
    public static function filter40(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $params = $dynamic['params'];
        $x = !empty($params['x']) ? (int)$params['x'] : 9000; // 默认9000组

        // 如果x小于等于0，返回空数组
        if($x <= 0){
            $label = !empty($filterDesc['label']) ? $filterDesc['label'] : '随机x组号码';
            $betDesc = $label . "[x:{$x}]：参数无效，返回空数组";
            NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);
            return [];
        }

        $allPos = NumService::$ALL_POSES; // ['1','2','3','4'] 对应千、百、十、个
        $fixedSelPos = []; // 已勾选的位置（字符串格式）

        // 获取定位置信息
        if($playway != 3){ // 二定或三定
            $codeHz = Json::decode($plan->hz_Arr);
            if(!empty($codeHz['fixed_sel_pos'])){
                $fixedSelPos = array_map('trim', explode(',', $codeHz['fixed_sel_pos'])); // 已勾选的位置
                $fixedSelPos = array_filter($fixedSelPos); // 过滤空值
            }

            // 如果不勾选定位置，则根据玩法类型随机位置
            if(empty($fixedSelPos)){
                if($playway == 2){ // 三定：随机一个位置为X
                    $randomIndex = array_rand($allPos); // 随机选择一个位置的索引
                    $randomXPos = [$allPos[$randomIndex]]; // 获取对应的位置值
                    $fixedSelPos = array_values(array_diff($allPos, $randomXPos));
                }elseif($playway == 1){ // 二定：随机两个位置为X
                    $randomIndices = array_rand($allPos, 2); // 随机选择两个位置的索引
                    $randomXPos = [$allPos[$randomIndices[0]], $allPos[$randomIndices[1]]]; // 获取对应的位置值
                    $fixedSelPos = array_values(array_diff($allPos, $randomXPos));
                }
            }
        }

        // 构建查询条件
        $query = Num4Type::find()
            ->select(['code', 'code_type'])
            ->andWhere(['=', 'code_type', $playway+1]);

        // 对于二定和三定，根据定位置设置查询条件
        if($playway != 3 && !empty($fixedSelPos)){
            // 已勾选的位置设置为X
            foreach($fixedSelPos as $pos){
                $query->andWhere(['=', 'code_'.$pos, 'X']);
            }
        }

        // 先查询符合条件的总数
        $allNumTypes = $query->asArray()->all();
        $allCodes = ArrayHelper::getColumn($allNumTypes, 'code');
        $totalCount = count($allCodes);

        // 如果x大于等于总数，返回所有号码
        if($x >= $totalCount){
            $label = !empty($filterDesc['label']) ? $filterDesc['label'] : '随机x组号码';
            $posDesc = '';
            if($playway != 3 && !empty($fixedSelPos)){
                $posNames = ['1'=>'千', '2'=>'百', '3'=>'十', '4'=>'个'];
                $fixedPosNames = array_map(function($p) use ($posNames){ return $posNames[$p] ?? $p; }, $fixedSelPos);
                $posDesc = '，定X位：' . implode('、', $fixedPosNames) . '位';
            }
            $betDesc = $label . "[x:{$x}]：随机" . $totalCount . "组（全部）{$posDesc}";
            NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);
            return $allCodes;
        }

        // 随机选择x组号码
        $query = Num4Type::find()
            ->select(['code', 'code_type'])
            ->andWhere(['=', 'code_type', $playway+1])
            ->orderBy('RAND()')
            ->limit($x);

        // 对于二定和三定，根据定位置设置查询条件
        if($playway != 3 && !empty($fixedSelPos)){
            // 已勾选的位置设置为X
            foreach($fixedSelPos as $pos){
                $query->andWhere(['=', 'code_'.$pos, 'X']);
            }
        }

        $NumTypes = $query->asArray()->all();
        $codes = ArrayHelper::getColumn($NumTypes, 'code');

        // 添加下注描述
        $label = !empty($filterDesc['label']) ? $filterDesc['label'] : '随机x组号码';
        $posDesc = '';
        if($playway != 3 && !empty($fixedSelPos)){
            $posNames = ['1'=>'千', '2'=>'百', '3'=>'十', '4'=>'个'];
            $fixedPosNames = array_map(function($p) use ($posNames){ return $posNames[$p] ?? $p; }, $fixedSelPos);
            $posDesc = '，定位：' . implode('、', $fixedPosNames) . '位';
        }
        $betDesc = $label . "[x:{$x}]：随机" . count($codes) . "组{$posDesc}";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '随机x组号码', [
            'plan_id' => $plan->id,
            'lottery_type' => $lottery_type,
            'playway' => $playway,
            'x' => $x,
            'fixed_sel_pos' => $fixedSelPos,
            'total_count' => $totalCount,
            'result_count' => count($codes),
        ]);

        return $codes;
    }

    # 随机位置号码个数
    public static function filter41(object $plan, $dynamic=[], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $params = $dynamic['params'];
        $x = !empty($params['x']) ? (int)$params['x'] : 0; // 位置1（千位）随机号码个数
        $y = !empty($params['y']) ? (int)$params['y'] : 0; // 位置2（百位）随机号码个数
        $z = !empty($params['z']) ? (int)$params['z'] : 0; // 位置3（十位）随机号码个数
        $h = !empty($params['h']) ? (int)$params['h'] : 0; // 位置4（个位）随机号码个数

        // 位置名称映射
        $posNames = ['1'=>'千', '2'=>'百', '3'=>'十', '4'=>'个'];
        
        // 所有可选号码 0-9
        $allNumbers = range(0, 9);
        
        // 存储每个位置随机选择的号码
        $posNumbers = [];
        $posConfigs = [
            '1' => ['count' => $x, 'name' => '千'],
            '2' => ['count' => $y, 'name' => '百'],
            '3' => ['count' => $z, 'name' => '十'],
            '4' => ['count' => $h, 'name' => '个'],
        ];
        
        // 随机选择每个位置的号码
        foreach ($posConfigs as $pos => $config) {
            $count = $config['count'];
            if ($count > 0 && $count <= 10) {
                // 从0-9中随机选择count个号码
                shuffle($allNumbers);
                $posNumbers[$pos] = array_slice($allNumbers, 0, $count);
                sort($posNumbers[$pos]); // 排序以便显示
            } else {
                // 如果未填写或无效，使用所有号码0-9
                $posNumbers[$pos] = $allNumbers;
            }
        }
        
        // 如果所有位置都没有填写，返回空数组
        if ($x <= 0 && $y <= 0 && $z <= 0 && $h <= 0) {
            $label = !empty($filterDesc['label']) ? $filterDesc['label'] : '随机位置号码个数';
            $betDesc = $label . "[x:{$x}, y:{$y}, z:{$z}, h:{$h}]：参数无效，返回空数组";
            NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);
            return [];
        }
        
        // 直接使用数据库查询，根据每个位置的号码范围进行过滤
        $query = Num4Type::find()
            ->select(['code', 'code_type'])
            ->andWhere(['=', 'code_type', $playway+1]);
        
        // 根据每个位置的随机号码设置查询条件
        if (!empty($posNumbers['1']) && count($posNumbers['1']) < 10) {
            $query->andWhere(['IN', 'code_1', $posNumbers['1']]);
        }
        if (!empty($posNumbers['2']) && count($posNumbers['2']) < 10) {
            $query->andWhere(['IN', 'code_2', $posNumbers['2']]);
        }
        if (!empty($posNumbers['3']) && count($posNumbers['3']) < 10) {
            $query->andWhere(['IN', 'code_3', $posNumbers['3']]);
        }
        if (!empty($posNumbers['4']) && count($posNumbers['4']) < 10) {
            $query->andWhere(['IN', 'code_4', $posNumbers['4']]);
        }
        
        // 对于二定和三定，需要处理定位置（X位置）
        if ($playway != 3) {
            $codeHz = Json::decode($plan->hz_Arr);
            $fixedSelPos = [];
            if(!empty($codeHz['fixed_sel_pos'])){
                $fixedSelPos = array_map('trim', explode(',', $codeHz['fixed_sel_pos']));
                $fixedSelPos = array_filter($fixedSelPos);
            }
            
            // 如果没有指定定位置，根据玩法类型随机选择
            if(empty($fixedSelPos)){
                $allPos = NumService::$ALL_POSES;
                if($playway == 2){
                    // 三定：随机选择一个位置为X
                    $randomIndex = array_rand($allPos);
                    $xPos = $allPos[$randomIndex];
                    $fixedSelPos = array_values(array_diff($allPos, [$xPos]));
                }elseif($playway == 1){
                    // 二定：随机选择两个位置为X
                    $randomIndices = array_rand($allPos, 2);
                    $xPos = [$allPos[$randomIndices[0]], $allPos[$randomIndices[1]]];
                    $fixedSelPos = array_values(array_diff($allPos, $xPos));
                }
            }
            
            // 将定位置设置为X（这些位置不受随机号码限制）
            foreach ($fixedSelPos as $pos) {
                $query->andWhere(['=', 'code_'.$pos, 'X']);
            }
        }
        
        // 执行查询
        $NumTypes = $query->asArray()->all();
        $validCodes = ArrayHelper::getColumn($NumTypes, 'code');
        
        // 添加下注描述
        $label = !empty($filterDesc['label']) ? $filterDesc['label'] : '随机位置号码个数';
        $posDesc = [];
        foreach ($posConfigs as $pos => $config) {
            if ($config['count'] > 0 && $config['count'] <= 10) {
                $selectedNums = isset($posNumbers[$pos]) ? implode('', $posNumbers[$pos]) : '';
                $posDesc[] = $config['name'] . "位{$config['count']}个(" . $selectedNums . ")";
            }
        }
        $posDescStr = implode('、', $posDesc);
        $betDesc = $label . "[x:{$x}, y:{$y}, z:{$z}, h:{$h}]：{$posDescStr}，共" . count($validCodes) . "个号码";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);
        
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '随机位置号码个数', [
            'plan_id' => $plan->id,
            'lottery_type' => $lottery_type,
            'playway' => $playway,
            'x' => $x,
            'y' => $y,
            'z' => $z,
            'h' => $h,
            'pos_numbers' => $posNumbers,
            'result_count' => count($validCodes),
        ]);
        
        return $validCodes;
    }

    /**
     * 两数分离：指定两个数字不能同时上奖
     * x:1 和值两数分离—上期和值的两位不能同时上奖（如5916和值21，则2、1不能同时上奖）
     * x:2 期号尾号最后两位不能同时上奖（如20260203123，则2、3不能同时上奖）
     * @param object $plan
     * @param array $dynamic
     * @param array $filterDesc
     * @return array
     */
    public static function filter42(object $plan, $dynamic = [], $filterDesc = []): array
    {
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        $params = $dynamic['params'];
        $x = (string)($params['x'] ?? '');
        if ($x === '') {
            throw_info('两数分离参数x不能为空，x:1 和值两数分离，x:2 期号尾号最后两位');
        }

        $d1 = null;
        $d2 = null;
        $sourceDesc = '';

        if ($x === '1') {
            // 和值两数分离：上期开奖和值的十位、个位不能同时上奖
            $targetQiHao = QihaoService::getLastQiHao($lottery_type, $nextQiHao, 1);
            if (empty($targetQiHao)) {
                throw_info('无法获取上期开奖数据');
            }
            $targetKjData = NumCodeService::getKjData($targetQiHao, $lottery_type);
            if (empty($targetKjData) || !isset($targetKjData['codes_hz'])) {
                throw_info('上期开奖和值数据不存在');
            }
            $sum = (int)$targetKjData['codes_hz'];
            $d1 = (string)((int)floor($sum / 10) % 10);
            $d2 = (string)($sum % 10);
            $sourceDesc = "上期和值{$sum}，两数{$d1}、{$d2}不能同时上奖";
        } elseif ($x === '2') {
            // 期号尾号最后两位不能同时上奖
            $tail2 = substr($nextQiHao, -2);
            if (strlen($tail2) < 2) {
                throw_info('期号长度不足，无法取最后两位');
            }
            $d1 = $tail2[0];
            $d2 = $tail2[1];
            $sourceDesc = "期号{$nextQiHao}最后两位{$d1}、{$d2}不能同时上奖";
        } else {
            throw_info('两数分离参数x只能为1或2，x:1 和值两数分离，x:2 期号尾号最后两位');
        }

        if ($d1 === $d2) {
            // 两数相同：“同时上奖”视为该数出现至少两次，排除该数出现>=2次的号码
            $countExpr = "((code_1=:d1)+(code_2=:d1)+(code_3=:d1)+(code_4=:d1))";
            $excludeQuery = (new \yii\db\Query())
                ->select(['code'])
                ->from('lt_num4_type')
                ->where(['code_type' => $playway + 1])
                ->andWhere(['>=', new \yii\db\Expression($countExpr), 2])
                ->addParams([':d1' => $d1]);
            $excludeCodes = $excludeQuery->column();
        } else {
            // 排除：四个位置中既出现 d1 又出现 d2 的号码
            $excludeQuery = (new \yii\db\Query())
                ->select(['code'])
                ->from('lt_num4_type')
                ->where(['code_type' => $playway + 1])
                ->andWhere(['or', ['code_1' => $d1], ['code_2' => $d1], ['code_3' => $d1], ['code_4' => $d1]])
                ->andWhere(['or', ['code_1' => $d2], ['code_2' => $d2], ['code_3' => $d2], ['code_4' => $d2]]);
            $excludeCodes = $excludeQuery->column();
        }

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway + 1]);

        if (!empty($excludeCodes)) {
            $query->andWhere(['not in', 'code', $excludeCodes]);
        }

        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');

        $betDesc = ($filterDesc['label'] ?? '两数分离') . "[x:{$x}]：" . $sourceDesc;
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc);

        return $codes;
    }
}
