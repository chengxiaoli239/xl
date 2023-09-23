<?php

namespace common\service\thirdD;

use common\models\thirdD\PlayMethod;
use common\service\BaseService;
use common\service\helpers\ThirdD;

class PlayMethodService extends BaseService
{
    public static function getMethodsMKey($alias='alias'){
        return 'third_d_getMethodsMKey_x0_'.$alias;
    }

    /**
     * 获取玩法
     * @return array
     */
    public static function getMethods($indexByKey=0){
        $m = \Yii::$app->cache;
        $mkey = self::getMethodsMKey();
        $datas = $m->get($mkey);
        if(true OR empty($datas)){
            $datasQuery = PlayMethod::find()
                ->where(['status'=>self::STATUS_ACTIVE])->orderBy(['LENGTH(name)'=>SORT_DESC]);
            if($indexByKey){
                $datasQuery->indexBy(['id']);
            }
            $datas = $datasQuery->asArray()->all();
            $m->set($mkey, $datas, 120);
        }

        return $datas;
    }

    /**
     * 获取别名玩法
     * @return array
     */
    public static function getMethodsAlias(){
        $m = \Yii::$app->cache;
        $mkey = self::getMethodsMKey($alias='alias');
        $datas = $m->get($mkey);
        if(empty($datas) OR true){
            $datas = PlayMethod::find()
                ->select(['id', 'name', 'alias_name', 'money', 'bouns'])
                ->where(['status'=>self::STATUS_ACTIVE])
                ->andWhere(['!=', 'alias_name', ''])
                ->orderBy(['LENGTH(name)'=>SORT_DESC])->asArray()->all();
            #$datas = ArrayHelper::getColumn($datas, 'alias_name');
            $m->set($mkey, $datas, 120);
        }

        return $datas;
    }

    /**
     * @return array
     */
    public static function getAllMethodsAndAliasName($indexByKey=0, &$orignMethods=[]){
        $methods = self::getMethods($indexByKey);
        $orignMethods = $methods;
        $aliaMethods = self::getMethodsAlias();
        foreach ($aliaMethods as $aliaMethod){
            $alias_names = explode(',', $aliaMethod['alias_name']);
            foreach ($alias_names as $alias_name){
                unset($aliaMethod['alias_name']);
                $aliaMethod['name'] = $alias_name;
                $methods[] = $aliaMethod;
            }
        }

        usort($methods, function ($a, $b) {
            return strlen($b['name']) - strlen($a['name']);
        });
        if($indexByKey) {
            $newMethods = [];
            foreach ($methods as $method){
                $newMethods[$method['name']] = $method;
            }
            $methods = $newMethods;
        }

        return $methods;
    }

}
