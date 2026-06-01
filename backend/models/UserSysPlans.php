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
 * @property int $bet_direct 下方向:1正2反
 * @property string $tz_sites 计划投注站点，lt_tz_systems.id
 * @property string $hz_Arr 扩展【部分投注】
 * @property int $nums 默认投注注数
 * @property int $sel_same 是否含上次一样的号码
 * @property int $is_custom 是否智能切换购买方向
 * @property int $is_test 是否为系统测试计划
 * @property int $is_batch_simulate 是否批量模拟计划
 * @property int $is_profits_record 是否参与账号盈利统计0是1否
 * @property int $is_area_profits 是否区间盈利记录0否1是
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
 * @property string $base_codes 基础号码
 * @property string $desc 描述
 * @property string $remark 备注
 * @property int $plan_type 计划类型:0正常1止盈止损计划
 * @property int $change_per 号码轮换
 * @property int $tz_sort 投注排序:从小到大
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class UserSysPlans extends \common\models\base\BaseModel
{

    public $ids;
    public $type_2; # 双重
    public $type_3; # 三重
    public $type_4; # 四重
    public $type_22; # 双双重
    public $type_22b; # 双两兄弟
    public $type_2b; # 两兄弟
    public $type_3b; # 三兄弟
    public $type_4b; # 四兄弟
    public $type_3n_2b; # 三现:双重+两兄
    public $hz; # 四兄弟
    public $arise; # 上奖
    public $get_arises; # 上奖：取
    public $remove_arises; # 上奖：除

    public $fixed_pos_sel; #  定位置选项
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
    public $type_2log; # 双对数
    public $turn_key; # 轮换号码组的key
    public $import_codes_txts; # 导入号码
    public $type_4ds; # 号码类型、四定单双:0保留1四单2四双3两单两双4一单三双5一双三单
    public $get_types; #  取类型
    public $remove_types; #  排除类型
    public $get_hzs; #  取和值
    public $remove_hzs; #  排除和值
    public $xhefen; #  四定系统快捷定位合分值
    public $codes;
    public $in_codes; # 在号码基础上过滤

    # 定位合分
    public $hefen1; #  定位合分值1
    public $hefen_pos1; #  定位合分位置1
    public $hefen2; #  定位合分值2
    public $hefen_pos2; #  定位合分位置2
    public $hefen3; #  定位合分值3
    public $hefen_pos3; #  定位合分位置3
    public $hefen4; #  定位合分值4
    public $hefen_pos4; #  定位合分位置4
    public $no_fix_hefen2;
    public $no_fix_hefen_pos_2;
    public $no_fix_hefen3;
    public $no_fix_hefen_pos_3;

    public $change_per; #  导入方式每期轮换号码
    public $change_turn_pos; #  号码轮换位置，指定号码数字轮换指定组

    public $fixed_pos_hefen_sel; #  定位合分选项

    public $ps_sel; #  配数选项
    public $ps_1; #  配数1
    public $ps_2; #  配数2
    public $ps_3; #  配数3
    public $ps_4; #  配数4

    public $log_sel; #  对数选项
    public $log_1; #  对数1
    public $log_2; #  对数2
    public $log_3; #  对数3

    public $odd_sel; #  单选项
    public $odd_pos; #  单位置
    public $even_sel; #  双选项
    public $even_pos; #  双位置
    public $big_sel; #  大选项
    public $big_pos; #  大位置
    public $small_sel; #  小选项
    public $small_pos; #  小位置

    public $fixed_sel_pos; #  定位置选项

    public $no_fix_hefen; #  定位合分值
    public $no_fix_hefen_pos; #  定位合分位置
    public $arise_in; #  三定含
    public $exclude_codes; # 排除
    public $arise_in_sel; #  三定含，除取
    public $singles_key; # 倍数key
    public $betStatus; # 投注状态
    public $is_init; # 是否初始
    public $bet_while_miss; # 遗漏多少期启投
    public $current_miss; # 当前遗漏期数
    public $desc; # 计划备注
    //public $type_3_txt; # 三定-导入
    //public $type_4_txt; # 四定-导入

    public $type_ds_details; # 单双类型：1122,2121,1111 等

    public $filters;
    public $filter_dates;
    public $filter_qihaos;
    ###### 排除过滤参数开始 #######
    # 1、排除前x期
    public $is_filter; # 是否排除
    public $filter_xQ_before; # 前多少期，获取区间
    public $filter_pos1; # 排除那些位置，四个多选框
    public $filter_pos2; # 排除那些位置，四个多选框

    # 2、排除前x天同期
    public $is_filter_date; # 是否排除
    public $filter_xD_before; # 前多少期，获取区间
    public $filter_date_pos1; # 排除那些位置，四个多选框
    public $filter_date_pos2; # 排除那些位置，四个多选框

    # 3、排除期号的定位，比如058期，二定则去除：58XX
    public $is_filter_qihao; # 是否排除
    ###### 排除过滤参数结束 #######

    ############### 新过滤快打—start #################
    public $arb_pos_isbaohan; # 任意位置之"是否包含"，0,1
    public $arb_pos_codes; # 任意位置之"号码" 0123456789
    public $arb_pos_nums; # 任意位置之"至少个数" 2个3个
    ############### 新过滤快打—end #################

    ############### 动态过滤 - 模拟 start ##################
    public $filter_type; #
    public $filter_nums; # 过滤前x期同位置号码
    public $filter_poses; # 过滤位置
    public $start_qihao; # 模拟开始期号
    public $test_period_days; # 模拟近x天
    public $history_max_miss; # 历史最大遗漏
    public $current_kj_qihao; # 当前开奖期号
    ############### 动态过滤 - 模拟 end ####################

    ############## A出x次B出y次投B、A出x次B出y次投B start ###################
    public $arise_A_codes;
    public $arise_B_codes;
    public $arise_A_times; # A出次数
    public $arise_B_times; # B出次数
    public $current_arise_A_times; # A出当前次数
    public $current_arise_B_times; # B出当前次数
    public $A_x_B_y_status; # 状态：0初始1等待符合条件中2开始投
    public $A_x_B_y_start_time;
    public $current_yl_desc;
    public $start_bet_yl_nums;
    public $single_key;
    public static $A_x_arise_B_y_arise_bet_B_types = [12, 13]; # A出x次B出y次投B、A出x次B出y次投B_2
    ############## A出x次B出y次投B、A出x次B出y次投B end   ####################

    ########################## 统计期数区间 止盈止损 start ########################
    public $area_all_qishus; # 统计期数
    public $area_yl_qishus; # 统计期间遗漏期数
    public $area_loss_start; # 亏损起投金额
    public $area_profits; # 统计期间后起投止盈
    public $area_loss; # 统计期间后起投止损
    public $areaBetStatus; # 0监控状态1下注状态
    public $current_area_profits; # 区间利润
    public $area_arise_qishus; # 统计区间上奖次数
    public $area_msg; # 区间描述
    public $start_loss; # 触发亏损门槛条件
    ########################### 统计期数区间 止盈止损 end #########################

    ########################## 过滤前xx期号码 start ########################
    public $is_filter_dynamic; # 是否动态过滤
    public $filter_dynamic_types; # 过滤类型
    public $filter_dynamic_types2; # 动态过滤类型2
    ########################### 过滤前xx期号码 end #########################

    ####################### 中则投、中则倍投、中则波推倍投 #####################
    public $has_bet_nums;
    ####################### 中则投、中则倍投、中则波推倍投 #####################

    ####################### 分离数开始 #####################
    public $fenli_shu_sel;
    public $fenli_shu_sel_0;
    public $fenli_shu_sel_1;
    public $fenli_shu_sel_2;
    public $fenli_shu_sel_3;
    public $fenli_shu_sel_4;
    public $fenli_shu_sel_5;
    public $fenli_shu_sel_6;
    public $fenli_shu_sel_7;
    public $fenli_shu_sel_8;
    public $fenli_shu_sel_9;
    public $fenli_shu_sel_10;
    public $fenli_shu_sel_11;
    public $fenli_shu_code;
    ####################### 分离数结束 #####################
    //public $base_codes;

    ####################### 合数与差分-开始 #####################
    public $hsAndCf_twoFone; # 两合上1
    ####################### 合数与差分-结束 #####################
    //public $bet_direct = self::BET_DIRECT_Z;
    public $bet_op_to_wp = self::BET_DIRECT_Z;
    public $bet_op_to_wp_singles = 1; # 投盘口倍数的倍数

    const IS_INIT_PERDATE_N = 1;
    const IS_INIT_PERDATE_Y = 2;

    const BET_DIRECT_Z = 1;
    const BET_DIRECT_F = 2;
    const BET_DIRECT_OPTION = [
        self::BET_DIRECT_Z => '正',
        self::BET_DIRECT_F => '反',
    ];


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
            [['is_parent', 'uid', 'playway', 'status', 'tz_type', 'bet_direct', 'buy_type', 'nums', 'sel_same', 'is_custom', 'is_test', 'is_batch_simulate', 'is_profits_record', 'is_area_profits', 'is_init_perdate', 'lottery_type', 'plan_type', 'tz_sort', 'created_at', 'updated_at'], 'integer'],
            [['uid', 'account', 'single', 'created_at', 'updated_at'], 'required'],
            [['single', 'take_profits', 'stop_loss', 'current_profits'], 'number'],
            [['update_time'], 'safe'],
            [['children_plan_id'], 'string', 'max' => 255],
            [['singles', 'ids', 'base_codes', 'hz_Arr', 'desc', 'remark'], 'string'],
            [['account', 'tz_sites'], 'string', 'max' => 24],
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
            'codes' => '号码',
            'status' => '状态:0关闭1开启',
            'single' => '投注倍数(元/注)',
            'singles' => '翻倍梯度',
            'tz_type' => '投注类型:1大小单双三字定2大小三字定3单双三字定',
            'bet_direct' => '下注方向：1正向2反向',
            'buy_type' => '购买方向:0反买1正买',
            'tz_sites' => '计划投注站点，lt_tz_systems.id',
            'hz_Arr' => '扩展【部分投注】',
            'nums' => '默认投注注数',
            'sel_same' => '是否含上次一样的号码',
            'is_custom' => '是否智能切换购买方向',
            'is_test' => '是否为系统测试计划',
            'is_batch_simulate' => '是否批量模拟计划',
            'is_profits_record' => '是否参与账号盈利统计',
            'is_area_profits' => '是否区间盈利记录',
            'is_init_perdate' => '是否每天初始化',
            'lottery_type' => '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
            'take_profits' => '止盈点',
            'stop_loss' => '止损点',
            'current_profits' => '当前盈利',
            'plan_type' => '计划类型:0正常1止盈止损计划',
            'tz_sort' => '投注排序:从小到大',
            'base_codes' => '基础号码',
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
