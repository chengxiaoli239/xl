<?php
# 开彩网
namespace common\kj\xjssc;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class XjSsc extends BaseKj {
    public static $lottery_type = 6;

    /**
     * @desc 7天-新疆
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNoSevenDay($returnType = 'json'){

        if(true OR !$kjData = self::getCurrentKjData(self::$lottery_type)){
            $datas = self::batchGrabSevenDay();

            $kjData = ['expect'=>$datas[1][0], 'opentime'=>$datas[2][0], 'opencode'=>$datas[3][0]];
            foreach ($kjData as $key=>$d){
                $d = str_replace('<font color="#330099">', '', $d);
                $d = str_replace('/', '-', $d);
                $d = str_replace("</font>", '', $d);
                $kjData[$key] = str_replace("<-font>", '', $d);
            }
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

    /**
     * @desc 七天 - 新疆 批量数据出口
     * @return mixed
     */
    public static function batchSevenDay($returnType = 'json'){
        $datas = self::batchGrabSevenDay();
        $qihaos = $datas[1];
        $times = $datas[2];
        $codes = $datas[3];
        $kjDatas = [];
        //$qihaos = array_reverse($qihaos);
        foreach ($qihaos as $key=>$qihao){
            $kjDatas[] = ['expect'=>$qihao, 'opentime'=>$times[$key], 'opencode'=>$codes[$key]];
        }

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            $str = '<?xml version="1.0" encoding="utf-8"?>';
            foreach ($kjDatas as $kjData){
                $str .= '<xml><row expect="'.$kjData['expect'].'" opencode="'.$kjData['opencode'].'" opentime="'.$kjData['opentime'].'" /></xml>';
            }
            ob_end_flush();exit;
        }

        return $kjDatas;
    }

    /**
     * @desc 七天 - 新疆 批量数据
     * @return mixed
     */
    public static function batchGrabSevenDay(){
        $domain = BaseKj::getApiHost(13);
        $url = $domain.'/kaijiang/list.aspx?lot=jxssc';
        $mkey = 'batchGrabSevenDay_lottery_type_6';

        $m = \Yii::$app->cache;
        $content = file_get_contents($url);
        if($datas = $m->get($mkey)) return $datas;
        if(!$content){
            $h = str_replace('https://', '', $domain);
            $h = str_replace('http://', '', $h);

            $headers = [
               'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
               'Accept-Encoding: gunzip, deflate, br',
               'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
               'Connection: keep-alive',
               //'Cookie: Hm_lvt_afe1c3da922eb68bb36abb2f9a4ad0ce=1568795085; Hm_lpvt_afe1c3da922eb68bb36abb2f9a4ad0ce=1568795097',
               'Host: '.$h,
               'Upgrade-Insecure-Requests: 1',
               'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
            ];
            $content = CurlService::getCurl($url, $headers);
        }
        $preg = "/<td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism"; // 这里是表达式，大神看看
        preg_match_all($preg,$content,$matches);

        $datas = $matches;

        $m->set($mkey, $datas,  15);
        return $datas;
    }

    /**
     * @desc 直播网 - 新疆
     * @param string $returnType
     * @return array
     */
    public static function getLotteryNoZhiBo($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHost(12);
            sleep(3);
            $date = date('Y-m-d');
            //$url = $domain.'/data/xjssc/lotteryList/'.$date.'.json?t='.time();
            $url = $domain.'/data/Current/xjssc/CurIssue.json?'.time();
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content;

            if (!$data) return false;
            $kjData['expect'] = $data['preIssue']; # 2019091905
            $kjData['opencode'] = implode(',', $data['openNum']); # 0,4,1,9,1
            $kjData['opentime'] = date('Y-m-d H:i:s', $data['openDateTime']/1000); # 2019-9-19 11:41:05
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
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/xj_ssc', 'INFO', '号码抓取-直播网', $logArr);

        return $rst;
    }

    /**
     * @desc 9号娱乐网 - 重庆
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNoNineNum($returnType = 'json'){

        if(true OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/xj-ssc/nine-num');
            $date = date('Y-m-d');
            if('00:00' < date('H:i:s') && date('H:i:s') < '03:00'){
                $date = date('Y-m-d', time()-86400);
            }
            $url = $domain.'/api/v1/result/service/mobile/results/hist/HF_XJSSC?limit=4&brand=09cp'; # limit 数量
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content[0];

            if (!isset($data['uniqueIssueNumber']) OR !$data) return false;
            $str = substr($data['uniqueIssueNumber'], 0, 8);
            $qh = substr(str_replace($str, '', $data['uniqueIssueNumber']), -2);
            $kjData['expect'] = $str. $qh; # 20200427-59
            $kjData['opencode'] = $data['openCode']; # 1,4,3,5,1
            $kjData['opentime'] = date('Y-m-d H:i:s', strtotime($data['openTime']));
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

    /**
     * @desc 直播网 - 新疆 - 批量
     * @param string $returnType
     * @return array
     */
    public static function getLotteryNoBatch($returnType = 'json'){
        $m = \Yii::$app->cache;

        $mkey = 'XJSSC_getLotteryNoBatch_5';
        $domain = BaseKj::getApiHost(12);

        if(!$date = $m->get($mkey)){
            $date = '2019-09-19';
        }
        $url = $domain.'/data/xjssc/lotteryList/'.$date.'.json?'.time();
        $datas = CurlService::httpGet($url);
        //$datas = array_reverse($datas);

        if (!$datas) return false;
        $rstDatas = [];
        foreach ($datas as $key=>$data){
            $rstDatas[$key]['expect'] = $data['issue']; # 2019091905
            $rstDatas[$key]['opencode'] = implode(',', $data['openNum']); # 0,4,1,9,1
            $rstDatas[$key]['opentime'] = $data['openDateTime']; # 2019-9-19 11:41:05
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }

        //self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        $date = date('Y-m-d', strtotime($date) + 86400);
        $m->set($mkey, $date, \Yii::$app->params['GET_BASE_DATA_CACHE_TIME']);
        if($returnType == 'xml'){
            header("Content-type: application/xml");
            $str = '<?xml version="1.0" encoding="utf-8"?>';
            foreach ($rstDatas as $rstData){
                $str .= '<xml><row expect="'.$rstData['expect'].'" opencode="'.$rstData['opencode'].'" opentime="'.$rstData['opentime'].'" /></xml>';
            }
            echo $str;
            ob_end_flush();exit;
        }
        $logArr = $rstDatas;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/xj_ssc_batch', 'INFO', '号码批量抓取-直播网', $logArr);

        return $rstDatas;
    }

}
