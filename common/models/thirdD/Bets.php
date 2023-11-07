<?php
namespace common\models\thirdD;

use common\models\base\BaseModel;

class Bets extends BaseModel
{
    const LOTTERY_FUCAI = 26;
    const LOTTERY_PL3 = 27;
    const LOTTERYS = [
        self::LOTTERY_FUCAI => '福',
        self::LOTTERY_PL3 => '排',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bets}}';
    }

}
