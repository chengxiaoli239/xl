<?php

use yii\db\Migration;

class m260727_000001_allow_member_switch_bet_location extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole('收费会员');
        $permission = $auth->getPermission('/forum/user/switch-is-local-bet');
        if(!$permission){
            $permission = $auth->createPermission('/forum/user/switch-is-local-bet');
            $permission->description = '切换下注位置';
            $auth->add($permission);
        }
        if($role && $permission && !$auth->hasChild($role, $permission)){
            $auth->addChild($role, $permission);
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole('收费会员');
        $permission = $auth->getPermission('/forum/user/switch-is-local-bet');
        if($role && $permission && $auth->hasChild($role, $permission)){
            $auth->removeChild($role, $permission);
        }
    }
}
