<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_ds_static}}".
 *
 * @property int $id
 * @property string $positions 位置，1,2;2,3;3:4;1,4
 * @property string $qihao 当前期号
 * @property int $periods 近多少期，20，50，100，150，200，300，500期
 * @property int $DS 单双
 * @property int $SD 双单
 * @property int $DD 单单
 * @property int $SS 双双
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SscDsStatic extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_ds_static}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['periods', 'DS', 'SD', 'DD', 'SS', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['positions'], 'string', 'max' => 8],
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
            'positions' => Yii::t('app', '位置，1,2;2,3;3:4;1,4'),
            'qihao' => Yii::t('app', '当前期号'),
            'periods' => Yii::t('app', '近多少期，20，50，100，150，200，300，500期'),
            'DS' => Yii::t('app', '单双'),
            'SD' => Yii::t('app', '双单'),
            'DD' => Yii::t('app', '单单'),
            'SS' => Yii::t('app', '双双'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SscDsStaticQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscDsStaticQuery(get_called_class());
    }
}
