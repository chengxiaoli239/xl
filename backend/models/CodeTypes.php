<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%code_types}}".
 *
 * @property int $id
 * @property string $type 号码类型
 * @property string $type_name 类型名称
 * @property string $type_key 类型key
 * @property int $playway 投注类型:1二定2三定3四定
 * @property int $status 状态
 * @property string $codes 组合号码
 * @property string $desc 描述
 * @property int $flag 类型标识
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class CodeTypes extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%code_types}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['playway', 'status', 'flag', 'created_at', 'updated_at'], 'integer'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['type'], 'string', 'max' => 255],
            [['type_name'], 'string', 'max' => 64],
            [['type_key'], 'string', 'max' => 24],
            [['codes', 'desc'], 'string', 'max' => 640],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '号码类型',
            'type_name' => '类型名称',
            'type_key' => '类型key',
            'playway' => '投注类型:1二定2三定3四定',
            'status' => '状态',
            'codes' => '组合号码',
            'desc' => '描述',
            'flag' => '类型标识',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return CodeTypesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new CodeTypesQuery(get_called_class());
    }
}
