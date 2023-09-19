<?php
namespace common\service\jobs\robots\message;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\eyun\EYunMessageOperateService;
use yii\helpers\Json;

class WechatPrivateMsgReceiveJobs extends CommonJob {
    public static $waitSeconds = 5;  # 消息延迟5s发送

    public static function getName($params) {
        self::$name = '微信私聊消息处理';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        try {
            $wcId = $params['wcId']; # 微信原始id
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['wcId'=>$wcId, 'params'=>$params]);
            $user_id = $params['user']; # 用户id
            $data = $params['data']; # 消息内容体

            $text = $data['content'];
            $MessageService = new EYunMessageOperateService($user_id);
            $MessageService->receive($text);

            $sendData = [
                'wcId' => $wcId,
                'user_id' => $user_id,
                'fromUser' => $data['fromUser'], # 谁发就给谁回复，要先判断是否是群聊，判断条件：fromGroup 存在且有值
                'queue_delay_time' => self::$waitSeconds,
                'content' => '这是你发给我的消息：'.$text, # 测试阶段调试信息
            ];
            if(!empty($data['fromGroup'])){
                $sendData['fromGroup'] = $data['fromGroup'];
                $sendData['content'] = '群消息：'.$sendData['content'];
            }
            push_queue_open(SendWechatMsgJobs::class, $sendData);
        }catch (\Exception $e){
            return $e->getMessage();
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['wcId'=>$wcId, 'text'=>$text]);

        return '微信登录状态同步成功:';
    }

}
