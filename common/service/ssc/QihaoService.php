<?php
namespace common\service\ssc;
/**
 * Created by PhpStorm.
 *
 * Date: 2018/05/06
 * Time: 09:40
 */

use backend\models\DataDealStatus;
use backend\models\SscKjData;
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
     * 返回当前已经开奖的期号和下一期期号
     * @param int $lottery_type
     * @return array|mixed
     */
    public static function getKjQiHao(int $lottery_type=DEFAULT_LOTTERY_TYPE)
    {
        $mKey = CacheKeyService::lotteryQiHaoInfo($lottery_type);
        $data = commonRedis()->get($mKey);
        if(empty($data) OR true){
            $whereNext = ['AND', ['=', 'lottery_type', $lottery_type], ['IS NOT', 'next_qihao', NULL]];
            $DataDealStatus = DataDealStatus::find()->select(['qihao', 'next_qihao'])
                ->where($whereNext)->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();
            $currentKjQihao = $DataDealStatus['qihao'];
            $nextQihao = $DataDealStatus['next_qihao'];
            $data = [$currentKjQihao, $nextQihao];
            commonRedis()->setex($mKey, 2, $data);
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

    /**
     * 获取指定期数的上一期期号
     * @param int $lottery_type 彩种类型
     * @param string $currentQiHao 当前期号
     * @param int $count 往前推几期，默认1期
     * @return string|false
     */
    public static function getLastQiHao(int $lottery_type, string $currentQiHao, int $count = 1)
    {
        if ($count < 1) {
            return false;
        }

        $targetQiHao = $currentQiHao;

        for ($i = 0; $i < $count; $i++) {
            // 获取上一期期号
            $targetQiHao = KjDataGet::getBeforeQiHaoByQiHao($targetQiHao, $lottery_type);

            if (empty($targetQiHao)) {
                return false;
            }
        }

        return $targetQiHao;
    }
}
