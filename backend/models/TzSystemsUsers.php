<?php

namespace backend\models;

use common\models\AdminModel;
use Yii;

/**
 * This is the model class for table "{{%tz_systems_users}}".
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int $is_agent 是否代理
 * @property string $username 账号名称
 * @property int $tz_system_id 系统类型id，lt_tz_systems.id
 * @property string $sys_name 系统名称
 * @property string $account 投注账号
 * @property string $flow_wp_accounts 跟随正买账号
 * @property double $flow_wp_player_bs 跟随正买倍数
 * @property string $flow_op_accounts 跟随反买账号
 * @property double $flow_op_player_bs 跟随反买倍数
 * @property string $password 网盘密码
 * @property string $sys_password 机器人网盘密码
 * @property string $sys_repassword 机器人重复密码
 * @property string $balance 系统余额
 * @property int $status 系统开启状态
 * @property string $secure_code 安全码
 * @property int $is_auto_login 是否自动登陆
 * @property string $ssc_domain 网盘地址
 * @property string $cookie 登陆cookie
 * @property string $user_agent 浏览器代理
 * @property int $kj_num 前x位为开奖号码
 * @property string $cookie_wx_web 微信web登录cookie
 * @property string $access_token 授权Token凭证
 * @property int $follow_status 跟随开关
 * @property int $tz_sort 投注排序:从小到大
 * @property string $odds_2x 代理二现赔率
 * @property string $odds_3x 代理三现赔率
 * @property string $odds_4x 代理四现赔率
 * @property string $odds_2d 二定赔率
 * @property string $odds_3d 三定赔率
 * @property string $odds_4d 四定赔率
 * @property string $warn_val 预警值
 * @property string $desc 盘口状态
 * @property float $take_profits 止盈
 * @property float $stop_loss 止损
 * @property float $current_profits 当前盈利
 * @property int $is_auto_bet 自动下注
 * @property int $is_use_proxy 使用代理
 * @property int $is_proxy_login 登录接口使用代理
 * @property int $is_proxy_bet 非登录/下注接口使用代理
 * @property int $is_local_bet 是否本地
 * @property int $proxy_type 代理类型
 * @property int $ssl_mode TLS模式
 * @property int $user_type 用户类型:0:管理员;1:七星;2:3d代理;3:3d总管
 * @property int $expire_time 到期时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $update_time 更新时间
 */
class TzSystemsUsers extends \common\models\base\BaseModel
{
    public $sys_password;
    public $sys_repassword;
    const BET_TYPE_LOCAL_API = 1;
    const BET_TYPE_LOCAL_SELENIUM = 2;
    const BET_TYPE_SERVER = 3;
    const SSL_MODE_INHERIT = 0;
    const SSL_MODE_AUTO = 1;
    const SSL_MODE_TLS12 = 2;
    const SSL_MODE_COMPATIBLE = 3;
    # 下单模式选项
    const BET_TYPE_OPTIONS = [
        self::BET_TYPE_LOCAL_API => '本地api',
        self::BET_TYPE_LOCAL_SELENIUM => '本地selenium点击',
        self::BET_TYPE_SERVER => '服务器下单',
    ];
    const SSL_MODE_OPTIONS = [
        self::SSL_MODE_INHERIT => '继承全局',
        self::SSL_MODE_AUTO => '自动协商',
        self::SSL_MODE_TLS12 => 'TLS 1.2',
        self::SSL_MODE_COMPATIBLE => '兼容 TLS',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tz_systems_users}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'is_agent', 'tz_system_id', 'status', 'follow_status', 'is_auto_login', 'kj_num', 'tz_sort', 'is_auto_bet', 'is_use_proxy', 'is_proxy_login', 'is_proxy_bet', 'user_type', 'is_local_bet', 'proxy_type', 'ssl_mode', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            ['ssl_mode', 'in', 'range' => array_keys(self::SSL_MODE_OPTIONS)],
            [['balance', 'flow_wp_player_bs', 'flow_op_player_bs', 'odds_2x', 'odds_3x', 'odds_4x', 'odds_2d', 'odds_3d', 'odds_4d', 'take_profits', 'stop_loss', 'current_profits'], 'number'],
            [['secure_code', 'cookie', 'cookie_wx_web'], 'string'],
            [['updated_at'], 'required'],
            [['update_time'], 'safe'],
            [['sys_password', 'sys_repassword'], 'trim'],
            [['username', 'sys_name', 'account', 'flow_wp_accounts', 'flow_op_accounts', 'ssc_domain', 'access_token'], 'string', 'max' => 64],
            [['password', 'sys_password', 'sys_repassword'], 'string', 'max' => 20],
            ['sys_repassword', 'compare', 'compareAttribute' => 'sys_password', 'message' => '请再正确输入重复密码'],
            [['user_agent', 'desc'], 'string', 'max' => 640],
            [['flow_wp_accounts', 'flow_op_accounts'], 'string', 'max' => 120],
            [['warn_val'], 'string', 'max' => 11],
        ];
    }

    /**
     * 修改网页登陆密码
     * @param string $sys_password
     * @param string $sys_repassword
     * @return bool
     * @throws \yii\base\Exception
     */
    public static function changePassword(string $sys_password='', string $sys_repassword='', $userId=0): bool
    {
        if(empty($userId)){
            $id = YII::$app->user->id;
        }else{
            $id = $userId;
        }
        $admin=  AdminModel::findIdentity($id);
        if(true OR Yii::$app->getSecurity()->validatePassword($sys_password, $admin->password_hash)){
            //p([$sys_password , $sys_repassword]);
            if($sys_password == $sys_repassword){
                $admin->desc = '账号：'.$admin->username.' 密码：'.$sys_password;
                $admin->setPassword($sys_password);
                $admin->generateAuthKey();
                //$newPass = Yii::$app->getSecurity()->generatePasswordHash($sys_password);
                //$admin->password = $newPass;
                if($admin->save()){
                    return true;
                }else{
                    return false;
                }
            }else{
                Yii::$app->session->setFlash('contact','两次新密码不相等');
                return false;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uid' => Yii::t('app', '用户id'),
            'is_agent' => Yii::t('app', '是否代理'),
            'username' => Yii::t('app', '账号名称'),
            'tz_system_id' => Yii::t('app', '系统类型id，lt_tz_systems.id'),
            'sys_name' => Yii::t('app', '系统名称'),
            'account' => Yii::t('app', '投注账号'),
            'flow_wp_accounts' => Yii::t('app', '跟随正买账号'),
            'flow_wp_player_bs' => Yii::t('app', '跟随正买倍数'),
            'flow_op_accounts' => Yii::t('app', '跟随反买账号'),
            'flow_op_player_bs' => Yii::t('app', '跟随反买倍数'),
            'password' => Yii::t('app', '网盘密码'),
            'sys_password' => Yii::t('app', '机器人登陆密码'),
            'sys_repassword' => Yii::t('app', '机器人登陆重复密码'),
            'balance' => Yii::t('app', '系统余额'),
            'status' => Yii::t('app', '系统开启状态'),
            'secure_code' => Yii::t('app', '安全码'),
            'is_auto_login' => Yii::t('app', '是否自动登陆'),
            'follow_status' => Yii::t('app', '是否自动跟'),
            'ssc_domain' => Yii::t('app', '网盘地址'),
            'cookie' => Yii::t('app', '登陆cookie'),
            'user_agent' => Yii::t('app', '浏览器代理'),
            'kj_num' => Yii::t('app', '前x位为开奖号码'),
            'cookie_wx_web' => Yii::t('app', '微信web登录cookie'),
            'access_token' => Yii::t('app', '授权Token凭证'),
            'tz_sort' => Yii::t('app', '投注排序:从小到大'),
            'odds_2x' => Yii::t('app', '代理二现赔率'),
            'odds_3x' => Yii::t('app', '代理三现赔率'),
            'odds_4x' => Yii::t('app', '代理四现赔率'),
            'odds_2d' => Yii::t('app', '二定赔率'),
            'odds_3d' => Yii::t('app', '三定赔率'),
            'odds_4d' => Yii::t('app', '四定赔率'),
            'warn_val' => Yii::t('app', '预警值'),
            'desc' => Yii::t('app', '盘口状态'),
            'is_auto_bet' => Yii::t('app', '自动下注'),
            'is_use_proxy' => Yii::t('app', '使用代理'),
            'is_proxy_login' => Yii::t('app', '登录代理'),
            'is_proxy_bet' => Yii::t('app', '非登录代理'),
            'is_local_bet' => Yii::t('app', '本地下注'),
            'proxy_type' => Yii::t('app', '代理类型'),
            'ssl_mode' => Yii::t('app', 'TLS模式'),
            'user_type' => Yii::t('app', '用户类型:0:管理员;1:七星;2:3d代理;3:3d总管'),
            'expire_time' => Yii::t('app', '到期时间'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
            'update_time' => Yii::t('app', '更新时间'),
        ];
    }

    /**
     * @inheritdoc
     * @return TzSystemsUsersQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TzSystemsUsersQuery(get_called_class());
    }
}
