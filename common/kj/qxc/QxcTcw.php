<?php
# 开彩网
namespace common\kj\qxc;
use backend\service\CurlService;
use backend\service\NineNine\NineNineNewService;
use common\kj\BaseKj;
use common\kj\ssc\Thirdd;
use common\service\thirdD\CommonBaseService;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use  yii;
use yii\helpers\Json;

class QxcTcw extends BaseKj{
    public static $lottery_type = 1; # 七星彩
    public static $tcwTypes = [ # 系统对体彩网 类型枚举
        1 => '04', # 七星彩
        17 => '350133', # 排列五
    ];
    public static $tcwTypeStr = [
        1 => 'qxc',
        17 => 'pl5',
    ];

    public static function getLotteryNo($returnType = 'json', $is_auto = 1){

        //$url='http://wd.apiplus.net/tef05c6c66079ff29k/cqssc-3.json';
        $url='http://www.lottery.gov.cn/historykj/history.jspx?_ltype=qxc';
        //$content = file_get_contents($url);
        $content = CurlService::httpGet($url);
        $preg = '/<td width="40" height="23" align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)" class="red">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<\/tr>/is'; // 这里是表达式，大神看看
        preg_match_all($preg,$content,$matches);
        $qihao = $matches[2][0];
        $kjdata = $matches[5][0];
        if(!$kjdata) return false;
        $date = $matches[53][0];
        $kjData = ['expect'=>'20'.$qihao, 'opencode'=>"$kjdata[0],$kjdata[1],$kjdata[2],$kjdata[3],$kjdata[4],$kjdata[5],$kjdata[6]", 'date'=>$date];

        $expect = $kjData['expect'];
        $opencode = $kjData['opencode'];
        $opentime = strtotime($kjData['date']);

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
     * @desc 批量获取qxc开奖号码
     * @param $type
     * @return array
     */
    public static function getBatchLotteryNo($type = json, $is_auto = 1){
        $m = \Yii::$app->cache;
        $mkey = 'BATCH_TCW_PAGE_V4';
        if($page = $m->get($mkey)){
            $page = $page - 1;
            $page = $page != 110 ? $page : 109;
        }else{
            $page = 112;
        }
        if($page<0) return false;

        $url = 'http://www.lottery.gov.cn/historykj/history_'.$page.'.jspx?_ltype=qxc';
        $content = CurlService::httpGet($url);
        $preg = '/<td width="40" height="23" align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)" class="red">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<\/tr>/is'; // 这里是表达式，大神看看
        preg_match_all($preg,$content,$matches);
        //p($content);
        $len = count($matches[0]);
        $kjDatas = [];
        for ($n=$len; $n>0; $n-- ){
            $key = $n - 1;
            $qihao = $matches[2][$key];
            $kjdata = $matches[5][$key];
            $date = $matches[53][$key];
            $kjDatas['opencode'][] = ['qihao'=>'20'.$qihao, 'codes'=>"$kjdata[0],$kjdata[1],$kjdata[2],$kjdata[3],$kjdata[4],$kjdata[5],$kjdata[6]", 'date'=>$date];
        }
        if($kjDatas) $m->set($mkey, $page, 2*3600);

        return $kjDatas;
    }

    /**
     * @desc 七星彩  中国体育彩票 , $lottery_type = 1   https://www.lottery.gov.cn/kj/kjlb.html?qxc
     * @param string $returnType
     * @return array|bool 返回格式(数组)：{"expect":"2020100623","opencode":"0,8,6,3,6,3,4","opentime":"2020-10-06 17:41:38"}
     */
    public static function getNineNineLottery(string $returnType = 'json', $is_auto = 1, $lottery_type = 1){

        if($is_auto == 2 OR !$kjData = self::getCurrentKjData($lottery_type) OR true) {
            $domain = BaseKj::getApiHostByRoute('/kj/qxc/nine-nine-plw');
            $lotNames = [1=>'hnqxc', 17=>'plw', 26=>'fcsd', '27'=>'plw'];
            $url = $domain.'/cloud-lottery-service-server/gameInfo/lotteryissue/lastTen/'.$lotNames[$lottery_type];

            $data = CurlService::getCurl($url);

            if (!isset($data['code']) OR empty($data['data'][0])) return false;
            $kData = $data['data'][0];
            $tmp_codes = $kData['result']['numbers'];
            $kjData['expect'] = $kData['issue'];
            if($lottery_type == CommonBaseService::LOTTERY_TYPE_FUCAI){
                $tmp_codes[3] = 0;
            }
            $kjData['opencode'] = $tmp_codes[0].','.$tmp_codes[1].','.$tmp_codes[2].','.$tmp_codes[3].',0';
            $kjData['opentime'] = date('Y-m-d H:i:s', (int)($kData['openTime']/1000));
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ] # 返回格式
            Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '获取九九数据', ['lottery_type'=>$lottery_type, 'kjData'=>$kjData]);
            self::setKjDataCache($lottery_type, $kjData['expect'], $kjData, 3600);
        }
        return self::extracted($kjData, $lottery_type, $returnType, $is_auto);
    }

    /**
     * @desc 排列五、七星已经开奖的期号
     * @param int $lottery_type
     * @return string|array
     */
    public static function getNineNineQihao(int $lottery_type = 1, $is_auto=1){
        try {
            $m = \Yii::$app->cache;
            $mkey = 'getNineNineQihao_'.$lottery_type;
            if($is_auto==2 OR !$qihao = $m->get($mkey)){
                $domain = BaseKj::getApiHostByRoute('/kj/qxc/nine-nine-plw');
                $url = $domain.'/cloud-lottery-service-server/gameInfo/lotteryissue/lastTen/'.NineNineNewService::$lotNames[$lottery_type];
                $rstData = CurlService::getCurl($url);
                Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '获取九九期号', ['lottery_type'=>$lottery_type, 'rst'=>$rstData]);
                if(isset($rstData['code'])  && $rstData['code']==200 && isset($rstData['data'][0])){
                    $qihao = $rstData['data'][0]['issue'];
                    $m->set($mkey, $qihao, 600);
                }
            }
        }catch (\Exception $e){

        }

        return $qihao;
    }

    /**
     * @desc 七星彩  中国体育彩票 , $lottery_type = 1   https://www.lottery.gov.cn/kj/kjlb.html?qxc
     * https://www.sporttery.cn/digitallottery/ # 排列五、七星开奖
     * @param string $returnType
     * @return array|bool 返回格式(数组)：{"expect":"2020100623","opencode":"0,8,6,3,6,3,4","opentime":"2020-10-06 17:41:38"}
     */
    public static function getTcwOne($returnType = 'json', $is_auto = 1, $lottery_type = 1){

        if($is_auto == 2 OR !$kjData = self::getCurrentKjData($lottery_type)) {
            $data = self::QixingCaiBatch($is_new = 1, $lottery_type);

            if (!isset($data[0]) OR !$kData = $data[0]) return false;
            $kjData['expect'] = $kData['expect'];
            $kjData['opencode'] = $kData['opencode'];
            $kjData['opentime'] = $kData['opentime'];
            //p($kjData);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ] # 返回格式
        }
        return self::extracted($kjData, $lottery_type, $returnType, $is_auto); # 开奖号码
        $opentime = $kjData['opentime']; # 开奖时间
        $expect = $kjData['expect']; # 期号
        //p([DEFAULT_LOTTERY_TYPE,$expect, $kjData]);

        self::setKjDataCache($lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = array_merge($rst, [
            'is_auto' => $is_auto,
            'lottery_type' => $lottery_type,
        ]);
        Tool_Common::log('cqssc_kl8', 'INFO', '体彩网-号码抓取', $logArr);

        return $rst;
    }

    /**
     * @desc 中国体彩网 - 七星彩
     * @param int $is_new
     * @param int $lottery_type
     * @return - json|xml
     */
    public static function QixingCaiBatch($is_new = 0, $lottery_type = 1){
        $datas = self::QixingCaiBatchDatas($is_new, $lottery_type);
        $logArr = ['is_new'=>$is_new, 'lottery_type'=>$lottery_type, 'datas'=>$datas];
        Tool_Common::log('QixingCaiBatch', 'INFO', '号码抓取-体彩网-1', $logArr);

        return $datas;
    }

    /**
     * @desc 中国体彩网 - 七星彩
     * @return - json|xml|array
     */
    public static function QixingCaiBatchDatas($is_new = 0, $lottery_type = 1){

        $m = \Yii::$app->cache;
        $mkey_rstData = 'QixingCaiBatchDatas_'.$is_new.'_'.$lottery_type;
        $rstData = $m->get($mkey_rstData);
        if($is_new && !empty($rstData)) return $rstData;

        $mkey = 'QixingCaiBatch_page_4_'.$lottery_type.'_'.$is_new;
        $default_page = 84;
        if($lottery_type==17){
            $mkey = $mkey.'_'.$lottery_type;
            $default_page = 192;
        }
        $page = $m->get($mkey) ? : $default_page;
        if($is_new==1) $page = 1;
        $gameNo = self::$tcwTypes[$lottery_type];

        $running_status_key = 'QixingCaiBatch_status';
        if($lottery_type==17) $running_status_key = $running_status_key.'_2_'.$lottery_type;
        if($status = $m->get($running_status_key)) return ['status'=>300, 'msg'=>'有在执行的任务，请稍后'];
        $m->set($running_status_key, 1, 180);

        if($lottery_type == 17){ # 排列五
            $route = '/kj/qxc/pl5-batch';
        }else{
            $route = '/kj/qxc/qxc-batch';
        }
        $domain = BaseKj::getApiHostByRoute($route);
        $url = $domain.'/gateway/lottery/getHistoryPageListV1.qry?gameNo='.$gameNo.'&provinceId=0&pageSize=30&isVerify=1&pageNo='.$page; # limit 数量

        $headers = [
            "Accept: application/json, text/javascript, */*; q=0.01",
            "Accept-Encoding: gunzip, deflate, br",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Host: webapi.sporttery.cn",
            "Origin: https://static.sporttery.cn",
            "Referer: https://static.sporttery.cn/",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-site",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36                ",
        ];

        $content = CurlService::httpGet($url, $headers);
        if(isset($content['value']['list']) && !$datas = $content['value']['list']);
        //$datas = array_reverse($data);

        $rstData = [];
        foreach ($datas as $data){
            $expect = '20'.$data['lotteryDrawNum'];
            $opencode = str_replace(' ', ',', $data['lotteryDrawResult']);
            $opentime = $data['lotterySaleEndtime'];
            $rstData[] = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $page = $page-1;
        $page < 2 && $page = 1;
        $m->set($mkey, $page, 24*3600);
        $m->delete($running_status_key); # 跑完任务删除key

        $m->set($mkey_rstData, $rstData, 900);

        $logArr = ['page'=>$page, 'lottery_type'=>$lottery_type, 'data'=>$rstData];
        Tool_Common::log('qxc_batch', 'INFO', '号码抓取-体彩网-0', $logArr);

        return $rstData;
    }

    /**
     * 排列3号码抓取
     * @param string $returnType
     * @param int $is_auto
     * @param int $lottery_type 26 福、27 排
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getOfficialCode(string $returnType = 'json', int $is_auto = 1, int $lottery_type=27): ?array
    {
        try {
            $dateHI = date('H:i');
            $seconds = ('00:00'<$dateHI && $dateHI<'21:00') ? 1800 : 120;
            if($is_auto==1){
                self::lockGrab($lottery_type, $seconds);
            }
            if(!$kjData = Thirdd::getCurrentKjData($lottery_type, $current_qihao) OR $is_auto==2) {
                $domain = BaseKj::getApiHostByRoute($route='/kj/qxc/pl3-official');
                $url = $domain."/gateway/lottery/getHistoryPageListV1.qry";
                $params = [
                    'query' => [
                        'gameNo' => 35,
                        'provinceId' => 0,
                        'pageSize' => 5,
                        'isVerify' => 1,
                        'pageNo' => 1
                    ]
                ];

                $headers = [
                    'headers' => [
                        'authority' => 'webapi.sporttery.cn',
                        'accept' => 'application/json, text/javascript, */*; q=0.01',
                        'accept-language' => 'zh-CN,zh;q=0.9',
                        'origin' => 'https://static.sporttery.cn',
                        'referer' => 'https://static.sporttery.cn/',
                        'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                        'sec-ch-ua-mobile' => '?0',
                        'sec-ch-ua-platform' => '"Windows"',
                        'sec-fetch-dest' => 'empty',
                        'sec-fetch-mode' => 'cors',
                        'sec-fetch-site' => 'same-site',
                        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ]
                ];

                $client = new Client();
                $response = $client->request('GET', $url, $params + $headers);

                $data = $response->getBody()->getContents();
                $content = Json::decode($data);
                Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '3d数据抓取-官网', ['lottery_type'=>$lottery_type, 'cq'=>$current_qihao, 'content'=>$content]);
                if($content['errorCode']==0 && $content['success']){
                    $latestData = $content['value']['list'][0];
                    $kjData['expect'] = '20'.$latestData['lotteryDrawNum'];
                    $kjData['opencode'] = trim(str_replace(' ', ',', $latestData['lotteryUnsortDrawresult']));
                    $kjData['opentime'] = $latestData['lotterySaleBeginTime'];
                }else{
                    throw_info('号码抓取异常');
                }
                //p(['data'=>$data]);
            }
        }catch (\Exception $e){
            $kjData = Thirdd::getCurrentKjData($lottery_type);
            Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '3d数据抓取-异常', ['lottery_type'=>$lottery_type, 'cq'=>$current_qihao, 'kjData'=>$kjData, 'err_msg'=>$e->getMessage()]);
        }
        $data =  self::extracted($kjData, $lottery_type, $returnType, $is_auto);

        return $data;
    }
}
