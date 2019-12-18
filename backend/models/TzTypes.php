<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%tz_types}}".
 *
 * @property int $id
 * @property string $type 投注/购买类型
 * @property string $type_name 类型名称
 * @property int $playway 投注方式:1二字定2三字定3四字定
 * @property int $status 状态
 * @property string $codes 组合号码
 * @property int $sort 排序
 * @property string $desc 描述
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class TzTypes extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tz_types}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['playway', 'status', 'sort', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['type'], 'string', 'max' => 255],
            [['type_name'], 'string', 'max' => 64],
            [['codes', 'desc'], 'string', 'max' => 640],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type' => Yii::t('app', '投注/购买类型'),
            'type_name' => Yii::t('app', '类型名称'),
            'playway' => Yii::t('app', '投注方式:1二字定2三字定3四字定'),
            'status' => Yii::t('app', '状态'),
            'codes' => Yii::t('app', '组合号码'),
            'sort' => Yii::t('app', '排序'),
            'desc' => Yii::t('app', '描述'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return TzTypesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TzTypesQuery(get_called_class());
    }
}
