<?php

namespace common\service\thirdD;

use common\models\thirdD\PlayMethod;

class PlayMethodService extends CommonBaseService
{
    public static function getMethodsMKey($alias=''){
        return 'third_d_getMethodsMKey_x1_'.$alias;
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
                $datasQuery->orderBy(['LENGTH(name)'=>SORT_DESC]);
            }
            #$sql = $datasQuery->createCommand()->getRawSql();p($sql);
            $datas = $datasQuery->asArray()->all();
            $m->set($mkey, $datas, 120);
        }

        return $datas;
    }

    /**
     * 获取别名玩法 - 最原始表数据
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
     * @param int $indexByKey 玩法表名称长度排序
     * @param array $aliasNameToOriginName 生成一组,用于替换 ['别名1'=>'玩法名称', '玩法2'=>'玩法名称2']
     * @return array
     */
    public static function getAllMethodsAndAliasName($indexByKey=0, &$aliasNameToOriginName=[])
    {
        $methods = self::getMethods($indexByKey);
        $aliaMethods = self::getMethodsAlias();
        foreach ($aliaMethods as $aliaMethod){
            $alias_names = explode(',', $aliaMethod['alias_name']);
            $aliaMethod['originName'] = $aliaMethod['name'];
            foreach ($alias_names as $alias_name){
                unset($aliaMethod['alias_name']);
                $aliaMethod['name'] = $alias_name;
                $methods[$alias_name] = $aliaMethod;
            }
        }

        usort($methods, function ($a, $b) {
            return strlen($b['name']) - strlen($a['name']);
        });
        if($indexByKey) {
            $newMethods = [];
            foreach ($methods as $method){
                $aliasNameToOriginName[$method['name']] = $method['originName'];
                $method['originName'] = $method['originName']??$method['name'];
                $newMethods[$method['name']] = $method;
            }
            $methods = $newMethods;
        }

        return $methods;
    }

    /**
     * 回复玩法名称简化
     * @param $methodName
     * @return mixed|string
     */
    public static function getReplyMethodName($methodName=''){
        switch (true){
            case strpos($methodName, '跨度') !== false:
                $methodName = '跨度';
                break;
            case strpos($methodName, '和值') !== false:
                $methodName = '和值';
                break;
            case strpos($methodName, '定位') !== false:
                $methodName = '定';
                break;
            default:
                break;
        }

        return $methodName;
    }
}
