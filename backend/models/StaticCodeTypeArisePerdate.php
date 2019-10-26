<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_code_type_arise_perdate}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property int $type_2 双重
 * @property int $type_3 三重
 * @property int $type_22 双双重
 * @property int $type_2b 两兄弟
 * @property int $type_3b 三兄弟
 * @property int $type_4b 四兄弟
 * @property int $type_2_type_2b 双重&两兄弟
 * @property int $type_2_type_3b 双重&三兄弟
 * @property int $type_3n_2b 三现:双重+兄弟
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticCodeTypeArisePerdate extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_code_type_arise_perdate}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type_2', 'type_3', 'type_22', 'type_2b', 'type_3b', 'type_4b', 'type_2_type_2b', 'type_2_type_3b', 'type_3n_2b', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
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
            'type_2' => Yii::t('app', '双重'),
            'type_3' => Yii::t('app', '三重'),
            'type_22' => Yii::t('app', '双双重'),
            'type_2b' => Yii::t('app', '两兄弟'),
            'type_3b' => Yii::t('app', '三兄弟'),
            'type_4b' => Yii::t('app', '四兄弟'),
            'type_2_type_2b' => Yii::t('app', '双重&两兄弟'),
            'type_2_type_3b' => Yii::t('app', '双重&三兄弟'),
            'type_3n_2b' => Yii::t('app', '三现:双重+兄弟'),
            'lottery_type' => Yii::t('app', '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return StaticCodeTypeArisePerdateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticCodeTypeArisePerdateQuery(get_called_class());
    }
}
