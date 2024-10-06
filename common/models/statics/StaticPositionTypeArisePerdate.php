<?php

namespace common\models\statics;

use Yii;

/**
 * This is the model class for table "{{%static_position_type_arise_perdate}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property int $type 类型
 * @property int $p1_1 p1_1数量
 * @property int $p1_2 p1_2数量
 * @property int $p2_1 p2_1数量
 * @property int $p2_2 p2_2数量
 * @property int $p3_1 p3_1数量
 * @property int $p3_2 p3_2数量
 * @property int $p4_1 p4_1数量
 * @property int $p4_2 p4_2数量
 * @property int $p5_1 p5_1数量
 * @property int $p5_2 p5_2数量
 * @property int $lottery_type 彩种类型
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticPositionTypeArisePerdate extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_position_type_arise_perdate}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'p1_1', 'p1_2', 'p2_1', 'p2_2', 'p3_1', 'p3_2', 'p4_1', 'p4_2', 'p5_1', 'p5_2', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['date'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'date' => Yii::t('app', '日期'),
            'type' => Yii::t('app', '类型'),
            'p1_1' => Yii::t('app', 'p1_1数量'),
            'p1_2' => Yii::t('app', 'p1_2数量'),
            'p2_1' => Yii::t('app', 'p2_1数量'),
            'p2_2' => Yii::t('app', 'p2_2数量'),
            'p3_1' => Yii::t('app', 'p3_1数量'),
            'p3_2' => Yii::t('app', 'p3_2数量'),
            'p4_1' => Yii::t('app', 'p4_1数量'),
            'p4_2' => Yii::t('app', 'p4_2数量'),
            'p5_1' => Yii::t('app', 'p5_1数量'),
            'p5_2' => Yii::t('app', 'p5_2数量'),
            'lottery_type' => Yii::t('app', '彩种类型'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return StaticPositionTypeArisePerdateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticPositionTypeArisePerdateQuery(get_called_class());
    }
}
