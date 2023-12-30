<?php
namespace common\service\jobs\robots\message;

use backend\models\thirdD\BetsBackend;
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
            $atIds = [];
            if(!empty($params['fromGroup'])){
                $wcId = $params['fromGroup'];
                $atIds[] = $params['targetUser'];
            }else{
                $wcId = $params['targetUser']; # 要回复的目标好友微信id
            }
            $fromUser = !empty($params['fromGroup']) ? $params['fromGroup'] : $params['replyToUser']; # 发送者
            if(empty($fromUser)){
                throw_info('接收的微信好友Id不能为空');
            }
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['wcId'=>$wcId, 'fromUser'=>$fromUser, 'atIds'=>$atIds, 'params'=>$params]);
            $user_id = $params['user_id']; # 用户id

            $text = $params['content'];
            $MessageService = new EYunMessageOperateService($user_id);
            $rst = $MessageService->send($wcId, $text, $atIds); # 谁发就给谁回
            if($rst['code'] != 1000){
                throw_info($rst['message']??'回复异常', 30001);
            }
            if(!empty($params['order_ids'])){
                BetsBackend::updateAll(['has_reply'=>BetsBackend::HAS_REPLY_YES], ['order_id'=>$params['order_ids']]);
            }

        }catch (\Exception $e){
            if(30000<$e->getCode() && $e->getCode()<40000){
                throw_info($e->getMessage());
            }
            return $e->getMessage();
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['text'=>$text]);

        return '发送微信消息成功';
    }

}
