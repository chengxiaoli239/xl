<?php

namespace common\service\thirdD;

use backend\models\thirdD\BetsBackend;
use backend\models\wechat\Bets;
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
            ->andWhere(['>', 'created_at', $now_time-$beforeTime])
            ->orWhere(['=', 'wechat_user_id', 19]) //->createCommand()->getRawSql();
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
                $sql = $BetsQuery->createCommand()->getRawSql();//p($sql);
                $Bets = $BetsQuery->asArray()->all();
                //p([$Bets, $wechatUserId, $sql], 0);
                $oneUserReplyTxts = '';
                $replyTxts = [];
                $order_ids = [];
                foreach ($Bets as $bet){
                    $replyContent = Json::decode($bet->reply_content);
                    $oneUserReplyTxts .= $replyContent['txt']."\n";
                    $user_id = $bet->user_id;
                    $order_ids[] = $bet->order_id;
                }

                # 微信回复用户
                $MessageService = new EYunMessageOperateService($user_id);
                if(!empty($replyContent['fromGroup'])){
                    $wcId = $replyContent['fromGroup'];
                    $atIds[] = $replyContent['fromUser'];
                }else{
                    $wcId = $replyContent['fromUser'];
                }
                $rst = $MessageService->send($wcId, $oneUserReplyTxts, $atIds); # 谁发就给谁回
                if($rst['code'] != 1000){
                    throw_info($rst['message']??'回复异常', 30001);
                }
                if(!empty($order_ids)){
                    BetsBackend::updateAll(['has_reply'=>BetsBackend::HAS_REPLY_YES], ['order_id'=>$order_ids]);
                }

                $transaction->commit();
            }catch (\Exception $e){
                $transaction->rollBack();;
            }
        }


        return [0, [], '处理成功'];
    }
}
