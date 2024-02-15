<?php
namespace common\service\ssc;
/**
 * Created by PhpStorm.
 *   
 * Date: 2018/05/06
 * Time: 09:40
 */

use backend\models\DataDealStatus;
use backend\models\SscKjDataDs;
use common\service\cache\CacheKeyService;
use common\service\CommonService;
use common\tools\KjDataGet;


class QihaoService extends CommonService
{


    /**
     * @description 获取开始日期到今天的所有期号
     * @param string $date_start
     * @return array
     */
    public static function getQihaos($date_start = '20180101'){
        $qihaos = [];
        $date_end = date('Ymd');
        $dateArr = CommonService::genDateArr($split = '-', $date_start, $date_end);
        foreach ($dateArr as $date){
            $tmpDateQihaos = [];
            for( $qihao = $date.'001'; $qihao <= $date.'120'; $qihao++ ){
                $tmpDateQihaos[] = str_replace('-','',$qihao);
            }
            $qihaos[$date] = $tmpDateQihaos;
        }

        return $qihaos;
    }

    /**
     * @param int $lottery_type
     * @return array|mixed
     */
    public static function getKjQiHao(int $lottery_type=DEFAULT_LOTTERY_TYPE)
    {
        $mkey = CacheKeyService::lotteryQiHaoInfo($lottery_type);
        $data = commonRedis()->get($mkey);
        if(empty($data)){
            $whereNext = ['AND', ['=', 'lottery_type', $lottery_type], ['IS NOT', 'next_qihao', NULL]];
            $DataDealStatus = DataDealStatus::find()->where($whereNext)->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
            $currentKjQihao = $DataDealStatus['qihao'];
            $nextQihao = $DataDealStatus['next_qihao'];
            $data = [$currentKjQihao, $nextQihao];
            commonRedis()->setex($mkey, 10, $data);
        }

        return $data;
    }

    /**
     * @param int $lottery_type
     * @return bool|int|string
     */
    public static function getNextStaticDsQiHao(int $lottery_type = DEFAULT_LOTTERY_TYPE){
        $last_qihao = SscKjDataDs::find()->select(['qihao as last_qihao'])->where(['lottery_type'=>$lottery_type])
            ->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one()['last_qihao'];

        $next_qihao = KjDataGet::getNextQihaoByQihao($last_qihao, $lottery_type);

        return $next_qihao;
    }
}
