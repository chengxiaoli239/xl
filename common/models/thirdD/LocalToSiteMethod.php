<?php

namespace common\models\thirdD;

use Yii;

/**
 * This is the model class for table "{{%local_to_site_method}}".
 *
 * @property int $id
 * @property int $system_type_id 系统类型id：tz_systems.system_type_id
 * @property int $method_id 玩法id
 * @property int $site_method_id 玩法id
 * @property string $name 玩法名称
 * @property string $desc 描述
 * @property int $created_at
 * @property int $updated_at
 * @property string $update_at 更新时间
 */
class LocalToSiteMethod extends \common\models\base\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%local_to_site_method}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['system_type_id', 'method_id', 'site_method_id', 'created_at', 'updated_at'], 'integer'],
            [['desc'], 'string'],
            [['created_at', 'updated_at'], 'required'],
            [['update_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'system_type_id' => Yii::t('app', '系统类型id：tz_systems.system_type_id'),
            'method_id' => Yii::t('app', '玩法id'),
            'site_method_id' => Yii::t('app', '玩法id'),
            'name' => Yii::t('app', '玩法名称'),
            'desc' => Yii::t('app', '描述'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'update_at' => Yii::t('app', '更新时间'),
        ];
    }
}
