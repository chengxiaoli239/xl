<?php
namespace common\service\open\telegram;

use backend\models\SscKjData;
use common\helpers\LotteryType;
use common\service\jobs\telegram\SendMessageJobs;
use common\service\lottery\aozhou5\AoZhou5Service;
use common\tools\Tool_Common;

class AoZhouKjService  extends BaseService
{
    public static int $lottery_type = LotteryType::AZ_LUCKY_5;

    public function operateSendKjData($qiHao='')
    {
        $switch = \Yii::$app->params['AZ_MESSAGE_SWITCH']??0;
        if(!$switch) return false;

        list($codeHz, $kjCode, $ds, $ft) = AoZhouKjService::getAoZhouKjData($qiHao);

        $text = "============================\n";
        $text .= LotteryType::TYPE_OPTIONS[self::$lottery_type].'（前'.(AoZhou5Service::KJ_CODE_NUM).'位数番摊）'."\n\n".
            "第 {$qiHao} 期\n".
            str_replace(',', '', $kjCode)."总和{$codeHz}(".$ds.",".$ft.")\n\n".
            "以下是历史课程表\n\n";
        $historyKjData = SscKjData::find()->where(['lottery_type'=>self::$lottery_type])->andWhere(['>', 'qihao', $qiHao-135])
            ->asArray()->all();
        foreach ($historyKjData as $k=>$historyKjDatum){
            $kk = $k + 1;
            $heZhi = (AoZhou5Service::KJ_CODE_NUM==5)? $historyKjDatum['codes_hz']:$historyKjDatum['codes_4nums_hz'];
            $text .= self::getFanTan((int)$heZhi).' ';
            if($kk%15==0){
                $text .= "\n\n";
            }
        }
        $text .= "============================";
        Tool_Common::log('/kj_aozhou5/'.__FUNCTION__, 'INFO', '开奖后群消息', ['lottery_type'=>self::$lottery_type, 'qihao'=>$qiHao, 'text'=>$text]);

        $config = \Yii::$app->params['TELEGRAM'];
        $params = [
            'business_id' => $qiHao,
            'content' => $text,
            'chat_id' => $config['GROUP_ID'],
            'token' => $config['TOKEN'],
        ];
        push_queue(SendMessageJobs::class, $params); # 开奖结果消息发送 - 群

    }

    public static function getFanTan($heZhi=0): int
    {
        return ($heZhi%4)?:4;
    }

    /**
     * 获取开奖结果描述
     * @param $qiHao
     * @return array
     */
    public static function getAoZhouKjData($qiHao): array
    {
        $kjData = SscKjData::find()->where(['lottery_type'=>self::$lottery_type, 'qihao'=>$qiHao])->asArray()->one();

        if(AoZhou5Service::KJ_CODE_NUM==5){
            $codeHz = $kjData['codes_hz'];
            $kjCode = $kjData['code_str'];
        }else{
            $codeHz = $kjData['codes_4nums_hz'];
            $kjCode = $kjData['code_4n_str'];
        }

        $ds = ($codeHz%2==0) ? '双' : '单';
        $ft = ($codeHz%4)?:4;

        return [$codeHz, $kjCode, $ds, $ft];
    }
}
