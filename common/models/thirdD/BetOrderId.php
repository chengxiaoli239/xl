<?php
namespace common\models\thirdD;

use common\models\base\BaseModel;

class BetOrderId extends BaseModel
{
    /**
     * @var int|mixed|null
     */
    public $created_at;
    /**
     * @var int|mixed|null
     */
    public $updated_at;

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%bets_order_id}}';
    }

}
