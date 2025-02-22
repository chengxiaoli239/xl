<?php
namespace backend\service\wanbo;

use backend\models\SystemConfig;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\PoxyIPService;
use backend\service\ProxyBaseService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class WanBoBaseService {

    /**
     * @desc 批量号码拆解下注
     * @param $qihao
     * @param $plan_id
     * @param $codes
     * @return array
     */
    public static function postBatchBet($qihao, $plan_id, $codes){
        $tmpCodes = $codes;
        $plan = UserSysPlans::findOne($plan_id);
        if($plan->tz_type == 22){ # 四定单双,codes格式：13579,13579,02468,13579@13579,13579,02468,02468@13579,02468,13579,13579
            $codesArr = self::getBetCodes($codes, $plan->single, $plan->playway);
        }elseif($plan->tz_type == 18){
            $codesArr = self::getBetCodes($codes, $plan->single, $plan->playway);
        }else{
            $tmpCodes = str_replace(',', '', $tmpCodes);
            $codesArr = explode('@', $tmpCodes);
        }
        $BET_BIG_LIMIT_STATUS = BetService::getConfig('BET_BIG_LIMIT_STATUS');
        if($BET_BIG_LIMIT_STATUS){
            if(count($codesArr)>6000) return ['status'=>300, 'msg'=>'号码组数太多不能超过6000组号码'];
        }

        # 组数
        $count = count($codesArr);

        $betNums = self::getBetNumsPer();
        $codesArrs = self::splitCodes($codesArr,  $betNums); # 2500一次
        //p($codesArrs);

        $playway = $plan->playway ? $plan->playway : 3;
        $single = $plan->single ? $plan->single : 0.1;
        $tz_type = $plan->tz_type ? $plan->tz_type : 0;
        $lottery_type = $plan->lottery_type;
        //p(['playway'=>$playway, 'totalCount'=>count($codes), 'single'=>$single, 'qihao'=>$qihao, 'tz_type'=>$tz_type, 'buy_type'=>$plan->buy_type,'codes'=>$codes]);
        if(!self::$user_id) return ['status'=>400,'msg'=>'账号为空，不能识别用户'];

        $data = ['status'=>200, 'msg'=>$qihao.'期投注成功!', 'time'=>date('Y-m-d H:i:s')];

        $url = self::getTzSiteInfo(self::$tz_system_id, 'MULBET_URL');//.'?'.http_build_query($post_data);
        $way = self::getWay($tz_type);
        $snInfo_sn = '';
        $snInfo_snid = '';
        $rst = [];
        foreach ($codesArrs as $key=>$tmpcodesArr){

            if($playway == 4){ # 一字定
                $post_data = [
                    'bets' => json_encode($tmpcodesArr),
                    'way' => $way,
                    'period_no' => $qihao,
                ];

            }else{ # 四定、三定
                $bet_codes = implode(',', $tmpcodesArr);
                $post_data = [
                    'bet_number'=>$bet_codes,
                    'bet_money'=>$single,
                    'bet_way'=>$way,
                    'is_xian'=>0,
                    'number_type'=>40,
                    //'guid'=>'3e1752e5-e455-4075-b657-0fd13b90d65d',
                    'bet_log'=>'[四定位]，定位置“[取]”：千=[1]，百=[24]，十=[4]，个=[6]',
                    'is_package' => 0,
                    'period_no'=>$qihao,
                    'operation_condition' => self::getOperationCondition(),
                ];
            }

            $_t = round(microtime(true) * 1000);
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$plan->uid, 'tz_system_id'=>self::$tz_system_id]);
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                'Accept-Encoding: gunzip, deflate',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Cache-Control: max-age=0',
                'Connection: keep-alive',
                'Content-Length:'.strlen(http_build_query($post_data)),
                //'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: '.$TzSystemsUsers->cookie,
                'Host: '.str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain)),
                'Origin: '.$TzSystemsUsers->ssc_domain,
                'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$_t,
                'Upgrade-Insecure-Requests: 1',
                $TzSystemsUsers->user_agent,
            ];

            # 缓存锁
            $m = \Yii::$app->cache;
            $betKey = BetService::buildBetKey($plan->account, self::$tz_system_id, $lottery_type, $qihao, $plan_id).'_'.$key; # 分配下注后面加key
            //if($betLock = $m->get($betKey)) return ['status'=>303, 'msg'=>'已经投注过了', 'key'=>$betKey];

            //if(in_array($tz_type, [20, 23, 25]) OR $bigFlag == 1){
            # 和值投注反应时间比较久，无需返回直接锁住
            $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
            $m->set($betKey, 1, $time);
            //}
            # 真实投注
            $start_time = microtime(true);
            $tmpRst = self::postBetCurl($url, $post_data, $headers, $TzSystemsUsers->uid);
            //sleep(1);
            //p(['url'=>$url, 'headers'=>$headers, 'rst'=>$tmpRst,'post_data'=>$post_data]);
            $rst[$key] = $tmpRst;
            //$rst = json_encode($rst);
            $end_time = microtime(true);
            $time_consume = ($end_time - $start_time). 's';
            if($tmpRst['Status'] != 1){
                $tzRst = [
                    'uid'=>self::$user_id, 'lottery_type'=>$lottery_type, 'status'=>301, 'msg'=>$qihao.$rst['msg'],'url'=>$url,
                    'post_data'=>$post_data, 'user_id'=>self::$user_id, 'headers'=>self::$headers, 'postRst'=>$rst, 'time_consume'=>$time_consume
                ];
                $mkey = 'request_login_'.$TzSystemsUsers->uid.'_'.$qihao.'_'.$key;
                if($f = $m->get($mkey)){
                    return ['status'=>300, 'msg'=>'已经重复登录过一次'];
                }
                if($rst[$key]['code'] == 303){ # 判断掉线登录一次
                    if($rst[$key]['errno']>0){
                        $m = \Yii::$app->cache;
                        $mkey_proxy = PoxyIPService::builProxyIpKey();
                        $m->delete($mkey_proxy);
                    }
                    BaseService::login($TzSystemsUsers->id);
                    $tmpRst = self::postBetCurl($url, $post_data, $headers, $TzSystemsUsers->uid);
                    $m->set($mkey, 1, 5*60);
                }
                //if($tz_type != 20) $tzRst['code'] = $codes;
                Tool_Common::log('bet_error','INFO','幸运星分批投注记录-投注失败', $tzRst);
                # 302余额不足、303请登录、304重复提交、305已关盘、306系统维护，307账号停押
                if(!in_array($plan->account, \Yii::$app->params['test_account']) && in_array($rst[$key]['code'], [302, 303, 304, 305, 306, 307])){
                    //return $rst;
                    continue;
                }
                //return $rst;
            }

            $time = BetService::getBetCacheTime($lottery_type, $qihao); # 投注之后缓存时间
            $m->set($betKey, 1, $time);

            # 获取方案号，记录id, 用于撤单
            $snInfo = self::getSn(self::$user_id, self::$tz_system_id);// 用户信息 Array ( [sn] => 403054677338701312 [qihao] => 190412023 [snid] => 31724311|1,31724312|1 )
            $snInfo_snid .= '{'.$snInfo['sn'].'}|'.count($tmpcodesArr).';'; # 多次下单需要分开，多次撤单
            $snInfo_sn .= $snInfo['sn'].';'; # 多次下单需要分开，多次撤单
        }
        $data['rst'] = $rst;

        $n = count(explode('@',$codes));
        if(in_array($playway, [2, 3]) && $tz_type != 20){
            $totalmoney = SscDataService::calTzTotalMoney($codes, $single, $playway);
        }else{
            $totalmoney = $n * $single; // 投注总金额 = 注数 * 倍数
        }
        if($playway == 4 && $tz_type == 18){ # 一字定
            $totalmoney = $count * $single;
        }

        $insertData = [
            'playway'=> $playway,  // 投注方式
            'tz_type'=> $tz_type,  // 投注类型
            'buy_type'=> 1,  // 购买方向类型
            'uid'=> self::$user_id,  // 投注账号id
            'lottery_type' => $lottery_type, # 彩种
            'account' => $plan->account,
            'plan_id' => $plan_id, # 计划id
            'codes' => (string)$codes,  // 投注号码
            'qihao' => $qihao,  // 投注期号
            'tz_system_id' => self::$tz_system_id,  // 投注系统tz_systems_id
            'sn'=> trim($snInfo_sn, ';'),
            'snid'=> trim($snInfo_snid, ';'),
            'order_type'=>3, # 单双三字定
            'is_simulate' => 0,  // 是否模拟投注
            'single' => $single,  // 投注倍数
            'betting_money'=> round($totalmoney, 2),  // 投注金额
        ];
        $insertRst = BetService::_logRecords($insertData);
        self::$headers = [];

        if(strlen($post_data['bet_number'])>2000) $post_data['bet_number'] = substr($post_data['bet_number'], 0, 200);
        $logArr = ['uid'=>self::$user_id,'url'=>$url,'post_data'=>$post_data,'headers'=>self::$headers, 'bigFlag'=>1, 'postRst'=>$rst,'insertData'=>$insertData, 'insertRst'=>$insertRst];
        Tool_Common::log('bet','INFO','万博批量插入记录-真实投注', $logArr);

        return $data;
    }

    /**
     * @decription post请求根据，接受传递的header头
     * @param $url
     */
    public static function postBetCurl($url,$post_data = [],$headers=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 30;

        //$cookie = dirname(__FILE__)."/cookie.txt";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        if(strpos($url, 'ww662889') !== false){
            //curl_setopt($ch, CURLOPT_USERAGENT, ['Chrome 42.0.2311.135']);
        }

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

        $start_time = microtime(true);
        $data = curl_exec($ch);
        $end_time = microtime(true);
        //d($data);
        $errno = curl_errno( $ch );
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno]; p($logArr);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求-1-13', $logArr);
        }

        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if($data == 'ok'){
            return 'ok';
        }
        $rstData = json_decode($data, true); # data : {"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);
        if(strpos($data, "\"Status\":1") !== false && strpos($data, "\"CompletedStatus\":1") !== false){ # json解析异常处理
            $rstData['Status'] = 1;
        }

        if(strpos($data, '余额不足') !== false){
            $rstData = ["Status"=>0, 'code'=>302, 'msg'=>'余额不足'];
        }elseif(strpos($data, '登录') !== false OR strpos($data, 'Bad Gateway') !== false OR strpos($data, 'Object moved') !== false){
            $rstData = ["Status"=>0, 'code'=>303, 'msg'=>'请重新登录'];
        }elseif(strpos($data, '短时间内重复提交') !== false){
            $rstData = ["Status"=>0, 'code'=>304, 'msg'=>'短时间内重复提交'];
        }elseif(strpos($data, '已关盘') !== false){
            $rstData = ["Status"=>0, 'code'=>305, 'msg'=>'已关盘'];
        }elseif(strpos($data, '维护中') !== false){
            $rstData = ["Status"=>0, 'code'=>306, 'msg'=>'系统线路维护中'];
        }elseif(strpos($data, '停押') !== false){
            $rstData = ["Status"=>0, 'code'=>307, 'msg'=>'您的账号已被停押'];
        }else{
            $rstData = json_decode($data, TRUE);
        }
        if($errno OR in_array($rstData['code'], [302, 303, 304, 305, 306])){
            if(isset($post_data['bet_number']) && strlen($post_data['bet_number'])>200) $post_data['bet_number'] = substr($post_data['bet_number'], 0, 300);
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            Tool_Common::log('httpPostError','INFO','httpPost请求-3', $logArr);
        }
        $rstData['errno'] = $errno;
        $time_consume = ($end_time-$start_time).'s';
        $logArr = ['url'=>$url, 'headers'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'time_consume'=>$time_consume, 'poxy_addr'=>$poxy_addr];
        Tool_Common::log('postBetCurl','INFO','httpPost下注请求-万博', $logArr);
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);

        return $rstData;
    }

    /**
     * @desc 判断是否登录
     * @param $uid
     * @param $tz_system_id
     * @return bool
     */
    public static function isLogin($uid, $tz_system_id){

        $balance = LuckyBaseService::getBalance($uid,$tz_system_id);

        $flag = $balance > 0 ? true : false;

        return (boolean)$flag;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function getCurl($url,$header=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        //$header = array_merge(self::$postHeaders,$header);
        //if(strpos($url, 'GetPeriodsQuery')){ p([$url, $header]); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);
        //if(strpos($url, 'GetInfoByName') !== false){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        $errno = curl_errno($ch);
        if($errno>0) {
            $str = 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
            Tool_Common::log('getCurl', 'ERR', 'getCurl获取', ['url'=>$url, 'postRst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr]);
            return $str;
        }
        if(!BaseService::is_json($data)){
            return $data;
        }
        $data = json_decode($data, true);

        if($data['Status'] == false){
            //$data['headers'] = $header;
        }

        return $data;
    }

    /**
     * @decription
     * @param $url
     */
    public static function httpGet($url,$header=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        //if(strpos($url, 'GetPeriodsQuery')){ p([$url, $header]); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);

        //$logArr = ['url'=>$url, 'url'=>$url, 'headers'=>$header,'data'=>$data]; p($logArr);
        //if(strpos($url, 'GetInfoByName') !== false){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if(!BaseService::is_json($data)){
            return $data;
        }
        $data = json_decode($data, true);

        if($data['Status'] == false){
            //$data['headers'] = $header;
        }

        return $data;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function httpPost($url,$post_data = [],$header=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 15;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        $poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($ch);
        $errno = curl_errno( $ch );
        //if($errno && strstr($url, 'BatchBet') OR strstr($url, 'MultipleBet')){
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno];p($logArr);
        curl_close($ch);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno, 'poxy_addr'=>$poxy_addr];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求', $logArr);
            return '';
        }

        //if(strpos($url, 'betNumber')){ p(['url'=>$url, 'header'=>$header,'post_data'=>$post_data,'rstData'=>$data,curl_close($ch),$errno]); }
        if($data == 'ok'){
            return 'ok';
        }
        $rstData = json_decode($data, TRUE);
        //p(['data'=>$data, 'rstData'=>$rstData, 'post_data'=>$post_data, 'header'=>$header]);

        return $rstData;
    }

    /**
     * @desc 设置全局代理
     * @param $ch
     * @return bool
     */
    public static function setPoxy($ch, $url='', $uid = 0){
        $POXY_STATUS = BetService::getConfig('CURL_POXY_STATUS');
        if(!$POXY_STATUS) return []; # CURL 代理开关

        $poxy_addr = ProxyBaseService::getCurrentValidProxyIp();
        if(!empty($poxy_addr)){
            //$poxy_addr = '218.85.247.70:20000';
            Tool_Common::log('setPoxy', 'INFO', '设置全局代理9', ['url'=>$url, 'poxy_addr'=>$poxy_addr, 'uid'=>$uid]);
            $POXY_USER_IDS = BetService::getConfig('TENNIS_POXY_USER_IDS');
            $uids = explode(',', $POXY_USER_IDS);
            if(empty($uids) OR !in_array($uid, $uids) OR !$uid){
                return [];
            }

            if(!empty($poxy_addr)){
                //设置代理
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($ch, CURLOPT_PROXY, $poxy_addr);
                //设置代理用户名密码（私密代理/独享代理）
                //如果是开放代理，请注释掉下面两句
                $username = \Yii::$app->params['KUAI_USERNAME'];
                $password = \Yii::$app->params['KUAI_PASSWORD'];
                curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$username}:{$password}");
            }
        }

        return $poxy_addr;
    }

}
