<?php
namespace backend\service\clients;

use backend\models\TzSystemsUsers;
use backend\service\BetService;
use common\service\CommonService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class UserSysPlansService extends ClientsBaseService{

    public static $module_key = 'backend\models\UserSysPlans';

    public static function getLists($uid=''){
        $post = \Yii::$app->request->post();
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
                $where = array_merge($where, [['=', 'uid', $uid]]);
            }

            $datas = (self::$module_key)::find()->where($where)->asArray()->all();
            $m->set($mkey, $datas, 30);
        }

        return ['status'=>200, 'datas'=>$datas, 'msg'=>'操作成功'];
    }

    /**
     * @desc 用户列表key
     * @return string
     */
    public static function buildUserKey(){
        $mkey = 'get'.self::$module_key.'Lists_key';

        return $mkey;
    }

    /**
     * @desc 删除用户缓存信息
     */
    public static function delUserData(){
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
        if(!$access_tokens = $m->get($mkey)){
            $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
            $TzSystemsUsers = TzSystemsUsers::find()->select(['access_token'])->where(['status'=>1])->asArray()->all();
            $access_tokens = ArrayHelper::getColumn($TzSystemsUsers, 'access_token');

            $access_tokens = array_merge($access_tokens, [$ADMIN_ACCESS_TOKEN]);
            $m->set($mkey, $access_tokens, 600);
        }

        return $access_tokens;
    }

}
