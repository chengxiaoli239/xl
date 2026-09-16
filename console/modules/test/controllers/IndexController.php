<?php
namespace console\modules\test\controllers;

use backend\models\TzSystemsUsers;
use yii\console\Controller;

class IndexController extends Controller
{
    public function actionLogin()
    {
        // 模拟 TLS 保存逻辑
        $id = 91; // uid=78
        $sslMode = 2; // TLS 1.2
        
        $model = TzSystemsUsers::findOne($id);
        echo "model found: " . ($model ? "yes (uid={$model->uid})" : "no") . "\n";
        
        if($model){
            echo "before: ssl_mode={$model->ssl_mode}, updated_at={$model->updated_at}\n";
            $model->ssl_mode = $sslMode;
            $model->updated_at = time();
            
            $rst = $model->save(false, ['ssl_mode', 'updated_at']);
            echo "save result: " . ($rst ? 'SUCCESS' : 'FAILED') . "\n";
            
            if(!$rst){
                echo "errors: " . json_encode($model->getErrors()) . "\n";
            }
            
            $model->refresh();
            echo "after: ssl_mode={$model->ssl_mode}, updated_at={$model->updated_at}\n";
        }
    }
}
