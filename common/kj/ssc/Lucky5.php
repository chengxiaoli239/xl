<?php
# 开彩网
namespace common\kj\ssc;
use backend\models\TzSystemsUsers;
use backend\service\CurlService;
use backend\service\Lucky5\LuckyBaseService;
use common\kj\BaseKj;
use common\service\CommonService;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use  yii;

class Lucky5 extends BaseKj {
    public static $lottery_type = 8;

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
            $is_get_remote = 0;
            $status = KjDataGet::isCanGrab(self::$lottery_type);
            if(empty($status)){
                throw_info('非开奖抓取时间节点');
            }
            $m = \Yii::$app->cache;
            $redis = \Yii::$app->redis;
            $kjData = self::getCurrentKjData(self::$lottery_type, $current_qihao);
            $redisKey = 'getLotteryShiXunOne_'.self::$lottery_type;
            $is_exist = $redis->sadd($redisKey, $current_qihao);
            if(!$is_exist){
                throw_info('并发请求...');
            }
            $mkey = $redisKey.'_'.$current_qihao;
            $lockFlag = $m->get($mkey);
            if($lockFlag){
                throw_info('短时间内请求...');
            }
            if($is_auto==2 OR empty($kjData['opencode']) OR empty($kjData['expect'])) {
                $m->set($mkey, 1, 15);
                $domain = BaseKj::getApiHostByRoute('/kj/lucky5/shi-xun-one');

                $t = round(microtime(true) * 1000);
                $url = $domain.'/kaijiang/history/ygxy5.json?v='.$t; #当前开奖号码
                # 当前开奖链接：https://web01.cc138008.com/kaijiang/history/ygxy5.json?v=1582557689975

                $is_get_remote = 1;
                $rst = CurlService::getCurl($url);
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
            Tool_Common::log('/kj_datas/'.__FUNCTION__, 'INFO', '号码抓取-实讯网', ['lottery_type'=>self::$lottery_type, 'kjData'=>$kjData, 'is_remote'=>$is_get_remote]);
            $redis->srem($redisKey, $current_qihao);
        }catch (\Exception $e){
            $redis->srem($redisKey, $current_qihao);
            Tool_Common::log('/kj_datas/'.__FUNCTION__, 'ERR', '号码抓取异常-实讯网', ['lottery_type'=>self::$lottery_type, 'kjData'=>$kjData, 'rst'=>$rst, 'err_msg'=>$e->getMessage(), 'is_remote'=>$is_get_remote]);
            return false;
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
            //$data = CurlService::getCurl($url);
            $data = CurlService::getCurl302($url);
            //$data = file_get_contents($url);

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

            $content = CurlService::getCurl($url, $headers);
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
}
