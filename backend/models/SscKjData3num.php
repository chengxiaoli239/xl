<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_kj_data_3num}}".
 *
 * @property int $id
 * @property string $code_str 开奖号码str
 * @property string $code_3n 三字现号码
 * @property int $qihao 期号
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property string $date 开奖日期
 * @property int $created_at 创建时间
 * @property string $update_time 创建时间
 * @property int $updated_at 更新时间
 */
class SscKjData3num extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_kj_data_3num}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['qihao', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['date', 'update_time'], 'safe'],
            [['code_str', 'code_3n'], 'string', 'max' => 24],
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
            'code_3n' => '三字现号码',
            'qihao' => '期号',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'date' => '开奖日期',
            'created_at' => '创建时间',
            'update_time' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return SscKjData3numQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscKjData3numQuery(get_called_class());
    }
}
