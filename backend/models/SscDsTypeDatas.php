<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%ssc_ds_type_datas}}".
 *
 * @property int $id
 * @property string $name 名称
 * @property string $positions 位置，1,2;2,3;3:4;1,4
 * @property string $vals 值
 * @property int $code_type 1:一定;2:二定;3:三定;4:四定
 * @property int $sort 排序
 * @property int $status 统计状态
 * @property int $static_nums 统计期数
 * @property string $desc 描述
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class SscDsTypeDatas extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ssc_ds_type_datas}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code_type', 'sort', 'status', 'static_nums', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['name'], 'string', 'max' => 32],
            [['positions'], 'string', 'max' => 8],
            [['vals'], 'string', 'max' => 256],
            [['desc'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '名称',
            'positions' => '位置，1,2;2,3;3:4;1,4',
            'vals' => '值',
            'code_type' => '1:一定;2:二定;3:三定;4:四定',
            'sort' => '排序',
            'status' => '统计状态',
            'static_nums' => '统计期数',
            'desc' => '描述',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * @inheritdoc
     * @return SscDsTypeDatasQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SscDsTypeDatasQuery(get_called_class());
    }
}
