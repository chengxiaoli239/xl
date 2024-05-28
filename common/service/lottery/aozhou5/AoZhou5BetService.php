<?php

namespace common\service\lottery\aozhou5;

use backend\models\DataDealStatus;
use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\models\wechat\Bets;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\BetService;
use common\helpers\lottery\DrawLottery;
use common\helpers\RequestHelper;
use common\models\wechat\WechatUser;
use common\open\aozhou5\api\OrderApi;
use common\open\aozhou5\api\UserApi;
use common\service\CommonService;
use common\service\open\ActionBaseService;
use common\service\open\aozhou5\ActionService;
use common\service\ssc\QihaoService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\jobs\SsxxBetJobs;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\Odds3dService;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use yii\helpers\Json;

class AoZhou5BetService extends CommonBaseService
{
    # 推送ID
    const PUSH_ID_FIVE = 8;
    const PUSH_ID_FOUR = 9;
    const TEST_BET_ID = 33159;

    const PUSH_ID_OPTIONS = [
        DrawLottery::BET_FOUR_NUM => AoZhou5BetService::PUSH_ID_FOUR,
        #DrawLottery::BET_FOUR_NUM => AoZhou5BetService::PUSH_ID_FIVE,
        DrawLottery::BET_FIVE_NUM => AoZhou5BetService::PUSH_ID_FIVE,
    ];

    # 盘口信息
    public static array $siteSystemInfo = [];
    # 本地对盘口 玩法ID
    public static array $localToSiteMethodInfo = [];
    public static array $platformUser = [];
    public static function preBetValidate($betRowId): array
    {
        try {
            $betRow = Bets::findOne($betRowId);
            return [0, ['betRow'=>$betRow], '校验成功']; # 测试
            $wechatUser = WechatUser::findOne($betRow->wechat_user_id);
            $lottery_type = $betRow->lottery_type;
            $qihao = $betRow->qihao;
            $codeStr = CommonService::getAwardNumberByQihao($qihao, $lottery_type);
            switch (true){
                case $betRow->status == CommonBaseService::STATUS_LT_CANCEL:
                    throw_info('已是撤单状态，无需推送盘口', SsxxBetJobs::INVALID_STATUS_CODE);
                case $wechatUser->is_chi == 1:
                    throw_info('该用户私下吃，无需推送盘口', SsxxBetJobs::INVALID_STATUS_CODE);
                case !empty($codeStr):
                    throw_info('已开奖期号['.$lottery_type.'_'.$qihao.']，禁止推送盘口', SsxxBetJobs::INVALID_STATUS_CODE);
                default:
                    break;
            }
        }catch (\Exception $e){
            return [$e->getCode(), [], $e->getMessage()];
        }

        return [0, ['betRow'=>$betRow], '校验成功'];
    }

    /**
     * 推向盘口
     * @param int $betRowId
     * @return array
     */
    public static function postToSite(int $betRowId): array
    {
        if(empty($betRowId)){
            return [];
        }
        try {
            list($code, $data, $msg) = AoZhou5BetService::preBetValidate($betRowId);
            if($code>0){
                throw_info($msg, $code);
            }
            $betRow = $data['betRow']; # object
            $lottery_type = $betRow->lottery_type;
            $qiHao = $betRow->qihao;
            $user_id = $betRow->user_id;
            $method_id = $betRow->play_method;
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推盘口', ['betRowId'=>$betRow->id, 'lottery_type'=>$lottery_type, 'method_id'=>$method_id]);

            self::$siteSystemInfo = CommonBaseService::getSystemBaseInfo($user_id, $lottery_type); # 盘口信息
            self::$localToSiteMethodInfo = CommonBaseService::getLocalToSiteMethods($method_id, self::$siteSystemInfo['system_type_id'], $betRow->codes, $betRow->kj_num); #
            self::$platformUser = WechatUser::find()->where(['id'=>$betRow->wechat_user_id])->asArray()->limit(1)->one();

            Tool_Common::log('/betSite/'.__FUNCTION__, 'INFO', '盘口信息', [
                'method_id'=>$method_id,
                'siteSystemInfo'=>self::$siteSystemInfo,
                'localToSiteMethodInfo'=>self::$localToSiteMethodInfo,
            ]);
            $betCodes = $betRow->codes;
            //p(['method_id'=>$method_id, 'betCodes'=>$betCodes, 'siteSystemInfo'=>self::$siteSystemInfo, 'localToSiteMethodInfo'=>self::$localToSiteMethodInfo]);
            $postRst = self::postBet($betRow, $betCodes); # 投盘口

            # 下单扣减
            AgentUsersBalanceService::updateBalance((string)$betRowId, $betRow->bet_money, $betRow->wechat_user_id, WechatUserService::TYPE_ORDER_BET);
            $resultData = ['betRowId'=>$betRow->id, 'betQiHao'=>$qiHao, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'postRst'=>$postRst, 'err_msg'=>'处理结束'];
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '推送盘口处理结束99', $resultData);
            var_dump(date('Y-m-d H:i:s ').'处理成功：betRowId:'.$betRow->id.'_method_id:'.$method_id);
        }catch (\Exception $e){
            $err_msg = $e->getMessage();
            $logArr = ['betRowId'=>$betRow->id, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'errCode'=>$e->getCode(), 'err_msg'=>$err_msg.$e->getFile().$e->getLine()];
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '推送盘口处理异常11', $logArr);
            var_dump($err_msg);
            $betRow->push_status = BetsBackend::PUSH_STATUS_CANNOT;
            $betRow->push_desc = $err_msg;
            $betRow->save();
            //throw_info($e->getMessage(), $e->getCode());
            return [10004, $logArr, $err_msg];
        }

        $betRow->push_status = BetsBackend::PUSH_STATUS_SUCCESS;
        $betRow->save();


        return [0, ['resultData'=>$resultData], '推送成功'];
    }

    /**
     * 一码定位号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetOneFixed($dataStr): string
    {
        $codeDatas = explode(MethodMatchService::ZU_SPLIT_FLAG, $dataStr);

        $dataCodes = [];
        foreach ($codeDatas as $codeStr){
            //if(!preg_match_all('/[百十个](\d)/u', str_replace(':','',$datas), $mc)) continue;
            $datas = explode(':', $codeStr);
            switch(true){
                case $datas[0] == '百':
                    for ($i=0; $i<strlen($datas[1]); $i++){
                        $dataCodes[] = $datas[1][$i] . 'XX';
                    }
                    break;
                case '十':
                    for ($i=0; $i<strlen($datas[1]); $i++) {
                        $dataCodes[] = 'X' . $datas[1][$i] . 'X';
                    }
                    break;
                case '个':
                    for ($i=0; $i<strlen($datas[1]); $i++) {
                        $dataCodes[] = 'XX'.$datas[1][$i];
                    }
                    break;
                default:
                    throw_info('匹配异常11');
                    break;
            }
        }
        $dataStr = implode(',', $dataCodes);

        return $dataStr;
    }

    /**
     * 二码定位号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetTwoFixed($dataStr): string
    {
        $datas = explode(',', $dataStr);
        $codeDatas = [];
        $first = explode(':', $datas[0]);
        $codeDatas[$first[0]] = $first[1];

        $second = explode(':', $datas[1]);
        $codeDatas[$second[0]] = $second[1];
        #p($codeDatas, 0);
        $datas = [];
        switch (true){
            case isset($codeDatas['百']) && isset($codeDatas['十']):
                for ($i=0; $i<strlen($codeDatas['百']); $i++){
                    for ($j=0; $j<strlen($codeDatas['十']); $j++){
                        $datas[] = $codeDatas['百'][$i].$codeDatas['十'][$j].'X';
                    }
                }
                break;
            case isset($codeDatas['百']) && isset($codeDatas['个']):
                for ($i=0; $i<strlen($codeDatas['百']); $i++){
                    for ($j=0; $j<strlen($codeDatas['个']); $j++){
                        $datas[] = $codeDatas['百'][$i].'X'.$codeDatas['个'][$j];
                    }
                }
                break;
            case isset($codeDatas['十']) && isset($codeDatas['个']):
                for ($i=0; $i<strlen($codeDatas['十']); $i++){
                    for ($j=0; $j<strlen($codeDatas['个']); $j++){
                        $datas[] = $codeDatas['十'][$i].'X'.$codeDatas['个'][$j];
                    }
                }
                break;
        }


        $dataStr = implode(',', $datas);

        return $dataStr;
    }

    /**
     * 定位直选复式号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetOneFixedZhiXuanFuShi($dataStr): string
    {
        # 福[定位直选复式] => 百:34578,十:34569,个:23467  =>	375.00元
        //p($dataStr);
        $datas = explode(',', $dataStr);
        $codeDatas = [];
        $first = explode(':', $datas[0]);
        $codeDatas[$first[0]] = $first[1];

        $second = explode(':', $datas[1]);
        $codeDatas[$second[0]] = $second[1];

        $third = explode(':', $datas[2]);
        $codeDatas[$third[0]] = $third[1];
        //p([$dataStr, $codeDatas]);
        $datas = [];
        for ($i=0; $i<strlen($codeDatas['百']); $i++){
            for ($j=0; $j<strlen($codeDatas['十']); $j++){
                for ($k=0; $k<strlen($codeDatas['个']); $k++) {
                    $datas[] = $codeDatas['百'][$i] . $codeDatas['十'][$j] . $codeDatas['个'][$k];
                }
            }
        }

        $dataStr = implode(',', $datas);

        return $dataStr;
    }

    /**
     * 全倒 号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetOneQuanDao($dataStr): string
    {
        # 全倒 => 459 678  =>	元
        return self::extracted($dataStr);
    }

    /**
     * 直选复式号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetOneZhiXuanFuShi($dataStr): string
    {
        # 福[直选复式] => 45678  =>	60.00元
        return self::extracted($dataStr);
    }


    private static function postBet(object $betRow, $betCodes=''): bool
    {
        $betRowId = $betRow->id; # 记录ID
        $method_id = $betRow->play_method; # 玩法ID
        $user_id = $betRow->user_id; # 用户ID
        $lottery_type = $betRow->lottery_type; # 彩种类型

        $site = self::$siteSystemInfo;
        $methodData = self::$localToSiteMethodInfo;

        //p(['site'=>$site, 'methodData'=>$methodData, 'betRow'=>$betRow]);
        $codes = str_replace(MethodMatchService::ZU_SPLIT_FLAG, ',', $betCodes);
        if(empty($codes) && $codes !== '0'){
            throw_info('推送盘口异常:号码为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        if(!isset($Odds['odds'])){
            throw_info('赔率获取异常user_id:'.$betRow->user_id.'_play_method:'.$betRow->play_method);
        }
        list($currentQiHao, $nextQiHao) = QihaoService::getKjQiHao(self::LOTTERY_TYPE_AOZHOU5);
        if($betRow->qihao != $nextQiHao){
            throw_info("下注期号异常{$betRow->qihao} != $nextQiHao");
        }
        $TzSystemUsers = TzSystemsUsers::findOne($site['id']);
        $headers = [
            'cookie' => $TzSystemUsers->cookie,
            "User-Agent" =>"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36","X-Requested-With" => "XMLHttpRequest"
        ];
        $postData1 = [
            '__'=>'isAutoOdds',
            'gameId'=>601,
            'rebate' => 'A',
            'data' => [
                [$methodData['site_method_id'], $Odds['odds'], (string)floatval($betRow->bet_money)], // 赔率待处理
            ],
            #'cbk' => '0a2016edb310cd7c3a6afae7ee88ed8077d9aa29853867b9b9e0e735eaf8bb470fcc5bc44796ce782116ccb2ab2631ae08fa23f414c7e6c6',
            'cbk' => explode('=', trim($site['cookie']))[1],
        ];
        $objectClass = ActionBaseService::getClass($site['system_type_id']);
        $objectClass->domain = $site['ssc_domain'];
        $objectClass->tzSystemUsers = $TzSystemUsers;
        $parsed_url = parse_url($site['ssc_domain']); # Array ( [scheme] => https [host] => ac3868.com )
        $url = "https://url{$objectClass->line_number}.{$parsed_url['host']}";
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推盘口下注01', [
            'url' => $url,
            'headers' => $headers,
            'postData1' => $postData1,
            'nextQiHao' => $nextQiHao,
            'system_type_id' => $site['system_type_id'],
        ]);

        #$result1 = OrderApi::push($url, $postData1, $headers);
        if(!empty($result1['error'])){
            throw_info($result1['error']??'推送盘口异常1', 30001);
        }

        $postData2 = [
            '__'=>'bettingSingle',
            'data' => Json::encode([
                'gameId'=>601,
                'pusId' => self::PUSH_ID_OPTIONS[$objectClass->tzSystemUsers->kj_num],
                'openingNum' => (int)$nextQiHao,
                'rebate' => 'A',
                'data' => [
                    [$methodData['site_method_id'], $Odds['odds'], (string)floatval($betRow->bet_money)], // 赔率待处理
                ]
            ]),
            #'cbk' => explode('=', trim($site['cookie']))[1],
            'cbk' => trim($site['cookie']),
        ];
        $headers = array_merge($headers, [
            'Origin' => "https://url{$objectClass->line_number}.{$parsed_url['host']}",
            'Referer' => "https://url{$objectClass->line_number}.{$parsed_url['host']}/member/",
            //'User-Agent' => trim(str_replace('User-Agent:', '', $TzSystemUsers->user_agent)),
        ]);
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推网盘10', [
            'betRowId'=>$betRowId,
            'user_id'=>$user_id,
            'headers' => $headers,
            'method_id'=>$method_id,
            'methodData'=>$methodData,
            'post_data2'=>$postData2,
        ]);
        #$result2 = OrderApi::pushBettingSingleA($url, $postData2, $headers2);

        $apiUrl = $url . '/api/';
        // 创建 CookieJar 来存储 cookie
        $cookieJar = new CookieJar();
        // 创建 Guzzle 客户端
        $client = new Client(['cookies' => $cookieJar]);
        // 第一个请求的 URL
        $firstUrl = $apiUrl;

        // 发起第一个 GET 请求
        $response1 = $client->request('POST', $firstUrl, [
            RequestOptions::HEADERS => $headers,
            RequestOptions::FORM_PARAMS => $postData1,
        ]);
        $body = $response1->getBody()->getContents();
        $result1 = Json::decode($body);

        // 获取响应头中的 Set-Cookie
        $setCookie = $response1->getHeader('Set-Cookie');

        // 提取需要的 Cookie
        $cookie = reset($setCookie); // 获取第一个 Set-Cookie

        sleep(2);
        // 第二个请求的 URL
        $apiUrl = $url . '/api/';
        // 发起第二个 GET 请求
        $response2 = $client->request('POST', $apiUrl, [
            RequestOptions::HEADERS => $headers,
            RequestOptions::FORM_PARAMS => $postData2,
        ]);

        // 获取响应内容
        $body = $response2->getBody()->getContents();
        $result2 = Json::decode($body);
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推网盘10', [
            'betRowId'=>$betRowId,
            'user_id'=>$user_id,
            'apiUrl'=>$apiUrl,
            'headers' => $headers,
            'method_id'=>$method_id,
            'methodData'=>$methodData,
            'post_data1'=>$postData1,
            'post_data2'=>$postData2,
            'lottery_type'=>$lottery_type,
            'result1'=>$result1,
            'result2'=>$result2,
        ]);
        if(!empty($result2['error'])){ # 错误码：2成功、9918 登录超时....
            $logArr['result'] = $result2;
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推网盘20', $logArr);
            throw_info($result2['error']??'推送盘口异常2', 30002);
        }
        $objectClass->getUserInfo(1); # 同步余额
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推网盘30', ['result2'=>$result2]);

        return true;
    }

    public static function postRecordToSite(): array
    {
        $where = [
            'AND',
            ['=', 'push_status', BetsBackend::PUSH_STATUS_FAIL],
            ['>=', 'created_at', time()-1800]
        ];
        $BetsQuery = BetsBackend::find()->select(['id', 'order_id'])->where($where)->limit(100)->orderBy(['id'=>SORT_DESC]);
        //$sql = $BetsQuery->createCommand()->getRawSql();var_dump($sql);
        $Bets = $BetsQuery->asArray()->all();
        foreach ($Bets as $bet){
            try {
                $result = AoZhou5BetService::postToSite($bet['id']);
                Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '异常数据补上盘', ['id'=>$bet['id'], 'order_id'=>$bet['order_id'], 'result'=>$result]);
            }catch (\Exception $e){
                Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '异常数据补上盘-异常', ['id'=>$bet['id'], 'order_id'=>$bet['order_id'], 'err_msg'=>$e->getMessage()]);
            }
        }
        var_dump('执行结束 '.date('Y-m-d H:i:s').' 补打'.count($Bets).'条');

        return [0, '操作成功'];
    }

    /**
     * @param $dataStr
     * @return string
     */
    public static function extracted($dataStr): string
    {
        $dataStrs = explode(MethodMatchService::ZU_SPLIT_FLAG, $dataStr);

        $datas = [];
        foreach ($dataStrs as $dataStr) {
            $len = strlen($dataStr);
            for ($i = 0; $i < $len; $i++) {
                for ($j = 0; $j < $len; $j++) {
                    if ($j == $i) continue;
                    for ($k = 0; $k < $len; $k++) {
                        if ($j == $k or $i == $k) continue;
                        $datas[] = $dataStr[$i] . $dataStr[$j] . $dataStr[$k];
                    }
                }
            }
        }
        $dataStr = implode(',', $datas);
        return $dataStr;
    }

    /**
     * 获取下注任务
     * @param $id
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function getBetTasks($currentQiHao='', $id=0): array
    {
        $betTasksQuery = BetsBackend::find();

        if(!empty($id)){
            $betTasksQuery->where(['id'=>$id, 'push_status'=>BetsBackend::PUSH_STATUS_WAIT]);
        }else{
            $betTasksQuery->where(['push_status'=>BetsBackend::STATUS_WAIT, 'qihao'=>$currentQiHao]);
        }
        $betTasks = $betTasksQuery->asArray()->all();
        $data = [];
        $betType = BetService::getConfig('aozhou5_bet_type')??BetsBackend::BET_TYPE_API; # 下注方式：1接口2模拟操作
        foreach ($betTasks as $betRow){
            $Odds = Odds3dService::getOdds($betRow['user_id'], $betRow['play_method']); # 玩法赔率
            if(!isset($Odds['odds'])){
                throw_info('赔率获取异常user_id:'.$betRow['user_id'].'_play_method:'.$betRow['play_method']);
            }

            #$method_id = $betRow['play_method'];
            #p([$method_id, $betRow]);
            $siteSystemInfo = CommonBaseService::getSystemBaseInfo($betRow['user_id'], $betRow['lottery_type']); # 盘口信息
            $methodData = CommonBaseService::getLocalToSiteMethods($betRow['play_method'], $siteSystemInfo['system_type_id'], $betRow['codes'], $betRow['kj_num']); #
            $TzSystemUsers = TzSystemsUsers::findOne(['uid'=>$betRow['user_id']]);
            $cookie = explode('=', trim($TzSystemUsers->cookie))[1];
            $postData1 = [
                '__'=>'isAutoOdds',
                'gameId'=>601,
                'rebate' => 'A',
                'data' => [
                    [$methodData['site_method_id'], $Odds['odds'], (string)floatval($betRow['bet_money'])], // 赔率待处理
                ],
                'cbk' => $cookie,
            ];
            $ht = explode('//', $TzSystemUsers->ssc_domain);

            $d = 'url'.ActionService::LINE_NUMBER.'.'.$ht[1];
            $headers = [
                ':authority' => $d,
                ':method' => 'POST',
                ':path' => '/api/',
                ':scheme' => 'https',
                'accept' => '*/*',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
                'Accept-Language' => 'zh-CN,zh;q=0.9',
                'Cookie' => $TzSystemUsers->cookie,
                "Content-Type" => "application/x-www-form-urlencoded",
                'Content-Length' => (string)strlen(http_build_query($postData1)),
                'origin' => 'https://'.$d,
                'priority' => 'u=1, i',
                'referer' => 'https://'.$d.'/member/',
                'sec-ch-ua' => '"Google Chrome";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => "Windows",
                'sec-fetch-dest' => 'empty',
                'sec-fetch-mode' => 'cors',
                'sec-fetch-site' => 'same-origin',
                'User-Agent' => trim(str_replace('User-Agent:', '', $TzSystemUsers->user_agent)),
            ];

            if($id == AoZhou5BetService::TEST_BET_ID){
                $betRow['qihao'] = DataDealStatus::find()->select('next_qihao')->where(['lottery_type'=>28])->limit(1)->orderBy(['id'=>SORT_DESC])->scalar();
            }
            $postData2 = [
                '__'=>'bettingSingle',
                'data' => Json::encode([
                    'gameId'=>601,
                    'pusId' => self::PUSH_ID_OPTIONS[$betRow['kj_num']],
                    'openingNum' => (int)$betRow['qihao'],
                    'rebate' => 'A',
                    'data' => [
                        [$methodData['site_method_id'], $Odds['odds'], (string)floatval($betRow['bet_money'])], // 赔率待处理
                    ]
                ]),
                'cbk' => $cookie,
            ];

            $headers2 = array_merge($headers, [
                'Content-Length' => (string)strlen(http_build_query($postData2)),
            ]);

            $oneBetData = [
                'plan_id' => $betRow['id'],
                'qihao' => $betRow['qihao'],
                'betType' => $betType, # 1接口2模拟操作
                'slow_seconds' => 30,
            ];
            if($betType == BetsBackend::BET_TYPE_SELENIUM){
                $oneBetData = array_merge($oneBetData, [
                    'method' => $Odds['name'],
                    'bet_money' => (int)$betRow['bet_money'],

                    'code' => trim(str_replace(['角', '番'], '', $betRow['codes'])),
                ]);
            }else{
                $oneBetData = array_merge($oneBetData, [
                    'headers1' => $headers,
                    'postData1' => $postData1,
                    'headers2' => $headers2,
                    'postData2' => $postData2,
                ]);

            }

            $data[] = $oneBetData;
        }

        return ['status'=>200, 'data'=>$data, 'msg'=>'操作成功'];
    }
}
