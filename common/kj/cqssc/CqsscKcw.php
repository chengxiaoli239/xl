<?php
# 开彩网
namespace common\kj\cqssc;
use backend\models\KjConfig;
use backend\models\TzSystemsUsers;
use backend\service\CurlService;
use common\kj\BaseKj;
use backend\service\HN0898Service;
use common\tools\Tool_Common;
use  yii;

class CqsscKcw extends BaseKj {

    public static $lotteryTypeArr = [
        5 => 1, # 5:1.5分
        6 => 2, # 6:3分
        7 => 3, # 7:5分
        8 => 4, # 8:10分
    ];
    public static $lotteryNameArr = [
        1 => '希腊1.5分', # 5:1.5分
        2 => '希腊3分', # 6:3分
        3 => '希腊5分', # 7:5分
        4 => '希腊10分', # 8:10分
        5 => '重庆时时彩', # 20分
        6 => '新疆时时彩', # 20分
        7 => '北京快乐8', # 5分
    ];

    public static function getLotteryNo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData()) {
            $domain = BaseKj::getApiHost(6);
            sleep(3);
            $url = $domain.'/tef05c6c66079ff29k/cqssc-3.json';
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content;
            //p($data);

            if (!$data OR !isset($data['data']) OR !$kjData = $data['data'][0]) return false;
            if (!$kjData) return false;
            $str = substr($kjData['expect'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $kjData['expect']);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kcw', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;
    }

    /**
     * @desc 希腊ssc
     * @param string $returnType
     * @param int $type
     * @return array|bool
     */
    public static function getLotteryNoXl($lotteryId = 2, $returnType = 'json' ){

        if(!$kjData = self::getCurrentKjData()) {
            //sleep(3);
            //$lotteryId = self::$lotteryTypeArr[$type];
            $domain = BaseKj::getApiHost($lotteryId);
            $url = $domain.'/home/GetNumbers?lotteryId='.$lotteryId.'&pageNmuber=1&number=3&_=1556344774304';
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content;
            //p($content);

            if (!$data OR !isset($data['LsPeridos']) OR !$kjData = $data['LsPeridos'][0]) return false;
            if (!$kjData) return false;
            $str = substr($kjData['expect'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $kjData['expect']);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['ResultNumber'];
        preg_match('/\d+/', $kjData['DrawDt'], $matches);
        $opentime = date('Y-m-d H:i:s', $matches[0]/1000);
        $expect = substr($kjData['PeriodsNumber'], 0,8).'-'.substr($kjData['PeriodsNumber'], 8);

        $lottery_type = $lotteryId;
        self::setKjDataCache($lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        $logArr['lottery'] = CqsscKcw::$lotteryNameArr[$lotteryId];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kcw', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;
    }

    /**
     * @desc 北京快乐8
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryKuaiLe8($returnType = 'json', $lottery = 18){

        if(!$kjData = self::getCurrentKjData()) {
            $domain = BaseKj::getApiHost(10);
            $post_data = [
                'lottery' => $lottery,
                'BeginDt'=> date('Y-m-d'),
                'EndDt'=> date('Y-m-d'),
                'pageSize'=> 20,
                'PageNum'=> 1,
            ];
            $url = $domain.'/api/MemberDesk/GetPeriodsResult?'.http_build_query($post_data);
            $cookie = TzSystemsUsers::findOne(5)->cookie;

            $headers = [
                'Accept: application/json, text/plain, */*',
                'Referer: '.$domain.'/',
                //'Cookie:ValidateToken=7e078323c5b5ef23197621d25ac95d58; Token=4GuaAP94kXCQN83fHLDz3BJZqNZW01Z3vFoR8dJXYbk%3d',
                'Cookie:'.$cookie,
                'Host: '.str_replace('http://', '',$domain),
            ];
            $content = CurlService::httpGet($url, $headers);
            $logArr = ['url'=>$url, 'headers'=>$headers, 'rst'=>$content];
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kl8', 'INFO', '号码抓取-kcw', $logArr);

            $data = $content;
            //p($data);
            //p([$url, $headers,$post_data, $data]);

            if (!$data OR !isset($data['Data']) OR !$kjData = $data['Data']['List'][0]) return false;
            if (!$kjData) return false;
            $str = substr($kjData['expect'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $kjData['periodsNumber']);
            $kjData['opencode'] = $kjData['resultNumber'];
            $kjData['opentime'] = str_replace('/', '-',$kjData['drawDt']);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];
        //p([DEFAULT_LOTTERY_TYPE,$expect, $kjData]);

        self::setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_kl8', 'INFO', '号码抓取-kcw', $logArr);

        return $rst;
    }

}
