<?php
namespace common\models\thirdD;

use common\models\base\BaseModel;

class PlayMethod extends BaseModel
{
    const TYPE_3D = 1;
    const TYPE_GP = 2;
    const TYPE_OPTIONS = [
        self::TYPE_3D => '3D',
        self::TYPE_GP => '龟盘',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%playmethod}}';
    }

}
