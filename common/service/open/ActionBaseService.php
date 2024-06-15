<?php

namespace common\service\open;
use backend\models\TzSystems;
use common\open\aozhou5\api\UserApi;
use common\service\chat\Tool_Common;
use common\service\open\aozhou5\ActionService;

class ActionBaseService
{
    public object $tzSystemUsers;
    public string $domain;
    public string $account;
    public string $password;
    public string $objectClass;
    public string $securityCode;
    public function __construct($tzSystemsUsers=null)
    {
        $this->tzSystemUsers = $tzSystemsUsers;
        $TzSystems = TzSystems::findOne($tzSystemsUsers->tz_system_id);
        #$this->objectClass = self::getClass($TzSystems->system_type_id);
        $this->domain = $tzSystemsUsers->ssc_domain;
        $this->account = $tzSystemsUsers->account;
        $this->password = $tzSystemsUsers->password;
        $this->securityCode = $tzSystemsUsers->secure_code;
    }

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

    public function login($isAuto=1): array
    {
        return [];
    }

    public function getUserInfo(): array
    {
        return $this->getUserInfo()?:[];
    }

}