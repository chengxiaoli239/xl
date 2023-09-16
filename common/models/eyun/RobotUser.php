<?php
namespace common\models\eyun;

use common\models\base\BaseModel;

class RobotUser extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%robot_user}}';
    }

}
