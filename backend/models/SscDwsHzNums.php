<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_dws_hz_nums}}".
 *
 * @property string $id
 * @property int $hezhi 二定和值
 * @property string $positions 定位位置
 * @property string $periods 区间
 * @property string $qihao 期号
 * @property int $nums 区间出现的次数
 * @property int $updated_at 更新时间
 * @property int $created_at 创建时间
 * @property string $update_time
 */
class SscDwsHzNums extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_dws_hz_nums}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hezhi', 'nums', 'updated_at', 'created_at'], 'integer'],
            [['update_time'], 'safe'],
            [['positions'], 'string', 'max' => 255],
            [['periods'], 'string', 'max' => 8],
            [['qihao'], 'string', 'max' => 24],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'hezhi' => Yii::t('app', '二定和值'),
            'positions' => Yii::t('app', '定位位置'),
            'periods' => Yii::t('app', '区间'),
            'qihao' => Yii::t('app', '期号'),
            'nums' => Yii::t('app', '区间出现的次数'),
            'updated_at' => Yii::t('app', '更新时间'),
            'created_at' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', 'Update Time'),
        ];
    }

    /**
     * @inheritdoc
     * @return SscDwsHzNumsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscDwsHzNumsQuery(get_called_class());
    }
}
