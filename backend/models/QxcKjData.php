<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%qxc_kj_data}}".
 *
 * @property string $id
 * @property string $kj_code 开奖号码，4位
 * @property string $kj_7code 开奖号码7位
 * @property int $hezhi 开奖前4位和值
 * @property int $qihao 期号
 * @property int $code1
 * @property int $code2
 * @property int $code3
 * @property int $code4
 * @property int $code5
 * @property int $code6
 * @property int $code7
 * @property string $date 开奖日期
 * @property int $time 开奖时间戳
 * @property int $updated_at
 * @property string $update_time 更新时间
 * @property int $created_at 创建时间
 */
class QxcKjData extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%qxc_kj_data}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hezhi', 'qihao', 'code1', 'code2', 'code3', 'code4', 'code5', 'code6', 'code7', 'time', 'updated_at', 'created_at'], 'integer'],
            [['date', 'update_time'], 'safe'],
            [['time', 'created_at'], 'required'],
            [['kj_code'], 'string', 'max' => 11],
            [['kj_7code'], 'string', 'max' => 25],
            [['qihao'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'kj_code' => Yii::t('app', '开奖号码，4位'),
            'kj_7code' => Yii::t('app', '开奖号码7位'),
            'hezhi' => Yii::t('app', '开奖前4位和值'),
            'qihao' => Yii::t('app', '期号'),
            'code1' => Yii::t('app', 'Code1'),
            'code2' => Yii::t('app', 'Code2'),
            'code3' => Yii::t('app', 'Code3'),
            'code4' => Yii::t('app', 'Code4'),
            'code5' => Yii::t('app', 'Code5'),
            'code6' => Yii::t('app', 'Code6'),
            'code7' => Yii::t('app', 'Code7'),
            'date' => Yii::t('app', '开奖日期'),
            'time' => Yii::t('app', '开奖时间戳'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'update_time' => Yii::t('app', '更新时间'),
            'created_at' => Yii::t('app', '创建时间'),
        ];
    }
}
