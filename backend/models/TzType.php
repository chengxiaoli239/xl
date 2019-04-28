<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%tz_type}}".
 *
 * @property int $id
 * @property string $name 彩种
 * @property string $lottery_type 投注类型ssc、qxc
 * @property string $tz_route 投注路由
 * @property int $system_id 系统id
 * @property int $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class TzType extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tz_type}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['system_id', 'created_at'], 'integer'],
            [['updated_at'], 'safe'],
            [['name'], 'string', 'max' => 64],
            [['lottery_type'], 'string', 'max' => 11],
            [['tz_route'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', '彩种'),
            'lottery_type' => Yii::t('app', '投注类型ssc、qxc'),
            'tz_route' => Yii::t('app', '投注路由'),
            'system_id' => Yii::t('app', '系统id'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
        ];
    }
}
