<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\TzSystems;
use backend\models\TzSystemsAuth;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\clients\TzSystemUsersService;
use common\models\AdminModel;
use common\models\AuthAssignment;
use common\service\cache\CacheKeyService;
use common\tools\Tool_Common;
use backend\models\User;
use backend\models\UserFollowData;
use  yii;
use yii\helpers\Json;

class UserService extends BaseService {
    const USER_CLIENT_LOGIN_NO = 0;
    const USER_CLIENT_LOGIN_NEED = 1;
    const USER_CLIENT_LOGIN_ING = 2;
    const USER_CLIENT_LOGIN_OPTIONS = [
        self::USER_CLIENT_LOGIN_NO => '无需登录',
        self::USER_CLIENT_LOGIN_NEED => '需登录',
        self::USER_CLIENT_LOGIN_ING => '正在登录',
    ];


    /**
     * @decription Yii 控制器初始化方法
     */
    public static function _init(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $time = date("H:i");
    }

    public static function opUser($admin_id, $action, $role){
        $rst = true;
        $model = User::findOne(['admin_id'=>$admin_id]);
        $now_time = time();
        if($action == 'add'){
            # 添加赔率记录
            if(!$model){
                $User = new User();
                $AdminModel = AdminModel::findOne($admin_id);
                $insertData = [
                    'admin_id'=>$admin_id,
                    'email'=>$AdminModel->email?:$AdminModel->username.'@126.com',
                    'account'=>$AdminModel->username,
                    'username'=>$AdminModel->username,
                    'user_type' => $AdminModel->user_type,
                    'created_at'=> $now_time,
                    'updated_at'=> $now_time,
                ];
                $User->setAttributes($insertData);
                if(!$User->save()){
                    throw_info(Json::encode($User->getErrors()));
                }
                //p(['3d'=>UserService::is3dAdmin(\Yii::$app->user->identity), 'attr'=>$User->attributes]);

                AuthAssignment::deleteRecord(['user_id'=>$admin_id, 'item_name'=>$role]);
                $AuthAssignment = new AuthAssignment();
                $insertData = [
                    'item_name' => $role,
                    'user_id'=>$admin_id,
                    'created_at'=>$now_time,
                    'updated_at'=>$now_time,
                ];
                $AuthAssignment->setAttributes($insertData);
                if(!$AuthAssignment->save(false)){
                    throw_info(Json::encode($AuthAssignment->getErrors()));
                }
            }
        }else{
            # 删除用户记录
            $rst = User::deleteRecord(['admin_id'=>$admin_id]);
        }

        return $rst;

    }

    /**
     * @desc 预处理表单信息
     * @param $post
     * @return bool
     */
    public static function preOpenData(&$post, $uid){
        if(!$post) return false;

        $post['TzSystemsAuth']['tz_systems_ids'] && $post['TzSystemsAuth']['tz_systems_ids'] = implode(',',$post['TzSystemsAuth']['tz_systems_ids']);
        $post['TzSystemsAuth']['tz_types'] && $post['TzSystemsAuth']['tz_types'] = implode(',',$post['TzSystemsAuth']['tz_types']);
        $post['TzSystemsAuth']['lottery_types'] && $post['TzSystemsAuth']['lottery_types'] = implode(',',$post['TzSystemsAuth']['lottery_types']);

        $post['TzSystemsAuth']['uid'] = $uid;
        $post['TzSystemsAuth']['updated_at'] = time();

        if(!$post['TzSystemsAuth']['id']){
            $post['TzSystemsAuth']['created_at'] = time();
        }

        return $post;
    }


    /**
     * @desc 添加系统和投注方式等权限
     * @param $tz_systems_ids_Arr
     * @param $uid
     * @param string $opType
     */
    public static function saveTzSystemUsers($tz_systems_ids_Arr, $uid, string $opType = 'add'): bool
    {
        //p([$tz_systems_ids_Arr, $uid, $opType],0);

        foreach ($tz_systems_ids_Arr as $tz_system_id){
            if($opType == 'add' OR $opType == 'update'){
                $setData = [];
                if(!$TzSystemsUsers = TzSystemsUsers::findOne(['tz_system_id'=>$tz_system_id, 'uid'=>$uid])){
                    $TzSystemsUsers = new TzSystemsUsers();
                    $setData['created_at'] = time();
                    $setData['expire_time'] = time() + 30*86400 + 5*3600; # 默认 30天加5个小时
                }
                $TzSystems = TzSystems::findOne($tz_system_id);
                $user = AdminModel::findOne($uid);
                $setData['updated_at'] = time();
                $setData = array_merge($setData,[
                    'uid' => $uid,
                    'username' => $user->username,
                    'user_type' => $user->user_type,
                    'ssc_domain' => $TzSystemsUsers->ssc_domain ? $TzSystemsUsers->ssc_domain : $TzSystems->ssc_domain,
                    'access_token' => $TzSystemsUsers->access_token ? $TzSystemsUsers->access_token : md5('tz_systemUsers_'.$user->username),
                    'tz_system_id' => $tz_system_id,
                    'sys_name' => $TzSystems->name,
                ]);

                $TzSystemsUsers->setAttributes($setData);
                $rst = $TzSystemsUsers->save();
            }
        }
        $where = ['and', ['=', 'uid', $uid], ['not in', 'tz_system_id', $tz_systems_ids_Arr]];
        //TzSystemsUsers::deleteAll($where); # 逻辑删除
        TzSystemsUsers::deleteRecord($where); # 物理删
        return $rst;
    }

    /**
     * @desc 更新用户信息
     * @param $data
     * @return bool
     */
    public static function updateTzSystemUsers($data){
        $where = ['username'=>$data['Admin']['username']];
        $TzSystemsUsers = TzSystemsUsers::findAll($where);
        foreach ($TzSystemsUsers as $TzSystemsUser){
            $TzSystemsUser->expire_time = strtotime($data['Admin']['pay_time']) + 30 * 86400;
            $TzSystemsUser->updated_at = time();

            $rst[$TzSystemsUser->id] = $TzSystemsUser->save();
        }

        return $rst;
    }

    /**
     * @description 更新用户表状态
     * @param $id
     * @param $status
     * @return array
     */
    public static function updateUserStatus($id, $status)
    {
        if(!$id) return ['status'=>300, 'msg'=>'id为空'];
        $m = \Yii::$app->cache;
        $mkey = 'updateUserStatus_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        $data = AdminModel::findOne($id);
        $data->status = (int)$status;

        $m->set($mkey, 1, 10);

        $rst = $data->save(false);
        $TzSystemsUsers = TzSystemsUsers::findAll(['uid'=>$id]);
        foreach ($TzSystemsUsers as $TzSystemsUser){
            $TzSystemsUser->status = $status == 1 ? 0 : 1;
            $TzSystemsUser->save();
        }

        return $rst;
    }

    /**
     * @description 更新用户表状态 tz_systems_users 表状态
     * @param $id
     * @param $status
     * @return array|bool
     */
    public static function updateUserTzSystemStatus($id, $status)
    {
        if(!$id) return ['status'=>300, 'msg'=>'id为空'];

        TzSystemUsersService::getAuthAccessTokens(2);

        $TzSystemsUser = TzSystemsUsers::findOne($id);
        $TzSystemsUser->status = (int)$status;
        $TzSystemsUser->cookie = '';
        $TzSystemsUser->balance = '';
        $rst = $TzSystemsUser->save();

        return $rst;
    }


    /**
     * @desc 用户默认投注彩种
     * @param $uid
     * @return mixed
     */
    public static function getUserDefaultLotteryType($uid){

        $defaultSiteIds = explode(',',TzSystemsAuth::findOne(['uid'=>$uid])->lottery_types);

        return $defaultSiteIds[0] ? : DEFAULT_LOTTERY_TYPE;
    }

    /**
     * @desc 用户默认投注彩种
     * @param $uid
     * @return mixed
     */
    public static function getUserDefaultSite($uid){

        $defaultSiteIds = explode(',',TzSystemsAuth::findOne(['uid'=>$uid])->tz_systems_ids);

        return $defaultSiteIds[0] ? : DEFAULT_LOTTERY_TYPE;
    }

    /**
     * @dssc 账号是否过期 描述
     * @param string $user_id
     * @param string $tz_system_id
     * @param string $TzSystemsUsers
     * @return boolean
     */
    public static function accountIsExpire($user_id = '', $tz_system_id = '', &$TzSystemsUsers=''){
        $where = ['uid'=>$user_id];
        if(!empty($tz_system_id)){
            $where['tz_system_id'] = $tz_system_id;
        }
        $TzSystemsUsers = TzSystemsUsers::findOne($where);
        if(!$TzSystemsUsers) return false;
        $flag = false;
        if(!empty($TzSystemsUsers->expire_time)){
            if($TzSystemsUsers->expire_time >= time()){
                $flag = true;
            }else{
                $TzSystemsUsers->desc = '账号过期，请及时续费';
                $TzSystemsUsers->save();
            }
        }else{
            $flag = true;
        }

        return  $flag;
    }

    /**
     * @dssc 账号是否过期 描述
     * @param string $tz_system_users_id
     * @return false|string
     */
    public static function accountIsExpireDesc($user_id = '', $tz_system_id = ''){
        $Model = TzSystemsUsers::findOne(['uid'=>$user_id, 'tz_system_id'=>$tz_system_id]);
        //$Model = TzSystemsUsers::findOne($tz_system_users_id);
        if(!$Model) return '';
        $date_time = 86400; # 一天时间戳
        if(!empty($Model->expire_time)){
            if($Model->expire_time <= time()){
                $txt = '<font color="red">已过期，请续费</font>';
            }elseif($Model->expire_time - 2 * $date_time < time()){
                $txt = date('m-d H:i', $Model->expire_time) . ' [<font color="red">即将到期</font>]';
            }else{
                $txt = date('m-d H:i', $Model->expire_time);
            }
        }else{
            $txt = '<font color="green">永久</font>';
        }
        $options = [
            'class' => 'renew-account',
            'data-id' => $Model->id,
            'data-expire-time' => date('Y-m-d H:i', $Model->expire_time),
            'data-username' => $Model->username,
            'id' => 'renew_'.$Model->id,
        ];
        $txt = yii\helpers\Html::a($txt, '#', $options);

        return $txt;
    }

    /**
     * @desc 过期时间设置
     * @param $id TzSystemsUsers.id
     * @param $expire_time
     */
    public static function upExpireTime($id, $expire_time = ''){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        if(empty($expire_time)) $expire_time = '';
        $TzSystemsUsers = TzSystemsUsers::findOne($id);
        if(!$TzSystemsUsers){
            return ['status'=>302, 'msg'=>'找不到记录'];
        }
        $TzSystemsUsers->expire_time = strtotime($expire_time);
        $TzSystemsUsers->updated_at = time();
        $TzSystemsUsers->desc = '';
        $flag = $TzSystemsUsers->save();
        if(!$flag){
            return ['status'=>303, 'msg'=>current($TzSystemsUsers->getErrors())];
        }
        $rst['data']['expire_time'] = $expire_time;

        return $rst;
    }

    /**
     * @desc 记录用户的登陆cookies
     * @param $datas
     * @return array
     */
    public static function updateUserCookies($datas = []){
        if(empty($datas['cookies'])){
            return ['status'=>302, 'msg'=>'用户cookies不能为空'];
        }
        if(empty($datas['access_token'])){
            return ['status'=>302, 'msg'=>'用户凭证不能为空'];
        }

        $where = ['access_token'=>$datas['access_token']];
        $TzSystemsUsers = TzSystemsUsers::findOne($where);
        if(empty($TzSystemsUsers)){
            return ['status'=>303, 'msg'=>'找不到用户记录'];
        }
        $TzSystemsUsers->ssc_domain = trim($datas['ssc_domain']);
        $TzSystemsUsers->account = trim($datas['account']);
        $TzSystemsUsers->password = trim($datas['password']);

        $cookies = $datas['cookies'];
        $cookies_str = '';
        foreach ($cookies as $cookie){
            $cookies_str .= $cookie['name'].'='.$cookie['value'].';';
        }
        $TzSystemsUsers->cookie = trim(trim($cookies_str), ';');
        $user_agent = $datas['user_agent'] ? 'User-Agent: '.$datas['user_agent'] : $TzSystemsUsers->user_agent;
        $TzSystemsUsers->user_agent = $user_agent;
        $r = $TzSystemsUsers->save();
        if(!$r){
            $msg = $TzSystemsUsers->getErrors();
            return ['status'=>304, 'msg'=>$msg];
        }
        $mKey = CacheKeyService::getIsClientNeedLoginKey($TzSystemsUsers->uid);
        commonRedis()->setex($mKey, 120, self::USER_CLIENT_LOGIN_NO); # 更新登录标识
        #$synRst = BetService::synBalance($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id);

        return ['status'=>200, 'msg'=>'操作成功', 'data'=>$r, /*'synRst'=>$synRst*/];
    }

    /**
     * @desc 获取用户账号信息
     * @param array $data
     * @return array
     */
    public static function getUserInfoByToken($data=[]){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        if(empty($data)){
            return ['status'=>404, 'msg'=>'找不到用户数据'];
        }
        if(empty($data['access_token'])){
            return ['status'=>405, 'msg'=>'非法请求'];
        }
        $TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$data['access_token']]);
        $rstData = [
            //'uid' => $TzSystemsUsers->uid,
            'username' => $TzSystemsUsers->username,
            'ssc_domain' => $TzSystemsUsers->ssc_domain,
            'account' => $TzSystemsUsers->account,
            'password' => $TzSystemsUsers->password,
            'follow_status' => $TzSystemsUsers->follow_status,
            'secure_code' => $TzSystemsUsers->secure_code,
            'access_token' => $TzSystemsUsers->access_token,
            'status' => $TzSystemsUsers->status,
            'bet_status' => $TzSystemsUsers->is_auto_bet,
            'expire_time' => date('Y-m-d H:i:s', $TzSystemsUsers->expire_time),
        ];
        $mKey = CacheKeyService::getIsClientNeedLoginKey($TzSystemsUsers->uid);
        $rstData['is_need_login'] = (int)commonRedis()->get($mKey);
        if($TzSystemsUsers->username == 'aa30301'){
            #$rstData['is_need_login'] = 1;
        }

        $rst['data'] = $rstData;

        return $rst;
    }

    /***
     * 更新客户端登录了标识
     * @param $access_token
     * @param $flag
     * @return array
     */
    public static function updateClientLoginFlag($access_token, $flag=2): array
    {
        $TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
        $mKey = CacheKeyService::getIsClientNeedLoginKey($TzSystemsUsers->uid);
        $rstData['is_need_login'] = commonRedis()->setex($mKey, 120, $flag);

        return $rstData;
    }

    # 清空用户的登陆seesion数据
    public static function clearUserLoginInfo($uid){

        $m = \Yii::$app->cache;
        $mkey = self::builtUserLoginInfoMkey($uid);

        return $m->delete($mkey);
    }

    # 获取用户的登陆seesion数据
    public static function getUserLoginInfo($uid){

        $m = \Yii::$app->cache;
        $mkey = self::builtUserLoginInfoMkey($uid);

        return $m->get($mkey);
    }

    public static function builtUserLoginInfoMkey($uid=''){
        $mkey = 'setUserLoginInfo_'.$uid;

        return $mkey;
    }

    # 设置用户登陆session
    public static function setUserLoginInfo($uid, $session_id=''){
        if(empty($session_id)){
            $session_id = Yii::$app->getSession()->id;
        }
        $m = \Yii::$app->cache;
        $mkey = self::builtUserLoginInfoMkey($uid);
        $data = $m->get($mkey);
        $data = empty($data) ? [] : $data;

        $data[] = $session_id;

        $m->set($mkey, $data, 30*86400);
    }

    # 设置用户登陆session
    public static function delUserOneSessionId($uid, $session_id=''){
        if(empty($session_id)){
            return false;
        }
        $m = \Yii::$app->cache;
        $mkey = self::builtUserLoginInfoMkey($uid);
        $data = $m->get($mkey);

        $data = empty($data) ? [] : $data;
        foreach ($data as $key=>$u_session_id){
            if($u_session_id == $session_id){
                unset($data[$key]);
            }
        }

        $m->set($mkey, $data, 30*86400);
        return true;
    }

    /**
     * 设置止盈止损
     * @param $uid
     * @param array $data
     * @return bool
     */
    public static function setProfits($uid, $data=[]){
        try {
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
            $TzSystemsUsers->stop_loss = ($data['stop_loss']?:0.00);
            $TzSystemsUsers->take_profits = ($data['take_profits']?:0.00);
            #$TzSystemsUsers->current_profits = ($data['current_profits']?:0.00);
            #$TzSystemsUsers->desc = ($data['take_profits']?:'');
            $TzSystemsUsers->save();
        }catch (\Exception $e){

        }

        return false;
    }

    /**
     * 设置止盈止损
     * @param $uid
     * @param array $data
     * @return bool
     */
    public static function setFollowBuy($uid, $data=[]){
        try {
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
            $TzSystemsUsers->flow_wp_accounts = ($data['flow_wp_accounts']? trim(str_replace('，', ',', $data['flow_wp_accounts'])):'');
            $TzSystemsUsers->flow_wp_player_bs = ($data['flow_wp_player_bs']? round(trim($data['flow_wp_player_bs']), 1):'');
            $TzSystemsUsers->flow_op_accounts = ($data['flow_op_accounts']? trim(str_replace('，', ',', $data['flow_op_accounts'])):'');
            $TzSystemsUsers->flow_op_player_bs = ($data['flow_op_player_bs']? round(trim($data['flow_op_player_bs']), 1):'');
            if(!$TzSystemsUsers->save()){
                throw_info(yii\helpers\Json::encode($TzSystemsUsers->getErrors(), 320));
            }
        }catch (\Exception $e){
            return false;
        }

        return true;
    }

    /**
     * 账号当前盈利
     * @param $uid
     * @return mixed
     */
    public static function staticUserProfits($uid){
        $where = ['is_profits_record'=>0, 'is_area_profits'=>0, 'uid'=>$uid, 'status'=>[0, 1], 'is_test'=>0];
        $current_profits_query = UserSysPlans::find()->select(['current_profits'=>'SUM(current_profits)'])
            ->where($where);
        $sql = $current_profits_query->createCommand()->getRawSql();
        $current_profits = $current_profits_query->asArray()->one()['current_profits'];
        Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '统计当前盈利', ['user_id'=>$uid, 'sql'=>$sql, 'current_profits'=>$current_profits]);

        return $current_profits?:0.00;
    }

    /**
     * 同步最新的用户盈利
     * @param $uid
     * @return array
     */
    public static function updateUserProfits($TzSystemsUsers){
        try {
            if($TzSystemsUsers->take_profits>0 OR $TzSystemsUsers->stop_loss>0){
                $current_profits = UserService::staticUserProfits($TzSystemsUsers->uid);
                if($current_profits>$TzSystemsUsers->take_profits OR $current_profits<(0-$TzSystemsUsers->stop_loss)){
                    #throw_info('触发止盈止损：'.$current_profits, BetService::STOP_BET_CODE);
                    $TzSystemsUsers->desc = '触发止盈止损：'.$current_profits;
                    $TzSystemsUsers->current_profits = $current_profits;
                    $TzSystemsUsers->save();
                    $code = BetService::STOP_BET_CODE;
                }else{
                    $TzSystemsUsers->desc = '';
                    $TzSystemsUsers->current_profits = $current_profits;
                    $TzSystemsUsers->save();
                    $code = 0;
                }
            }
        }catch (\Exception $e){
            Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '归零异常', ['username'=>$TzSystemsUsers->username, 'err_msg'=>$e->getMessage()]);
        }

        return [$code, $current_profits, $TzSystemsUsers];
    }

    /**
     * 重置access_token
     * @param $id
     * @return string
     * @throws \common\exceptions\InfoException
     */
    public static function resetToken($user_id){

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$user_id]);
        if(empty($TzSystemsUsers)){
            throw_info(yii\helpers\Json::encode('找不到用户记录uid:'.$user_id));
        }
        $new_str = date('Y-m-d').'_'.rand(100, 999).'_'.$user_id;
        $new_access_token = md5($new_str);
        $TzSystemsUsers->access_token = $new_access_token;
        if(!$TzSystemsUsers->save()){
            throw_info(yii\helpers\Json::encode($TzSystemsUsers->getErrors()));
        }

        return $new_access_token;
    }

    /**
     * 判断是否为3d用户
     * @param $user_id
     * @return bool
     */
    public static function is3dUser($user_id): bool
    {
        $model = TzSystemsUsers::find()->where(['uid'=>$user_id])->one();
        $is_3d = false;
        if(strpos($model->sys_name, '排') !==false OR strpos($model->sys_name, '福') !==false){
            $is_3d = true;
        }

        return $is_3d;
    }

    /**
     * 判断是否为3d总管
     * @param $user
     * @return bool
     */
    public static function is3dAdmin($user): bool
    {
        $is_3d_admin = false;
        if(array_key_exists('3D总管', Yii::$app->authManager->getRolesByUser($user->id)) OR $user->user_type == AdminModel::USER_TYPE_3D_ADMIN){
            $is_3d_admin = true;
        }

        return $is_3d_admin;
    }

    /**
     * 判断是否为3d代理
     * @param $user
     * @return bool
     */
    public static function is3dProxy($user): bool
    {
        $is_3d_proxy = false;
        if(array_key_exists('3D代理', Yii::$app->authManager->getRolesByUser($user->id)) OR $user->user_type == AdminModel::USER_TYPE_3D){
            $is_3d_proxy = true;
        }

        return $is_3d_proxy;
    }

    /**
     * 判断是否为龟盘总代
     * @param $user
     * @return bool
     */
    public static function isGuiProxyAll($user): bool
    {
        $is_proxy = false;
        if(array_key_exists('龟盘总代', Yii::$app->authManager->getRolesByUser($user->id)) OR $user->user_type == AdminModel::USER_TYPE_GUI_ALL){
            $is_proxy = true;
        }

        return $is_proxy;
    }

    /**
     * 获取管理员管理可以管理的user_types
     * @param $user
     * @return array
     */
    public static function getAdminUserTypes($user, $act=0): array
    {
        $user_types = [];
        if($user->id==1){
            foreach (AdminModel::USER_TYPE_OPTIONS as $user_type=>$name){
                if($user_type==AdminModel::USER_TYPE_SUPER_ADMIN) continue;
                if($act==1 && $user_type==AdminModel::USER_TYPE_3D_ADMIN) continue; # 详细信息
                $user_types[] = ['user_type'=>$user_type, 'name'=>$name];
            }
        }else if(array_key_exists('3D总管', Yii::$app->authManager->getRolesByUser($user->id)) OR $user->user_type == AdminModel::USER_TYPE_3D_ADMIN) {
            $user_types[] = ['user_type' => AdminModel::USER_TYPE_3D, 'name' => AdminModel::USER_TYPE_OPTIONS[AdminModel::USER_TYPE_3D]];
        }elseif($user->user_type == AdminModel::USER_TYPE_GUI_ALL){
            $user_types[] = ['user_type' => AdminModel::USER_TYPE_GUI, 'name' => AdminModel::USER_TYPE_OPTIONS[AdminModel::USER_TYPE_GUI]];
        }

        return $user_types;
    }

    /**
     * 获取当前管理下的user_type
     * @param $user
     * @param array $queryParams
     * @return int
     */
    public static function getUserType($user, array $queryParams=[], $modelName='Assignment'): int
    {
        if($user->id==1){
            $mkey = CacheKeyService::userType($user->id);
            if(isset($queryParams[$modelName]['user_type'])){
                $user_type = (int)$queryParams[$modelName]['user_type'];
            }else{
                $user_type = commonRedis()->get($mkey) ? : AdminModel::USER_TYPE_QX;
            }
            commonRedis()->setex($mkey, 1800, $user_type);
        }else if(UserService::is3dAdmin($user)){
            $user_type = AdminModel::USER_TYPE_3D;
        }else if(UserService::is3dProxy($user)){
            $user_type = AdminModel::USER_TYPE_3D_CHILD;
        }else if(UserService::isGuiProxyAll($user)){
            $user_type = AdminModel::USER_TYPE_GUI;
        }else{
            $user_type = 99;
        }

        return (int)$user_type;
    }

    /**
     * 获取当前创建账号默认的user_type
     * @param $user
     * @param int $userType
     * @return array
     */
    public static function getCreateDefaultRole($user, $userType=0)
    {
        if(AdminModel::USER_TYPE_OPTIONS[$userType]){
            $role = AdminModel::USER_TYPE_OPTIONS[$userType];
        }else if($user->id==1){
            $role = '收费会员';
            $userType = AdminModel::USER_TYPE_QX;
        }else if(UserService::is3dAdmin($user)){
            $role = '3D代理';
            $userType = AdminModel::USER_TYPE_3D;
        }else if(UserService::is3dProxy($user)){
            $role = '3D代理下级';
            $userType = AdminModel::USER_TYPE_3D_CHILD;
        }else if(UserService::isGuiProxyAll($user)){
            $role = '龟代理';
            $userType = AdminModel::USER_TYPE_GUI;
        }else{
            $role = '收费会员';
            $userType = AdminModel::USER_TYPE_QX;
        }

        return [$userType, $role];
    }
}
