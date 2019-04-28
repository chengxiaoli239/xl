<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%user_custom_plans}}".
 *
 * @property string $id
 * @property string $account 用户账号
 * @property string $hezhis 号码和值
 * @property int $playway 投注方式
 * @property string $positions 定位位置
 * @property int $status 是否激活
 * @property string $codes 方案号码
 * @property int $playway_type 投注方式：1:和值 2:单双
 * @property double $single 投注倍数(元/注)
 * @property string $tz_sites 投注站点，lt_tz_systems表id，只有is_simulate=1时有效
 * @property int $periods_open 开启统计的期数
 * @property int $threshold_open 开启阈值
 * @property int $periods_close 关闭统计的期数
 *
 * @property int $current_miss 当前遗漏
 * @property int $position_1 万位
 * @property int $position_2 千位
 * @property int $position_3 百位
 * @property int $position_4 十位
 *
 * @property int $threshold_close 关闭阈值
 * @property int $is_simulate 是否激活
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class UserCustomPlans extends \common\models\base\BaseModel
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
        return '{{%user_custom_plans}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['playway', 'status', 'playway_type', 'periods_open', 'threshold_open', 'periods_close', 'threshold_close', 'is_simulate', 'created_at', 'updated_at'], 'integer'],
            [['codes'], 'string'],
            [['single'], 'number'],
            [['account'], 'string', 'max' => 32],
            [['hezhis', 'tz_sites'], 'string', 'max' => 24],
            [['positions'], 'string', 'max' => 50],
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
            'hezhis' => Yii::t('app', '号码和值'),
            'playway' => Yii::t('app', '投注方式'),
            'positions' => Yii::t('app', '定位位置'),
            'status' => Yii::t('app', '是否激活'),
            'codes' => Yii::t('app', '方案号码'),
            'playway_type' => Yii::t('app', '投注方式：1:和值 2:单双'),
            'single' => Yii::t('app', '投注倍数(元/注)'),
            //'tz_sites' => '投注站点，lt_tz_systems表id，只有is_simulate=1时有效',
            'tz_sites' => '投注站点',
            'periods_open' => Yii::t('app', '开启统计的期数'),
            'threshold_open' => Yii::t('app', '开启阈值'),
            'periods_close' => Yii::t('app', '关闭统计的期数'),
            'threshold_close' => Yii::t('app', '关闭阈值'),
            'is_simulate' => Yii::t('app', '是否激活'),
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
     * @return UserCustomPlansQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserCustomPlansQuery(get_called_class());
    }
}
