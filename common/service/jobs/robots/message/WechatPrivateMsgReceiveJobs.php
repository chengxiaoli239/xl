<?php
namespace common\service\jobs\robots\message;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\thirdD\CommonBaseService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\WechatUserService;
use yii\helpers\Json;

class WechatPrivateMsgReceiveJobs extends CommonJob
{

    public static function getName($params): string
    {
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
        $message = '消息处理成功';
        try {
            $user_id = $params['user_id']; # 代理用户id，系统用户id
            $wcId = $params['wcId']; # 微信原始id

            $data = $params['data']; # 消息内容体
            $fromUser = $data['fromUser'];
            $content = $data['content'];
            $mkey = md5(self::class_basename(__CLASS__).'_'.$user_id.'_'.$fromUser.'_'.$content);
            p([$mkey, self::class_basename(__CLASS__).'_'.$user_id.'_'.$fromUser.'_'.$content]);
            $num = \Yii::$app->redis->incr($mkey);
            if($num>1){
                throw_info('短时间内重复操作，忽略处理', 50002);
            }
            \Yii::$app->redis->expire($mkey, 2);

            $wechatUser = WechatUserService::getWechatUsers($user_id)[$fromUser];
            # 1、好友判断
            if(!$wechatUser['status'] OR empty($wechatUser)){
                throw_info($wechatUser['nickName'].'好友接受消息状态未开启', 50001);
            }
            $data['fromUserNickName'] = $wechatUser['nickName'];

            # 2、盘口判断
            self::preValiate($params); # 校验关盘

            $MessageService = new EYunMessageOperateService($user_id);
            Tool_Common::log('/bet_3d/'.self::class_basename(__CLASS__), 'INFO', self::$name.'0', ['wcId'=>$wcId, 'params'=>$params, 'data'=>$data, 'type'=>gettype($data)]);


            $text = $data['content'];
            list($code, $vdata, $msg) = $MessageService->receive($text, $fromUser, $data);
            Tool_Common::log('/bet_3d/'.self::class_basename(__CLASS__), 'ERR', self::$name.'01', ['user_id'=>$user_id, 'wcId'=>$wcId, 'code'=>$code, 'text'=>$text, 'vdata'=>$vdata, 'msg'=>$msg]);
            if($code>0){
                throw_info($msg, $code);
            }
            $replyTxts = $vdata['replyTxts'];
            if(!empty($replyTxts)){
                self::reply($user_id, $replyTxts, $data); # 回复消息
            }
        }catch (\Exception $e){
            $err_msg =  ($e->getCode() == CommonBaseService::CODE_FOR_USER) ? $e->getMessage() : '处理异常，请正确输入';
            if($e->getCode()>50000){ # 大于50000
                Tool_Common::log('/bet_3d/'.self::class_basename(__CLASS__), 'ERR', self::$name.'11', ['user_id'=>$user_id, 'wcId'=>$wcId, 'data'=>$data, 'err_msg'=>$e->getMessage(), 'code'=>$e->getCode()]);
                return '忽略回复：'.$e->getMessage();
            }
            $r = self::reply($user_id, [$err_msg], $data); # 回复消息
            Tool_Common::log('/bet_3d/'.self::class_basename(__CLASS__), 'ERR', self::$name.'12', ['user_id'=>$user_id, 'wcId'=>$wcId, 'data'=>$data, 'r'=>$r, 'err_msg'=>$e->getMessage(), 'file'=>$e->getFile().'_'.$e->getLine()]);

            $message = $err_msg;
        }
        //push_queue_fast(UserDayStaticsJobs::class, ['user_id'=>$user_id, 'type'=>$vdata['type'], 'msg'=>'', 'wechat_user_id'=>$wechatUser['id']]);

        Tool_Common::log('/bet_3d/'.self::class_basename(__CLASS__), 'INFO', self::$name.'13', ['wcId'=>$wcId, 'text'=>$text, 'replyTxts'=>$replyTxts]);

        return $message;
    }

    /**
     * 消息回复前处理
     * @param $user_id
     * @param $wcId
     * @param string $replyTxts ['你好', '您好，您的申请已通过']
     * @param array $data ['fromUser'=>'wxid_875i1kgd38x122'];
     * @return bool
     */
    public static function reply($user_id, $replyTxts=[], array $data=[]){
        $mkey = md5(__FUNCTION__.'_x1_'.$user_id.'_'.Json::encode($replyTxts).'_'.$data['fromUser']);
        $incr = \Yii::$app->redis->incr($mkey);
        Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '消息回复前处理', ['user_id'=>$user_id, 'replyTxts'=>$replyTxts, 'data'=>$data]);
        if($incr>1){
            return false;
        }
        \Yii::$app->redis->expire($mkey, 2);
        $fromUser = $data['fromUser'];
        if(empty($fromUser)){
            return '接收的微信好友Id不能为空0';
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
                'fromUser' => $fromUser, # 谁发就给谁回复，要先判断是否是群聊，判断条件：fromGroup 存在且有值
                //'queue_delay_time' => rand(2, 4), # self::$waitSeconds,
                'content' => $content, # 测试阶段调试信息 - 用户下注完回复
                'business_id' => $user_id,
            ];
            if(!empty($order_ids)){
                $sendData['order_ids'] = $order_ids;
            }
            if(!empty($data['fromGroup'])){
                $sendData['fromGroup'] = $data['fromGroup'];
                $sendData['content'] = '@'.$data['fromUserNickName']."\n". $sendData['content']."\n";
            }
            push_queue_open(SendWechatMsgJobs::class, $sendData);
        }

        return true;
    }
}
