<?php

namespace common\service\thirdD;

use backend\models\wechat\Odds;
use common\models\thirdD\PlayMethod;
use common\service\BaseService;
use yii\helpers\Json;

class Odds3dService extends BaseService
{

    /**
     * 会员赔率表
     * @param $user_id
     * @return array
     */
    public static function addUserOdds($user_id){
        $Odds = PlayMethod::find()->asArray()->all();
        foreach ($Odds as $odd){
            $where = ['user_id'=>$user_id, 'play_method_id'=>$odd['id']];
            $Odds = Odds::findOne($where);
            $setData = [];
            if(empty($Odds)){
                $Odds = new Odds();
                $setData = [
                    'user_id' => $user_id,
                    'play_method_id' => $odd['id'],
                    'name' => $odd['name'],
                    'status' => PlayMethodService::STATUS_ACTIVE,
                    'money' => str_replace('元', '', $odd['money']),
                    'bouns' => str_replace('元', '', $odd['bouns']),
                    'odds' => round((int)str_replace('元', '', $odd['bouns'])/(int)str_replace('元', '', $odd['money']), 2),
                    'created_at' => time(),
                ];
            }
            $setData = array_merge($setData, [
                'updated_at' => time()
            ]);
            $Odds->setAttributes($setData, false);
            #p($Odds->getAttributes());
            if(!$Odds->save()){
                return ['status'=>300, 'msg'=>Json::encode($Odds->getErrors())];
            }
        }

        return ['status'=>200, 'msg'=>'操作成功'];
    }
}
