<?php
namespace common\service\cache\keys\lottery;

trait LotteryCacheKeyTrait
{
    /**
     * 管理员lottery key
     * @param int $grabDataStatus
     * @return string
     */
    public static function lotteryData(int $grabDataStatus=1): string
    {
        return 'lottery:type:grabDataStatus_x0' . $grabDataStatus;
    }
}
