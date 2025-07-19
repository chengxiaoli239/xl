<?php


namespace common\service\lottery;

use backend\models\LotteryType;
use backend\service\StaticService;
use common\service\BaseService;
use common\service\cache\CacheKeyService;
use common\tools\Tool_Common;
use yii\helpers\Json;

class LotteryTypeService extends BaseService
{
    public static function getLotteryTypeData($grabStatus='', $useCache=1): array
    {
        $mKey = CacheKeyService::lotteryData($grabStatus);

        $lotteryTypeData = commonRedis()->get($mKey);
        if(!$useCache OR empty($lotteryTypeData)){
            $lotteryTypeDataQuery = LotteryType::find();
            if($grabStatus !== ''){
                $lotteryTypeDataQuery->where(['grabDataStatus'=>(int)$grabStatus]);
            }
            $lotteryTypeData = $lotteryTypeDataQuery->indexBy(['lottery_type'])->asArray()->all();

            commonRedis()->setex($mKey, 1800, $lotteryTypeData);
        }

        return $lotteryTypeData;
    }

    /**
     * «Â¿Ìª∫¥Ê
     * @param int $lotteryType
     * @return void
     */
    public static function clearCache(int $lotteryType=8, $useCache=0)
    {
        LotteryTypeService::getLotteryTypeData($grabDataStatus=1, $useCache);
        LotteryTypeService::getLotteryTypeData($grabDataStatus='', $useCache);
        StaticService::getGrabDataLotteryTypes($useCache);
    }

}
