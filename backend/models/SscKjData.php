<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_kj_data}}".
 *
 * @property int $id
 * @property int $index_id 顺序id
 * @property string $kj_code 开奖号码
 * @property string $code_str 开奖号码str
 * @property int $codes_hz 号码和值
 * @property int $codes_4nums_hz 前4位和值
 * @property int $code1 万位
 * @property int $code2 千位
 * @property int $code3 百位
 * @property int $code4 十位
 * @property int $code5 个位
 * @property int $code_1_2 1、2位和值
 * @property int $code_1_3 1、3位和值
 * @property int $code_1_4 1、4位和值
 * @property int $code_2_3 2、3位和值
 * @property int $code_2_4 2、4位和值
 * @property int $code_3_4 3、4位和值
 * @property int $qihao 期号
 * @property string $date 开奖日期
 * @property int $type_2 是否双重
 * @property int $type_22 是否双双重
 * @property int $type_3 是否三重
 * @property int $type_4 是否四重
 * @property int $type_2b 是否双重
 * @property int $type_3b 是否三兄弟
 * @property int $type_4b 是否四兄弟
 * @property int $type_4ds 单双：0非四单四双1四单2四双
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $created_at 创建时间
 * @property string $update_time 创建时间
 * @property int $updated_at 更新时间
 */
class SscKjData extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_kj_data}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['index_id', 'codes_hz', 'codes_4nums_hz', 'code1', 'code2', 'code3', 'code4', 'code5', 'code_1_2', 'code_1_3', 'code_1_4', 'code_2_3', 'code_2_4', 'code_3_4', 'qihao', 'type_2', 'type_22', 'type_3', 'type_4', 'type_2b', 'type_3b', 'type_4b', 'type_4ds', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['date', 'update_time'], 'safe'],
            [['kj_code'], 'string', 'max' => 8],
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
            'index_id' => '顺序id',
            'kj_code' => '开奖号码',
            'code_str' => '开奖号码str',
            'codes_hz' => '号码和值',
            'codes_4nums_hz' => '前4位和值',
            'code1' => '万位',
            'code2' => '千位',
            'code3' => '百位',
            'code4' => '十位',
            'code5' => '个位',
            'code_1_2' => '1、2位和值',
            'code_1_3' => '1、3位和值',
            'code_1_4' => '1、4位和值',
            'code_2_3' => '2、3位和值',
            'code_2_4' => '2、4位和值',
            'code_3_4' => '3、4位和值',
            'qihao' => '期号',
            'date' => '开奖日期',
            'type_2' => '是否双重',
            'type_22' => '是否双双重',
            'type_3' => '是否三重',
            'type_4' => '是否四重',
            'type_2b' => '是否双重',
            'type_3b' => '是否三兄弟',
            'type_4b' => '是否四兄弟',
            'type_4ds' => '单双：0非四单四双1四单2四双',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'created_at' => '创建时间',
            'update_time' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return SscKjDataQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscKjDataQuery(get_called_class());
    }
}
