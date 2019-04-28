<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_4d_profits_perdate}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property string $codes_4d_all 所有号码
 * @property string $codes_13_31 一双三单||一单三双
 * @property string $codes_22_22 两双两单
 * @property string $codes_1111_2222 四双四单
 * @property string $codes_13 一单三双
 * @property string $codes_31 一双三单
 * @property string $codes_13_2222 一单三双||四双
 * @property string $codes_31_1111 一双三单||四单
 * @property string $codes_31_2222 一双三单||四双
 * @property string $codes_13_1111 一单三双||四单
 * @property string $codes_31_2222_1111 一双三单||四双||四单
 * @property string $codes_13_1111_2222 一单三双||四单||四双
 * @property string $codes_2222 四双
 * @property string $codes_1111 四单
 * @property int $codes_1_nums 单数量
 * @property int $codes_2_nums 双数量
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class Static4dProfitsPerdate extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_4d_profits_perdate}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['codes_4d_all', 'codes_13_31', 'codes_22_22', 'codes_1111_2222', 'codes_13', 'codes_31', 'codes_13_2222', 'codes_31_1111', 'codes_31_2222', 'codes_13_1111', 'codes_31_2222_1111', 'codes_13_1111_2222', 'codes_2222', 'codes_1111'], 'number'],
            [['codes_1_nums', 'codes_2_nums', 'created_at', 'updated_at'], 'integer'],
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
            'codes_4d_all' => Yii::t('app', '所有号码'),
            'codes_13_31' => Yii::t('app', '一双三单||一单三双'),
            'codes_22_22' => Yii::t('app', '两双两单'),
            'codes_1111_2222' => Yii::t('app', '四双四单'),
            'codes_13' => Yii::t('app', '一单三双'),
            'codes_31' => Yii::t('app', '一双三单'),
            'codes_13_2222' => Yii::t('app', '一单三双||四双'),
            'codes_31_1111' => Yii::t('app', '一双三单||四单'),
            'codes_31_2222' => Yii::t('app', '一双三单||四双'),
            'codes_13_1111' => Yii::t('app', '一单三双||四单'),
            'codes_31_2222_1111' => Yii::t('app', '一双三单||四双||四单'),
            'codes_13_1111_2222' => Yii::t('app', '一单三双||四单||四双'),
            'codes_2222' => Yii::t('app', '四双'),
            'codes_1111' => Yii::t('app', '四单'),
            'codes_1_nums' => Yii::t('app', '单数量'),
            'codes_2_nums' => Yii::t('app', '双数量'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return Static4dProfitsPerdateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new Static4dProfitsPerdateQuery(get_called_class());
    }
}
