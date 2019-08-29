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
 * @property int $type 类型：1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重
 * @property int $static_nums 统计期数
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 * @property int $type_2 是否双重
 * @property int $type_22 是否双双重
 * @property int $type_3 是否三重
 * @property int $type_4 是否四重
 * @property int $type_2b 是否两兄弟
 * @property int $type_3b 是否三兄弟
 * @property int $type_4b 是否四兄弟
 * @property int $type_4d 是否四单
 * @property int $type_4s 是否四双
 * @property int $type_log 是否对数
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
            [['status', 'type', 'static_nums', 'created_at', 'updated_at', 'type_2', 'type_22', 'type_3', 'type_4', 'type_2b', 'type_3b', 'type_4b', 'type_4d', 'type_4s', 'type_log'], 'integer'],
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
            'type' => '类型：1和值2号码类型[例如:双双重、三重]3三字现带双重4四字现带双重5四字现不带双重',
            'static_nums' => '统计期数',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
            'type_2' => '是否双重',
            'type_22' => '是否双双重',
            'type_3' => '是否三重',
            'type_4' => '是否四重',
            'type_2b' => '是否两兄弟',
            'type_3b' => '是否三兄弟',
            'type_4b' => '是否四兄弟',
            'type_4d' => '是否四单',
            'type_4s' => '是否四双',
            'type_log' => '是否对数',
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
