<?php

namespace common\service\wechat\eyun;

use common\models\eyun\EyunAuth;
use common\models\eyun\RobotUser;
use common\models\wechat\WechatUser;
use common\service\BaseService;
use common\service\chat\Tool_Common;
use common\service\jobs\robots\user\AfterWechatLoginJobs;
use common\service\jobs\robots\user\EYunUserJobs;

class EYunBaseService  extends BaseService
{
    # 系统用户id  user.id
    public $user_id = '';
    # 微信原始id （首次登录平台的号传空，掉线重登必须传值，否则会频繁掉线！！！） 第三步会返回此字段，记得入库保存
    public $wcId = '';
    # 登录实例标识 （本值非固定的，每次重新登录会返回新的，数据库记得实时更新wid）
    public $wId = '';
    # 用户需安装app/pc，且上传app/pc中的字段 若是开发者公司有app/pc也可直接集成sdk至app/pc中，可以做到无需用户上传，且无需下载我司提供的软件
    public $ttuid = '';
    # e云平台接口域名
    public $base_url = '';
    # e云平台账号
    public $account = '';
    # e云平台密码
    public $password = '';
    # e云平台授权key
    public $Authorization = '';
    # 头
    public $headers = [];

    # 消息内容类型：1原生版2:优化版
    const MSG_TYPE_BASE = 1;
    const MSG_TYPE_IMPROVE = 2;

    public function __construct($user_id='', $wcId='', $config = [])
    {
        $this->user_id = $user_id;
        if(empty($config)){
            $c = \Yii::$app->params['E_YUN'];
            $config = [
                'base_url' => $c['BASE_URL'],
                'account' => $c['ACCOUNT'],
                'password' => $c['PASSWORD'],
                'ttuid' => $c['TTUID'],
            ];
            $this->base_url = $config['base_url'];
            $this->ttuid = $config['ttuid'];
            $this->account = $config['account'];
            $this->password = $config['password'];
        }
        $eyunAuth = EyunAuth::find()->where(['type'=>EyunAuth::TYPE_EYUN])->one();
        if(!empty($eyunAuth)){
            $headers = [
                'Authorization' => $eyunAuth->authorization,
            ];
            $this->headers = $headers;
            $this->account = $eyunAuth->account;
        }
        $RobotUser = RobotUser::findOne(['user_id'=>$user_id, 'wcId'=>$wcId]);
        if(!empty($RobotUser)){
            $this->wcId = $RobotUser->wcId;
            $this->wId = $RobotUser->wId;
        }
        parent::__construct($config);
    }

    /**
     * 第一步：登录E云平台
     * @return bool|mixed|null
     */
    public function memberLogin(){
        $url = $this->base_url . '/member/login';
        $params = [
            'account' => $this->account,
            'password' => $this->password,
        ];
        $response = $this->request($url, $params);
        $mkey = self::getAuthorizationKey();
        if($response['code'] == 1000 && !empty($response['data'])){
            $this->Authorization = $response['data']['Authorization'];
            $m = \Yii::$app->cache;
            $m->set($mkey, $this->Authorization);
            $e = EyunAuth::find()->where(['type'=>EyunAuth::TYPE_EYUN])->one();;
            $setData = [];
            if(empty($e)){
                $e = new EyunAuth();
                $setData = [
                    'account' => $this->account,
                    'password' => $this->password,
                    'created_at' => time(),
                    'status' => self::STATUS_ACTIVE,
                ];
            }
            $setData = array_merge($setData, [
                'authorization' => $this->Authorization,
                'desc' => 'e云平台登陆key',
                'updated_at' => time(),
            ]);
            $e->setAttributes($setData, false);
            $e->save();
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', 'e平台登陆key', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    public static function getUserWIdKey($user_id=''){
        return 'getUserWIdKey_x0_'.$user_id;
    }

    /**
     * 获取二维码(第二步-方式一) - 推荐对接，在调用此接口之前，用户点对应的登陆微信先更新lt_robot_user.wcId
     * @return bool|mixed|null
     */
    public function localIPadLogin($wcId=''){
        $url = $this->base_url . '/localIPadLogin';
        $params = [
            'wcId' => $wcId ? : $this->wcId,
            'ttuid' => $this->ttuid
        ];
        $response = $this->request($url, $params, $this->headers);
        if($response['code'] == 1000 && !empty($response['data'])){
            $m = \Yii::$app->cache;
            $mkey = EYunBaseService::getUserWIdKey($this->user_id);
            $m->set($mkey, $response['data']['wId'], 3600); # 登录实例Id，第一次登录存缓存，登录成功则存lt_robot_user表
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '获取二维码(方式一)', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    /**
     * 获取二维码（第二步-方式2）
     * @return bool|mixed|null
     */
    public function iPadLogin(){
        $url = $this->base_url . '/iPadLogin';
        $params = [
            'account' => $this->account,
            'password' => $this->password,
        ];
        $response = $this->request($url, $params);

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '获取二维码(方式二)', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    public static function getAuthorizationKey(){
        return 'getAuthorizationKey_x0';
    }

    /**
     * 执行微信登录（第三步）  第二步获取二维码之后循环持续掉，或者调用一次一直等待（前端页面调用）
     * @param string $wId
     * @return bool|mixed|null
     */
    public function getIPadLoginInfo($wId=''){
        $url = $this->base_url . '/getIPadLoginInfo';
        $params = [
            'wId' => $wId ? : $this->wId,
        ];
        $response = $this->request($url, $params, $this->headers);
        if($response['code'] == '1000' && !empty($response['data'])){
            $data = $response['data'];
            $data['busness_id'] = $data['wcId'];
            $data['user_id'] = $this->user_id;
            push_queue_open(AfterWechatLoginJobs::class, $data);
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '执行微信登录（第三步）', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    /**
     * 初始化通讯录列表（第四步）
     * @return bool|mixed|null
     */
    public function initAddressList(){
        $url = $this->base_url . '/initAddressList';
        $params = [
            'wId' => $this->wId,
        ];
        $response = $this->request($url, $params, $this->headers);

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '初始化通讯录列表（第四步）', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    /**
     * 初始化通讯录列表（第五步），此步仅获取微信id，便于下一步获取微信用户信息
     * @return bool|mixed|null
     */
    public function getAddressList(){
        $url = $this->base_url . '/getAddressList';
        $params = [
            'wId' => $this->wId,
        ];
        $response = $this->request($url, $params, $this->headers);
        if($response['code'] == 1000 && !empty($response['data']['friends'])){
            $friends = $response['data']['friends'];
            $newFriends = array_filter($friends, function ($value){
                if(!in_array($value, [
                    'filehelper'
                ])){
                    return true;
                }
            });
            $params = [
                'friends' => $newFriends,
                'user_id' => $this->user_id,
                'wcId' => $this->wcId,
                'business_id' => $this->user_id,
            ];
            push_queue(EYunUserJobs::class, $params);
        }


        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '初始化通讯录列表（第五步）', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    /**
     * 获取联系人信息（第六步）
     * @param string $user_id
     * @param array $wcIds
     * @return bool|mixed|null
     */
    public function getContact($wcIds=[]){
        if(empty($wcIds)){
            return '';
        }
        $url = $this->base_url . '/getContact';
        $params = [
            'wId' => $this->wId,
            'wcId' => implode(',', $wcIds), # 好友微信id/群id,多个好友/群 以","分隔每次最多支持20个微信/群号,记得本接口随机间隔300ms-1500ms，频繁调用容易导致掉线
        ];
        $response = $this->request($url, $params, $this->headers);

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '获取联系人信息（第六步）', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    public static function getUserIdByFromUserKey($wcId=''){
        return 'getUserIdByFromUserKey_'.$wcId;
    }

    /**
     * 微信消息用户获取系统用户id
     * @param string $fromUser wxid_ckgr7i2q9fr522
     * @return int
     */
    public static function getUserIdByFromUser($fromUser='', $is_auto=1){
        $m = \Yii::$app->cache;
        $mkey = self::getUserIdByFromUserKey($fromUser);
        $data = $m->get($mkey);
        if(empty($data) OR $is_auto==2){
            $data = WechatUser::find()->where(['userName'=>$fromUser])->asArray()->limit(1)->one();;
            $m->set($mkey, $data, 1800);
        }

        return (int)$data['user_id'];
    }

    public static function getRobotInfoKey($wcId=''){
        return 'getRobotInfoKey_'.$wcId;
    }

    /**
     * 获取机器人信息by wcId robot_user
     * @param string $wcId 微信原始id （首次登录平台的号传""，掉线后必须传值，否则会频繁掉线！！！） 第三步会返回此字段，记得入库保存
     * @return array
     */
    public static function getRobotByWcId($wcId='', $is_auto=1){
        $m = \Yii::$app->cache;
        $mkey = self::getRobotInfoKey($wcId);
        $data = $m->get($mkey);
        if(empty($data) OR $is_auto==2){
            $data = RobotUser::find()->where(['wcId'=>$wcId])->asArray()->limit(1)->one();
            $m->set($mkey, $data, 1800);
        }

        return $data;
    }

    /**
     * 是否在线
     * @return bool
     */
    public function isOnline(){
        $url = $this->base_url . '/isOnline';
        $params = [
            'wId' => $this->wId,
        ];
        $response = $this->request($url, $params, $this->headers);
        if($response['code'] == '1000' && !empty($response['data'])){
            $data = $response['data'];
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '是否在线', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    /**
     * 批量下线微信号
     * @param array $wcIds
     * @return bool|mixed|null
     */
    public function setOffline($wcIds=[]){
        $url = $this->base_url . '/member/offline';
        $params = [
            'account' => $this->account,
            'wcIds' => $wcIds,
        ];
        $response = $this->request($url, $params, $this->headers);
        if($response['code'] == '1000' && !empty($response['data'])){
            $data = $response['data'];
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '是否在线', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }
}
