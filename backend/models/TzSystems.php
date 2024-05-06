<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%tz_systems}}".
 *
 * @property int $id id
 * @property string $name 系统名称
 * @property int $system_type_id 系统类型id
 * @property string $ssc_domain 系统站点
 * @property int $status 系统开启状态
 * @property int $lottery_type 系统彩种类型：0为全部否则系统为指定彩种
 * @property int $type 类型:1时时彩2网球
 * @property string $tz_types 已经对接的玩法 lt_tz_typs.type
 * @property int $is_auto_login 是否自动登陆
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class TzSystems extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tz_systems}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['system_type_id', 'kj_num', 'status', 'lottery_type', 'type', 'is_auto_login', 'created_at', 'updated_at'], 'integer'],
            [['update_time'], 'safe'],
            [['name'], 'string', 'max' => 64],
            [['ssc_domain', 'tz_types'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'name' => '系统名称',
            'system_type_id' => '系统类型id',
            'ssc_domain' => '系统站点',
            'status' => '系统开启状态',
            'lottery_type' => '系统彩种类型：0为全部否则系统为指定彩种',
            'type' => '类型:1时时彩2网球',
            'tz_types' => '已经对接的玩法 lt_tz_types.type',
            'is_auto_login' => '是否自动登陆',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
        ];
    }
}
