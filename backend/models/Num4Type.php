<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%num4_type}}".
 *
 * @property int $id
 * @property string $code 彩种
 * @property string $code_1
 * @property string $code_2
 * @property string $code_3
 * @property string $code_4
 * @property int $type_2 是否双重
 * @property int $type_22 是否双双重
 * @property int $type_3 是否三重
 * @property int $type_4 是否四重
 * @property int $type_2b 是否两兄弟
 * @property int $type_3b 是否三兄弟
 * @property int $type_4b 是否四兄弟
 * @property int $type_4ds 单双：0非四单四双1四单2四双
 * @property int $type_log 是否对数
 * @property int $type_3n_2b 三现:双重+兄弟
 * @property int $type_3d 三单
 * @property int $type_3s 三双
 * @property int $type_4d 四单
 * @property int $type_4s 四双
 * @property int $codes_hz 号码和值
 * @property int $code_type 号码类型:1一字定2二字定3三字定4四字定
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class Num4Type extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%num4_type}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type_2', 'type_22', 'type_3', 'type_4', 'type_2b', 'type_3b', 'type_4b', 'type_4ds', 'type_log', 'type_3n_2b', 'type_3d', 'type_3s', 'type_4d', 'type_4s', 'codes_hz', 'code_type', 'created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['code'], 'string', 'max' => 8],
            [['code_1', 'code_2', 'code_3', 'code_4'], 'string', 'max' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => '彩种',
            'code_1' => 'Code 1',
            'code_2' => 'Code 2',
            'code_3' => 'Code 3',
            'code_4' => 'Code 4',
            'type_2' => '是否双重',
            'type_22' => '是否双双重',
            'type_3' => '是否三重',
            'type_4' => '是否四重',
            'type_2b' => '是否两兄弟',
            'type_3b' => '是否三兄弟',
            'type_4b' => '是否四兄弟',
            'type_4ds' => '单双：0非四单四双1四单2四双',
            'type_log' => '是否对数',
            'type_3n_2b' => '三现:双重+兄弟',
            'type_3d' => '三单',
            'type_3s' => '三双',
            'type_4d' => '四单',
            'type_4s' => '四双',
            'codes_hz' => '号码和值',
            'code_type' => '号码类型:1一字定2二字定3三字定4四字定',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return Num4TypeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new Num4TypeQuery(get_called_class());
    }
}
