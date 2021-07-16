<?php
namespace backend\service\clients;

use backend\models\BettingRecords;
use backend\models\TzSystemsUsers;
use backend\service\BetService;
use common\service\CommonService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class BettingRecordsService extends ClientsBaseService{
    public static $module_key = 'backend\models\BettingRecords';

    /**
     * @desc 用户游戏列表
     * @param $uid
     * @return array
     */
    public static function getLists($uid){
        $post = \Yii::$app->request->post();
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey();
        $datas = $m->get($mkey);
        if(true OR empty($datas)){
            $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
            $where = ['AND', '1=1'];
            if($ADMIN_ACCESS_TOKEN != $post['access_token']){
                $where = array_merge($where, [['=', 'uid', $uid]]);
            }

            $datas = (self::$module_key)::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(50)->asArray()->all();
            $m->set($mkey, $datas, 180);
        }

        return ['status'=>200, 'datas'=>$datas, 'msg'=>'操作成功'];
    }

    /**
     * @desc 用户列表key
     * @return string
     */
    public static function buildUserKey($uid=''){
        $mkey = 'buildBettingRecordsKey_key_'.$uid;

        return $mkey;
    }

    /**
     * @desc 删除用户缓存信息
     */
    public static function delBettingRecordsData($uid=''){
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey($uid);

        $m->delete($mkey);
    }



}
