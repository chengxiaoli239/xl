<?php
namespace common\models\eyun;

use common\models\base\BaseModel;

class EYunMessage extends BaseModel
{
    const STATUS_WAIT = 0;
    const STATUS_ACTINT = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAILED = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%eyun_message}}';
    }

}
