<?php
namespace common\service\jobs\robots\message;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\eyun\EYunMessageOperateService;

class TextReceiveJobs extends CommonJob {
    public static $waitSeconds = 5;  # 消息延迟5s发送

    public static function getName($params) {
        self::$name = '微信私聊消息处理';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    /**
     * 一般为批量处理 -- 测试
     * @param $params
     * @return array|string
     */
    public static function handle($params){
        try {
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name.'01', ['params'=>$params]);
            $user_id = $params['user_id']; # 用户id
            $text = $params['text']; # 下注文本
            $fromUser = $params['fromUser']; # 微信用户id

            $MessageService = new EYunMessageOperateService($user_id);
            $rst = $MessageService->receive($text, $fromUser);

            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name.'02', ['params'=>$params, 'betRst'=>$rst]);
        }catch (\Exception $e){
            Tool_Common::log('/eyun/'.__FUNCTION__, 'ERR', self::$name.'03', ['err_msg'=>$e->getMessage()]);
            return $e->getMessage();
        }
        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name.'99', ['betRst'=>$rst]);

        return $rst;
    }

}
