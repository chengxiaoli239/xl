<?php
namespace common\service\jobs\robots\message;

use common\models\eyun\RobotUser;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\RobotUserService;
use yii\helpers\Json;

class SendWechatMsgJobs extends CommonJob {

    public static function getName($params) {
        self::$name = '12发送微信消息处理';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        try {
            #$wcId = $params['wcId']; # 微信原始id
            $fromUser = !empty($params['fromGroup']) ? $params['fromGroup'] : $params['fromUser']; # 发送者
            if(empty($fromUser)){
                throw_info('接收的微信好友Id不能为空');
            }
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['fromUser'=>$fromUser, 'params'=>$params]);
            $user_id = $params['user_id']; # 用户id

            $text = $params['content'];
            $MessageService = new EYunMessageOperateService($user_id);
            $MessageService->send($fromUser, $text); # 谁发就给谁回

        }catch (\Exception $e){
            return $e->getMessage();
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['text'=>$text]);

        return '发送微信消息成功';
    }

}
