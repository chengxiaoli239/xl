<?php
namespace backend\service\numbers;

use backend\models\Num4Type;
use backend\service\BaseService;
use backend\service\NumService;
use common\helpers\Code;
use common\service\ssc\QihaoService;
use common\tools\Tools;
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
        $hCode = $params['x'];

        $hCodeArr = Code::codeStringToArray($hCode); # 合分数组
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
        $betDesc = "两合上1[".$hCode."]号码:"."合".implode(',', $hfArr)."(四定)";
        NumCodeService::addBetDescRand($plan->id, $nextQiHao, $betDesc); # 添加动态计划下注描述

        return $codes;
    }

}
