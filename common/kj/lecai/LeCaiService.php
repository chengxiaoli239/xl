<?php
# 乐彩资讯 - https://www.tw666666.com/home/history?lotteryId=twk5
namespace common\kj\lecai;

use backend\models\TzSystemsUsers;
use backend\service\CurlService;
use backend\service\LeCai\ZhongFaService;
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

            $logArr = ['url'=>$url, 'content'=>$content];
            Tool_Common::log('/zhongfa/'.__FUNCTION__, 'INFO', '台湾快五', $logArr);

            if(isset($content['success']) && $content['success'] != 1) return false;
            $data = $content['data']['rows'][0];

            $kjData = ['expect'=>$data['vol'], 'opentime'=>$data['openAt'], 'opencode'=>$data['result']];
        }

        if(empty($kjData)) return false;

        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        # 设置开奖数据缓存
        self::setKjDataCache($lottery_type, $expect, $kjData);

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
    public static function getLotteryByUser($returnType = 'json', $lottery_type=18, $is_auto = 1) {

        if ($is_auto == 2 or !$kjData = self::getCurrentKjData($lottery_type)) {

            $m = \Yii::$app->cache;
            $TzSystemsUsers = TzSystemsUsers::find()->where(['AND', ['=', 'status',1], ['>', 'balance', 0],['IN', 'tz_system_id', [16]] ])->all();
            foreach ($TzSystemsUsers as $TzSystemsUser) { # 用户账号去网盘抓数据
                $mkey = 'getLotteryLucky_0_' . $lottery_type;

                $t = microtime(true) * 1000;
                $querys = ['_nowTime'=>$t, '_uri'=>'/lottery-results', 'page'=>1, 'pageSize'=>5];
                $querys['sign'] = ZhongFaService::getSign($querys);
                $domain =  str_replace('https://', '', str_replace('http://', '', $TzSystemsUser->ssc_domain));
                $url = $TzSystemsUser->ssc_domain.'/user-api/lottery-results/?'.http_build_query($querys); #当前开奖号码
                $headers = [
                    ':authority: '.$domain,
                    ':method: GET',
                    ':path: /user-api/lottery-results/?'.http_build_query($querys),
                    ':scheme: https',
                    'accept: application/json, text/plain, */*',
                    'accept-encoding: gzip, deflate, br',
                    'accept-language: zh-CN,zh;q=0.9',
                    'cookie: '.$TzSystemsUser->cookie.'; main-lottery=twk5',
                    'referer: '.$TzSystemsUser->ssc_domain.'/lottery-result/',
                    'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
                    'sec-ch-ua-mobile: ?0',
                    'sec-fetch-dest: empty',
                    'sec-fetch-mode: cors',
                    'sec-fetch-site: same-origin',
                    $TzSystemsUser->user_agent,
                ];

                $content = ZhongFaService::httpGet($url, $headers, $TzSystemsUser->uid, $time_out=15);
                $logArr = ['url' => $url, 'headers'=>$headers, 'content' => $content];
                Tool_Common::log('/zhongfa/' . __FUNCTION__, 'INFO', '台湾快五', $logArr);

                if (isset($content['success']) && $content['success'] != 1) return false;
                if(!isset($content['data']['rows'][0])) return false;
                $kj = $content['data']['rows'];
                $data = $kj[0]['acted'] ? $kj[0] : $kj[1];

                $kjData = ['expect' => $data['vol'], 'opentime' => $data['openAt'], 'opencode' => $data['result']];
                $m->set($mkey, 1, 5);
            }
        }

        if (empty($kjData)) return false;

        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        # 设置开奖数据缓存
        self::setKjDataCache($lottery_type, $expect, $kjData);

        if ($returnType == 'xml') {
            header("Content-type: application/xml");
            echo '<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="' . "$expect" . '" opencode="' . "$opencode" . '" opentime="' . "$opentime" . '" /></xml>';
            ob_end_flush();
            exit;
        } else {
            return ['expect' => $expect, 'opencode' => $opencode, 'opentime' => $opentime];
        }
    }

    /**
     * @desc 台湾快5 批量数据出口
     * @return mixed
     */
    public static function getLotteryBatch($lottery_type=18){
        $datas = self::batchGrab($lottery_type);
        $datas = array_reverse($datas);

        return $datas;
    }

    /**
     * @desc 网盘批量 批量数据
     * @return mixed
     */
    public static function batchGrab($lottery_type=18){

        $m = \Yii::$app->cache;

        $mkey = 'batchGrab_lottery_type_'.$lottery_type;
        $page = $m->get($mkey);
        if(!$page) $page = 10;
        $page = 1;

        $mkey_status = 'batchGrab_lottery_type_4_'.$lottery_type.'_status';
        if($sync_status = $m->get($mkey_status)){ # 同步开关锁
            return ['status'=>300, 'msg'=>'有正在进行的任务，请稍后...'];
        }
        $m->set($mkey_status, 1, 300);

       $TzSystemsUsers = TzSystemsUsers::find()->alias('u')->select("u.*")
            ->leftJoin('{{%tz_systems}} s', 'u.tz_system_id=s.id')
            ->leftJoin('{{%user_sys_plans}} p', 'u.uid=p.uid')
            ->where(['AND',['=', 'u.status', 1], ['=', 'u.is_auto_login', 1], ['<>', 'u.ssc_domain', ''], ['=', 's.status', 1], ['=','p.status',1]])
            ->all();

        foreach ($TzSystemsUsers as $TzSystemsUser) { # 用户账号去网盘抓数据
            $t = microtime(true) * 1000;
            $querys = ['_nowTime'=>$t, '_uri'=>'/lottery-results', 'page'=>$page, 'pageSize'=>60];
            $querys['sign'] = ZhongFaService::getSign($querys);
            $domain =  str_replace('https://', '', str_replace('http://', '', $TzSystemsUser->ssc_domain));
            $url = $TzSystemsUser->ssc_domain.'/user-api/lottery-results/?'.http_build_query($querys); #当前开奖号码
            $headers = [
                ':authority: '.$domain,
                ':method: GET',
                ':path: /user-api/lottery-results/?'.http_build_query($querys),
                ':scheme: https',
                'accept: application/json, text/plain, */*',
                'accept-encoding: gunzip, deflate, br',
                'accept-language: zh-CN,zh;q=0.9',
                'cookie: '.$TzSystemsUser->cookie.'; main-lottery=twk5',
                'referer: '.$TzSystemsUser->ssc_domain.'/lottery-result/',
                'sec-ch-ua: " Not A;Brand";v="104", "Chromium";v="104", "Google Chrome";v="104"',
                'sec-ch-ua-mobile: ?0',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-origin',
                $TzSystemsUser->user_agent,
            ];

            $datas = [];
            try {
                $content = ZhongFaService::httpGet($url, $headers, $TzSystemsUser->uid, $time_out=15);

                $logArr = ['url' => $url, 'page'=>$page, 'headers'=>$headers, 'content' => $content];
                Tool_Common::log('/zhongfa/' . __FUNCTION__, 'INFO', '台湾快五', $logArr);

                if (isset($content['success']) && $content['success'] != 1) return false;
                if(!isset($content['data']['rows'])) return false;
                $rows = $content['data']['rows'];
                foreach ($rows as $k=>$row){
                    if($row['acted']){
                        $opencode = $row['result'];
                        $datas[] = ['expect'=>$row['vol'], 'opencode'=>$opencode, 'opentime'=>$row['actAt']];
                    }
                }

                $next_page = $page - 1;
                if($next_page<=0) $next_page = 100;
                $m->set($mkey, $next_page, 300);
                $m->delete($mkey_status);

                return $datas;
            }catch (\Exception $exception){
                $m->delete($mkey_status);
                $logArr = ['url'=>$url, 'headers'=>$headers, 'content'=>$content];
                Tool_Common::log('/zhongfa/'.__FUNCTION__.'_e', 'ERR', '批量获取[lottery_type:'.$lottery_type.']失败', $logArr);
                continue;
            }

        }
        return [];
    }

    /**
     * @desc 台湾快5 批量数据出口
     * @return mixed
     */
    public static function getLotteryBatchGw($lottery_type=18){
        $datas = self::batchGrabGw($lottery_type);
        $datas = array_reverse($datas);

        return $datas;
    }

    /**
     * @desc 官网批量 批量数据
     * @return mixed
     */
    public static function batchGrabGw($lottery_type=18){

        $m = \Yii::$app->cache;

        $mkey = 'batchGrabGw_lottery_type_0_'.$lottery_type;
        $page = $m->get($mkey);
        if(!$page) $page = 5;

        $mkey_status = 'batchGrabGw_lottery_type_4_'.$lottery_type.'_status';
        if($sync_status = $m->get($mkey_status)){ # 同步开关锁
            return ['status'=>300, 'msg'=>'有正在进行的任务，请稍后...'];
        }
        $m->set($mkey_status, 1, 300);
        $lottery_type_routes = [
            18 => '/kj/le-cai/k5-batch-gw',
        ];

        $domain = BaseKj::getApiHostByRoute($lottery_type_routes[$lottery_type]);
        $date = date('Y-m-d');
        $url = $domain.'/api/lottery-results/?dataStr='.$date.'&lotteryId=twk5&page='.$page.'&pageSize=60';

        try {
            $content = CurlService::httpGet($url);

            $logArr = ['url'=>$url, 'content'=>$content];
            Tool_Common::log('/zhongfa/'.__FUNCTION__, 'INFO', '台湾快五', $logArr);

            if(isset($content['success']) && $content['success'] != 1) return false;
            $datas = $content['data']['rows'];

            foreach ($datas as $k=>$row){
                if($row['acted']){
                    $opencode = $row['result'];
                    $datas[] = ['expect'=>$row['vol'], 'opencode'=>$opencode, 'opentime'=>$row['actAt']];
                }
            }

            $next_page = $page - 1;
            if($next_page<=0) $next_page = 100;
            $m->set($mkey, $next_page, 300);
            $m->delete($mkey_status);

            return $datas;
        }catch (\Exception $exception){
            $m->delete($mkey_status);
            $logArr = ['url'=>$url, 'content'=>$content];
            Tool_Common::log('/zhongfa/'.__FUNCTION__.'_e', 'ERR', '批量获取[lottery_type:'.$lottery_type.']失败', $logArr);
            return ['status'=>301, 'msg'=>$exception->getMessage()];
        }

        return [];
    }
}
