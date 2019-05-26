<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%kj_config}}".
 *
 * @property int $id
 * @property string $title 标题
 * @property string $name 类型名称
 * @property string $host 接口域名
 * @property string $path 路由
 * @property int $is_batch 是否批量
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property string $method 请求方式:GET/POST
 * @property string $post_data post数据
 * @property string $data_type 数据类型
 * @property int $enable 开关0关闭1开启
 * @property int $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class KjConfig extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%kj_config}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['is_batch', 'lottery_type', 'enable', 'created_at'], 'integer'],
            [['updated_at'], 'safe'],
            [['title', 'name'], 'string', 'max' => 64],
            [['host', 'path', 'post_data'], 'string', 'max' => 255],
            [['method'], 'string', 'max' => 11],
            [['data_type'], 'string', 'max' => 12],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '标题',
            'name' => '类型名称',
            'host' => '接口域名',
            'path' => '路由',
            'is_batch' => '是否批量',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'method' => '请求方式:GET/POST',
            'post_data' => 'post数据',
            'data_type' => '数据类型',
            'enable' => '开关0关闭1开启',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
