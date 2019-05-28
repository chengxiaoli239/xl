<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%num4_type}}".
 *
 * @property int $id
 * @property string $code 彩种
 * @property int $code_1
 * @property int $code_2
 * @property int $code_3
 * @property int $code_4
 * @property int $type_2 是否双重
 * @property int $type_22 是否双双重
 * @property int $type_3 是否三重
 * @property int $type_4 是否四重
 * @property int $type_2b 是否两兄弟
 * @property int $type_3b 是否三兄弟
 * @property int $type_4b 是否四兄弟
 * @property int $type_4ds 单双：0非四单四双1四单2四双
 * @property int $codes_hz 号码和值
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
            [['code_1', 'code_2', 'code_3', 'code_4', 'type_2', 'type_22', 'type_3', 'type_4', 'type_2b', 'type_3b', 'type_4b', 'type_4ds', 'codes_hz', 'created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['code'], 'string', 'max' => 8],
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
            'codes_hz' => '号码和值',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }
}
