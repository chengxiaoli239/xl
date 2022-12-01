<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%queue_log}}".
 *
 * @property int $id
 * @property string $system_queue_id 系统返回的队列消息ID
 * @property string $business_id 业务ID标识
 * @property string $params 请求参数，json格式
 * @property string $remark 结果备注
 * @property int $count 重试次数
 * @property string $name
 * @property string $job_class 队列处理类
 * @property string $job_class_md5 队列处理类
 * @property int $status 1-待处理 2-处理完成 2-处理失败
 * @property int $time 运行时长，秒
 * @property int $last_push_time 最后一次入列时间
 * @property int $complete_time 完成时间
 * @property int $delay 队列延迟时间
 * @property string $type
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */

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
        return '{{%queue_log}}';
    }

    public static function getStatusText($status)
    {
        return self::STATUS_TEXT[$status];
    }



    
}
