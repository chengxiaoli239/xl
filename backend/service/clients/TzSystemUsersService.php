<?php
namespace backend\service\clients;

use backend\models\TzSystemsUsers;
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
    public static function getCookiesByAccessToken($access_token=''){

        $m = \Yii::$app->cache;
        $mkey = self::buildUserCookesKey($access_token);
        $data = $m->get($mkey);

        if(empty($data)){
            $TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
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

    public static function buildUserCookesKey($access_token=''){
        $mkey = 'buildUserCookesKey_0_'.$access_token;
        return $mkey;
    }
}
