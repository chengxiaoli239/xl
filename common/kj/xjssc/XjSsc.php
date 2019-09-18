<?php
# 开彩网
namespace common\kj\xjssc;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class XjSsc extends BaseKj {
    public static $lottery_type = 7;

    /**
     * @desc 7天-新疆
     * @param string $returnType
     * @return bool
     */
    public static function getLotteryNoSevenDay($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)){
            $domain = BaseKj::getApiHost(13);
            $url = $domain.'/kaijiang/list.aspx?lot=jxssc';
            //$content = file_get_contents($url);
            $h = str_replace('https://', '', $domain);

            $headers = [
               'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
               'Accept-Encoding: gunzip, deflate, br',
               'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
               'Connection: keep-alive',
               'Cookie: Hm_lvt_afe1c3da922eb68bb36abb2f9a4ad0ce=1568795085; Hm_lpvt_afe1c3da922eb68bb36abb2f9a4ad0ce=1568795097',
               'Host: '.$h,
               'Upgrade-Insecure-Requests: 1',
               'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
            ];
            $content = CurlService::getCurl($url, $headers);
            $preg = "/<td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism"; // 这里是表达式，大神看看
            preg_match_all($preg,$content,$matches);

            $datas = $matches;
            $kjData = ['expect'=>$datas[1][0], 'opentime'=>$datas[2][0], 'opencode'=>$datas[3][0]];
        }

        if(!$kjData) return false;
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];
        //p([$opencode, $opentime, $expect]);

        if($kjData['opencode']){
            # 设置开奖数据缓存
            self::setKjDataCache(self::$lottery_type, $expect, $kjData);
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
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getLotteryNoSevenDay', 'INFO', '号码抓取-7天', $logArr);

        return $rst;
    }

    /**
     * @desc 99站点开奖数据抓取
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNo99($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)){
            $domain = BaseKj::getApiHost(9);
            $url = $domain.'/kaijiang/list.aspx?lot=jxssc';
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
        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

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
