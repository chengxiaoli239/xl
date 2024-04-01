<?php
namespace common\service\open\telegram;

use backend\models\SscKjData;
use common\helpers\LotteryType;
use common\service\jobs\telegram\SendMessageJobs;
use common\tools\Tool_Common;

class AoZhouKjService  extends BaseService
{
    public int $lottery_type = LotteryType::AZ_LUCKY_5;

    public function operateSendKjData($qiHao='')
    {
        $kjData = SscKjData::find()->where(['lottery_type'=>$this->lottery_type, 'qihao'=>$qiHao])->asArray()->one();

        $ds = ($kjData['codes_4nums_hz']%2==0) ? '双' : '单';
        $ft = ($kjData['codes_4nums_hz']%4)?:4;
        $text = "============================\n";
        $text .= LotteryType::TYPE_OPTIONS[$this->lottery_type].'（前四位数番摊）'."\n\n".
            "第 {$qiHao} 期\n".
            str_replace(',', '', $kjData['code_4n_str'])."总和{$kjData['codes_4nums_hz']}(".$ds.",".$ft.")\n\n".
            "以下是历史课程表\n\n";
        $historyKjData = SscKjData::find()->where(['lottery_type'=>$this->lottery_type])->andWhere(['>', 'qihao', $qiHao-135])
            ->asArray()->all();
        foreach ($historyKjData as $k=>$historyKjDatum){
            $kk = $k + 1;
            $text .= self::getFanTan((int)$historyKjDatum['codes_4nums_hz']).' ';
            if($kk%15==0){
                $text .= "\n\n";
            }
        }
        $text = "\n\n============================";
        Tool_Common::log('/kj_aozhou5/'.__FUNCTION__, 'INFO', '开奖后群消息', ['lottery_type'=>$this->lottery_type, 'qihao'=>$qiHao, 'text'=>$text]);

        $config = \Yii::$app->params['TELEGRAM'];
        $params = [
            'business_id' => $qiHao,
            'text' => $text,
            'chat_id' => $config['GROUP_ID'],
            'token' => $config['TOKEN'],
        ];
        #push_queue(SendMessageJobs::class, $params); # TG消息发送

    }

    public static function getFanTan($heZhi=0): int
    {
        return ($heZhi%4)?:4;
    }
}
