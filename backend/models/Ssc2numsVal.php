<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_2nums_val}}".
 *
 * @property int $id
 * @property string $val 和值范围
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class Ssc2numsVal extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_2nums_val}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['val'], 'string', 'max' => 120],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'val' => '和值范围',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return Ssc2numsValQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new Ssc2numsValQuery(get_called_class());
    }
}
