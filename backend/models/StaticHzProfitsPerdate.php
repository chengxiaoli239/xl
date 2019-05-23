<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%static_hz_profits_perdate}}".
 *
 * @property int $id
 * @property string $date 日期
 * @property string $hz_0_4 0到4
 * @property string $hz_1_6 1到6
 * @property string $hz_5_10 5到10
 * @property string $hz_11_15 11到15
 * @property string $hz_16_19 16到19
 * @property string $hz_20_24 20到24
 * @property string $hz_25_29 25到29
 * @property string $hz_30_35 30到35
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class StaticHzProfitsPerdate extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_hz_profits_perdate}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hz_0_4', 'hz_1_6', 'hz_5_10', 'hz_11_15', 'hz_16_19', 'hz_20_24', 'hz_25_29', 'hz_30_35'], 'number'],
            [['lottery_type', 'created_at', 'updated_at'], 'integer'],
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
            'id' => 'ID',
            'date' => '日期',
            'hz_0_4' => '0到4',
            'hz_1_6' => '1到6',
            'hz_5_10' => '5到10',
            'hz_11_15' => '11到15',
            'hz_16_19' => '16到19',
            'hz_20_24' => '20到24',
            'hz_25_29' => '25到29',
            'hz_30_35' => '30到35',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return StaticHzProfitsPerdateQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StaticHzProfitsPerdateQuery(get_called_class());
    }
}
