<?php
namespace common\models\thirdD;

use common\models\base\BaseModel;

class Bets extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bets}}';
    }

}
