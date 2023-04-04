<?php
namespace backend\service\clients;

use backend\models\BetErrorPlansTask;
use backend\models\DataDealStatus;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\BetService;
use backend\service\Lucky5\Lucky5Service;
use common\kj\ssc\Lucky5;
use common\service\CommonService;
use common\service\jobs\kj_data\GrabKjDatasJob;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class TzSystemUsersService extends ClientsBaseService{
    public static $module_key = 'backend\models\TzSystemsUsers';
    const PlAN_TYPE_LOCAL = 'local';
    const PlAN_TYPE_RE_LOCAL = 're_local';

    public static function getLists($post=[]){
        if(empty($post)){
            return ['status'=>300, 'msg'=>'非法请求'];
        }
        $token = $post['access_token'];
        if(empty($post['access_token'])){
            return ['status'=>301, 'msg'=>'缺少access_token参数'];
        }
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey();
        $datas = $m->get($mkey);
        if(empty($datas)){
            $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
            $where = ['AND',['<>', 'status', -2], '1=1'];
            if($ADMIN_ACCESS_TOKEN != $token){
                $where = array_merge($where, [['=', 'access_token', $token]]);
            }

            $datas = (self::$module_key)::find()->where($where)->asArray()->all();
            $m->set($mkey, $datas, 180);
        }

        return ['status'=>200, 'datas'=>$datas, 'msg'=>'操作成功'];
    }

    /**
     * @desc 用户列表key
     * @return string
     */
    public static function buildUserKey(){
        $mkey = 'getTzSystemUserLists_key';

        return $mkey;
    }

    /**
     * @desc 删除用户缓存信息
     */
    public static function delTzsystemUserData(){
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey();

        $m->delete($mkey);
    }

    /**
     * @desc 获取系统授权access_tokens
     * @return array|mixed
     */
    public static function getAuthAccessTokens(){
        $m = \Yii::$app->cache;
        $mkey = 'user_getAuthAccessTokens';
        if(true OR !$access_tokens = $m->get($mkey)){
            $ADMIN_ACCESS_TOKEN = BetService::getConfig('ADMIN_ACCESS_TOKEN'); # 管理员token
            $TzSystemsUsers = TzSystemsUsers::find()->select(['access_token'])->where(['status'=>1])->asArray()->all();
            $access_tokens = ArrayHelper::getColumn($TzSystemsUsers, 'access_token');

            $access_tokens = array_merge($access_tokens, [$ADMIN_ACCESS_TOKEN]);
            $m->set($mkey, $access_tokens, 600);
        }

        return $access_tokens;
    }

    /**
     * @desc 获取用户的cookies
     * @param string $access_token
     * @return mixed|string
     */
    public static function getCookiesByAccessToken($access_token='', $is_auto=1){

        $m = \Yii::$app->cache;
        $mkey = self::buildUserCookesKey($access_token);
        $data = $m->get($mkey);

        if($is_auto==2 OR empty($data)){
            //$TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            $cookies = $TzSystemsUsers->cookie;
            #$m->set($mkey, $cookies, 300);

            Lucky5Service::__init($TzSystemsUsers->uid, $TzSystemsUsers->tz_system_id);
            $tzSiteInfo = Lucky5Service::getTzSiteInfo($TzSystemsUsers->tz_system_id);
            $user_agent = trim(str_replace('User-Agent:', '',str_replace('user_agent:','', $TzSystemsUsers->user_agent)));
            $data = [
                'cookies' => trim(trim($cookies), ';'),
                'user_agent' => $user_agent,
                "Referer" => $TzSystemsUsers->ssc_domain."/App/Index?_=",
                "Host"=> str_replace('www.','',$tzSiteInfo['domain']),
                "v1" => "99",
                "v2" => "104",
            ];
            $m->set($mkey, $data, 60);
        }

        return ['status'=>200, 'data'=>$data];
    }

    /**
     * @desc 获取用户的cookies
     * @param string $access_token
     * @return mixed|string
     */
    public static function updateRobot7ByAccessToken($access_token='', $new_robot7='', $old_robot7=''){

        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token, $is_auto=2);
        $cookies = $TzSystemsUsers->cookie;
        $TzSystemsUsers->cookie = str_replace($old_robot7, $new_robot7, $cookies);
        $flag = $TzSystemsUsers->save();

        $m = \Yii::$app->cache;
        $mkey = self::buildUserCookesKey($access_token);
        $m->delete($mkey);
        TzSystemUsersService::getCookiesByAccessToken($access_token, $is_auto=2);

        return ['status'=>200, 'data'=>['flag'=>$flag]];
    }

    public static function buildTzSystemUsersKey($access_token){
        $mkey = 'buildTzSystemUsersKey_'.$access_token;

        return $mkey;
    }

    /**
     * @desc 获取下注用户信息
     * @param string $access_token
     * @return TzSystemsUsers|mixed|null
     */
    public static function getTzSystemsUsersByAccessToken($access_token='', $is_auto=1){
        $m = \Yii::$app->cache;
        $mkey = TzSystemUsersService::buildTzSystemUsersKey($access_token);
        $TzSystemsUsers = $m->get($mkey);
        if($is_auto==2 OR empty($TzSystemsUsers)){
            $TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
            $m->set($mkey, $TzSystemsUsers, 30);
        }

        return $TzSystemsUsers;
    }

    public static function buildUserCookesKey($access_token=''){
        $mkey = 'buildUserCookesKey_2_'.$access_token;
        return $mkey;
    }

    /**
     * @desc 获取用户激活的计划ids
     * @param string $access_token
     * @return mixed|string
     */
    public static function getActivePlanIds($access_token=''){

        $m = \Yii::$app->cache;
        $mkey = self::buildUserPlanidsKey($access_token);
        $data = $m->get($mkey);

        if(empty($data)){
            //$TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            $uid = $TzSystemsUsers->uid;

            $where = ['AND', ['=', 'status', 1], ['OR', ['=', 'is_batch_simulate', 0], ['IS', 'is_batch_simulate', NULL]], ['=', 'uid', $uid]]; # is_batch_simulate:0正常1批量模拟历史记录
            $plans = UserSysPlans::find()->where($where)->asArray()->all();
            $data = ArrayHelper::getColumn($plans, 'id');

            $m->set($mkey, $data, 60);
        }

        return ['status'=>200, 'data'=>$data];
    }


    /**
     * @desc 获取用户激活的计划ids
     * @param int $lottery_type
     * @return mixed|string
     */
    public static function getActiveQihao($lottery_type=DEFAULT_LOTTERY_TYPE){

        $m = \Yii::$app->cache;
        $mkey = self::buildActiveQihaoKey($lottery_type);
        $next_qihao = $m->get($mkey);

        if(empty($next_qihao)){
            $where = [
                'AND',
                ['=', 'lottery_type', $lottery_type],
                ['!=', 'next_qihao', ''],
                ['IS NOT', 'next_qihao', NULL],
            ];
            $query = DataDealStatus::find()->where($where);
            #p($query->createCommand()->getRawSql());
            $DataDealStatus = $query->asArray()->orderBy(['id'=>SORT_DESC])->one();
            $next_qihao = $DataDealStatus['next_qihao'];

            $m->set($mkey, $next_qihao, 65);
        }
        if(empty($next_qihao)){
            return ['status'=>300, 'data'=>[]];
        }

        return ['status'=>200, 'data'=>['next_qihao'=>$next_qihao]];
    }

    /**
     * @desc 激活的任务id
     * @param string $access_token
     * @return string
     */
    public static function buildUserPlanidsKey($access_token=''){
        $mkey = 'buildUserPlanidsKey_1_'.$access_token;
        return $mkey;
    }

    /**
     * @desc 激活的期号
     * @param string $access_token
     * @return string
     */
    public static function buildActiveQihaoKey($lottery_type=''){
        $mkey = 'buildActiveQihaoKey_1_'.$lottery_type;
        return $mkey;
    }

    /**
     * @desc 激活的下注任务
     * @param string $access_token
     * @return string
     */
    public static function buildUserPlanTasksKey($access_token=''){
        $mkey = 'buildUserPlanTasksKey_1_'.$access_token;
        return $mkey;
    }

    /**
     * @desc 客户端余额同步
     * @param string $access_token
     * @param float $balance
     */
    public static function syncClientBalance($access_token='', $balance=0.00){

        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
        if(!empty($TzSystemsUsers)){
            $TzSystemsUsers->balance = $balance;
            $TzSystemsUsers->updated_at = time();
            $flag = $TzSystemsUsers->save();
            Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '客户端余额同步', ['account'=>$TzSystemsUsers->username, 'access_token'=>$access_token, 'balance'=>$balance]);
        }

        return ['status'=>200, 'data'=>['flag'=>$flag], 'msg'=>'操作成功'];
    }

    /**
     * @desc 客户端开奖数据同步
     * @param array $kjData
     * @param int $lottery_type
     * @return array
     */
    public static function syncClientKjDatas($kjData=[], $lottery_type=DEFAULT_LOTTERY_TYPE){

        $expect = $kjData['expect'] = trim($kjData['expect']);
        $kjData['opencode'] = trim($kjData['opencode']);
        $kjData['opentime'] = $kjData['opentime'] ? : date('Y-m-d H:i:s');
        if(empty($kjData['opencode'])){
            return ['status'=>300, 'msg'=>'开奖数据不能为空'];
        }
        $m = \Yii::$app->cache;
        $mkey = 'syncClientKjDatas_x0_'.$lottery_type;
        Lucky5::setKjDataCache($lottery_type, $expect, $kjData);

        $m->set($mkey, 1, 15);

        if(!$flag = $m->get($mkey)){
            $params = ['lottery_type'=>$lottery_type, 'title'=>BetService::getLotteryName($lottery_type).'_网盘推送', 'is_grab_history'=>1];
            push_queue(GrabKjDatasJob::class, $params);
        }

        return ['status'=>200, 'data'=>[], 'msg'=>'数据同步成功'];
    }

    /**
     * @desc 获取用户激活的下注任务
     * @param string $access_token
     * @return mixed|string
     */
    public static function getActivePlanTasks($access_token='', $current_qihao='', $lottery_type=DEFAULT_LOTTERY_TYPE){

        $m = \Yii::$app->cache;
        $mkey = self::buildUserPlanTasksKey($access_token);
        $flag = $m->get($mkey);

        $RedisLock = new RedisLock();
        $Rkey = $access_token.'_'.__FUNCTION__.'_redis';
        $datas = [];
        if($RedisLock->lock($Rkey.'_redis', 10)){
            if(true OR empty($flag)){
                $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
                if(!empty($TzSystemsUsers->expire_time) && $TzSystemsUsers->expire_time <= time()){
                    return ['status'=>301,  'msg'=>'已过期，请续费'];
                }
                $uid = $TzSystemsUsers->uid;
                $where = ['AND', ['=', 'lottery_type', $lottery_type], ['IN', 'status', [0, 1]]]; # 可重推的状态0:未推送1推送失败可重推，不可重推:3
                if($uid){
                    $where = array_merge($where, [['=', 'uid', $uid]]);
                }
                if(!empty($current_qihao)){
                    $incr_qihao_key = 'incr_qihao_key_'.$lottery_type.'_'.$uid.'_'.$current_qihao;
                    $count = $RedisLock->_redis->incrby($incr_qihao_key, 1);
                    if($count<=1){
                        $where = array_merge($where, [['=', 'qihao', (string)$current_qihao]]);
                    }
                }

                $BetErrorPlansTasks = BetErrorPlansTask::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(8)->all();
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-0', ['uid'=>$uid, 'current_qihao'=>$current_qihao, 'where'=>$where, 'count'=>$count]);
                if(empty($BetErrorPlansTasks)){
                    Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-1', ['uid' => $uid, 'msg'=>'没有下注计划']);
                    return ['status'=>301, 'msg'=>'没有下注任务'];
                }
                $datas = [];
                $_t = round(microtime(true) * 1000);
                foreach ($BetErrorPlansTasks as $row){
                    $plan_id = $row->plan_id;
                    $account = $row->account;
                    $bet_url = $row->bet_url;
                    $qihao = $row->qihao;
                    if($qihao > $current_qihao){
                        continue;
                    }
                    $post_data = json_decode($row->post_datas, 320);
                    $uid = $row->uid;
                    $local_codes = '';
                    if(in_array($uid, \Yii::$app->params['ONE_FIXED_UIDS']) && $row->playway == 4){
                        $bets = json_decode($post_data['bets'], 320);
                        foreach ($bets as $d){
                            $local_codes .= ' '.$d['bet_no'];
                            $bet_money = $d['bet_money'];
                        }
                    }

                    if(false){
                        $headers = [
                            'Accept'=> 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                            'Accept-Encoding' => 'gunzip, deflate, br',
                            'Accept-Language' => 'zh-CN,zh;q=0.9',
                            'Cache-Control' => 'max-age=0',
                            'Connection' => 'keep-alive',
                            'Content-Length' => (string)strlen(http_build_query($post_data)),
                            'Content-Type' => 'application/x-www-form-urlencoded',
                            'Cookie' => $TzSystemsUsers->cookie,
                            'Host' => trim(str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain))),
                            'Origin' => trim($TzSystemsUsers->ssc_domain),
                            'Referer' => trim($TzSystemsUsers->ssc_domain).'/App/Index?_='.$_t,
                            'sec-ch-ua: " Not;A Brand";v="104", "Google Chrome";v="104", "Chromium";v="104"',
                            'sec-ch-ua-mobile: ?0',
                            'sec-ch-ua-platform: "Windows"',
                            'Sec-Fetch-Dest: empty',
                            'Sec-Fetch-Mode: cors',
                            'Sec-Fetch-Site: same-origin',
                            'Upgrade-Insecure-Requests' => '1',
                            'User-Agent' => trim(str_replace('User-Agent:', '', $TzSystemsUsers->user_agent)),
                        ];
                    }else{
                        $headers = [
                            "Accept"=>"application/json, text/javascript, */*; q=0.01",
                            "Accept-Encoding"=>"gzip, deflate, br",
                            "Accept-Language"=>"zh-CN,zh;q=0.9",
                            "Connection"=>"Close",
                            "Keep-Alive"=> "timeout=5, max=81",
                            'Content-Length' => (string)strlen(http_build_query($post_data)),
                            "Content-Type"=>"application/x-www-form-urlencoded; charset=UTF-8",
                            'Cookie' => $TzSystemsUsers->cookie,
                            'Origin' => trim($TzSystemsUsers->ssc_domain),
                            'Referer' => trim($TzSystemsUsers->ssc_domain).'/App/Index?_='.$_t,
                            'Host' => trim(str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain))),
                            "sec-ch-ua"=>'"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
                            "sec-ch-ua-mobile"=>"?0",
                            "sec-ch-ua-platform"=>'"Windows"',
                            "Sec-Fetch-Dest"=>"empty",
                            "Sec-Fetch-Mode"=>"cors",
                            "Sec-Fetch-Site"=>"same-origin",
                            'User-Agent' => trim(str_replace('User-Agent:', '', $TzSystemsUsers->user_agent)),
                            "X-Requested-With"=>"XMLHttpRequest"
                        ];
                    }

                    $slow_seconds = BetService::getConfig('BET_SLOW_SECONDS'); # 下注延迟秒数设置
                    $datas[] = [
                        'bet_url' => $bet_url,
                        'plan_type' => self::PlAN_TYPE_RE_LOCAL,
                        'local_data' => [
                            'local_codes' => trim($local_codes),
                            'bet_money' => $bet_money,
                        ],
                        'plan_id' => $plan_id,
                        'account' => $account,
                        'qihao' => $qihao,
                        'slow_seconds' => $slow_seconds,
                        'post_data' => $post_data,
                        'headers' => $headers,
                    ];
                    $m->set($mkey, 1, 30);
                }
            }
        }else{
            return ['status'=>300, 'data'=>[], 'msg'=>'没有下注任务xxx'];
        }
        if(empty($datas)){
            return ['status'=>301, 'msg'=>'没有下注任务yyy'];
        }

        return ['status'=>200, 'data'=>$datas];
    }

}
