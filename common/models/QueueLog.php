<?php

namespace common\models;

use Yii;

class QueueLog extends \yii\db\ActiveRecord
{
    const STATUS_TO_CONSUME = 1;
    const STATUS_CONSUMING = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_FAILED = 4;


    const STATUS_TEXT = [
        self::STATUS_TO_CONSUME => '待处理',
        self::STATUS_CONSUMING => '处理中',
        self::STATUS_SUCCESS => '完成',
        self::STATUS_FAILED =>'异常',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_log';
    }

    public static function getStatusText($status)
    {
        return self::STATUS_TEXT[$status];
    }



    
}
