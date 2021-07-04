<?php
namespace backend\service\clients;

use backend\models\TzSystemsUsers;
use backend\service\BetService;
use common\service\CommonService;
use common\tools\Tool_Common;

class TzSystemUsersService extends ClientsBaseService{

    public static function getTzSystemUserLists($post=[]){
        if(empty($post)){
            return ['status'=>300, 'msg'=>'非法请求'];
        }
        $token = $post['access_token'];
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }
        $m = \Yii::$app->cache;
        $mkey = self::buildTzSystemUserKey();
        $datas = $m->get($mkey);
        if(empty($datas)){
            $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
            $where = ['AND',['<>', 'status', -2], '1=1'];
            if($ADMIN_ACCESS_TOKEN != $token){
                $where = array_merge($where, [['=', 'access_token', $token]]);
            }

            $datas = TzSystemsUsers::find()->where($where)->asArray()->all();
            $m->set($mkey, $datas, 180);
        }

        return ['status'=>200, 'datas'=>$datas, 'msg'=>'操作成功'];
    }

    /**
     * @desc 用户列表key
     * @return string
     */
    public static function buildTzSystemUserKey(){
        $mkey = 'getTzSystemUserLists_key';

        return $mkey;
    }

    /**
     * @desc 删除用户缓存信息
     */
    public static function delTzsystemUserData(){
        $m = \Yii::$app->cache;
        $mkey = self::buildTzSystemUserKey();

        $m->delete($mkey);
    }

}
