<?php
# 开彩网
namespace common\kj\ssc;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class Lucky5 extends BaseKj {
    public static $lottery_type = 8;

    /**
     * @desc 幸运五星彩
     * @param string $returnType
     * @return array|bool
     */
    public static function getLottery($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)){
            $datas = self::batchGrabSevenDay();

            $kjData = ['expect'=>$datas[1][0], 'opentime'=>$datas[2][0], 'opencode'=>$datas[3][0]];
        }

        if(!$kjData) return false;
        $opencode = $kjData['opencode'];
        $opentime = str_replace('/', '-', $kjData['opentime']);
        $expect = $kjData['expect'];
        //p([$opencode, $opentime, $expect]);

        //p([$expect, $kjData,$kjData['opencode']]);
        # 设置开奖数据缓存
        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getLotteryNo', 'INFO', '号码抓取-7天', $logArr);

        return $rst;
    }

        public static function getLotteryNoZhiBo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHost(12);
            sleep(3);
            $date = date('Y-m-d');
            if('00:00' < date('H:i:s') && date('H:i:s') < '03:00'){
                $date = date('Y-m-d', time()-86400);
            }
            $url = $domain.'/data/cqssc/lotteryList/'.$date.'.json?t='.time();
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content[0];

            if (!isset($data['issue']) OR !$data) return false;
            $str = substr($data['issue'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $data['issue']);
            $kjData['opencode'] = implode(',', $data['openNum']);
            $kjData['opentime'] = $data['openDateTime'];
            //p($kjData);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc', 'INFO', '号码抓取-直播网', $logArr);

        return $rst;
    }

}
