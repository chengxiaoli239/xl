<?php
namespace common\service\open\telegram;

use backend\models\SscKjData;
use common\helpers\LotteryType;
use common\service\jobs\telegram\SendMessageJobs;
use common\tools\Tool_Common;

class AoZhouKjService  extends TelegramBaseService
{
    public int $lottery_type = LotteryType::AZ_LUCKY_5;

    public function operateSendKjData($qiHao='')
    {
        $kjData = SscKjData::find()->where(['lottery_type'=>$this->lottery_type, 'qihao'=>$qiHao])->asArray()->one();

        $ds = ($kjData['codes_4nums_hz']%2==0) ? '双' : '单';
        $ft = ($kjData['codes_4nums_hz']%4)?:4;
        $text = LotteryType::TYPE_OPTIONS[$this->lottery_type].'（前四位数番摊）'."\n\n".
            "第 {$qiHao} 期\n".
            str_replace(',', '', $kjData['code_4n_str'])."总和{$kjData['codes_4nums_hz']}(".$ds.",".$ft.")\n\n".
            "以下是历史课程表\n\n".
            "1 2 2 3 4 5";
        Tool_Common::log('/kj_aozhou/'.__FUNCTION__, 'INFO', '开奖后群消息', ['lottery_type'=>$this->lottery_type, 'qihao'=>$qiHao, 'text'=>$text]);

        $config = \Yii::$app->params['TELEGRAM'];
        $params = [
            'business_id' => $qiHao,
            'text' => $text,
            'chat_id' => $config['GROUP_ID'],
            'token' => $config['TOKEN'],
        ];
        push_queue(SendMessageJobs::class, $params); # TG消息发送

    }
}
