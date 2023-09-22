<?php
namespace common\models\thirdD;

use common\models\base\BaseModel;

class PlayMethod extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%thirdd_playmethod}}';
    }

}
