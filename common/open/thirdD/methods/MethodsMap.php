<?php
namespace common\open\thirdD\methods;

use common\models\thirdD\LocalToSiteMethod;
use common\models\thirdD\PlayMethod;
use yii\base\Component;

class MethodsMap extends Component
{

    /**
     * 网盘玩法映射 local to 盘口
     * @param int $tz_system_type
     * @return bool
     */
    public static function insertMapMethods(int $tz_system_type=15): bool
    {
        $playMethods = PlayMethod::findAll(['status'=>1]);
        $now_time = time();
        foreach ($playMethods as $playMethod){
            $where = [
                'AND',
                ['=', 'system_type_id', $tz_system_type],
                ['=', 'method_id', $playMethod->id],
            ];
            $setData = [];
            if(!$LocalToSiteMethod = LocalToSiteMethod::findOne($where)){
                $LocalToSiteMethod = new LocalToSiteMethod();
                $setData = [
                    'system_type_id' => $tz_system_type,
                    'method_id' => $playMethod->id,
                    'created_at'=>$now_time,
                ];
            }
            $setData = array_merge($setData, [
                'name' => $playMethod->name,
                'money' => $playMethod->money,
                'bouns' => $playMethod->bouns,
                'ratio' => $playMethod->ratio,
                'desc' => $playMethod->desc,
                'updated_at'=>$now_time,
            ]);
            $LocalToSiteMethod->setAttributes($setData);
            if(!$LocalToSiteMethod->save()){
                p($LocalToSiteMethod->getErrors());
            }
        }

        return true;
    }

}
