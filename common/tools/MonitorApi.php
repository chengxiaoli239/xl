<?php
/**
 * Created by PhpStorm.
 * User: deer_box
 * Date: 2016/11/21
 * Time: 11:41
 */

namespace common\tools;

use yii;
use common\classes\SendMobileMsgMn;

class MonitorApi
{

    /**
     * 短信发送接口KEY
     * @var string
     */
    const SMSPOST = "SMSPost";

    /**
     * 长益POST方式接口KEY
     * @var string
     */
    const CITPOST = "CITPost";

    /**
     * 长益GET方式接口KEY
     * @var string
     */
    const CITGET = "CITGet";

    /**
     * 长益FTP方式接口KEY
     * @var string
     */
    const CITFTP = "CITFtp";

    /**
     * 航班信息GET方式接口KEY
     * @var string
     */
    const FLIGHTGET = "FLIGHTGet";

    /**
     * 查询口岸接口POST方式接口KEY
     * @var string
     */
    const EportPOST = "EportPOST";

    /**
     * 查询口岸接口POST方式接口KEY
     * @var string
     */
    const ITEM_GOODS_GET = "ITEM_GOODS_GET";

    /**
     * 调用新促销接口 key
     * @var string
     */
    const PROMOTION = "PROMOTION";

    /**
     * 接口超时时间，单位：秒
     * @var int
     */
    const EXPIRED = 20;

    public static function getConfig($key = null) {
        $config_desc = [
            self::SMSPOST=>"onlylog", // 短信接口不能用时，不能发短信，所以这个配置项无用,后台在控制中心监控。
            self::CITPOST=>[
                'mobile_phone'=>[17000006022,15008080609],
                'content'=>'长益POST接口异常(推送数据接口、主要是生成订单接口)，请尽快处理。'
            ],
            self::CITGET=>[
                'mobile_phone'=>[17000006022,15008080609],
                'content'=>'长益GET接口异常(各种取数据接口)，请尽快处理。'
            ],
            self::CITFTP=>[
                'mobile_phone'=>[17000006022,15008080609],
                'content'=>'长益FTP接口异常(全量商品同步)，请尽快处理。'
            ],
            self::PROMOTION=>[
                'mobile_phone'=>[17000006022,15008080609],
                'content'=>'购物车调用促销失败，请尽快处理。'
            ],
            self::FLIGHTGET=>[
                'mobile_phone'=>[17000006022,15008080609],
                'content'=>'航班信息查询接口异常(将直接影响普通旅客下单)，请尽快处理'
            ],
            self::EportPOST=>[
                'mobile_phone'=>[17000006022,15008080609],
                'content'=>'口岸接口API信息查询接口异常(将直接影响用户额度查询及下单)，请尽快处理'
            ],
            self::ITEM_GOODS_GET=>[
                'mobile_phone'=>[17000006022,15008080609],
                'content'=>'商品信息接口异常（详情页将不能正常访问），请尽快处理'
            ]
        ];
        if($key !== null) return $config_desc[$key];
        else return $config_desc;
    }

    // 请求接口失败时的操作
    public static function trac($key, $msg, $istest=false) {
        $config = self::getConfig($key);

        if(!$key || !$config) {
            return false;
        }

        // 短信接口出问题时就不发短信了
        if ($key == self::SMSPOST) return true;

        // 每小时只提醒一次
        if ( !yii::$app->params['IS_FORMAL_SITE'] OR yii::$app->cache->get($key) ) {
            return true;
        }

        // 发短信
        foreach($config['mobile_phone'] as $mobile) {
            $sms = new SendMobileMsgMn();
            $sms->send(array($mobile), "({$_SERVER['HTTP_HOST']}-{$_SERVER['SERVER_ADDR']}-".date("Y-m-d H:i:s").")".$config['content']."[由技术部发出,每隔1小时重发]");

        }

        // 缓存失效时间为5分钟
        yii::$app->cache->set($key, $msg, 3*60);
    }

    // 检查短信接口是否异常
    public static function checkSmsApi() {
    }

}