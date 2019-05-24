<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_static_val}}".
 *
 * @property int $id
 * @property string $val 值
 * @property string $name 名字
 * @property int $status 是否显示0不显示1显示
 * @property int $type 类型：1和值2号码类型[例如:双双重、三重]
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SscStaticVal extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_static_val}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'type', 'created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['val'], 'string', 'max' => 120],
            [['name'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'val' => '值',
            'name' => '名字',
            'status' => '是否显示0不显示1显示',
            'type' => '类型：1和值2号码类型[例如:双双重、三重]',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return SscStaticValQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscStaticValQuery(get_called_class());
    }
}
