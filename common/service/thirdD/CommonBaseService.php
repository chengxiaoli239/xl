<?php

namespace common\service\thirdD;

use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use common\models\thirdD\LocalToSiteMethod;
use common\service\BaseService;
use common\service\lottery\aozhou5\MethodMapService;

class CommonBaseService extends BaseService
{
    const CODE_FOR_USER = 33333; # 回复用户
    const CODE_FOR_IGNORE = 44444; # 忽略处理
    const CODE_FOR_IGNORE_U = 77777; # 聊天忽略
    const CODE_FOR_OPTIONS = [
        self::CODE_FOR_USER => '回复用户',
        self::CODE_FOR_IGNORE => '忽略或无需处理',
        self::CODE_FOR_IGNORE_U => '忽略聊天',
    ];

    # lottery_type:26 福彩3d、27 排列三
    const LOTTERY_TYPE_LUCKY5 = 8;
    const LOTTERY_TYPE_FUCAI = 26;
    const LOTTERY_TYPE_PL3 = 27;
    const LOTTERY_TYPE_AOZHOU5 = 28;
    const THIRDD_LOTTERY_TYPES = [
        self::LOTTERY_TYPE_FUCAI,
        self::LOTTERY_TYPE_PL3,
    ];
    const THIRDD_LOTTERY_OPTIONS = [
        self::LOTTERY_TYPE_FUCAI => '福',
        self::LOTTERY_TYPE_PL3 => '排',
    ];

    const LOTTERY_TYPE_OPTIONS = [
        self::LOTTERY_TYPE_LUCKY5 => '幸运五',
        self::LOTTERY_TYPE_FUCAI => '福',
        self::LOTTERY_TYPE_PL3 => '排',
        self::LOTTERY_TYPE_AOZHOU5 => '澳洲幸运五',
    ];

    # 共用的状态值
    const STATUS_LT_WAIT = 0;
    const STATUS_LT_SUCCESS = 1;
    const STATUS_LT_FAIL = 2;

    const STATUS_LT_CANCEL = 3;
    const STATUS_LT_DRAWN = 4;
    const STATUS_OPTIONS = [
        self::STATUS_LT_WAIT => '待处理',
        self::STATUS_LT_SUCCESS => '已中奖',
        self::STATUS_LT_FAIL => '未中奖',
        self::STATUS_LT_CANCEL => '已撤单',
        self::STATUS_LT_DRAWN => '和局',
    ];

    const VALID_STATUS = [
        self::STATUS_LT_WAIT,
        self::STATUS_LT_SUCCESS,
        self::STATUS_LT_FAIL,
    ];

    /**
     * 网盘相关信息
     * @param int $user_id
     * @param int $lottery_type
     * @return
     */
    public static function getSystemBaseInfo(int $user_id, int $lottery_type=26){
        $system = TzSystemsUsers::find()->alias('tsu')
            ->select(['user_id'=>'tsu.uid', 'tsu.tz_system_id', 'tz.system_type_id', 'tsu.ssc_domain', 'cookie'=>'tsu.cookie'])
            ->leftJoin(TzSystems::tableName().' tz', 'tsu.tz_system_id=tz.id')
            ->where(['AND', ['=', 'tz.lottery_type', $lottery_type], ['=', 'tsu.uid', $user_id]])
            ->asArray()->one();

        return $system;
    }

    public static function getLocalToSiteMethodsMkey($system_type_id=0): string
    {
        return 'getLocalToSiteMethodsMkey_x1_'.$system_type_id;
    }

    public static function getSiteToLocalMethodsMkey($system_type_id=0): string
    {
        return 'getSiteToLocalMethodsMkey_x1_'.$system_type_id;
    }

    /**
     * 获取本地对盘口玩法ID映射关系
     * @param int $method_id
     * @param int $system_type_id     * @return array
     */
    public static function getLocalToSiteMethods(int $method_id=0, int $system_type_id=15, $betCodes=''): array
    {
        //p([$method_id, $system_type_id, $betCodes]);
        if($system_type_id == 16){
            $flippedArray = array_flip(MethodMapService::METHOD_TYPE_OPTIONS);
            if(!isset($flippedArray[$betCodes])){
                throw_info('不存在的玩法:'.$betCodes);
            }
            # 龟盘
            $data = [
                'method_id' => $method_id,
                'site_method_id' => $flippedArray[$betCodes]??0,
                'name' => $betCodes,
            ];
        }else{
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
        }

        return $data;
    }

    /**
     * 接口玩法映射本地玩法id
     * @param $method_id
     * @param $system_type_id
     * @return array|mixed|\yii\db\ActiveRecord|\yii\db\ActiveRecord[]
     */
    public static function getSiteToLocalMethods($method_id=0, $system_type_id=15){
        $m = \Yii::$app->cache;
        $mkey = CommonBaseService::getSiteToLocalMethodsMkey($method_id);
        if(!$data = $m->get($mkey)){
            $data = LocalToSiteMethod::find()
                ->select(['id', 'system_type_id', 'method_id', 'site_method_id', 'name'])
                ->indexBy('method_id')
                ->where(['=', 'system_type_id', $system_type_id])->indexBy('site_method_id')->asArray()->all() ;
            $m->set($mkey, $data, 600);
        }
        if(isset($data[$method_id])){
            return $data[$method_id];
        }

        return $data;
    }
}
