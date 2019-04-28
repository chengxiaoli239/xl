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
 * @property int $lottery_type 彩票类型1重庆时时彩2江西3新疆4七星彩5排列三6排列五7福彩3D、lt_lottery_type.id
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
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', '标题'),
            'name' => Yii::t('app', '类型名称'),
            'host' => Yii::t('app', '接口域名'),
            'path' => Yii::t('app', '路由'),
            'is_batch' => Yii::t('app', '是否批量'),
            'lottery_type' => Yii::t('app', '彩票类型1重庆时时彩2江西3新疆4七星彩5排列三6排列五7福彩3D、lt_lottery_type.id'),
            'method' => Yii::t('app', '请求方式:GET/POST'),
            'post_data' => Yii::t('app', 'post数据'),
            'data_type' => Yii::t('app', '数据类型'),
            'enable' => Yii::t('app', '开关0关闭1开启'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
        ];
    }
}
