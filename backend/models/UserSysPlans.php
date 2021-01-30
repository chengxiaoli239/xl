<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "{{%user_sys_plans}}".
 *
 * @property int $id
 * @property int $is_parent 是否是父id
 * @property string $children_plan_id 子计划id
 * @property int $uid 用户id
 * @property string $account 账号名称
 * @property int $playway 投注方式:1二字定2三字定3四字定
 * @property int $status 状态:0关闭1开启
 * @property double $single 投注倍数(元/注)
 * @property string $singles 翻倍梯度
 * @property int $tz_type 投注类型:1大小单双三字定2大小三字定3单双三字定
 * @property int $buy_type 购买方向:0反买1正买
 * @property string $tz_sites 计划投注站点，lt_tz_systems.id
 * @property string $hz_Arr 扩展【部分投注】
 * @property int $nums 默认投注注数
 * @property int $sel_same 是否含上次一样的号码
 * @property int $is_custom 是否智能切换购买方向
 * @property int $is_test 是否为系统测试计划
 * @property int $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
 * @property string $take_profits 止盈点
 * @property string $stop_loss 止损点
 * @property string $get_hzs 和值 取
 * @property string $remove_hzs 和值 除
 * @property string $get_types 号码类型 取
 * @property string $remove_types 号码类型 除
 * @property string $get_arise 上奖 除
 * @property string $remove_arise 上奖 除
 * @property string $current_profits 当前盈利
 * @property int $plan_type 计划类型:0正常1止盈止损计划
 * @property int $tz_sort 投注排序:从小到大
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
    public $hz; # 四兄弟
    public $arise; # 上奖
    public $get_arises; # 上奖：取
    public $remove_arises; # 上奖：除
    public $p1; # 第1位
    public $p2; # 第2位
    public $p3; # 第3位
    public $p4; # 第4位
    public $p5; # 第5位
    public $code1; # 快译号码组1,例如：千12345百12345十67890
    public $code2; # 快译号码组2,例如：千12345百12345十67890
    public $status_val; # 取号码组值
    public $type_4d; # 四单
    public $type_4s; # 四双
    public $type_log; # 对数
    public $import_codes_txt; # 导入号码
    public $type_4ds; # 号码类型、四定单双:0保留1四单2四双3两单两双4一单三双5一双三单
    public $get_types; #  取类型
    public $remove_types; #  排除类型
    public $get_hzs; #  取和值
    public $remove_hzs; #  排除和值
    public $xhefen; #  四定系统快捷定位合分值

    public $hefen; #  定位合分值1
    public $hefen_pos; #  定位合分位置1
    public $hefen2; #  定位合分值2
    public $hefen_pos2; #  定位合分位置2
    public $hefen3; #  定位合分值3
    public $hefen_pos3; #  定位合分位置3
    public $hefen4; #  定位合分值4
    public $hefen_pos4; #  定位合分位置4

    public $ps_1; #  配数1
    public $ps_2; #  配数2

    public $no_fix_hefen; #  定位合分值
    public $no_fix_hefen_pos; #  定位合分位置
    public $arise_in; #  三定含
    public $arise_in_sel; #  三定含，除取
    public $singles_key; # 倍数key
    public $betStatus; # 投注状态
    public $is_init; # 是否初始
    public $bet_while_miss; # 遗漏多少期启投
    public $current_miss; # 当前遗漏期数
    public $desc; # 计划备注
    //public $type_3_txt; # 三定-导入
    //public $type_4_txt; # 四定-导入
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
            [['is_parent', 'uid', 'playway', 'status', 'tz_type', 'buy_type', 'nums', 'sel_same', 'is_custom', 'is_test', 'lottery_type', 'plan_type', 'tz_sort', 'created_at', 'updated_at'], 'integer'],
            [['uid', 'account', 'created_at', 'updated_at'], 'required'],
            [['single', 'take_profits', 'stop_loss', 'current_profits'], 'number'],
            [['update_time'], 'safe'],
            [['children_plan_id', 'singles'], 'string', 'max' => 255],
            [['account', 'tz_sites'], 'string', 'max' => 24],
            [['hz_Arr'], 'string', 'max' => 640],
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
            'children_plan_id' => '子计划id',
            'uid' => '用户id',
            'account' => '账号名称',
            'playway' => '投注方式:1二字定2三字定3四字定',
            'status' => '状态:0关闭1开启',
            'single' => '投注倍数(元/注)',
            'singles' => '翻倍梯度',
            'tz_type' => '投注类型:1大小单双三字定2大小三字定3单双三字定',
            'buy_type' => '购买方向:0反买1正买',
            'tz_sites' => '计划投注站点，lt_tz_systems.id',
            'hz_Arr' => '扩展【部分投注】',
            'nums' => '默认投注注数',
            'sel_same' => '是否含上次一样的号码',
            'is_custom' => '是否智能切换购买方向',
            'is_test' => '是否为系统测试计划',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'take_profits' => '止盈点',
            'stop_loss' => '止损点',
            'current_profits' => '当前盈利',
            'plan_type' => '计划类型:0正常1止盈止损计划',
            'tz_sort' => '投注排序:从小到大',
            'desc' => '计划备注',
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
