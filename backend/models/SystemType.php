<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%system_type}}".
 *
 * @property int $id
 * @property string $type_name 类型名称
 * @property string $base_url 网盘跟域名
 * @property string $route_user_info 用户基本信息路由
 * @property string $route_dw 定位路由（二三定）
 * @property string $route_home 时时彩首页、登录请求路由
 * @property string $route_kj 系统开奖路由
 * @property int $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class SystemType extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%system_type}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at'], 'integer'],
            [['updated_at'], 'safe'],
            [['type_name', 'route_user_info', 'route_dw', 'route_home', 'route_kj'], 'string', 'max' => 64],
            [['base_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type_name' => Yii::t('app', '类型名称'),
            'base_url' => Yii::t('app', '网盘跟域名'),
            'route_user_info' => Yii::t('app', '用户基本信息路由'),
            'route_dw' => Yii::t('app', '定位路由（二三定）'),
            'route_home' => Yii::t('app', '时时彩首页、登录请求路由'),
            'route_kj' => Yii::t('app', '系统开奖路由'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
        ];
    }
}
