<?php

namespace common\service\thirdD;

use backend\models\thirdD\BetsBackend;
use common\service\cache\CacheKeyService;
use common\service\chat\Tool_Common;
use common\service\wechat\eyun\EYunMessageOperateService;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class ReplyService extends CommonBaseService
{

    /**
     * 打包回复
     * @return array
     */
    public static function packageReply(): array
    {
        $now_time = time();
        $beforeTime = 30 * 60; # 多少分钟内没回复的
        //var_dump('打包回复', 'dddd');
        $wechatUserIds = BetsBackend::find()
            ->select(['wechat_user_id', 'new_msg_id'])
            ->where(['has_reply'=>BetsBackend::HAS_REPLY_NO])
            ->where(['reply_type'=>BetsBackend::REPLY_TYPE_PACKAGE])
            ->andWhere(['>', 'created_at', $now_time-$beforeTime])
            //->orWhere(['=', 'wechat_user_id', 19]) //->createCommand()->getRawSql();
            ->groupBy(['wechat_user_id'])->column();

        //p($wechatUserIds);
        foreach ($wechatUserIds as $wechatUserId){
            //if($wechatUserId != 371) continue;
            try {
                $transaction = \Yii::$app->db->beginTransaction();
                $mkey = CacheKeyService::package($wechatUserId);
                $exist = \Yii::$app->commonRedis->sadd($mkey, $wechatUserId);
                if(!$exist){
                    //throw_info('短时间处理，请稍后');
                }
                \Yii::$app->commonRedis->expire($mkey, 100);

                $where = ['AND',
                    ['=', 'wechat_user_id', $wechatUserId],
                    ['=', 'has_reply', BetsBackend::HAS_REPLY_NO],
                    ['=', 'push_status', BetsBackend::PUSH_STATUS_SUCCESS],
                    ['=', 'reply_type', BetsBackend::REPLY_TYPE_PACKAGE], # BetsBackend::REPLY_TYPE_PACKAGE
                    ['>', 'created_at', $now_time-$beforeTime],
                ];
                $BetsQuery = BetsBackend::find()->where($where)->orderBy(['new_msg_id'=>SORT_DESC]);
                //$sql = $BetsQuery->createCommand()->getRawSql();p($sql);
                $Bets = $BetsQuery->asArray()->all();
                if(empty($Bets)){
                    throw_info('记录为空', 20001);
                }
                $count = count($Bets);
                $countMsgIds = count(ArrayHelper::index($Bets, 'new_msg_id'));
                //p([$Bets, $wechatUserId, $sql], 0);
                $oneUserReplyTxts = "本次打包共 ".$countMsgIds." 条：\n";
                $message_ids = [];
                $allMoney = 0.00;
                $allCount = 0;
                $qihao = '';
                $tmpRecordOrderIds = [];
                //p($Bets);
                $user_id = $Bets[0]['user_id'];
                foreach ($Bets as $k=>$bet){
                    $row = BetsBackend::find()->where(['push_status'=>[BetsBackend::PUSH_STATUS_WAIT, BetsBackend::PUSH_STATUS_FAIL]])
                        ->andWhere(['new_msg_id'=>$bet['new_msg_id'], 'wechat_user_id'=>$bet['wechat_user_id']])->one();
                    //p(['row'=>$row, 'new_msg_id'=>$bet['new_msg_id']]);
                    if(!empty($row)){
                        continue;
                    }
                    $replyContent = Json::decode($bet['reply_content']);
                    if(empty($tmpRecordOrderIds[$bet['order_id']])){
                        $oneUserReplyTxts .= "\n原文：\n".$bet['bet_desc'].":\n~~~~~~~~~~~~~~~~~~~~~~~~~~\n识别：\n";//.$bet['lottery_name'].$bet['qihao']."\n";;
                    }
                    $qihao = $bet['qihao'];
                    $tmpRecordOrderIds[$bet['order_id']] = true;
                    $oneUserReplyTxts .= $bet['lottery_name'].'单'.$bet['order_id'].' '.$replyContent['replyTxt']."\n";
                    $message_ids[] = $bet['new_msg_id'];

                    $allCount += $bet['count'];
                    $allMoney += $bet['bet_money'];
                    if(isset($Bets[$k+1]) && $bet['order_id'] != $Bets[$k+1]['order_id']){
                        $oneUserReplyTxts .= "\n==============黄金分割线==============\n";
                    }
                }
                $oneUserReplyTxts .= ("\n===========================\n【成功】√  共".$allCount."组，共".$allMoney.'咪');
                $date = date('Ymd');
                $dir = \Yii::$aliases['@backend'].'/web/statics/tmp/'.$date; //p($dir);
                p($oneUserReplyTxts);

                //p(['user_id'=>$user_id]);
                # 微信回复用户
                $MessageService = new EYunMessageOperateService($user_id);
                if(!empty($replyContent['fromGroup'])){
                    # 群里发，文件 + @
                    $oneUserReplyTxts = '@'.$replyContent['fromNickName'].' '.date('Y-m-d H:i:s')."\n".$oneUserReplyTxts;
                    $wcId = $replyContent['fromGroup'];
                    $atIds[] = $replyContent['fromUser'];
                }else{
                    # 私发，打包回复
                    $wcId = $replyContent['fromUser'];
                }
                $oneUserReplyTxts = printf($oneUserReplyTxts, count($message_ids));
                //p($oneUserReplyTxts);

                $logArr = ['wechatUserId'=>$wechatUserId, 'message_ids'=>$message_ids, $oneUserReplyTxts, 'atIds'=>$atIds];
                //p($logArr);
                if(!empty($replyContent['fromGroup'])){
                    # 回复两次
                    $fileName = $replyContent['fromNickName'].'_'.date('ymdHis').'.txt';
                    $replyTxt = "@".$replyContent['fromNickName']."\n打包自动回复本次共".$count."条，期号：".$qihao;
                    //$result = $MessageService->send($wcId, $replyTxt, $atIds); # 谁发就给谁回 text

                    //p([$dir.'/'.$fileName]);
                    Tool_Common::recordFile($dir, $fileName, $oneUserReplyTxts);
                    $filePath = \Yii::$app->params['domain'].'/statics/tmp/'.$date.'/'.$fileName;
                    $result = $MessageService->sendFile($wcId, $filePath, $fileName); # 回复 file
                    $logArr['filePath'] = $filePath;
                    $logArr['fileName'] = $fileName;
                }else{
                    $result = $MessageService->send($wcId, $oneUserReplyTxts, $atIds); # 谁发就给谁回 text
                }

                $logArr['result'] = $result;
                if($result['code'] != 1000){
                    throw_info($rst['message']??'回复异常', 30001);
                }
                if(!empty($message_ids)){
                    BetsBackend::updateAll(['has_reply'=>BetsBackend::HAS_REPLY_YES], ['new_msg_id'=>$message_ids, 'wechat_user_id'=>$wechatUserId]);
                }

                $transaction->commit();
                Tool_Common::log('/reply/'.__FUNCTION__, 'INFO', '打包回复结束', $logArr);
                \Yii::$app->commonRedis->srem($mkey, $wechatUserId);
            }catch (\Exception $e){
                $logArr = ['wechatUserId'=>$wechatUserId, 'err_msg'=>$e->getMessage()];
                if($e->getCode()>20000 && $e->getCode()<30000){
                    $logArr['msg'] = $e->getFile().'_'.$e->getLine();
                }
                Tool_Common::log('/reply/'.__FUNCTION__, 'ERR', '打包回复异常', $logArr);
                $transaction->rollBack();;
            }
        }


        return [0, [], '处理成功'];
    }
}
