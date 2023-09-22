<?php

namespace common\service\thirdD;

use common\models\thirdD\PlayMethod;
use common\service\BaseService;
use common\service\helpers\ThirdD;

class PlayMethodService extends BaseService
{
    public static function getMethodsMKey(){
        return 'third_d_getMethodsMKey_x0';
    }

    /**
     * 获取玩法
     * @return array
     */
    public static function getMethods(){
        $m = \Yii::$app->cache;
        $mkey = self::getMethodsMKey();
        $datas = $m->get($mkey);
        if(empty($datas)){
            $datas = PlayMethod::find()->where(['status'=>self::STATUS_ACTIVE])->orderBy(['LENGTH(name)'=>SORT_DESC])->asArray()->all();
            $m->set($mkey, $datas, 120);
        }

        return $datas;
    }

}
