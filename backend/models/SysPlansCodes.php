<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%sys_plans_codes}}".
 *
 * @property string $id
 * @property string $code 投注号码
 * @property int $playway 投注方式:1二字定2三字定3四字定
 * @property string $position 定位位置
 * @property int $status 类型:0计划外1计划内
 * @property int $tz_type 投注类型:1单双三字定2大小三字定3大小单双三字定
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SysPlansCodes extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sys_plans_codes}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code', 'created_at', 'updated_at'], 'required'],
            [['playway', 'status', 'tz_type', 'created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['code'], 'string', 'max' => 640],
            [['position'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'code' => Yii::t('app', '投注号码'),
            'playway' => Yii::t('app', '投注方式:1二字定2三字定3四字定'),
            'position' => Yii::t('app', '定位位置'),
            'status' => Yii::t('app', '类型:0计划外1计划内'),
            'tz_type' => Yii::t('app', '投注类型:1单双三字定2大小三字定3大小单双三字定'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }
}
