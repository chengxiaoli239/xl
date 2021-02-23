<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_he9_data}}".
 *
 * @property int $id
 * @property string $kj_code 开奖号码
 * @property int $cxcs_zj20 最近20期，2,3位出现和值为9的次数
 * @property int $cxcs_zj50 最近50期，2,3位出现和值为9的次数
 * @property int $cxcs_zj100 最近100期，2,3位出现和值为9的次数
 * @property string $qihao 期号
 * @property string $update_time 创建时间
 */
class SscHe9Data extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_he9_data}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cxcs_zj20', 'cxcs_zj50', 'cxcs_zj100'], 'integer'],
            [['update_time'], 'safe'],
            [['kj_code'], 'string', 'max' => 8],
            [['qihao'], 'string', 'max' => 24],
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
            'kj_code' => Yii::t('app', '开奖号码'),
            'cxcs_zj20' => Yii::t('app', '最近20期，2,3位出现和值为9的次数'),
            'cxcs_zj50' => Yii::t('app', '最近50期，2,3位出现和值为9的次数'),
            'cxcs_zj100' => Yii::t('app', '最近100期，2,3位出现和值为9的次数'),
            'qihao' => Yii::t('app', '期号'),
            'update_time' => Yii::t('app', '创建时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SscHe9DataQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscHe9DataQuery(get_called_class());
    }
}
