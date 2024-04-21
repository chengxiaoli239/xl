<?php

namespace backend\models\open;

use common\models\open\PlatformGroup as CommonPlatformGroup;
use yii\helpers\ArrayHelper;

class PlatformGroup extends CommonPlatformGroup
{

    /**
     * @param $userId
     * @return array [群id=>群名称。。。]
     */
    public static function getGroups($userId): array
    {
        $groups = PlatformGroup::find()->select(['group_id', 'name'])
            ->where(['user_id'=>$userId])
            ->asArray()->all();
        // 新的关联数组
        $groupsInfo = [];
        foreach ($groups as $item) {
            $groupsInfo[$item['group_id']] = $item['name'];
        }

        return $groupsInfo;
    }

}
