<?php

namespace common\service\thirdD;

use backend\models\thirdD\BetsBackend;
use backend\models\wechat\Bets;
use common\service\chat\Tool_Common;
use common\service\wechat\eyun\EYunMessageOperateService;
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
        $beforeTime = 180 * 60; # 多少分钟内没回复的
        var_dump('打包回复', 'dddd');
        $wechatUserIds = BetsBackend::find()
            ->select(['wechat_user_id'])
            ->where(['has_reply'=>BetsBackend::HAS_REPLY_NO])
            ->where(['reply_type'=>BetsBackend::REPLY_TYPE_PACKAGE])
            ->andWhere(['>', 'created_at', $now_time-$beforeTime])
            //->orWhere(['=', 'wechat_user_id', 19]) //->createCommand()->getRawSql();
            ->groupBy(['wechat_user_id'])->column();
        //p($wechatUserIds);

        print_r($wechatUserIds);
        foreach ($wechatUserIds as $wechatUserId){
            try {
                $transaction = \Yii::$app->db->beginTransaction();
                $where = ['AND',
                    ['=', 'wechat_user_id', $wechatUserId],
                    ['=', 'has_reply', BetsBackend::HAS_REPLY_NO],
                    ['=', 'push_status', BetsBackend::PUSH_STATUS_SUCCESS],
                    ['=', 'reply_type', BetsBackend::REPLY_TYPE_PACKAGE], # BetsBackend::REPLY_TYPE_PACKAGE
                    ['>', 'created_at', $now_time-$beforeTime],
                ];
                $BetsQuery = BetsBackend::find()->where($where);
                //$sql = $BetsQuery->createCommand()->getRawSql();p($sql);
                $Bets = $BetsQuery->asArray()->all();
                //p([$Bets, $wechatUserId, $sql], 0);
                $oneUserReplyTxts = "打包回复：\n".$Bets[0]['lottery_name'].$Bets[0]['qihao']."\n";
                $order_ids = [];
                $allMoney = 0.00;
                $allCount = 0;
                foreach ($Bets as $bet){
                    $replyContent = Json::decode($bet['reply_content']);
                    $oneUserReplyTxts .= '单'.$bet['order_id'].' '.$replyContent['replyTxt']."\n";
                    $user_id = $bet['user_id'];
                    $order_ids[] = $bet['order_id'];

                    $allCount += $bet['count'];
                    $allMoney += $bet['bet_money'];
                }
                $oneUserReplyTxts .= ("\n【成功】√  共".$allCount."组，共".$allMoney.'咪');

                # 微信回复用户
                $MessageService = new EYunMessageOperateService($user_id);
                if(!empty($replyContent['fromGroup'])){
                    $oneUserReplyTxts = '@'.$replyContent['fromNickName']."\n".$oneUserReplyTxts;
                    $wcId = $replyContent['fromGroup'];
                    $atIds[] = $replyContent['fromUser'];
                }else{
                    $wcId = $replyContent['fromUser'];
                }

                $logArr = ['wechatUserId'=>$wechatUserId, 'order_id'=>$order_ids, $oneUserReplyTxts, 'atIds'=>$atIds];
                #p($logArr);
                $result = $MessageService->send($wcId, $oneUserReplyTxts, $atIds); # 谁发就给谁回
                $logArr['result'] = $result;
                if($result['code'] != 1000){
                    throw_info($rst['message']??'回复异常', 30001);
                }
                if(!empty($order_ids)){
                    BetsBackend::updateAll(['has_reply'=>BetsBackend::HAS_REPLY_YES], ['order_id'=>$order_ids]);
                }

                $transaction->commit();
                Tool_Common::log('/reply/'.__FUNCTION__, 'ERR', '打包回复异常', $logArr);
            }catch (\Exception $e){
                Tool_Common::log('/reply/'.__FUNCTION__, 'ERR', '打包回复异常', ['wechatUserId'=>$wechatUserId, 'err_msg'=>$e->getMessage()]);
                $transaction->rollBack();;
            }
        }


        return [0, [], '处理成功'];
    }
}
