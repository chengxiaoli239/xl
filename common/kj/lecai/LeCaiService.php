<?php
# 乐彩资讯 - https://www.tw666666.com/home/history?lotteryId=twk5
namespace common\kj\lecai;

use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class LeCaiService extends BaseKj {
    public static $lottery_type = 18;

    /**
     * @desc 乐彩 - 台湾快五
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryK5($returnType = 'json', $lottery_type=18, $is_auto = 1){
        $lottery_type_routes = [
            18 => '/kj/le-cai/k5',
        ];

        if($is_auto == 2 OR !$kjData = self::getCurrentKjData($lottery_type)){
            $domain = BaseKj::getApiHostByRoute($lottery_type_routes[$lottery_type]);
            $date = date('Y-m-d');
            $url = $domain.'/api/lottery-results/?dataStr='.$date.'&lotteryId=twk5&page=1&pageSize=5';
            $content = CurlService::httpGet($url);

            if($content['success'] != 1) return false;
            $data = $content['data']['rows'][0];

            $kjData = ['expect'=>$data['vol'], 'opentime'=>$data['openAt'], 'opencode'=>$data['result']];
        }

        if(empty($kjData)) return false;

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

    /**
     * @desc 99站点开奖数据抓取
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNo99($returnType = 'json', $is_auto = 1){

        if($is_auto == 2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)){
            $domain = BaseKj::getApiHostByRoute('/kj/xj-ssc/nine-nine');
            $url = $domain.'/kaijiang/list.aspx?lot=jxssc';
            $content = CurlService::httpGet($url);
            $preg = "/<td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism"; // 这里是表达式，大神看看
            preg_match_all($preg,$content,$matches);

            $tdData = $matches[0][0];

            $preg = "/<tr align=\"center\" style=\"color:#330099;background-color:White;\">(.*?)<td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism";
            preg_match_all($preg,$tdData,$matcheDatas);

            $kjData = ['expect'=>$matcheDatas[2][0], 'opentime'=>str_replace('/', '-', $matcheDatas[3][0]), 'opencode'=>$matcheDatas[4][0]];
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
