<?php
namespace backend\service\clients;

use backend\models\BetErrorPlansTask;
use backend\models\DataDealStatus;
use backend\models\SscKjData;
use backend\models\TzSystems;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\BetService;
use backend\service\Lucky5\Lucky5Service;
use common\helpers\lottery\LotteryBet;
use common\helpers\LotteryType;
use common\kj\ssc\Lucky5;
use common\models\AdminModel;
use common\service\cache\CacheKeyService;
use common\service\CommonService;
use common\service\jobs\kj_data\GrabKjDatasJob;
use common\service\lottery\aozhou5\AoZhou5BetService;
use common\service\thirdD\CommonBaseService;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class TzSystemUsersService extends ClientsBaseService{
    public static $module_key = 'backend\models\TzSystemsUsers';
    const PlAN_TYPE_LOCAL = 'local';
    const PlAN_TYPE_RE_LOCAL = 're_local';

    const TZ_SYSTEM_TYPES_OPTIONS = [
        AdminModel::USER_TYPE_GUI_ALL => LotteryType::TZ_SYSTEM_TYPE_ID_AZ
    ];
    const LOTTERY_TYPES_OPTIONS = [
        AdminModel::USER_TYPE_GUI_ALL => LotteryType::AZ_LUCKY_5,
    ];

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
    public static function delTzSystemUserData(){
        $m = \Yii::$app->cache;
        $mkey = self::buildUserKey();

        $m->delete($mkey);
    }

    /**
     * @desc 获取系统授权access_tokens
     * @return array|mixed
     */
    public static function getAuthAccessTokens($isAuto=1){
        $m = \Yii::$app->cache;
        $mkey = 'user_getAuthAccessTokens';
        if($isAuto==2 OR !$access_tokens = $m->get($mkey)){
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

        try {
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
                    "v1" => "24",
                    "v2" => "125",
                ];
                $m->set($mkey, $data, 60);
            }
        }catch (\Exception $e){
            return ['status'=>300, 'data'=>[], 'msg'=>$e->getMessage()];
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
        if(true OR empty($TzSystemsUsers)){
            $TzSystemsUsers = TzSystemsUsers::findOne(['access_token'=>$access_token]);
            $m->set($mkey, $TzSystemsUsers, 10);
        }

        return $TzSystemsUsers;
    }

    public static function buildUserCookesKey($access_token=''){
        $mkey = 'buildUserCookesKey_2_'.$access_token;
        return $mkey;
    }

    /**
     * @desc 获取用户激活的计划ids
     * @param int $lottery_type
     * @return mixed|string
     */
    public static function getActiveQihao($lottery_type=DEFAULT_LOTTERY_TYPE, &$next_qihao=''){

        $m = \Yii::$app->cache;
        $mkey = self::buildActiveQihaoKey($lottery_type);
        $next_qihao = $m->get($mkey);

        $dateHI = date('H:i');
        if('09:00'<=$dateHI && $dateHI<='09:05' && $lottery_type==DEFAULT_LOTTERY_TYPE){
            return ['status'=>200, 'data'=>['next_qihao'=>date('Ymd').'109']];
        }

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

            $m->set($mkey, $next_qihao, 10);
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
     * @param string $current_qihao
     * @return string
     */
    public static function buildUserPlanTasksKey($access_token='', $current_qihao=''){
        $mkey = 'buildUserPlanTasksKey_1_'.$access_token.'_'.$current_qihao;
        return $mkey;
    }

    /**
     * @desc 客户端余额同步
     * @param string $access_token
     * @param float $balance
     */
    public static function syncClientBalance($access_token='', $balance=0.00){

        $flag = 1;
        try {
            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
            if(!empty($TzSystemsUsers)){
                $TzSystemsUsers->balance = $balance;
                $TzSystemsUsers->updated_at = time();
                $flag = $TzSystemsUsers->save();
                Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '客户端余额同步', ['account'=>$TzSystemsUsers->username, 'access_token'=>$access_token, 'balance'=>$balance]);
            }
        }catch (\Exception $e){
            return ['status'=>200, 'data'=>['flag'=>1], 'msg'=>'操作成功'];
        }

        return ['status'=>200, 'data'=>['flag'=>$flag], 'msg'=>'操作成功'];
    }

    /**
     * @desc 更新客户端用户robot_id
     * @param string $access_token
     * @param float $balance
     */
    public static function updateClientRobotId($access_token='', $err_msg=''): array
    {
        try {
            $flag = 0;
            $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token, $auto=2);
            if(!empty($TzSystemsUsers)){
                $newRobotId = Lucky5Service::getRobotIdByStr($err_msg, $TzSystemsUsers->ssc_domain);
                $cookie = $TzSystemsUsers->cookie; // = "NOTICE_LOGIN_IN=1;Akamai_Cookie=2618296842.12917.0000;ASP.NET_SessionId=2mx1bexbdo11lcfqmk1sdg01;robot7=wsWwPi0DaRyd+HRecIWxlszt90SoVxbjMZpTHl02OQiiMHa51PPbYloMxcux3rNBv2Nih/Hns/tWa/c/YPGOQQ==";
                preg_match("/robot7=([^\r\n]*)==/i", $cookie, $matches);
                if(!empty($matches[1]) && $newRobotId){
                    $new_cookie = str_replace('robot7='.$matches[1].'==', $newRobotId, $cookie);
                }elseif(strpos($cookie, 'robot7=') === false){
                    $new_cookie = str_replace(';;', ';', $cookie.';'.$newRobotId);
                }

                if(empty($new_cookie)){
                    throw_info('匹配cookie为空不能更新');
                }
                //p(['matches'=>$matches, 'newRobotId'=>$newRobotId, 'err_msg'=>$err_msg, 'cookie'=>$cookie, 'new_cookie'=>$new_cookie, 'newRobotId'=>$newRobotId]);
                $TzSystemsUsers->cookie = $new_cookie;

                $TzSystemsUsers->updated_at = time();
                $flag = $TzSystemsUsers->save();
                #p($TzSystemsUsers);
                Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '客户端余额同步', ['account'=>$TzSystemsUsers->username, 'access_token'=>$access_token, 'cookie'=>$cookie, 'new_cookie'=>$new_cookie]);
            }
        }catch (\Exception $e){
            Tool_Common::log('/client_xy/'.__FUNCTION__, 'INFO', '匹配替换cookie', ['matches'=>$matches, 'access_token'=>$access_token, 'cookie'=>$cookie, 'newRobotId'=>$newRobotId, 'new_cookie'=>$new_cookie]);
            return ['status'=>300, 'data'=>[], 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'data'=>['flag'=>$flag, 'cookie'=>$cookie, 'new_cookie'=>$new_cookie, 'new_robot_id'=>$newRobotId], 'msg'=>'操作成功'];
    }

    /**
     * @desc 客户端开奖数据同步
     * @param array $kjData
     * @param string $access_token
     * @param int $lottery_type
     * @return array
     */
    public static function syncClientKjDatas($kjData=[], $access_token='', $lottery_type=DEFAULT_LOTTERY_TYPE){

        try {
            $data = ['refresh'=>0, 'num'=>0];
            $now_time = date('Y-m-d H:i:s');
            $expect = $kjData['expect'] = trim($kjData['expect']);
            $kjData['opencode'] = trim($kjData['opencode']);
            $kjData['opentime'] = $kjData['opentime'] ? : $now_time;
            if(empty($kjData['expect'])){
                throw_info('开奖数据期号不能为空');
            }
            if(trim($kjData['expect']) == '期号'){
                throw_info('开奖数据期号异常：'.$kjData['expect']);
            }
            if(empty($kjData['opencode'])){
                throw_info('开奖数据不能为空');
            }
            $m = \Yii::$app->cache;
            $mkey = 'syncClientKjDatas_x0_'.$lottery_type.'_'.$expect;
            Lucky5::setKjDataCache($lottery_type, $expect, $kjData);

            if($m->get($mkey)){
                throw_info('15秒短时间不处理');
            }
            $m->set($mkey, 1, 15);

            $mcKey = 'mc_syncClientKjDatas_x0_'.$access_token.'_'.$lottery_type.'_'.$kjData['expect'];
            $num = \Yii::$app->redis->incr($mcKey);
            $SscKjData = SscKjData::findOne(['qihao'=>$expect, 'lottery_type'=>$lottery_type]);
            \Yii::$app->redis->expire($mcKey, 600);
            if(!empty($SscKjData)){
                $mcKey_0 = $mcKey.'_x0'; # 指导客户是否刷新网页缓存key
                $data['num'] = $num;
                if($num>5){
                    $rflag = $m->get($mcKey_0);
                    if($rflag && $num>14){
                        $data['refresh'] = 1;
                    }
                }else if($num>2){
                    $m->set($mcKey_0, 1, 300);
                    throw_info('已经开奖数据重复多次，忽略');
                }
                $minute_nums = substr($now_time, -5, -3);
                $minute_nums_d_1 = (int)$minute_nums % 5 - 1;
                if(!in_array($minute_nums_d_1, [0, 1])){
                    throw_info('非最新开奖，忽略处理');
                }
            }

            $params = [
                'lottery_type'=>$lottery_type,
                'qihao'=>$expect,
                'kj_data'=>$kjData,
                'title'=>BetService::getLotteryName($lottery_type).'_网盘推送',
                'business_id'=>$expect
            ];
            push_queue(GrabKjDatasJob::class, $params);
        }catch (\Exception $e){
            Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', '开奖数据同步异常', ['lottery_type'=>$lottery_type, 'kj_data'=>$kjData, 'err_msg'=>$e->getMessage()]);
            return ['status'=>301, 'data'=>$data, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'data'=>$data, 'msg'=>'数据同步成功'];
    }

    /**
     * @desc 获取用户激活的下注任务
     * @param string $access_token
     * @return mixed|string
     */
    public static function getActivePlanTasksBak($access_token='', $current_qihao='', $lottery_type=DEFAULT_LOTTERY_TYPE){

        $m = \Yii::$app->cache;
        $mkey = self::buildUserPlanTasksKey($access_token, $current_qihao);
        $flag = $m->get($mkey);

        $RedisLock = new RedisLock();
        $Rkey = $access_token.'_'.__FUNCTION__.'_redis';
        if($RedisLock->lock($Rkey.'_redis', 8)){
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
                    \Yii::$app->redis->expire($incr_qihao_key, 120);
                    if($count<=1){
                        $where = array_merge($where, [['=', 'qihao', (string)$current_qihao]]);
                    }
                }

                $BetErrorPlansTasks = BetErrorPlansTask::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(20)->all();
                Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-01', ['uid'=>$uid, 'current_qihao'=>$current_qihao, /*'where'=>$where,*/ 'count'=>$count]);
                if(empty($BetErrorPlansTasks)){
                    #Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-1', ['uid' => $uid, 'msg'=>'没有下注计划']);
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
                        "sec-ch-ua"=>'"Chromium";v="113", " Not A;Brand";v="24", "Google Chrome";v="113"',
                        "sec-ch-ua-mobile"=>"?0",
                        "sec-ch-ua-platform"=>'"Windows"',
                        "Sec-Fetch-Dest"=>"empty",
                        "Sec-Fetch-Mode"=>"cors",
                        "Sec-Fetch-Site"=>"same-origin",
                        'User-Agent' => trim(str_replace('User-Agent:', '', $TzSystemsUsers->user_agent)),
                        "X-Requested-With"=>"XMLHttpRequest"
                    ];

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

    /**
     * 校验
     * @param string $access_token
     * @return array
     */
    public static function validateAccount($access_token=''){
        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
        if(!empty($TzSystemsUsers->expire_time) && $TzSystemsUsers->expire_time <= time()){
            throw_info('已过期，请续费');
        }

        return [0, $TzSystemsUsers];
    }

    /**
     * 获取数据条件
     * @param string $uid
     * @param string $current_qihao
     * @param int $lottery_type
     * @return array
     */
    public static function getActivePlanTasksWhere($uid='', $current_qihao='', $direct=0, $lottery_type=DEFAULT_LOTTERY_TYPE){
        $RedisLock = new RedisLock();
        $where = ['AND', ['=', 'lottery_type', $lottery_type], ['IN', 'status', [0, 1]]]; # 可重推的状态0:未推送1异常可重复处理2推送成功3推送失败不可重推
        if($uid){
            $where = array_merge($where, [['=', 'uid', $uid]]);
        }
        if($direct){
            $where[] = ['=', 'bet_direct', $direct];
        }
        if(!empty($current_qihao)){
            $incr_qihao_key = 'incr_qihao_key_'.$lottery_type.'_'.$uid.'_'.$current_qihao;
            $count = $RedisLock->_redis->incrby($incr_qihao_key, 1);
            \Yii::$app->redis->expire($incr_qihao_key, 120);
            if($count<=1){
                $where = array_merge($where, [['=', 'qihao', (string)$current_qihao]]);
            }
        }
        Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-02', ['uid'=>$uid, 'current_qihao'=>$current_qihao, 'where'=>$where, 'count'=>$count]);

        return $where;
    }

    /**
     * @desc 获取用户激活的下注任务
     * @param string $access_token
     * @return mixed|string
     */
    public static function getActivePlanTasks($access_token='', $current_qihao='', $direct=0, $lottery_type=DEFAULT_LOTTERY_TYPE){

        try {
            list($code, $TzSystemsUsers) = TzSystemUsersService::validateAccount($access_token);
            $uid = $TzSystemsUsers->uid;

            if($lottery_type == LotteryType::AZ_LUCKY_5){
                # 澳洲五
                return AoZhou5BetService::getBetTasks($uid, $current_qihao);
            }
            $m = \Yii::$app->cache;
            $mkey = self::buildUserPlanTasksKey($access_token, $current_qihao);
            $flag = $m->get($mkey);
            if($flag){
                throw_info('没有任务yyy');
            }
            /*
            $status = (new LotteryBet())->checkLotteryStatus($lottery_type);
            $mKey = CacheKeyService::lotteryOpenSwitch($lottery_type);
            $switch = commonRedis()->get($mKey);
            if($status != LotteryBet::STATUS_START && !$switch){
                //throw_info('后台尚未开盘', CommonBaseService::CODE_FOR_USER);
            }
            Tool_Common::log('/lotteryBet/'.__FUNCTION__, 'INFO', '获取下注任务', ['username'=>$TzSystemsUsers->username, 'status'=>$status, 'switch'=>$switch]);
            */

            $HI = date('H:i:s');
            if('08:00:00'<$HI && $HI<'08:01:00'){
                throw_info('早盘开始晚一分钟下注');
            }

            $where = TzSystemUsersService::getActivePlanTasksWhere($uid, $current_qihao, $direct, $lottery_type);
            $BetErrorPlansTasksQuery = BetErrorPlansTask::find()->where($where);
            $BetErrorPlansTasks = $BetErrorPlansTasksQuery->orderBy(['bet_money'=>SORT_DESC, 'id'=>SORT_DESC])->limit(20)->all();
            /*
            $sql = $BetErrorPlansTasksQuery->createCommand()->getRawSql();
            $log = ['uid'=>$uid, 'current_qihao'=>$current_qihao, 'count'=>count($BetErrorPlansTasks),'sql'=>$sql];
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-0', $log);
            */
            if(empty($BetErrorPlansTasks)){
                throw_info('没有下注任务');
            }
            $data = [];
            $_t = round(microtime(true) * 1000);
            foreach ($BetErrorPlansTasks as $row){
                if($row->single<0.09) continue; // 异常倍数不下注
                $plan_id = $row->plan_id;
                $account = $row->account;
                $bet_url = $row->bet_url;
                $qihao = $row->qihao;
                if($qihao > $current_qihao){
                    continue;
                }
                $post_data = json_decode($row->post_datas, 320);
                $uid = $row->uid;
                $headers = [
                    "Accept"=>"application/json, text/javascript, */*; q=0.01",
                    "Accept-Encoding"=>"gzip, deflate, br, zstd",
                    "Accept-Language"=>"zh-CN,zh;q=0.9",
                    "Connection"=>"keep-alive",
                    //"Keep-Alive"=> "timeout=5, max=81",
                    'Content-Length' => (string)strlen(http_build_query($post_data)),
                    "Content-Type"=>"application/x-www-form-urlencoded; charset=UTF-8",
                    'Cookie' => $TzSystemsUsers->cookie,
                    'Origin' => trim($TzSystemsUsers->ssc_domain),
                    'Referer' => trim($TzSystemsUsers->ssc_domain).'/App/Index?_='.$_t,
                    'Host' => trim(str_replace('http://', '', str_replace('https:', 'http:', $TzSystemsUsers->ssc_domain))),
                    "sec-ch-ua"=>'"Chromium";v="127", " Not A;Brand";v="24", "Google Chrome";v="127"',
                    "sec-ch-ua-mobile"=>"?0",
                    "sec-ch-ua-platform"=>'"Windows"',
                    "Sec-Fetch-Dest"=>"empty",
                    "Sec-Fetch-Mode"=>"cors",
                    "Sec-Fetch-Site"=>"same-origin",
                    'User-Agent' => trim(str_replace('User-Agent:', '', $TzSystemsUsers->user_agent)),
                    "X-Requested-With"=>"XMLHttpRequest"
                ];

                $slow_seconds = BetService::getConfig('BET_SLOW_SECONDS'); # 下注延迟秒数设置
                $data[] = [
                    'task_id' => $row->id,
                    'bet_url' => $bet_url,
                    'bet_sort_key' => $row->bet_sort_key,
                    'plan_type' => self::PlAN_TYPE_RE_LOCAL,
                    'plan_id' => $plan_id,
                    'account' => $account,
                    'qihao' => $qihao,
                    'slow_seconds' => $slow_seconds,
                    'post_data' => $post_data,
                    'headers' => $headers,
                ];
            }
            $m->set($mkey, 1, 3);
        }catch (\Exception $e){
            Tool_Common::log('/repeatErrorBet/'.__FUNCTION__, 'ERR', '用户计划下注脚本-异常', ['username'=>$TzSystemsUsers->username, 'uid' => $uid, 'msg'=>$e->getMessage()]);
            return ['status'=>300, 'data'=>[], 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'data'=>$data??[], 'msg'=>'操作成功'];
    }

    /**
     * 获取站点
     * @param int $userType
     * @param int $useCache
     * @return array
     */
    public static function getSites(int $userType=0, int $useCache=1): array
    {
        $mKey = CacheKeyService::manageSites($userType);
        $data = commonRedis()->get($mKey);
        if(empty($data) OR !$useCache){
            $TzSystemTypeId = TzSystemUsersService::TZ_SYSTEM_TYPES_OPTIONS[$userType]??0;
            $TzSystemsQuery = TzSystems::find()->select(['id', 'name', 'ssc_domain', 'status', 'lottery_type', 'kj_num']);
            if(!empty($TzSystemTypeId)){
                $TzSystemsQuery->where(['system_type_id'=>$TzSystemTypeId]);
            }
            $data = $TzSystemsQuery->asArray()->all();
            commonRedis()->setex($mKey, 1800, $data);
        }

        return $data;
    }
}
