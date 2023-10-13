<?php
namespace common\models\eyun;

use common\models\base\BaseModel;

class HistoryRobots extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%history_robots}}';
    }

}
