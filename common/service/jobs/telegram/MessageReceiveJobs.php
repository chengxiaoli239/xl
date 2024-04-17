<?php
namespace common\service\jobs\telegram;

use backend\service\agent\AgentService;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\agent\AgentUsersService;
use common\exceptions\InfoException;
use common\models\wechat\WechatUser;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\open\telegram\MessageOperateService;
use common\service\thirdD\CommonBaseService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\WechatUserService;
use yii\helpers\Json;

class MessageReceiveJobs extends CommonJob
{

    public static function getName($params): string
    {
        self::$name = '102-TG私聊消息处理';
        return self::$name;
    }

    public function exec($params)
    {
        Tool_Common::log('/telegram/'.self::class_basename(__CLASS__), 'INFO', self::$name.'000', ['params'=>$params]);
        return self::handle($params);
    }

    /**
     * 1、参数校验  2、业务参数校验
     * @param array $params
     * @return void
     * @throws InfoException
     */
    public static function preValidate(array $params=[]){

        $dataHI = date('H:i');
        if('21:17'<$dataHI && $dataHI<'23:59'){
            //throw_info('本堂已关', CommonBaseService::CODE_FOR_USER);
        }

        if('00:00'<$dataHI && $dataHI<'08:00'){
            //throw_info('本堂未开', CommonBaseService::CODE_FOR_USER);
        }
    }

    public static function handle($params){
        try {
            $message = $params['message'];
            $from = $message['from'];
            $chat = $message['chat'];
            $userId = $params['user_id']; # 代理用户id，系统用户id
            $fromId = $from['id']; # 发送者用户id
            $chatId = $chat['id']; # 聊天所属用户id
            $content = trim($message['text']);

            if(in_array($content, ['查查', '通过通过', '拒绝拒绝']) OR
                WechatUser::find()->where(['user_id'=>$userId, 'userName'=>$fromId, 'is_admin'=>WechatUser::MEMBER_TYPE_ADMIN])->limit(1)->one()){
                # 管理员，处理上下分、查等业务
                preg_match('/上\s*(\d+)/', $content,$matches);
                $applyId = (int)$matches[1];
                $data = ['id'=>$applyId];
                switch (true){
                    case preg_match('/通过|拒绝/', $content, $matches):
                        $data['status'] = ($matches[0] === '通过') ? AgentUsersBalanceService::FLOW_CHECK_STATUS_PASS : AgentUsersBalanceService::FLOW_CHECK_STATUS_REFUSE;
                        AgentUsersService::userFlowsCheck($data, $userId, '管理员通过消息回复处理');
                        return [CommonBaseService::CODE_FOR_IGNORE, [], ['管理员已通过消息回复处理']];
                    case strpos($content, '查') !== false:
                        list($balance, $todayPl, $todayBet, $weekBet, $weekPl, $lastWeekBet, $lastWeekPl) = AgentService::getCalcMoney($userId);
                        $text = '盘口余额：'.$balance."\n"
                            .'今日盈亏：'.$todayPl."\n"
                            .'有效金额：'.$todayBet."\n"
                            .'本周下单金额：'.$weekBet."\n"
                            .'本周实际盈亏：'.$weekPl."\n"
                            .'上周下单金额：'.$lastWeekBet."\n"
                            .'上周实际盈亏：'.$lastWeekPl;

                        throw_info($text, CommonBaseService::CODE_FOR_USER);
                }

                throw_info('管理员发送：'.$content.'，未匹配到关键词', CommonBaseService::CODE_FOR_USER);
            }

            $mkey = md5(self::class_basename(__CLASS__).'_'.$userId.'_'.$fromId.'_'.$content);
            //p([$mkey, self::class_basename(__CLASS__).'_'.$userId.'_'.$fromUser.'_'.$content]);
            $num = \Yii::$app->redis->incr($mkey);
            if($num>1){
                //throw_info('短时间内重复操作，忽略处理', 50002);
            }
            \Yii::$app->redis->expire($mkey, 2);
            # 1、盘口判断
            self::preValidate($params); # 校验关盘

            /**
             * 确认订单：
             * 1、全部确认（除撤单的），管理员输入：全部代购
             * 2、指定单个订单确认，管理员输入：单号+已代购、已代购+单号
             *
             * 撤单：
             * 用户：单号+撤、撤+单号
             * 管理员：单号+撤、撤+单号
             */

            $messageService = new MessageOperateService($userId, $fromId);
            $fromUser = $messageService->platformUser;
            //p($fromUser);

            $message['fromUserNickName'] = $fromUser['nickName'];
            Tool_Common::log('/telegram/'.self::class_basename(__CLASS__), 'INFO', self::$name.'00', ['fromId'=>$fromId, 'params'=>$params, 'fromUser'=>$fromUser]);

            list($code, $vdata, $msg) = $messageService->receive($message, $params['token']);
            $rstData = [$code, $vdata, $msg];
            Tool_Common::log('/telegram/'.self::class_basename(__CLASS__), 'INFO', self::$name.'01', ['user_id'=>$userId, 'rstData'=>$rstData]);
            if($code == CommonBaseService::CODE_FOR_IGNORE){
                return ['message'=>CommonBaseService::CODE_FOR_OPTIONS[CommonBaseService::CODE_FOR_IGNORE]];
            }
            if($code>0){
                throw_info($msg, $code);
            }
            $replyTxts = $vdata['replyTxts'];
            if(!empty($replyTxts)){
                self::reply($userId, $replyTxts, ['targetId'=>$params['message']['from']['id'], 'token'=>$params['token']]); # 回复消息
            }
        }catch (\Exception $e){
            $err_msg =  ($e->getCode() == CommonBaseService::CODE_FOR_USER) ? $e->getMessage() : '处理异常，请正确输入';
            if($e->getCode()>50000){ # 大于50000
                Tool_Common::log('/telegram/'.self::class_basename(__CLASS__), 'ERR', self::$name.'11', ['user_id'=>$userId, 'params'=>$params, 'err_msg'=>$e->getMessage(), 'code'=>$e->getCode()]);
                return '忽略回复：'.$e->getMessage();
            }
            $r = self::reply($userId, [$err_msg], ['targetId'=>$params['message']['from']['id'], 'token'=>$params['token']]); # 回复消息
            Tool_Common::log('/telegram/'.self::class_basename(__CLASS__), 'ERR', self::$name.'12', ['user_id'=>$userId, 'params'=>$params, 'r'=>$r, 'err_msg'=>$e->getMessage(), 'file'=>$e->getFile().'_'.$e->getLine()]);

            $message['err_msg'] = $err_msg;
        }
        //push_queue_fast(UserDayStaticsJobs::class, ['user_id'=>$userId, 'type'=>$vdata['type'], 'msg'=>'', 'wechat_user_id'=>$wechatUser['id']]);

        Tool_Common::log('/telegram/'.self::class_basename(__CLASS__), 'INFO', self::$name.'13', ['text'=>$content, 'replyTxts'=>$replyTxts]);

        return $message;
    }

    /**
     * 消息回复前处理
     * @param $user_id
     * @param array $replyTxts ['你好', '您好，您的申请已通过']
     * @param array $data ['fromUser'=>'wxid_875i1kgd38x122']; 机器人要回复用到相关的id、token等
     * @return bool
     * @throws InfoException
     */
    public static function reply($user_id, array $replyTxts=[], array $data=[]){
        $targetUser = $data['targetId'];# 目标微信好友
        $mkey = md5(__FUNCTION__.'_x1_'.$user_id.'_'.Json::encode($replyTxts).'_'.$targetUser);
        $incr = \Yii::$app->redis->incr($mkey);
        $switch = \Yii::$app->params['AZ_MESSAGE_SWITCH']??0;
        Tool_Common::log('/telegram/'.__FUNCTION__, 'INFO', '消息回复前处理1', ['user_id'=>$user_id, 'replyTxts'=>$replyTxts, 'data'=>$data,'switch'=>$switch]);
        if(empty($switch)){
            return false;
        }
        if($incr>1){
            return false;
        }
        \Yii::$app->redis->expire($mkey, 2);
        if(empty($targetUser)){
            return '接收的平台好友Id不能为空0';
        }
        if(empty($replyTxts)){
            throw_info('回复消息replyTxts为空');
        }
        foreach ($replyTxts as $replyTxt){
            if(is_string($replyTxt)){
                $content = $replyTxt;
                $order_ids = [];
            }else{
                $order_ids = $replyTxt['order_ids'];
                $content = $replyTxt['replyTxt'];
            }
            if(empty($replyTxt)){
                throw_info('回复消息replyTxt为空');
            }
            $sendData = [
                'user_id' => $user_id,
                'chat_id' => $targetUser, # 谁发就给谁回复，要先判断是否是群聊，判断条件：fromGroup 存在且有值
                //'queue_delay_time' => rand(2, 4), # self::$waitSeconds,
                'content' => $content, # 测试阶段调试信息 - 用户下注完回复
                'business_id' => $user_id,
                'token' => $data['token'],
            ];
            if(!empty($order_ids)){
                $sendData['order_ids'] = $order_ids;
            }
            if(!empty($data['fromGroup'])){
                $sendData['fromGroup'] = $data['fromGroup'];
                $sendData['content'] = '@'.$data['fromUserNickName']."\n". $sendData['content']."\n";
            }
            push_queue(SendMessageJobs::class, $sendData); # TG消息发送
        }

        return true;
    }
}
