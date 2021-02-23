<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%user_follow_data}}".
 *
 * @property string $id
 * @property string $account 用户账号
 * @property string $code 投注号码
 * @property int $codes_hezhi 号码和值
 * @property int $playway 投注方式
 * @property string $position 定位位置
 * @property string $reference_codes 参考码
 * @property int $is_follow 是否追号
 * @property int $is_simulate 是否模拟
 * @property int $status 是否激活
 * @property double $single 投注倍数(元/注)
 * @property int $plan_type 计划类型:1用户2大数据3定制化
 * @property int $from_id 外部表id(定制化)
 *
 * @property int $current_miss 当前遗漏
 * @property int $position_1 万位
 * @property int $position_2 千位
 * @property int $position_3 百位
 * @property int $position_4 十位
 *
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class UserFollowData extends \common\models\base\BaseModel
{
    public $current_miss; # 当前遗漏
    public $position_1; # 万位
    public $position_2; # 千位
    public $position_3; # 百位
    public $position_4; # 十位
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_follow_data}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code'], 'required'],
            [['playway', 'is_follow', 'is_simulate', 'status', 'plan_type', 'from_id', 'created_at', 'updated_at'], 'integer'],
            [['single'], 'number'],
            [['account','codes_hezhi','position'], 'string', 'max' => 64],
            [['code'], 'string', 'max' => 640],
            [['reference_codes'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'account' => Yii::t('app', '用户账号'),
            'code' => Yii::t('app', '投注号码'),
            'codes_hezhi' => Yii::t('app', '号码和值'),
            'playway' => Yii::t('app', '投注方式'),
            'position' => Yii::t('app', '定位位置'),
            'reference_codes' => Yii::t('app', '参考码'),
            'is_follow' => Yii::t('app', '是否追号'),
            'is_simulate' => Yii::t('app', '是否模拟'),
            'status' => Yii::t('app', '是否激活'),
            'single' => Yii::t('app', '投注倍数(元/注)'),
            'plan_type' => Yii::t('app', '计划类型:1用户2大数据3定制化'),
            'from_id' => Yii::t('app', '外部表id(定制化)'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),


            'position_1' => Yii::t('app', '万位'),
            'position_2' => Yii::t('app', '千位'),
            'position_3' => Yii::t('app', '百位'),
            'position_4' => Yii::t('app', '十位'),
        ];
    }

    /**
     * @inheritdoc
     * @return UserFollowDataQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserFollowDataQuery(get_called_class());
    }
}
