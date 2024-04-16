<?php

namespace common\service\open;
use common\helpers\LotteryType;
use common\service\open\aozhou5\ActionService;

class ActionBaseService
{

    /**
     * @param $tzSystemId
     * @return ActionService|false
     */
    public static function getClass($tzSystemId)
    {
        $classes = [
            \common\helpers\LotteryType::TZ_SYSTEM_ID_AZ_LUCKY_5 => new \common\service\open\aozhou5\ActionService(), # 澳洲五系统
        ];
        return $classes[$tzSystemId]??false;
    }

    public function login($tzSystemsUser)
    {
        $objectClass = self::getClass($tzSystemsUser->tz_system_id);
        $objectClass->domain = $tzSystemsUser->ssc_domain;
        $objectClass->account = $tzSystemsUser->account;
        $objectClass->password = $tzSystemsUser->password;
        $objectClass->tzSystemUsers = $tzSystemsUser;

        $objectClass->login();
        $objectClass->getUserInfo();

        return true;
    }

    public function getUserInfo($tzSystemsUser): bool
    {
        $objectClass = self::getClass($tzSystemsUser->tz_system_id);
        $objectClass->domain = $tzSystemsUser->ssc_domain;
        $objectClass->tzSystemUsers = $tzSystemsUser;

        return $objectClass->getUserInfo();
    }
}