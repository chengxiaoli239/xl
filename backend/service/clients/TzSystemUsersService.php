<?php
namespace backend\service\clients;

use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\BetService;
use backend\service\Lucky5\Lucky5Service;
use common\service\CommonService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class TzSystemUsersService extends ClientsBaseService{
    public static $module_key = 'backend\models\TzSystemsUsers';

    public static function getLists($post=[]){
        if(empty($post)){
            return ['status'=>300, 'msg'=>'非法请求'];
        }
        $token = $post['access_token'];
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey();
        $datas = $m->get($mkey);
        if(empty($datas)){
            $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
            $where = ['AND',['<>', 'status', -2], '1=1'];
            if($ADMIN_ACCESS_TOKEN != $token){
                $where = array_merge($where, [['=', 'access_token', $token]]);
            }

            $datas = (self::$module_key)::find()->where($where)->asArray()->all();
            $m->set($mkey, $datas, 180);
        }

        return ['status'=>200, 'datas'=>$datas, 'msg'=>'操作成功'];
    }

    /**
     * @desc 用户列表key
     * @return string
     */
    public static function buildUserKey(){
        $mkey = 'getTzSystemUserLists_key';

        return $mkey;
    }

    /**
     * @desc 删除用户缓存信息
     */
    public static function delTzsystemUserData(){
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey();

        $m->delete($mkey);
    }

    /**
     * @desc 获取系统授权access_tokens
     * @return array|mixed
     */
    public static function getAuthAccessTokens(){
        $m = \Yii::$app->cache;
        $mkey = 'user_getAuthAccessTokens';
        if(true OR !$access_tokens = $m->get($mkey)){
            $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
            $TzSystemsUsers = TzSystemsUsers::find()->select(['access_token'])->where(['status'=>1])->asArray()->all();
            $access_tokens = ArrayHelper::getColumn($TzSystemsUsers, 'access_token');

            $access_tokens = array_merge($access_tokens, [$ADMIN_ACCESS_TOKEN]);
            $m->set($mkey, $access_tokens, 600);
        }

        return $access_tokens;
    }

    /**
     * @desc 获取用户的cookies
     * @param string $access_token
     * @return mixed|string
     */
    public static function getCookiesByAccessToken($access_token='', $is_auto=1){

        $m = \Yii::$app->cache;
        $mkey = self::buildUserCookesKey($access_token);
        $data = $m->get($mkey);

        if($is_auto==2 OR empty($data)){
            //$TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            $cookies = $TzSystemsUsers->cookie;
            #$m->set($mkey, $cookies, 300);

            Lucky5Service::__init($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id);
            $tzSiteInfo = Lucky5Service::getTzSiteInfo($TzSystemsUsers->tz_system_id);
            $data = [
                'cookies'=>$cookies,
                'user_agent'=>trim(str_replace('User-Agent:', '',str_replace('user_agent:','', $TzSystemsUsers->user_agent))),
                "Referer"=>$TzSystemsUsers->ssc_domain."/App/Index?_=",
                "Host"=>str_replace('www.','',$tzSiteInfo['domain']),
            ];
            $m->set($mkey, $data, 60);
        }

        return ['status'=>200, 'data'=>$data];
    }

    /**
     * @desc 获取用户的cookies
     * @param string $access_token
     * @return mixed|string
     */
    public static function updateRobot7ByAccessToken($access_token='', $new_robot7='', $old_robot7=''){

        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token, $is_auto=2);
        $cookies = $TzSystemsUsers->cookie;
        $TzSystemsUsers->cookie = str_replace($old_robot7, $new_robot7, $cookies);
        $flag = $TzSystemsUsers->save();
        TzSystemUsersService::getCookiesByAccessToken($access_token, $is_auto=2);

        return ['status'=>200, 'data'=>['flag'=>$flag]];
    }

    public static function buildTzSystemUsersKey($access_token){
        $mkey = 'buildTzSystemUsersKey_'.$access_token;

        return $mkey;
    }

    /**
     * @desc 获取下注用户信息
     * @param string $access_token
     * @return TzSystemsUsers|mixed|null
     */
    public static function getTzSystemsUsersByAccessToken($access_token='', $is_auto=1){
        $m = \Yii::$app->cache;
        $mkey = TzSystemUsersService::buildTzSystemUsersKey($access_token);
        $TzSystemsUsers = $m->get($mkey);
        if($is_auto==2 OR empty($TzSystemsUsers)){
            $TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
            $m->set($mkey, $TzSystemsUsers, 30);
        }

        return $TzSystemsUsers;
    }

    public static function buildUserCookesKey($access_token=''){
        $mkey = 'buildUserCookesKey_1_'.$access_token;
        return $mkey;
    }

    /**
     * @desc 获取用户的cookies
     * @param string $access_token
     * @return mixed|string
     */
    public static function getActivePlanIds($access_token=''){

        $m = \Yii::$app->cache;
        $mkey = self::buildUserPlanidsKey($access_token);
        $data = $m->get($mkey);

        if(empty($data)){
            //$TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            $uid = $TzSystemsUsers->uid;

            $where = ['AND', ['=', 'status', 1], ['OR', ['=', 'is_batch_simulate', 0], ['IS', 'is_batch_simulate', NULL]], ['=', 'uid', $uid]]; # is_batch_simulate:0正常1批量模拟历史记录
            $plans = UserSysPlans::find()->where($where)->asArray()->all();
            $data = ArrayHelper::getColumn($plans, 'id');

            $m->set($mkey, $data, 60);
        }

        return ['status'=>200, 'data'=>$data];
    }

    public static function buildUserPlanidsKey($access_token=''){
        $mkey = 'buildUserPlanidsKey_1_'.$access_token;
        return $mkey;
    }
}
