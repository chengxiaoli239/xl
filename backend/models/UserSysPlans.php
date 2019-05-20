<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%user_sys_plans}}".
 *
 * @property string $id
 * @property int $uid 用户id
 * @property string $account 账号名称
 * @property int $playway 投注方式:1二字定2三字定3四字定
 * @property int $status 状态:0关闭1开启
 * @property double $single 投注倍数(元/注)
 * @property int $tz_type 投注类型:1大小单双三字定2大小三字定3单双三字定
 * @property int $buy_type 购买方向:0反买1正买
 * @property string $tz_sites 计划投注站点，lt_tz_systems.id
 * @property string $hz_Arr 扩展【部分投注】
 * @property int $nums 默认投注注数
 * @property int $sel_same 是否含上次一样的号码
 * @property int $is_custom 是否智能切换购买方向
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class UserSysPlans extends \common\models\base\BaseModel
{
    public $type_2; # 双重
    public $type_3; # 三重
    public $type_4; # 四重
    public $type_22; # 双双重
    public $type_2b; # 两兄弟
    public $type_3b; # 三兄弟
    public $type_4b; # 四兄弟
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_sys_plans}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'account', 'created_at', 'updated_at'], 'required'],
            [['uid', 'playway', 'status', 'tz_type', 'buy_type', 'nums', 'sel_same', 'is_custom', 'created_at', 'updated_at'], 'integer'],
            [['single'], 'number'],
            [['update_time'], 'safe'],
            [['account', 'tz_sites'], 'string', 'max' => 24],
            [['hz_Arr'], 'string', 'max' => 120],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uid' => Yii::t('app', '用户id'),
            'account' => Yii::t('app', '账号名称'),
            'playway' => Yii::t('app', '投注方式:1二字定2三字定3四字定'),
            'status' => Yii::t('app', '状态:0关闭1开启'),
            'single' => Yii::t('app', '投注倍数(元/注)'),
            'tz_type' => Yii::t('app', '投注类型:1大小单双三字定2大小三字定3单双三字定'),
            'buy_type' => Yii::t('app', '购买方向:0反买1正买'),
            'tz_sites' => Yii::t('app', '计划投注站点，lt_tz_systems.id'),
            'hz_Arr' => Yii::t('app', '扩展【部分投注】'),
            'nums' => Yii::t('app', '默认投注注数'),
            'sel_same' => Yii::t('app', '是否含上次一样的号码'),
            'is_custom' => Yii::t('app', '是否智能切换购买方向'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return UserSysPlansQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserSysPlansQuery(get_called_class());
    }
}
