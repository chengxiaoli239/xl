<?php
namespace common\service\jobs\robots\message;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\WechatUserService;
use yii\helpers\Json;

class WechatPrivateMsgReceiveJobs extends CommonJob {
    public static function getName($params) {
        self::$name = '11微信私聊消息处理';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function preValiate($params=[]){

        $dataHI = date('H:i');
        if('21:15'<$dataHI && $dataHI<'23:59'){
            #throw_info('本堂已关');
        }

        if('00:00'<$dataHI && $dataHI<'08:00'){
            //throw_info('本堂未开');
        }
    }

    public static function handle($params){
        try {
            $user_id = $params['user_id']; # 代理用户id，系统用户id
            $data = $params['data']; # 消息内容体
            $fromUser = $data['fromUser'];

            $wechatUser = WechatUserService::getWechatUsers($user_id)[$fromUser];
            # 1、好友判断
            if(!$wechatUser['status'] OR empty($wechatUser)){
                throw_info($wechatUser['nickName'].'好友接受消息状态未开启', 50001);
            }

            # 2、盘口判断
            self::preValiate($params); # 校验关盘

            $MessageService = new EYunMessageOperateService($user_id);
            $wcId = $params['wcId']; # 微信原始id
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name.'0', ['wcId'=>$wcId, 'params'=>$params, 'data'=>$data, 'type'=>gettype($data)]);


            $text = $data['content'];
            list($code, $vdata, $msg) = $MessageService->receive($text, $data['fromUser']);
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'ERR', self::$name.'01', ['user_id'=>$user_id, 'wcId'=>$wcId, 'code'=>$code, 'text'=>$text, 'vdata'=>$vdata, 'msg'=>$msg]);
            if($code>0){
                throw_info($msg, $code);
            }
            $replyTxts = $vdata['replyTxts'];

            self::reply($user_id, $wcId, $replyTxts, $data); # 回复消息
        }catch (\Exception $e){
            $err_msg =  $e->getMessage();
            if($e->getCode()>50000){ # 大于50000
                Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'ERR', self::$name.'11', ['user_id'=>$user_id, 'wcId'=>$wcId, 'data'=>$data, 'err_msg'=>$err_msg, 'code'=>$e->getCode()]);
                return '忽略回复：'.$err_msg;
            }
            $r = self::reply($user_id, $wcId, [$err_msg], $data); # 回复消息
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'ERR', self::$name.'12', ['user_id'=>$user_id, 'wcId'=>$wcId, 'data'=>$data, 'r'=>$r]);

            return $err_msg;
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', self::$name.'12', ['wcId'=>$wcId, 'text'=>$text, 'replyTxts'=>$replyTxts]);

        return '消息处理成功:';
    }

    /**
     * @param $user_id
     * @param $wcId
     * @param string $replyTxts
     * @param array $data
     * @return bool
     */
    public static function reply($user_id, $wcId, $replyTxts=[], $data=[]){
        $fromUser = $data['fromUser'];
        if(empty($fromUser)){
            return '接收的微信好友Id不能为空0';
        }
        if(empty($replyTxts)){
            throw_info('回复消息replyTxts为空');
        }
        foreach ($replyTxts as $replyTxt){
            if(empty($replyTxt)){
                throw_info('回复消息replyTxt为空');
            }
            $sendData = [
                'wcId' => $wcId,
                'user_id' => $user_id,
                'fromUser' => $fromUser, # 谁发就给谁回复，要先判断是否是群聊，判断条件：fromGroup 存在且有值
                'queue_delay_time' => rand(3, 8), # self::$waitSeconds,
                'content' => $replyTxt, # 测试阶段调试信息 - 用户下注完回复
                'business_id' => $wcId,
            ];
            if(!empty($data['fromGroup'])){
                $sendData['fromGroup'] = $data['fromGroup'];
                $sendData['content'] = '群消息：'.$sendData['content'];
            }
            push_queue_open(SendWechatMsgJobs::class, $sendData);
        }

        return true;
    }
}
