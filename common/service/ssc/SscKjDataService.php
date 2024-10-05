<?php
namespace common\service\ssc;
/**
 * Created by PhpStorm.
 *   
 * Date: 2018/05/06
 * Time: 09:40
 */

use backend\models\SscKjData;
use common\service\CommonService;
use common\service\jobs\kj_data\OperateKjDataJob;
use yii\helpers\ArrayHelper;

class SscKjDataService extends CommonService
{
    const LIMIT_GRAB_TIME = 180;
    /**
     * @description 获取本地开奖数据记录
     * @param int $lotteryType
     * @param string $qiHao
     * @return array
     */
    public static function getKjData(int $lotteryType = DEFAULT_LOTTERY_TYPE, string $qiHao=''): array
    {
        $SscKjData = SscKjData::find()->where(['lottery_type'=>$lotteryType, 'qihao'=>$qiHao])->limit(1)->asArray()->one();

        return $SscKjData?:[];
    }

    /**
     * 接收开奖数据 - 接收外部数据
     * @param array $kjData
     * @param int $lotteryType
     * @return array
     */
    public static function acceptKjData(array $kjData=[], int $lotteryType=DEFAULT_LOTTERY_TYPE): array
    {
        try {
            if(!$openCode = $kjData['opencode']){
                throw_info('开奖数据不能为空');
            }
            if(!$qiHao = $kjData['expect']){
                throw_info('开奖期号不能为空');
            }
            if(!$openTime = $kjData['opentime']){
                throw_info('开奖时间不能为空');
            }
            $params = [
                'kj_data' => [
                    'expect' => $qiHao,
                    'opencode' => $openCode,
                    'opentime' => $openTime,
                ],
                'lottery_type' => $lotteryType,
                'business_id' => $lotteryType,
            ];
            push_queue_fast(OperateKjDataJob::class, $params);
        }catch (\Exception $e){
            return ['status'=>300, 'msg'=>$e->getMessage()];
        }

        return ['status' => 200, 'msg'=>'接收成功'];
    }

    /**
     * 获取x位近num个码
     * @param int $lotteryType
     * @param array $positions
     * @param int $num
     * @return array
     */
    public static function getRecentlyPosCodes(int $lotteryType=DEFAULT_LOTTERY_TYPE, array $positions=[1], int $num=1): array
    {
        $positions_str = 'code'.implode(',",",code', $positions);
        $groupByPos = [];
        foreach ($positions as $pp){
            $groupByPos[] = 'code'.$pp;
        }
        $beforeQuery = SscKjData::find()->select(['codes_str'=>'CONCAT('.$positions_str.')', 'qihao'=>'MAX(qihao)'])
            ->where(['lottery_type'=>$lotteryType])->groupBy($groupByPos)->orderBy(['MAX(qihao)'=>SORT_DESC])->limit($num);
        list($currentKjQiHao, $nextQiHao) = QihaoService::getKjQiHao($lotteryType);
        $beforeQuery->andWhere(['<=', 'qihao', $currentKjQiHao]);
        //p($beforeQuery->createCommand()->getRawSql());
        $currentKjCodes = $beforeQuery->asArray()->all(); # 最新一期
        //p($currentKjCodes);

        return ArrayHelper::getColumn($currentKjCodes, 'codes_str');
    }
}
