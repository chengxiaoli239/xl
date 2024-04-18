<?php
# 开彩网
namespace common\kj\ssc;
use backend\models\SscKjData;
use backend\models\TzSystemsUsers;
use common\helpers\LotteryType;
use common\kj\BaseKj;
use common\open\aozhou5\api\UserApi;
use common\service\open\ActionBaseService;
use common\service\ssc\QihaoService;
use common\service\ssc\SscKjDataService;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use yii\helpers\Json;

class Aozhou extends BaseKj {
    public static int $lottery_type = LotteryType::AZ_LUCKY_5;

    /**
     * @desc 澳洲幸运五，官网：https://1680632.com/view/aozxy5/ssc_index.html
     * @param string $returnType
     * @param int $is_auto
     * @return array|bool
     * @throws GuzzleException
     */
    public static function getLucky5(string $returnType = 'json', $is_auto = 1){
        try {
            $lottery_type = self::$lottery_type;
            $kjData = self::getCurrentKjData($lottery_type, $currentQiHao);

            $SscKjData = SscKjDataService::getKjData($lottery_type, $currentQiHao);
            if(!empty($SscKjData) && ((time()-self::LIMIT_GRAB_TIME)<$SscKjData['created_at'])){
                $kjData = ['expect'=>$SscKjData['qihao'], 'opencode'=>$SscKjData['code_str'], 'opentime'=>date('Y-m-d H:i:s', $SscKjData['created_at'])];
                throw_info('3分钟内新数据不需再次抓取'.$currentQiHao);
            }

            list($currentQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
            if($is_auto==2 OR !$kjData) {
                try {
                    $domain = BaseKj::getApiHostByRoute('/kj/aozhou/lucky5');

                    // 创建 CookieJar 来存储 cookie
                    $cookieJar = new CookieJar();
                    // 创建 Guzzle 客户端
                    $client = new Client(['cookies' => $cookieJar]);
                    // 第一个请求的 URL
                    $firstUrl = $domain.'/view/aozxy5/ssc_index.html';

                    $now_time = time();
                    // 第二个请求的 URL
                    $secondUrl = $domain.'/api/CQShiCai/getBaseCQShiCaiList.do?lotCode=10010';

                    // 设置请求头，包括 Cookie
                    $headers = [
                        'Accept' => 'application/json, text/javascript, */*; q=0.01',
                        'Accept-Encoding' => 'gzip, deflate, br, zstd',
                        'path' => '/api/CQShiCai/getBaseCQShiCaiList.do?lotCode=10010',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                        'Referer' => $firstUrl, // 使用第一个请求的 URL 作为 Referer
                        #'Cookie' => $cookie, // 使用从第一个响应中提取的 Cookie
                        'Cookie' => '_ga_8GZN0CR7N8=GS1.1.'.$now_time.'.1.0.'.$now_time.'.0.0.0', // 使用从第一个响应中提取的 Cookie
                    ];

                    // 发起第二个 GET 请求
                    $response = $client->request('GET', $secondUrl, [
                        'headers' => $headers,
                    ]);

                    // 获取响应内容
                    $body = $response->getBody()->getContents();
                    $content = Json::decode($body);
                    if($content['errorCode']==0 && $content['result']['businessCode']==0){
                        $kjData = $content['result']['data'][0];
                        $qihao = $kjData['preDrawIssue'];
                        $opencode = $kjData['preDrawCode'];
                    }else{
                        throw_info($content['message']??'查询异常');
                    }

                    $kjData = ['expect'=>$qihao, 'opencode'=>$opencode, 'opentime'=>$kjData['preDrawTime']];
                    Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据网盘抓取-正常', ['lottery_type'=>self::$lottery_type, LotteryType::getName($lottery_type), 'domain'=>$domain, 'kjData'=>$kjData, /*'headers'=>$headers*/]);
                }catch (\Exception $e){
                    Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', '开奖数据网盘获取-异常', ['lottery_type'=>self::$lottery_type,'name'=>LotteryType::getName($lottery_type), 'err_msg'=>$e->getMessage()]);
                }
            }else{
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', LotteryType::getName($lottery_type).'数据抓取-缓存', ['lottery_type'=>self::$lottery_type, LotteryType::getName($lottery_type), 'cq'=>$currentQiHao, 'kjData'=>$kjData, 'is_auto'=>$is_auto]);
            }
        }catch (\Exception $e){
            //$kjData = self::getCurrentKjData($lottery_type);
            Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', LotteryType::getName($lottery_type).'数据抓取-异常', ['lottery_type'=>self::$lottery_type, 'cq'=>$currentQiHao, 'currentQiHao'=>$currentQiHao, 'kjData'=>$kjData, 'err_msg'=>$e->getMessage()]);
        }
        if(empty($kjData)){
            return false;
        }
        return self::extracted($kjData, $lottery_type, $returnType, $is_auto);
    }

    /**
     * @desc 澳洲幸运五，盘口
     * @param string $returnType
     * @param int $is_auto
     * @return array|bool
     * @throws GuzzleException
     */
    public static function getSiteLucky5(string $returnType = 'json', $is_auto = 1){
        try {
            $lottery_type = self::$lottery_type;
            list($code, $currentQiHao, $kjData, $msg) = BaseKj::checkHasOpened($lottery_type);
            if($code>0){
                throw_info($msg);
            }

            list($currentQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
            $where = ['AND', ['=', 'status',1], ['>', 'balance', 0],['=', 'tz_system_id', 19], ['!=', 'ssc_domain', '']];
            $TzSystemsUser = TzSystemsUsers::find()->where($where)->limit(1)->one();

            $objectClass = ActionBaseService::getClass($TzSystemsUser->tz_system_id);
            $objectClass->domain = $TzSystemsUser->ssc_domain;
            $objectClass->tzSystemUsers = $TzSystemsUser;
            $parsed_url = parse_url($objectClass->domain); # Array ( [scheme] => https [host] => ac3868.com )
            $cookie = explode('=', $objectClass->tzSystemUsers->cookie)[1];
            $params = [
                #'__' => 'lotteryRecord', #'memberoddsdata',
                '__' => 'memberoddsdata',
                'gameId' => 601,
                'pusId' => 8,
                'tId' => 1,
                'pId' => -1,
                'rebate' => 'A',
                'cbk' => $cookie,
            ];
            $host = $parsed_url['host'];
            $headers = [
                'User-Agent' => str_replace('User-Agent:', '', $objectClass->tzSystemUsers->user_agent),
                'cookie' => $objectClass->tzSystemUsers->cookie,
                'origin' => "https://url{$objectClass->line_number}.{$host}",
                'referer' => "https://url{$objectClass->line_number}.{$host}/member/",
                'sec-ch-ua' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
                'Origin' => "{$parsed_url['scheme']}://url{$objectClass->line_number}.{$host}",
                'Referer' => "{$parsed_url['scheme']}://url{$objectClass->line_number}.{$host}/member/",
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
                'sec-fetch-dest' => 'empty',
                'sec-fetch-mode' => 'cors',
                'sec-fetch-site' => 'same-origin',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];
            $url = "https://url{$objectClass->line_number}.{$host}";
            $lotteryInfo = UserApi::getLotteryRecord($url, $params, $headers);
            $siteData = $lotteryInfo['drawList'][0]??[];

            $kjData = ['expect'=>$siteData['drawNumber'], 'opencode'=>implode(',', $siteData['drawNumber']), 'opentime'=>$siteData['drawTime']];
            Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据网盘抓取-正常', ['lottery_type'=>$lottery_type, LotteryType::getName($lottery_type), 'kjData'=>$kjData, 'lotteryInfo'=>$lotteryInfo]);
        }catch (\Exception $e){
            Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', '开奖数据网盘获取-异常', ['lottery_type'=>$lottery_type,'name'=>LotteryType::getName($lottery_type), 'err_msg'=>$e->getMessage()]);
        }
        if(empty($kjData)){
            return false;
        }
        return self::extracted($kjData, $lottery_type, $returnType, $is_auto);
    }

    /**
     * @desc 澳洲幸运五，内部
     * @param string $returnType
     * @return array|bool
     */
    public static function getLucky5Out(string $returnType = 'json', $is_auto = 1){
        try {
            $lottery_type = self::$lottery_type;

            $kjData = self::getCurrentKjData($lottery_type, $currentQiHao);
            $SscKjData = SscKjDataService::getKjData($lottery_type, $currentQiHao);
            if(!empty($SscKjData) && ((time()-self::LIMIT_GRAB_TIME)<$SscKjData['created_at'])){
                $kjData = ['expect'=>$SscKjData['qihao'], 'opencode'=>$SscKjData['code_str'], 'opentime'=>date('Y-m-d H:i:s', $SscKjData['created_at'])];
                throw_info('3分钟内新数据不需再次抓取');
            }

            list($currentQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
            if($is_auto==2 OR !$kjData) {
                try {
                    $domain = BaseKj::getApiHostByRoute('/kj/aozhou/lucky5-out');

                    $client = new Client();
                    $request = new Request('POST', $domain.'/kj/aozhou/lucky5-out');
                    $response = $client->sendAsync($request)->wait();

                    // 获取响应内容
                    $body = $response->getBody()->getContents();
                    $content = Json::decode($body);
                    if(empty($content['expect'])){
                        throw_info($content['message']??'查询异常');
                    }

                    $kjData = $content;
                    Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据网盘抓取-正常', ['lottery_type'=>self::$lottery_type, LotteryType::getName($lottery_type), 'domain'=>$domain, 'kjData'=>$kjData]);
                }catch (\Exception $e){
                    Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', '开奖数据网盘获取-异常', ['lottery_type'=>self::$lottery_type,'name'=>LotteryType::getName($lottery_type), 'err_msg'=>$e->getMessage()]);
                }
            }else{
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', LotteryType::getName($lottery_type).'数据抓取-缓存', ['lottery_type'=>self::$lottery_type, LotteryType::getName($lottery_type), 'cq'=>$currentQiHao, 'kjData'=>$kjData, 'is_auto'=>$is_auto]);
            }
        }catch (\Exception $e){
            Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', LotteryType::getName($lottery_type).'数据抓取-异常', ['lottery_type'=>self::$lottery_type, 'cq'=>$currentQiHao, 'currentQiHao'=>$currentQiHao, 'kjData'=>$kjData, 'err_msg'=>$e->getMessage()]);
        }
        if(empty($kjData)){
            return false;
        }
        return self::extracted($kjData, $lottery_type, $returnType, $is_auto);
    }
}
