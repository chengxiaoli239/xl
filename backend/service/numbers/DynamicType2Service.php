<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\service\BaseService;
use backend\service\NumService;
use common\helpers\Code;
use common\service\ssc\QihaoService;
use common\service\ssc\SscKjDataService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

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
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);

        //$historyKjData = NumCodeService::getKjData($currentKjQiHao, $lottery_type);
        $params = $dynamic['params'];
        $x = $params['x'];
        $filterCodesQuery = SscKjData::find()->select(['code_4n_str'])->where(['>=', 'created_at', (time()-$x*86400)])->andWhere(['lottery_type'=>$lottery_type]);
        //p($filterCodesQuery->createCommand()->getRawSql());
        $filterCodes = $filterCodesQuery->column();
        $where = ['NOT IN', 'code', $filterCodes];

        $playway = $plan->playway;

        $query = (new \yii\db\Query())
            ->select(['code', 'code_type'])
            ->from('lt_num4_type')
            ->where(['code_type' => $playway+1])
            ->andWhere($where);

        $sql = $query->createCommand()->getRawSql();//p($sql);
        Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '过滤两个位置一样的所有号码', [
            'plan_id'=>$plan->id,
            'lottery_type'=>$lottery_type,
            'current_kj_qihao'=>$currentKjQiHao,
            'sql'=>$sql
        ]);
        $results = $query->all();
        $codes = ArrayHelper::getColumn($results, 'code');
        //p(['count'=>count($codes), 'historyKjData'=>$historyKjData, /*'codes'=>$codes*/]);
        $betDesc = $filterDesc['desc'].":过滤近".$x."天直码(四定)";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

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
