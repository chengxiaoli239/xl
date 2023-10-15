<?php
namespace common\service\jobs\robots\message;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\WechatUserService;
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

    public static function preValiate($params=[]){

        $dataHI = date('H:i');
        if('21:15'<$dataHI && $dataHI<'23:59'){
            throw_info('本堂已关');
        }

        if('00:00'<$dataHI && $dataHI<'08:00'){
            throw_info('本堂未开');
        }
    }

    public static function handle($params){
        try {
            $user_id = $params['user_id']; # 代理用户id，系统用户id
            $data = $params['data']; # 消息内容体
            $fromUser = $data['fromUser'];

            self::preValiate($params); # 校验关盘

            $MessageService = new EYunMessageOperateService($user_id);
            $wcId = $params['wcId']; # 微信原始id
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['wcId'=>$wcId, 'params'=>$params]);
            $wechatUser = WechatUserService::getWechatUsers($user_id)[$fromUser];
            if(!$wechatUser['status'] OR empty($wechatUser)){
                throw_info($wechatUser['nickName'].'好友接受消息状态未开启', 50001);
            }

            $text = $data['content'];
            list($code, $data, $msg) = $MessageService->receive($user_id, $text, $data['fromUser']);
            if($code>0){
                throw_info($msg, $code);
            }
            $replyTxt = $text;

            self::reply($user_id, $wcId, $replyTxt, $data); # 回复消息
        }catch (\Exception $e){
            $err_msg =  $e->getMessage();
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['user_id'=>$user_id, 'wcId'=>$wcId, 'data'=>$data, 'err_msg'=>$err_msg, 'code'=>$e->getCode()]);
            if($e->getCode()>50000){ # 大于50000
                return '忽略回复：'.$err_msg;
            }
            self::reply($user_id, $wcId, $err_msg, $data); # 回复消息

            return $err_msg;
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['wcId'=>$wcId, 'text'=>$text]);

        return '消息处理成功:';
    }

    /**
     * @param $user_id
     * @param $wcId
     * @param string $replyTxt
     * @param array $data
     * @return bool
     */
    public static function reply($user_id, $wcId, $replyTxt='', $data=[]){
        $fromUser = $data['fromUser'];
        $sendData = [
            'wcId' => $wcId,
            'user_id' => $user_id,
            'fromUser' => $fromUser, # 谁发就给谁回复，要先判断是否是群聊，判断条件：fromGroup 存在且有值
            'queue_delay_time' => rand(3, 8), # self::$waitSeconds,
            'content' => '消息：'.$replyTxt, # 测试阶段调试信息 - 用户下注完回复
            'business_id' => $wcId,
        ];
        if(!empty($data['fromGroup'])){
            $sendData['fromGroup'] = $data['fromGroup'];
            $sendData['content'] = '群消息：'.$sendData['content'];
        }
        push_queue_open(SendWechatMsgJobs::class, $sendData);

        return true;
    }
}
