<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%tz_systems_auth}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property string $tz_systems_ids 系统类型id列表，lt_tz_systems.id
 * @property string $tz_types 投注方式tz_types
 * @property string $lottery_types 彩票类型
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class TzSystemsAuth extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tz_systems_auth}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['tz_systems_ids', 'tz_types', 'lottery_types'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => '用户id',
            'tz_systems_ids' => '系统类型id列表，lt_tz_systems.id',
            'tz_types' => '投注方式tz_types',
            'lottery_types' => '彩票类型',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return TzSystemsAuthQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TzSystemsAuthQuery(get_called_class());
    }
}
