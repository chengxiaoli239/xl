<?php

namespace common\service\thirdD;

use backend\models\thirdD\BetsBackend;
use common\service\cache\CacheKeyService;
use common\tools\Tool_Common;
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
        $beforeTime = 7200; # 多少分钟内没回复的
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
                list($code, $vdata, $msg) = self::getBets($where);
                $Bets = $vdata['Bets'];

                //p([$Bets, $wechatUserId, $sql], 0);
                $message_ids = [];
                $allMoney = 0.00;
                $allCount = 0;
                $tmpRecordOrderIds = [];
                //p($Bets);
                $user_id = $Bets[0]['user_id'];
                $oneUserReplyTxts = "";
                foreach ($Bets as $k=>$bet){
                    $row = ReplyService::validateMsgHasFinished($bet['wechat_user_id'], $bet['new_msg_id']);
                    if(!empty($row)){
                        continue;
                    }
                    $replyContent = Json::decode($bet['reply_content']);
                    if(empty($tmpRecordOrderIds[$bet['order_id']])){
                        $oneUserReplyTxts .= "\n原文：\n".$bet['bet_desc'].":\n~~~~~~~~~~~~~~~~~~~~~~~~~~\n识别：\n";//.$bet['lottery_name'].$bet['qihao']."\n";;
                    }
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

                //p(['user_id'=>$user_id]);
                # 微信回复用户
                $MessageService = new EYunMessageOperateService($user_id);
                $oneUserReplyTxts = '@'.$replyContent['fromNickName'].' '.date('Y-m-d H:i:s')."\n".$oneUserReplyTxts;
                if(!empty($replyContent['fromGroup'])){
                    # 群里发，文件 + @
                    $wcId = $replyContent['fromGroup'];
                    $atIds[] = $replyContent['fromUser'];
                }else{
                    # 私发，打包回复
                    $wcId = $replyContent['fromUser'];
                }
                $oneUserReplyTxts = "本次打包共 ".count(array_unique($message_ids))." 条：\n".$oneUserReplyTxts;
                //p(['$oneUserReplyTxts'=>$oneUserReplyTxts]);

                $logArr = ['wechatUserId'=>$wechatUserId, 'message_ids'=>$message_ids, $oneUserReplyTxts, 'atIds'=>$atIds];
                list($code, $vData, $msg) = ReplyService::getFileNameInfo($replyContent['fromNickName']);
                list($dir, $fileName, $filePath) = $vData;

                Tool_Common::recordFile($dir, $fileName, $oneUserReplyTxts);
                $result = $MessageService->sendFile($wcId, $filePath, $fileName); # 回复 file

                $logArr = array_merge($logArr, [
                    'filePath' => $filePath,
                    'fileName' => $fileName,
                    'result' => $result,
                ]);

                if($result['code'] != 1000){
                    throw_info($rst['message']??'回复异常', 30001);
                }
                if(!empty($message_ids)){
                    BetsBackend::updateAll(['has_reply'=>BetsBackend::HAS_REPLY_YES], ['new_msg_id'=>$message_ids, 'wechat_user_id'=>$wechatUserId]);
                }

                $transaction->commit();
                Tool_Common::log('/reply/'.__FUNCTION__, 'INFO', '0打包回复结束', $logArr);
                \Yii::$app->commonRedis->srem($mkey, $wechatUserId);
            }catch (\Exception $e){
                $logArr = ['wechatUserId'=>$wechatUserId, 'err_msg'=>$e->getMessage()];
                if($e->getCode()<20000 && $e->getCode()>30000){
                    $logArr['msg'] = $e->getFile().'_'.$e->getLine();
                }
                Tool_Common::log('/reply/'.__FUNCTION__, 'ERR', '0打包回复异常', $logArr);
                $transaction->rollBack();;
            }
        }
        return [0, [], '处理成功'];
    }

    /**
     * 打包回复散客
     * @return array
     */
    public static function packageReplyUser(): array
    {
        $now_time = time();
        $beforeTime = 7200; # 多少分钟内没回复的
        //var_dump('打包回复', 'dddd');
        $wechatUserIds = BetsBackend::find()
            ->select(['wechat_user_id', 'new_msg_id'])
            ->where(['has_reply'=>BetsBackend::HAS_REPLY_YES])
            ->where(['is_need_confirm'=>BetsBackend::NEED_CONFIRM_YES])
            ->andWhere(['>', 'created_at', $now_time-$beforeTime])
            //->orWhere(['=', 'wechat_user_id', 19]) //->createCommand()->getRawSql();
            ->groupBy(['wechat_user_id'])->column();

        foreach ($wechatUserIds as $wechatUserId){
            try {
                $transaction = \Yii::$app->db->beginTransaction();
                $mkey = CacheKeyService::packageReply($wechatUserId);
                $exist = \Yii::$app->commonRedis->sadd($mkey, $wechatUserId);
                if(!$exist){
                    //throw_info('短时间处理，请稍后');
                }
                \Yii::$app->commonRedis->expire($mkey, 100);

                $where = ['AND',
                    ['=', 'wechat_user_id', $wechatUserId],
                    ['=', 'has_reply', BetsBackend::HAS_REPLY_YES],
                    ['=', 'push_status', BetsBackend::PUSH_STATUS_SUCCESS],
                    ['=', 'is_need_confirm', BetsBackend::NEED_CONFIRM_YES], # BetsBackend::REPLY_TYPE_PACKAGE
                    ['>', 'created_at', $now_time-$beforeTime],
                ];
                list($code, $vdata, $msg) = self::getBets($where);
                $Bets = $vdata['Bets'];
                $message_ids = [];
                $allMoney = 0.00;
                $allCount = 0;
                $tmpRecordOrderIds = [];
                //p($Bets);
                $user_id = $Bets[0]['user_id'];
                $oneUserReplyTxts = "";
                foreach ($Bets as $k=>$bet){
                    $row = ReplyService::validateMsgHasFinished($bet['wechat_user_id'], $bet['new_msg_id']);
                    if(!empty($row)){
                        continue;
                    }
                    $replyContent = Json::decode($bet['reply_content']);
                    if(empty($tmpRecordOrderIds[$bet['order_id']])){
                        $oneUserReplyTxts .= "\n原文：\n".$bet['bet_desc'].":\n~~~~~~~~~~~~~~~~~~~~~~~~~~\n识别：\n";//.$bet['lottery_name'].$bet['qihao']."\n";;
                    }
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
                //p($oneUserReplyTxts);

                //p(['user_id'=>$user_id]);
                # 微信回复用户
                $MessageService = new EYunMessageOperateService($user_id);
                $oneUserReplyTxts = '@'.$replyContent['fromNickName'].' '.date('Y-m-d H:i:s')."\n".$oneUserReplyTxts;
                if(!empty($replyContent['fromGroup'])){
                    # 群里发，文件 + @
                    $wcId = $replyContent['fromGroup'];
                    $atIds[] = $replyContent['fromUser'];
                }else{
                    # 私发，打包回复
                    $wcId = $replyContent['fromUser'];
                }
                $oneUserReplyTxts = "本次打包共 ".count(array_unique($message_ids))." 条：\n".$oneUserReplyTxts;
                //p(['$oneUserReplyTxts'=>$oneUserReplyTxts]);

                $logArr = ['wechatUserId'=>$wechatUserId, 'message_ids'=>$message_ids, $oneUserReplyTxts, 'atIds'=>$atIds];
                list($code, $vData, $msg) = ReplyService::getFileNameInfo($replyContent['fromNickName']);
                //p($vData);
                list($dir, $fileName, $filePath) = $vData;

                Tool_Common::recordFile($dir, $fileName, $oneUserReplyTxts);
                $result = $MessageService->sendFile($wcId, $filePath, $fileName); # 回复 file
                $logArr = array_merge($logArr, [
                    'filePath' => $filePath,
                    'fileName' => $fileName,
                    'result' => $result,
                ]);

                if($result['code'] != 1000){
                    throw_info($rst['message']??'回复异常', 30001);
                }
                if(!empty($message_ids)){
                    BetsBackend::updateAll(['has_reply'=>BetsBackend::HAS_REPLY_YES_RE], ['new_msg_id'=>$message_ids, 'wechat_user_id'=>$wechatUserId]);
                }

                $transaction->commit();
                Tool_Common::log('/reply/'.__FUNCTION__, 'INFO', '1打包回复结束', $logArr);
                \Yii::$app->commonRedis->srem($mkey, $wechatUserId);
            }catch (\Exception $e){
                $logArr = ['wechatUserId'=>$wechatUserId, 'err_msg'=>$e->getMessage()];
                if($e->getCode()<20000 OR $e->getCode()>30000){
                    $logArr['msg'] = $e->getFile().'_'.$e->getLine();
                }
                Tool_Common::log('/reply/'.__FUNCTION__, 'ERR', '1打包回复异常', $logArr);
                $transaction->rollBack();;
            }
        }
        return [0, [], '处理成功'];
    }

    public static function getBets($where=[]): array
    {
        $BetsQuery = BetsBackend::find()->where($where)->orderBy(['new_msg_id'=>SORT_DESC]);
        //$sql = $BetsQuery->createCommand()->getRawSql();p($sql);
        $Bets = $BetsQuery->asArray()->all();
        if(empty($Bets)){
            throw_info('记录为空', 20001);
        }

        return [0, ['Bets'=>$Bets], '成功'];
    }

    public static function validateMsgHasFinished($wechat_user_id, $msg_id=''){
        $row = BetsBackend::find()->where(['push_status'=>[BetsBackend::PUSH_STATUS_WAIT, BetsBackend::PUSH_STATUS_FAIL]])
            ->andWhere(['new_msg_id'=>$msg_id, 'wechat_user_id'=>$wechat_user_id])->one();

        return $row?:[];
    }

    /**
     * 打包文件路径信息
     * @param string $fromNickName
     * @return array
     */
    public static function getFileNameInfo(string $fromNickName=''): array
    {
        $date = date('Ymd');
        $dir = \Yii::$aliases['@backend'].'/web/statics/tmp/'.$date; //p($dir);

        $fileName = $fromNickName.'_'.date('mdHis').'.txt';
        $filePath = \Yii::$app->params['domain'].'/statics/tmp/'.$date.'/'.$fileName;

        $data = [$dir, $fileName, $filePath];

        return [0, $data, '成功'];
    }
}
