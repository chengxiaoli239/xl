<?php
# 开彩网
namespace common\kj\xjssc;
use backend\service\CurlService;
use backend\service\NineNine\NineNineNewService;
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
    public static function getLotteryNoSevenDay($returnType = 'json', $is_auto = 1){

        if($is_auto==2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)){
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
        Tool_Common::log('getLotteryNoSevenDay', 'INFO', '号码抓取-7天', $logArr);

        return $rst;
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

    /**
     * @desc 七天 - 新疆 批量数据出口
     * @return mixed
     */
    public static function batchSevenDay($returnType = 'json', $is_auto = 1){
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
    public static function getLotteryNoZhiBo($returnType = 'json', $is_auto = 1){

        if($is_auto == 2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/xj-ssc/zhi-bo-wang');
            $url = $domain.'/data/Current/xjssc/CurIssue.json?'.time();
            $content = CurlService::httpGet($url);
            $data = $content;

            if (!$data OR !isset($data['preIssue'])) return false;
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
        Tool_Common::log('xj_ssc', 'INFO', '号码抓取-直播网', $logArr);

        return $rst;
    }

    /**
     * @desc 9号娱乐网 - 重庆
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNoNineNum($returnType = 'json', $is_auto = 1){

        if($is_auto==2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
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
        Tool_Common::log('cqssc', 'INFO', '号码抓取-直播网', $logArr);

        return $rst;
    }

    /**
     * @desc 直播网 - 新疆 - 批量
     * @param string $returnType
     * @return array
     */
    public static function getLotteryNoBatch($returnType = 'json', $is_auto = 1){
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
        Tool_Common::log('xj_ssc_batch', 'INFO', '号码批量抓取-直播网', $logArr);

        return $rstDatas;
    }

    /**
     * @desc 九九网 - 新疆时时彩
     * @return json|xml
     */
    public static function NineNineNew($returnType = 'json', $is_auto = 1){
        if($is_auto == 2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/xj-ssc/nine-nine-new');
            $url = $domain.'/cloud-lottery-service-server/gameInfo/lotteryissue/lastTen/xjssc'; # limit 数量
            $content = CurlService::httpGet($url);

            if ($content['code'] != 200 OR !$content['data']) return false;
            $data = $content['data'][0];

            $str = substr($data['issue'], 0, 8);
            $qh = substr(str_replace($str, '', $data['issue']), -2);
            $kjData['expect'] = $str. $qh; # 20200427-59
            $kjData['opencode'] = $data['lotteryNum']; # 1,4,3,5,1
            $kjData['opentime'] = date('Y-m-d H:i:s', substr($data['createTime'], 0, 10));
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
        Tool_Common::log('cqssc', 'INFO', '号码抓取-九九网', $logArr);

        return $rst;
    }

    /**
     * @desc 福利彩网 - 新疆时时彩   https://833cp1.com/
     * @return json|xml
     */
    public static function fuLiCai($returnType = 'json', $is_auto = 1){
        if($is_auto == 2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/xj-ssc/fu-li-cai');
            $url = $domain.'/api/v1/result/service/mobile/results/hist/HF_XJSSC?limit=1&brand=833'; # limit 数量
            $content = CurlService::httpGet($url);

            $data = $content[0];
            if ($data['gameUniqueId'] != 'HF_XJSSC' OR empty($data['openCode'])) return false;

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
        Tool_Common::log('cqssc', 'INFO', '号码抓取-福利彩网', $logArr);

        return $rst;
    }

    /**
     * @desc 皇冠网 - 新疆时时彩   https://cp9393.co/
     * @return json|xml
     */
    public static function huangGuan($returnType = 'json', $is_auto = 1){
        if($is_auto == 2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/xj-ssc/huang-guan');
            $url = $domain.'/user/getResult'; #

            $post_data = ['game_code'=>159, 'page'=>1, 'numb'=>10];

            $headers = [
                "Accept: application/json, text/plain, */*",
                "Accept-Encoding: gzip, deflate, br",
                "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
                "Connection: keep-alive",
                "Content-Length: 36",
                "Content-Type: application/x-www-form-urlencoded",
                "Cookie: PHPSESSID=lhfoi0442euttb5dqi8nnl1356; ApiUrl=//cp9393.co",
                "Host: cp9393.co",
                "Origin: https://cp9393.co",
                "Referer: https://cp9393.co/",
                "Sec-Fetch-Dest: empty",
                "Sec-Fetch-Mode: cors",
                "Sec-Fetch-Site: same-origin",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
            ];
            $content = NineNineNewService::postBetCurl($url, $post_data, $headers);
            p($content);

            $content = $content['result'];
            if (isset($content[0]) && empty($content[0])) return false;
            $data = $content[0];

            //$str = substr($data['uniqueIssueNumber'], 0, 8);
            //$qh = substr(str_replace($str, '', $data['uniqueIssueNumber']), -2);
            $kjData['expect'] = $data['round']; # 20200427-59
            $kjData['opencode'] = $data['number']; # 1,4,3,5,1
            $kjData['opentime'] = date('Y-m-d H:i:s', $data['endtime']);
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
        Tool_Common::log('cqssc', 'INFO', '号码抓取-福利彩网', $logArr);

        return $rst;
    }

    /**
     * @desc 皇冠网 - 新疆时时彩   https://game.lottery.tripleone.tech/
     * @return json|xml
     */
    public static function cg($returnType = 'json', $is_auto = 1){
        if($is_auto == 2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/xj-ssc/cg');
            $url = $domain.'/game/opencode?&id=2&pagesize=1'; #

            $content = NineNineNewService::getCurl($url);

            $content = $content['rstData'];
            if (isset($content['items']) && empty($content['items'][0])) return false;
            $data = $content['items'][0];

            //$str = substr($data['uniqueIssueNumber'], 0, 8);
            //$qh = substr(str_replace($str, '', $data['uniqueIssueNumber']), -2);
            $kjData['expect'] = $data['period']; # 20200427-59
            $kjData['opencode'] = str_replace('|', ',', $data['result']); # 1,4,3,5,1
            $kjData['opentime'] = date('Y-m-d H:i:s', $data['time']);
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
        Tool_Common::log('cqssc', 'INFO', '号码抓取-福利彩网', $logArr);

        return $rst;
    }

}
