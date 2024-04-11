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

class SscKjDataService extends CommonService
{
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


}
