<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%user_sys_plans}}".
 *
 * @property int $id
 * @property int $is_parent 是否是父id
 * @property int $parent_plan_id 父计划id
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
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class UserSysPlans extends \common\models\base\BaseModel
{
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
            [['is_parent', 'parent_plan_id', 'uid', 'playway', 'status', 'tz_type', 'buy_type', 'nums', 'sel_same', 'is_custom', 'lottery_type', 'created_at', 'updated_at'], 'integer'],
            [['uid', 'account', 'created_at', 'updated_at'], 'required'],
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
            'id' => 'ID',
            'is_parent' => '是否是父id',
            'parent_plan_id' => '父计划id',
            'uid' => '用户id',
            'account' => '账号名称',
            'playway' => '投注方式:1二字定2三字定3四字定',
            'status' => '状态:0关闭1开启',
            'single' => '投注倍数(元/注)',
            'tz_type' => '投注类型:1大小单双三字定2大小三字定3单双三字定',
            'buy_type' => '购买方向:0反买1正买',
            'tz_sites' => '计划投注站点，lt_tz_systems.id',
            'hz_Arr' => '扩展【部分投注】',
            'nums' => '默认投注注数',
            'sel_same' => '是否含上次一样的号码',
            'is_custom' => '是否智能切换购买方向',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'update_time' => '更新时间',
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
