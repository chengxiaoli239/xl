<?php


namespace common\service\lottery;

use backend\models\LotteryType;
use common\service\BaseService;
use common\service\cache\CacheKeyService;
use common\tools\Tool_Common;
use yii\helpers\Json;

class LotteryTypeService extends BaseService
{
    public static function getLotteryTypeData($grabStatus=''): array
    {
        $mkey = CacheKeyService::lotteryData($grabStatus);

        $lotteryTypeData = commonRedis()->get($mkey);
        if(empty($lotteryTypeData)){
            $lotteryTypeDataQuery = LotteryType::find();
            if($grabStatus !== ''){
                $lotteryTypeDataQuery->where(['grabDataStatus'=>(int)$grabStatus]);
            }
            $lotteryTypeData = $lotteryTypeDataQuery->indexBy(['lottery_type'])->asArray()->all();

            commonRedis()->setex($mkey, 1800, $lotteryTypeData);
        }

        return $lotteryTypeData;
    }

}
