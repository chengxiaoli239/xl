<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_ds_yl}}".
 *
 * @property int $id
 * @property string $positions 位置
 * @property string $zhi 值
 * @property string $current_miss 本期遗漏
 * @property string $last_time_miss 上次遗漏
 * @property string $last_time_miss_range 上次遗漏范围
 * @property string $max_miss 最大遗漏(近200期)
 * @property string $max_range 最大遗漏范围(近200期)
 * @property string $yl_records 遗漏记录
 * @property string $history_max_miss 历史最大遗漏
 * @property int $type 1一定2二定3三定4四定
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SscDsYl extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_ds_yl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['yl_records'], 'string'],
            [['type', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['positions'], 'string', 'max' => 8],
            [['zhi'], 'string', 'max' => 120],
            [['current_miss', 'last_time_miss', 'max_miss', 'max_range', 'history_max_miss'], 'string', 'max' => 12],
            [['last_time_miss_range'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'positions' => Yii::t('app', '位置'),
            'zhi' => Yii::t('app', '值'),
            'current_miss' => Yii::t('app', '本期遗漏'),
            'last_time_miss' => Yii::t('app', '上次遗漏'),
            'last_time_miss_range' => Yii::t('app', '上次遗漏范围'),
            'max_miss' => Yii::t('app', '最大遗漏(近200期)'),
            'max_range' => Yii::t('app', '最大遗漏范围(近200期)'),
            'yl_records' => Yii::t('app', '遗漏记录'),
            'history_max_miss' => Yii::t('app', '历史最大遗漏'),
            'type' => Yii::t('app', '1一定2二定3三定4四定'),
            'lottery_type' => Yii::t('app', '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return SscDsYlQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscDsYlQuery(get_called_class());
    }
}
