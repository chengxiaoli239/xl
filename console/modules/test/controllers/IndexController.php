<?php
namespace console\modules\test\controllers;

use backend\models\TzSystemsUsers;
use backend\service\Lucky5\Lucky5Service;
use backend\service\BaseService;
use yii\console\Controller;

class IndexController extends Controller
{
    public function actionLogin()
    {
        $uid = 78;
        $id = TzSystemsUsers::findOne(['uid'=>$uid])->id;

        // 清除缓存
        \Yii::$app->cache->delete('rsa_public_key_7');
        echo "缓存已清除\n";

        // 直接调用Lucky5Service::login (public)
        echo "=== Lucky5Service::login ===\n";
        $rst = Lucky5Service::login($uid, 7);
        echo "结果: " . json_encode($rst, JSON_UNESCAPED_UNICODE) . "\n";

        // 检查desc
        $u = TzSystemsUsers::findOne(['uid'=>$uid]);
        echo "desc: " . $u->desc . ", balance: " . $u->balance . "\n";
    }
}
