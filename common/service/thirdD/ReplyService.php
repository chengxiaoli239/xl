<?php

namespace common\service\thirdD;

use backend\models\thirdD\BetsBackend;
use backend\models\wechat\Bets;
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
        $beforeTime = 15 * 60; # 多少分钟内没回复的
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
            $where = ['AND',
                ['=', 'wechat_user_id', $wechatUserId],
                ['=', 'has_reply', BetsBackend::HAS_REPLY_NO],
                ['=', 'reply_type', BetsBackend::REPLY_TYPE_QUICK], # BetsBackend::REPLY_TYPE_PACKAGE
                ['>', 'created_at', $now_time-$beforeTime],
            ];
            $Bets = BetsBackend::find()->where($where)->asArray()->all();
            p($Bets);
        }


        return [0, [], '处理成功'];
    }
}
