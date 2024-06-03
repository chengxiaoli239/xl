<?php

namespace common\service\open;
use backend\models\TzSystems;
use common\open\aozhou5\api\UserApi;
use common\service\chat\Tool_Common;
use common\service\open\aozhou5\ActionService;

class ActionBaseService
{

    /**
     * @param $systemTypeId
     * @return ActionService|false
     */
    public static function getClass($systemTypeId)
    {
        $classes = [
            \common\helpers\LotteryType::TZ_SYSTEM_TYPE_ID_AZ => new \common\service\open\aozhou5\ActionService(), # 澳洲五系统
        ];
        return $classes[$systemTypeId]??false;
    }

    public function login($tzSystemsUser, $isAuto=1): array
    {
        $TzSystems = TzSystems::findOne($tzSystemsUser->tz_system_id);
        $objectClass = self::getClass($TzSystems->system_type_id);
        $objectClass->domain = $tzSystemsUser->ssc_domain;
        $objectClass->account = $tzSystemsUser->account;
        $objectClass->password = $tzSystemsUser->password;
        $objectClass->tzSystemUsers = $tzSystemsUser;
        $objectClass->securityCode = $tzSystemsUser->secure_code;

        try {
            $userInfo = $objectClass->getUserInfo();
        }catch (\Exception $e){}

        if($isAuto==2 OR empty($tzSystemsUser->cookie) OR empty($userInfo)){
            //$objectClass->preLogin();
            $objectClass->login();
            $userInfo = $objectClass->getUserInfo();
        }
        if(!empty($userInfo)){
            $userInfo = $objectClass->memberThreadMd();
        }

        return [0, $userInfo, '完成'];
    }

    public function getUserInfo($tzSystemsUser): array
    {
        $TzSystems = TzSystems::findOne($tzSystemsUser->tz_system_id);
        $objectClass = self::getClass($TzSystems->system_type_id);
        $objectClass->domain = $tzSystemsUser->ssc_domain;
        $objectClass->tzSystemUsers = $tzSystemsUser;

        return $objectClass->getUserInfo()?:[];
    }

    /**
     * 获取盘口报表信息
     * @param $tzSystemsUser
     * @return array
     */
    public function getSiteStaticsInfo($tzSystemsUser): array
    {
        $TzSystems = TzSystems::findOne($tzSystemsUser->tz_system_id);
        $objectClass = self::getClass($TzSystems->system_type_id);
        $objectClass->domain = $tzSystemsUser->ssc_domain;
        $objectClass->tzSystemUsers = $tzSystemsUser;

        return $objectClass->getSiteStatics()?:[];
    }
}