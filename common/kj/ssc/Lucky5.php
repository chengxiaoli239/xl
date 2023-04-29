<?php
# 开彩网
namespace common\kj\ssc;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\CurlService;
use backend\service\Lucky5\LuckyBaseService;
use common\kj\BaseKj;
use common\service\CommonService;
use common\service\proxy\ProxyBaseService;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use  yii;

class Lucky5 extends BaseKj {
    public static $lottery_type = 8;
    CONST SUCCESS_CODE = 20000;

    /**
     * @desc 幸运五星彩
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryLucky($returnType = 'json', $is_auto = 1){

        $hasActivePlan = CommonService::hasPlansActive(self::$lottery_type);
        $status = KjDataGet::isCanGrab(self::$lottery_type);
        if(in_array(self::$lottery_type, [8]) && (!$hasActivePlan OR !$status)){
            return false;
        }

        if($is_auto==2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $where = ['AND', ['=', 'status',1], ['>', 'balance', 0],['IN', 'tz_system_id', [7, 9]], ['!=', 'ssc_domain', '']];
            $TzSystemsUserses = TzSystemsUsers::find()->where($where)->all();
            $m = \Yii::$app->redis;
            foreach ($TzSystemsUserses as $TzSystemsUsers){ # 用户账号去网盘抓数据
                try {
                    sleep(8);
                    $exsit_key = 'ssc_kj_data_wangpan_x0'.self::$lottery_type;
                    $is_exsit = $m->sadd($exsit_key, $TzSystemsUsers->id);
                    if(!$is_exsit){
                        throw_info('频繁操作');
                    }
                    //$domain = BaseKj::getApiHost(18);
                    $domain = $TzSystemsUsers->ssc_domain;

                    $t = microtime(true) * 10000;
                    $url = $domain.'/Member/GetMemberPrint?_='.$t; #当前开奖号码
                    # 当前开奖链接：http://f9.ww99865.xyz:5678/Member/GetMemberPrint?_=1570547160015

                    $headers = [
                        'Accept: application/json, text/javascript, */*',
                        'Accept-Encoding: gunzip, deflate',
                        'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                        'Connection: keep-alive',
                        'Cookie: '.$TzSystemsUsers->cookie,
                        'Host: '.str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain)),
                        'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$t,
                        $TzSystemsUsers->user_agent,
                        'X-Requested-With: XMLHttpRequest',
                    ];
                    $content = LuckyBaseService::getCurl($url, $headers, $TzSystemsUsers->uid);
                    //$data = json_decode($content,320);
                    $data = $content;
                    if(isset($data['Status']) && $data['Status'] == 1){

                    }

                    if (!isset($data['Status']) OR $data['Status'] != 1 OR !isset($data['Data']['draw_info'][0])) {
                        Tool_Common::log('getLotteryLucky', 'ERR', '幸运五号码抓取异常', ['url'=>$url, 'headers'=>$headers, 'content'=>$content]);
                        throw_info('幸运五号码抓取异常');
                    }
                    $row = $data['Data']['draw_info'][0];
                    $opencode = $row['thousand_no'].','.$row['hundred_no'].','.$row['ten_no'].','.$row['one_no'].','.$row['ball5'];
                    if($opencode == '0,0,0,0,0'){
                        throw_info('开奖号码异常');
                    }
                    $kjData = ['expect'=>$row['period_no'], 'opencode'=>$opencode, 'opentime'=>date('Y-m-d H:i:s')];
                    Tool_Common::log('luck5', 'INFO', '号码网盘抓取-幸运网1', ['domain'=>$domain, 'kjData'=>$kjData]);
                }catch (\Exception $e){
                    $m->srem($exsit_key, $TzSystemsUsers->id);
                    Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '网盘开奖数据获取异常', ['lottery_type'=>self::$lottery_type, 'err_msg'=>$e->getMessage()]);
                }
            }
        }
        if(empty($kjData['opencode'])) return false;
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        if(!empty($opencode)){
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

        return $rst;
    }

    /**
     * @desc 幸运五星彩 - 实时资讯网 https://cc138001.com 未完
     * http://web01.cc138008.com/?url=pc/live/ygxy5#/pc/live/ygxy5
     * @param string $returnType
     * @param int $is_auto 1:自动2手动
     * @return array|bool
     */
    public static function getLotteryShiXunOne($returnType = 'json', $is_auto=1){
        try {
            $is_remote = 0;
            $status = KjDataGet::isCanGrab(self::$lottery_type);
            if(empty($status)){
                throw_info('非开奖抓取时间节点:'.date('Y-m-d H:i:s'));
            }
            $redis = \Yii::$app->redis;
            $kjData = self::getCurrentKjData(self::$lottery_type, $current_qihao);
            $redisKey = 'getLotteryShiXunOne_'.self::$lottery_type;
            $is_exist = $redis->sadd($redisKey, $current_qihao);
            \Yii::$app->redis->expire($redisKey, 120);
            if(!$is_exist){
                throw_info('并发请求...');
            }

            Tool_Common::log('/kj_datas/'.__FUNCTION__, 'INFO', '号码抓取-实讯网01', ['lottery_type'=>self::$lottery_type, 'current_qihao'=>$current_qihao, 'kjData'=>$kjData]);
            if($is_auto==2 OR empty($kjData)) {
                $domain = BaseKj::getApiHostByRoute('/kj/lucky5/shi-xun-one');

                $t = round(microtime(true) * 1000);
                $url = $domain.'/kaijiang/history/ygxy5.json?v='.$t; #当前开奖号码
                # 当前开奖链接：https://web01.cc138008.com/kaijiang/history/ygxy5.json?v=1582557689975

                $is_remote = 1;
                #$rst = CurlService::getCurl($url, $header=[], 30, 1);
                $headers = [
                    'accept: application/json, text/plain, */*',
                    'accept-encoding: gzip, deflate, br',
                    'accept-language: zh-CN,zh;q=0.9',
                    'cookie: uuidafd4aea0-251e-11ed-bb4d-0050568551f7=2319762621252830284; cf_clearance=ykJ7Ag0JfZodLp6XpDUvSyqFp20CZFxSUf0R0KpdMUw-1680942882-0-150; AC=4b388c04651682734312550f729da1ad2072d62ea3; noticePage=6643005; __cf_bm=LtTX859s.hSXJgDnfqKJqUIAHEwk.Hec8L4nwaD525c-1682734326-0-AWF/TTUBvi7lPd9m4bp7UVYxNFnsi24J9MQYAbZuGh0CESY2xj0dQEd5dDx8nzGfwliQa9EUnJe7QQQB4VEJhdpJgw3LUxrlPjHmXq637DVB',
                    'referer: '.$domain.'/',
                    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
                    'x-requested-with: XMLHttpRequest'
                ];
                #$rst = self::getCurl($url, $headers, 30);
                $rst = self::getCurl302($url, $headers, 30);
                $data = $rst['data']['list'][0];

                if (!isset($rst['data']['list'][0]) OR empty($data)){
                    throw_info('开奖数据为空：'.yii\helpers\Json::encode($rst, 320));
                };
                $opencode = implode(',', $data['code']);
                if($opencode == '0,0,0,0,0'){
                    throw_info('开奖数据为空：'.$opencode);
                }
                //$kjData = ['expect'=>$data['preDrawIssue'], 'opencode'=>$opencode, 'opentime'=>$data['preDrawTime']];
                $kjData = ['expect'=>str_replace('期', '', $data['pc_issue'][0]), 'opencode'=>$opencode, 'opentime'=>$data['open_date'].' '.trim($data['pc_issue'][1])];
            }
            if(empty($kjData['opencode'])){
                throw_info('开奖号码不能为空');
            }
            Tool_Common::log('/kj_datas/'.__FUNCTION__, 'INFO', '号码抓取-实讯网02', ['lottery_type'=>self::$lottery_type, 'kjData'=>$kjData, 'is_remote'=>$is_remote]);
            $redis->srem($redisKey, $current_qihao);
        }catch (\Exception $e){
            $redis->srem($redisKey, $current_qihao);
            $current_proxy_addr = ProxyBaseService::getCurrentValidProxyIp();

            Tool_Common::log('/kj_datas/'.__FUNCTION__, 'ERR', '号码抓取异常-实讯网03', ['lottery_type'=>self::$lottery_type, 'kjData'=>$kjData, 'rst'=>$rst, 'err_msg'=>$e->getMessage(), 'is_remote'=>$is_remote, 'current_proxy_addr'=>$current_proxy_addr]);
            if($e->getCode() != self::SUCCESS_CODE){
                return false;
            }
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

        return $rst;
    }

    /**
     * @desc 幸运五星彩 - 实时资讯网 https://cc138001.com
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryShiXun($returnType = 'json', $is_auto=1){
        $is_remote = 0;
        if($is_auto==2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/lucky5/shi-xun');

            $t = round(microtime(true) * 1000);
            $url = $domain.'/kaijiang/ygxy5.json?v='.$t; #当前开奖号码
            # https://web01.cc138008.com/kaijiang/ygxy5.json?v=1582561329435
            # 当前开奖链接：https://1.cc138001.com/kaijiang/ygxy5.json?v=1570866018057

            $is_remote = 1;
            $data = CurlService::getCurl302($url);

            if (!isset($data['code'])) return false;
            $opencode = implode(',', $data['code']);
            if($opencode == '0,0,0,0,0') return false;
            $kjData = ['expect'=>$data['preDrawIssue']?$data['preDrawIssue']:$data['issue'], 'opencode'=>$opencode, 'opentime'=>$data['preDrawTime']?$data['preDrawTime']:$data['draw_time']];
            //p($kjData);
        }
        if(empty($kjData['opencode'])) return false;
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        if(!empty($opencode)){
            self::setKjDataCache(self::$lottery_type, $expect, $kjData);
        }

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime, 'is_remote'=>$is_remote];
        }
        $logArr = $rst;
        Tool_Common::log('luck5', 'INFO', '号码抓取-时讯网', $logArr);

        return $rst;
    }

    /**
     * @desc 幸运五星彩 批量数据出口
     * @return mixed
     */
    public static function batch($returnType = 'json'){
        $datas = self::batchGrab();
        $datas = array_reverse($datas);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            $str = '<?xml version="1.0" encoding="utf-8"?>';
            foreach ($datas as $kjData){
                $str .= '<xml><row expect="'.$kjData['expect'].'" opencode="'.$kjData['opencode'].'" opentime="'.$kjData['opentime'].'" /></xml>';
            }
            echo $str;
            ob_end_flush();exit;
        }

        return $datas;
    }

    /**
     * @desc 幸运五星 批量数据
     * @return mixed
     */
    public static function batchGrab(){
        $domain = BaseKj::getApiHost(18);
        $tz_system_users_id = 15;
        $TzSystemsUsers = TzSystemsUsers::findOne($tz_system_users_id);
        $datas = [];
        for($i=2; $i>=1; $i--){
            //for($i=16; $i>=1; $i--){
            $t = microtime(true) * 10000;
            $url = $domain.'/DrawNo/GetDrawNoTable?pageindex='.$i.'&_='.$t; #当前开奖号码

            $headers = [
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding: gunzip, deflate',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Connection: keep-alive',
                'X-Requested-With: XMLHttpRequest',
                'Cookie: '.$TzSystemsUsers->cookie,
                'Host: '.str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain)),
                'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$t,
                $TzSystemsUsers->user_agent,
            ];
            $mkey = 'batchGrab_lottery_type_6_'.$i;
            # http://f9.ww99865.xyz:5678/DrawNo/GetDrawNoTable?pageindex=2&_=1570530310790

            $m = \Yii::$app->cache;
            //if($datas = $m->get($mkey)) return $datas;

            $content = self::getCurl($url, $headers);
            //p([$headers, $url, $content]);
            $data = $content;
            if($data['Status'] == 1 && !empty($data['Data']['Rows'])){
                $rows = array_reverse($data['Data']['Rows']);
                # $str .= '<xml><row expect="'.$kjData['expect'].'" opencode="'.$kjData['opencode'].'" opentime="'.$kjData['opentime'].'" /></xml>';
                foreach ($rows as $k=>$row){
                    $opencode = $row['thousand_no'].','.$row['hundred_no'].','.$row['ten_no'].','.$row['one_no'].','.$row['ball5'];
                    $datas[] = ['expect'=>$row['period_no'], 'opencode'=>$opencode, 'opentime'=>$row['draw_datetime']];
                }
            }

            $m->set($mkey, $datas, 20 * 60);
        }
        //d($datas);
        return $datas;
    }

    /**
     * @desc 获取官方前xx期号码
     * @param int $num
     * @param int $lottery_type
     * @return array
     */
    public static function getBeforeKjCodesFromSite($num = 1000){
        $codes = [];
        try {
            $allDateCodes = [];
            for($i=0; $i<5; $i++){
                $b = 0 - $i;
                $date = date('Y-m-d', strtotime($b.'day'));
                $dataCodes = Lucky5::getCodesByBeforeDate($date);
                $allDateCodes = array_merge($allDateCodes, $dataCodes);
            }
            $codes = array_unique($allDateCodes);
            $codes = array_slice($codes, 0, $num);
        }catch (\Exception $e){
            Tool_Common::log('/codes/'.__FUNCTION__, 'ERR', '获取号码异常', ['err_msg'=>$e->getMessage()]);
        }

        return $codes;
    }

    /**
     * @desc 获取指定日期的号码数据
     * @param int $nums
     * @param string $date
     * @return mixed
     */
    public static function getCodesByBeforeDate($date='', $nums=288){
        $codes = [];
        #$nums = ($nums>288) ? 288 : $nums;
        try {
            $domain = BaseKj::getApiHostByRoute('/kj/lucky5/shi-xun');
            if(empty($date)){
                $date = date('Y-m-d');
            }

            $m = \Yii::$app->cache;
            $mkey = 'getCodesByBeforeDate_0_'.$date;
            if($date != date('Y-m-d')){
                $codes = $m->get($mkey);
                if(!empty($codes)) return $codes;
            }
            $url = $domain . '/server/history/award?page=1&page_size='.$nums.'&gt=ygxy5&open_time='.$date;
            $rst = CurlService::getCurl302($url);
            if(isset($rst['data']['list']) && !empty($rst['data']['list'])){
                $lists = $rst['data']['list'];
                $draw_codes = [];#yii\helpers\ArrayHelper::getColumn($lists, 'draw_code');
                foreach ($lists as $list){
                    $draw_codes[] = substr($list['draw_code'], 0, 7);
                }
                $m->set($mkey, $draw_codes, 5*86400);
                $codes = $draw_codes;
            }
        }catch (\Exception $e){
            Tool_Common::log('/codes/'.__FUNCTION__, 'ERR', '获取号码异常', ['err_msg'=>$e->getMessage()]);
        }

        return $codes;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function getCurl($url,$header=[], $timeout=''){
        if(!$timeout){
            $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);
        //p(['header'=>$header, 'url'=>$url, 'rst'=>$data]);
        $errno = curl_errno($ch);
        if($errno>0) {
            $str = 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
            Tool_Common::log('/err/getCurl', 'ERR', 'getCurl获取', ['url'=>$url, 'errno'=>$errno, 'postRst'=>$data, 'error'=>$str]);
            return ['Status'=>2, 'code'=>300, 'Data'=>'代理网络超时，错误码:'.$errno, 'errno'=>$errno];
        }
        curl_close($ch);
        if(!self::is_json($data)){
            return $data;
        }
        $data = json_decode($data, true);

        return $data;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function getCurl302($url,$headers=[], $timeout=5){
        if(!$timeout){
            $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        #curl_setopt($ch, CURLOPT_SSLVERSION, 2);
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1); // 开启TCP keepalive功能，保持长连接
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    # 302 redirect
        curl_setopt($ch, CURLOPT_ENCODING, true);    # 302 redirect
        ProxyBaseService::setProxy($ch); # 设置全局代理

        $data = curl_exec($ch);
        $errno = curl_errno($ch);
        #p(['headers'=>$headers, 'url'=>$url, 'rst'=>$data, 'errno'=>$errno]);
        if($errno>0) {
            $err_msg = 'Curl error: ' . curl_error($ch);
            Tool_Common::log('/err/getCurl', 'ERR', 'getCurl获取', ['url'=>$url, 'errno'=>$errno, 'postRst'=>$data, 'err_msg'=>$err_msg]);
            return ['Status'=>2, 'code'=>300, 'Data'=>'代理网络超时，错误码:'.$errno, 'errno'=>$errno];
        }
        curl_close($ch);
        if(!BaseService::is_json($data)){
            return $data;
        }
        $data = json_decode($data, true);

        return $data;
    }
}
