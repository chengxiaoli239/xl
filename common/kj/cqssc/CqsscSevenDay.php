<?php
# 开彩网
namespace common\kj\cqssc;
use backend\service\CurlService;
use backend\service\HN0898Service;
use backend\service\SscDataService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class CqsscSevenDay extends BaseKj {

    public static function getLotteryNo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData()){
            $domain = BaseKj::getApiHost(7);
            $url = $domain.'/kaijiang/list.aspx?lot=ssc';
            $content = file_get_contents($url);
            //$content = CurlService::httpGet($url);
            $preg = "/<td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism"; // 这里是表达式，大神看看
            preg_match_all($preg,$content,$matches);
            //p($content);

            $tdData = $matches[0][0];

            $preg = "/<td><font color=\"\#330099\">(.*?)<\/font><\/td><td><font color=\"\#330099\">(.*?)<\/font><\/td><td><font color=\"\#330099\">(.*?)<\/font><\/td>/ism";
            preg_match_all($preg,$tdData,$matcheDatas);

            $kjData = ['expect'=>$matcheDatas[1][0], 'opentime'=>str_replace('/', '-', $matcheDatas[2][0]), 'opencode'=>$matcheDatas[3][0]];
            $str = substr($kjData['expect'],0,6);
            $kjData['expect'] = '20'.str_replace($str, $str.'-',$kjData['expect']);
        }

        if(!$kjData) return false;
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        if($kjData['opencode']){
            # 设置开奖数据缓存
            self::setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, $expect, $kjData);
        }

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc_seven', 'INFO', '号码抓取-7天', $logArr);
    }

    /**
     * @desc 99站点开奖数据抓取
     * @param string $returnType
     * @return array|bool
     */
    public static function getLottery99($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData()){
            $domain = BaseKj::getApiHost(8);
            $url = $domain.'/kaijiang/list.aspx?lot=ssc';
            $content = CurlService::httpGet($url);
            $preg = "/<td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism"; // 这里是表达式，大神看看
            preg_match_all($preg,$content,$matches);

            $tdData = $matches[0][0];

            $preg = "/<tr align=\"center\" style=\"color:#330099;background-color:White;\">(.*?)<td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism";
            preg_match_all($preg,$tdData,$matcheDatas);

            $kjData = ['expect'=>$matcheDatas[2][0], 'opentime'=>str_replace('/', '-', $matcheDatas[3][0]), 'opencode'=>$matcheDatas[4][0]];
            $str = substr($kjData['expect'],0,6);
            $kjData['expect'] = '20'.str_replace($str, $str.'-',$kjData['expect']);
        }

        if(!$kjData) return false;
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        # 设置开奖数据缓存
        self::setKjDataCache($lottery_type = DEFAULT_LOTTERY_TYPE, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            return ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
    }
}
