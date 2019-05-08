<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_kj_data_ds}}".
 *
 * @property int $id
 * @property string $code_str 开奖号码str
 * @property int $code_1 1位
 * @property int $code_2 2位
 * @property int $code_3 3位
 * @property int $code_4 4位
 * @property int $code_1_2 1、2位
 * @property int $code_1_3 1、3位
 * @property int $code_1_4 1、4位
 * @property int $code_2_3 2、3位
 * @property int $code_2_4 2、4位
 * @property int $code_3_4 3、4位
 * @property int $code_1_2_3 1、2、3位
 * @property int $code_1_2_4 1、2、4位
 * @property int $code_1_3_4 1、3、4位
 * @property int $code_2_3_4 2、3、4位
 * @property int $code_1_2_3_4 1、3、4位
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $qihao 期号
 * @property string $date 开奖日期
 * @property int $created_at 创建时间
 * @property string $update_time 创建时间
 * @property int $updated_at 更新时间
 */
class SscKjDataDs extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_kj_data_ds}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code_1', 'code_2', 'code_3', 'code_4', 'code_1_2', 'code_1_3', 'code_1_4', 'code_2_3', 'code_2_4', 'code_3_4', 'code_1_2_3', 'code_1_2_4', 'code_1_3_4', 'code_2_3_4', 'code_1_2_3_4', 'lottery_type', 'qihao', 'created_at', 'updated_at'], 'integer'],
            [['date', 'update_time'], 'safe'],
            [['code_str'], 'string', 'max' => 24],
            [['qihao'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code_str' => '开奖号码str',
            'code_1' => '1位',
            'code_2' => '2位',
            'code_3' => '3位',
            'code_4' => '4位',
            'code_1_2' => '1、2位',
            'code_1_3' => '1、3位',
            'code_1_4' => '1、4位',
            'code_2_3' => '2、3位',
            'code_2_4' => '2、4位',
            'code_3_4' => '3、4位',
            'code_1_2_3' => '1、2、3位',
            'code_1_2_4' => '1、2、4位',
            'code_1_3_4' => '1、3、4位',
            'code_2_3_4' => '2、3、4位',
            'code_1_2_3_4' => '1、3、4位',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'qihao' => '期号',
            'date' => '开奖日期',
            'created_at' => '创建时间',
            'update_time' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return SscKjDataDsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscKjDataDsQuery(get_called_class());
    }
}
