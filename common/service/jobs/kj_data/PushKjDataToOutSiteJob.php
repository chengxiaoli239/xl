<?php
namespace common\service\jobs\kj_data;

use backend\models\SscKjData;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use GuzzleHttp\Client;
use yii\helpers\Json;

class PushKjDataToOutSiteJob extends CommonJob {

    public static function getName($params): string
    {
        self::$name = '40-推最新开奖数据给外部';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $lotteryType = $params['lottery_type'];
        try {
            $outSites = \Yii::$app->params['AcceptDataSystem'];
            if(empty($outSites)){
                throw_info('没配置站点，无需推送');
            }

            $select = [
                'qihao',
                'code_str',
                'create_time',
            ];
            $SscKjData = SscKjData::find()->select($select)
                ->where(['lottery_type'=>$lotteryType])
                ->asArray()->limit(1)->one();
            $kjData = [
                'expect' => $SscKjData['qihao'],
                'opencode' => $SscKjData['code_str'],
                'opentime' => date('Y-m-d H:i:s', $SscKjData['created_at']),
            ];
            $pushData = [
                'lottery_type' => $lotteryType,
                'kjData' => $kjData,
                'token' => $outSites['token'],
            ];

            foreach ($outSites['list'] as $outSite){
                try {
                    $url = $outSite['domain'] . $outSite['api'];
                    $client = new Client();
                    $response = $client->post($url, [
                        'body' => Json::encode($pushData),
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                    ]);
                    $body = $response->getBody()->getContents();
                    $result = Json::decode($body) ?: false;
                }catch (\Exception $e1){
                    $errMsg1 = $e1->getMessage();
                }
                Tool_Common::log('/pushData/'.self::class_basename(__CLASS__), 'INFO', '推送kj数据异常0', ['lottery_type'=>$lotteryType, 'url'=>$url, 'data'=>$pushData, 'result'=>$result, 'err_msg1'=>$errMsg1]);
            }
            Tool_Common::log('/pushData/'.self::class_basename(__CLASS__), 'INFO', '推送kj数据异常1', ['lottery_type'=>$lotteryType]);
        }catch (\Exception $e){
            Tool_Common::log('/pushData/'.self::class_basename(__CLASS__), 'INFO', '推送kj数据异常', ['lottery_type'=>$lotteryType, 'err_msg'=>$e->getMessage()]);
            return $e->getMessage();
        }
        return true;
    }

}