<?php
namespace common\service\cache\keys\lottery;

trait LotteryCacheKeyTrait
{
    /**
     * ¿ª½±²ÊÖÖ»º´ækey
     * @param int $grabDataStatus
     * @return string
     */
    public static function lotteryData(int $grabDataStatus=1): string
    {
        return 'lottery:type:grabDataStatus_x0' . $grabDataStatus;
    }
}
