<?php

namespace backend\models\sports;

use Yii;

/**
 * This is the model class for table "{{%sports_plates}}".
 *
 * @property int $id
 * @property string $plate_name 盘口名称
 * @property string $base_url 网盘域名
 * @property string $football_url 足球入口
 * @property string $tennis_url 网球入口
 * @property string $basketball_url 篮球入口
 * @property string $desc 描述备注
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SportsPlates extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sports_plates}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['desc'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['plate_name', 'football_url', 'tennis_url', 'basketball_url'], 'string', 'max' => 64],
            [['base_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'plate_name' => Yii::t('app', '盘口名称'),
            'base_url' => Yii::t('app', '网盘域名'),
            'football_url' => Yii::t('app', '足球入口'),
            'tennis_url' => Yii::t('app', '网球入口'),
            'basketball_url' => Yii::t('app', '篮球入口'),
            'desc' => Yii::t('app', '描述备注'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SportsPlatesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SportsPlatesQuery(get_called_class());
    }
}
