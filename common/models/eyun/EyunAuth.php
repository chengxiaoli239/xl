<?php
namespace common\models\eyun;

use common\models\base\BaseModel;

class EyunAuth extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%eyun_auth}}';
    }

}
