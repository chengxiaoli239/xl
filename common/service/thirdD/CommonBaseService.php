<?php

namespace common\service\thirdD;

use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use common\models\thirdD\LocalToSiteMethod;
use common\service\BaseService;

class CommonBaseService extends BaseService
{
    const CODE_FOR_USER = 33333;

    # lottery_type:26 福彩3d、27 排列三
    const LOTTERY_TYPE_FUCAI = 26;
    const LOTTERY_TYPE_PL3 = 27;
    const THIRDD_LOTTERY_TYPES = [
        self::LOTTERY_TYPE_FUCAI,
        self::LOTTERY_TYPE_PL3,
    ];
    const THIRDD_LOTTERY_OPTIONS = [
        self::LOTTERY_TYPE_FUCAI => '福',
        self::LOTTERY_TYPE_PL3 => '排',
    ];

    # 共用的状态值
    const STATUS_LT_WAIT = 0;
    const STATUS_LT_SUCCESS = 1;
    const STATUS_LT_FAIL = 2;

    const STATUS_LT_CANCEL = 3;
    const STATUS_OPTIONS = [
        self::STATUS_LT_WAIT => '待处理',
        self::STATUS_LT_SUCCESS => '已中奖',
        self::STATUS_LT_FAIL => '未中奖',
        self::STATUS_LT_CANCEL => '已撤单',
    ];

    const VALID_STATUS = [
        self::STATUS_LT_WAIT,
        self::STATUS_LT_SUCCESS,
        self::STATUS_LT_FAIL,
    ];

    /**
     * 网盘相关信息
     * @param int $user_id
     * @param $lottery_type
     * @return array|TzSystemsUsers|null
     */
    public static function getSystemBaseInfo(int $user_id, $lottery_type=26){
        $system = TzSystemsUsers::find()->alias('tsu')
            ->select(['user_id'=>'tsu.uid', 'tz.system_type_id', 'tsu.ssc_domain', 'cookie'=>'tsu.cookie'])
            ->leftJoin(TzSystems::tableName().' tz', 'tsu.tz_system_id=tz.id')
            ->where(['AND', ['=', 'tz.lottery_type', $lottery_type], ['=', 'tsu.uid', $user_id]])
            ->asArray()->one();

        return $system;
    }

    public static function getLocalToSiteMethodsMkey($system_type_id=0): string
    {
        return 'getLocalToSiteMethodsMkey_x0_'.$system_type_id;
    }

    /**
     * 获取本地对盘口玩法ID映射关系
     * @param int $system_type_id
     * @param int $method_id
     * @return array
     */
    public static function getLocalToSiteMethods(int $system_type_id=0, int $method_id=0): array
    {
        $m = \Yii::$app->cache;
        $mkey = CommonBaseService::getLocalToSiteMethodsMkey($system_type_id);
        if(!$data = $m->get($mkey)){
            $data = LocalToSiteMethod::find()
                ->select(['id', 'system_type_id', 'method_id', 'site_method_id', 'name'])
                ->indexBy('method_id')
                ->where(['=', 'system_type_id', $system_type_id])->asArray()->all() ;
            $m->set($mkey, $data, 600);
        }
        if(isset($data[$method_id])){
            return $data[$method_id];
        }

        return $data;
    }
}
