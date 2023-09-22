<?php
namespace common\models\thirdD;

use common\models\base\BaseModel;

class BetOrderId extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bets_order_id}}';
    }

}
