<?php

namespace common\service\open\aozhou5;

use common\exceptions\InfoException;
use common\open\aozhou5\yiFanApi\UserApi;
use common\service\cache\CacheKeyService;
use common\service\chat\Tool_Common;
use common\service\lottery\aozhou5\AoZhou5BetService;
use common\service\open\ActionBaseService;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use yii\helpers\Json;

class ActionYIFanService extends ActionBaseService
{
    const GAME_INDEX_AZ5 = 16;
    const GAME_INDEX_AZ5_NEW = 18;
    const GAME_INDEX_OPTIONS = [
        self::GAME_INDEX_AZ5 => '澳洲五',
        self::GAME_INDEX_AZ5_NEW => '新澳洲五',
    ];
    const PLAY_TYPE_LIANG_MIAN = 8;
    const PLAY_TYPE_DAN_MA = 9;
    const PLAY_TYPE_1_BALL = 2;
    const PLAY_TYPE_2_BALL = 3;
    const PLAY_TYPE_3_BALL = 4;
    const PLAY_TYPE_4_BALL = 5;
    const PLAY_TYPE_5_BALL = 6;
    const PLAY_TYPE_FAN_TAN = 10;
    const PLAY_TYPE_OPTIONS = [
        self::PLAY_TYPE_LIANG_MIAN => '两面盘',
        self::PLAY_TYPE_DAN_MA => '单码1-5',
        self::PLAY_TYPE_1_BALL => '第一球',
        self::PLAY_TYPE_2_BALL => '第二球',
        self::PLAY_TYPE_3_BALL => '第三球',
        self::PLAY_TYPE_4_BALL => '第四球',
        self::PLAY_TYPE_5_BALL => '第五球',
        self::PLAY_TYPE_FAN_TAN => '番摊',
    ];

    public string $userAgent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
    public string $securityCode='686855';
    public $line_number = 3;# 这里假设线路号是5

    public function __construct($tzSystemUsers)
    {
        parent::__construct($tzSystemUsers);
        $this->line_number = self::getLineNumber();
    }

    /**
     * 获取用户数据
     * @param int $isAuto
     * @return array
     * @throws InfoException
     */
    public function getUserData(int $isAuto=1): array
    {
        try {
            $userInfo = $this->getUserInfo();
        }catch (\Exception $e){}

        if($isAuto==2 OR empty($tzSystemsUser->cookie) OR empty($userInfo)){
            $this->login();
            $userInfo = $this->getUserInfo();
        }

        return [0, $userInfo, '操作成功'];
    }

    public function login($isAuto=1): array
    {
        try {
            //$params = ['search'=>$this->securityCode];
            //$search = UserApi::searchLine($this->domain, $params);
            // 创建 CookieJar 来存储 cookie
            $cookieJar = new CookieJar();
            $parsed_url = parse_url($this->domain); # Array ( [scheme] => https [host] => ac3868.com )
            //p(['search'=>$search, 'parsed_url'=>$parsed_url]);

            if ($parsed_url && isset($parsed_url['host'])) {
                $host = $parsed_url['host'];
                // 提取域名（例如：ac3868.com）
                $domain = explode('.', $host)[1]; // 提取第二个部分，即域名

                // 第一个请求的 URL
                $firstUrl = "{$parsed_url['scheme']}://url{$this->line_number}.{$host}/member/#/";
                // 第二个请求的 URL
                $secondUrl = "{$parsed_url['scheme']}://url{$this->line_number}.{$host}/api/code";
                // 第二个请求的 URL
                $thirdUrl = "{$parsed_url['scheme']}://url{$this->line_number}.{$host}/api/";
                //p([$firstUrl, $secondUrl, $thirdUrl]);
            } else {
                Tool_Common::log('/aozhou5/'.__FUNCTION__, 'ERR', '登录异常', ['domain'=>$this->domain, 'parsed_url'=>$parsed_url]);
                return [];
            }

            // 创建 Guzzle 客户端
            $client = new Client(['cookies' => $cookieJar]);

            $now_time = time();

            // 设置请求头，包括 Cookie
            $headers = [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
                'User-Agent' => $this->userAgent,
                'Referer' => $firstUrl, // 使用第一个请求的 URL 作为 Referer
            ];

            // 发起第一个 GET 请求
            #$response1 = $client->request('GET', $firstUrl, [
            #    'headers' => $headers,
            #]);
            #p($response1);

            $headers2 = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 '
            ];
            // 发起第二个 GET 请求
            $response2 = $client->request('GET', $secondUrl, [
                'headers' => $headers2,
            ]);
            // 获取响应内容
            $body = $response2->getBody()->getContents();
            $code = $body;

            $params[RequestOptions::FORM_PARAMS] = [
                '__' => 'memberLogin',
                'userName' => $this->account,
                'password' => $this->password,
                'validateCode' => $code,
                'host' => "url{$this->line_number}.{$host}"
            ];
            //$requestBody = http_build_query($params[RequestOptions::FORM_PARAMS]);
            $params[RequestOptions::HEADERS] = $headers;
            //$params['verify'] = false;
            # 发起第三个 POST 请求
            $response3 = $client->request('POST', $thirdUrl, $params);
            $body = $response3->getBody()->getContents();
            //p(['thirdUrl'=>$thirdUrl, 'params'=>$params, 'headers'=>$headers3, 'body'=>$body]);

            // 获取响应中设置的 Cookie start
            $setCookieHeaders = $response3->getHeader('Set-Cookie');
            $cookieStr = trim(explode(';', $setCookieHeaders[1])[0]);
            $this->tzSystemUsers->cookie = $cookieStr;
            $this->tzSystemUsers->updated_at = time();
            $this->tzSystemUsers->user_agent = 'User-Agent: '.$this->userAgent;
            $this->tzSystemUsers->save();
            // 获取响应中设置的 Cookie end

            $result = Json::decode($body);

            $params[RequestOptions::FORM_PARAMS] = [
                '__' => 'memberInitialization',
                'cbk' => AoZhou5BetService::getCbk($this->tzSystemUsers->cookie),
            ];
            $requestBody = http_build_query($params[RequestOptions::FORM_PARAMS]);
            $headers['Content-Length'] = strlen($requestBody);
            $params[RequestOptions::HEADERS] = $headers;
            $response4 = $client->request('POST', $thirdUrl, $params);
            $body = $response4->getBody()->getContents();
            $result4 = Json::decode($body);
            Tool_Common::log('/aozhou5/'.__FUNCTION__, 'ERR', '登录结束', ['params'=>$params, 'code'=>$code, 'result'=>$result, 'cookieStr'=>$cookieStr, 'result4'=>$result4]);
        }catch (\Exception $e){
            return [10001, $e->getMessage()];
        }

        try {
            $userInfo = $this->getUserInfo();
        }catch (\Exception $e){}

        if($isAuto==2 OR empty($tzSystemsUser->cookie) OR empty($userInfo)){
            $this->login();
            $userInfo = $this->getUserInfo();
        }

        return $result;
    }

    /**
     * 获取用户信息
     * @param int $useCache
     * @return array
     * @throws InfoException
     */
    public function getUserInfo($useCache=0): array
    {
        $parsed_url = parse_url($this->domain); # Array ( [scheme] => https [host] => ac3868.com )
        $params = [
            '__' => 'memberGame',
            'gIndex' => self::GAME_INDEX_AZ5,
            'type' => 10,
            'rebate' => 1,
            '__CBK' => self::getCbk($this->tzSystemUsers->cookie),
        ];
        $host = $parsed_url['host'];
        $headers = [
            'cookie' => $this->tzSystemUsers->cookie,
            'User-Agent' => str_replace('User-Agent:', '', $this->tzSystemUsers->user_agent),
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cookie' => 'rebate=1',
            'referer' => 'https://vip3.vvp138.com/Member/?__CBK=%20pemANRohcqGsrQsTGqjM767NtuRqFOSSzXc2fDlbGE=',
        ];
        $url = "https://vip{$this->line_number}.{$host}";
        //p(['url'=>$url, 'params'=>$params, 'headers'=>$headers]);

        $mKey = CacheKeyService::userSiteInfo($this->tzSystemUsers->uid).'_'.$useCache;
        $userInfo = commonRedis()->get($mKey);
        if(!empty($userInfo)){
            return $userInfo;
        }

        $userInfo = UserApi::getUserInfo($url, $params, $headers);
        Tool_Common::log('/aozhou5/'.__FUNCTION__, 'INFO', '获取用户信息', ['username'=>$this->tzSystemUsers->username, 'account'=>$this->tzSystemUsers->account, 'balance'=>$userInfo['balance'], 'params'=>$params, 'headers'=>$headers]);
        if(!isset($userInfo['balance'])){
            Tool_Common::log('/aozhou5/'.__FUNCTION__, 'INFO', '获取用户信息-异常', ['username'=>$this->tzSystemUsers->username, 'account'=>$this->tzSystemUsers->account, 'balance'=>$userInfo['balance'], 'userInfo'=>$userInfo, 'headers'=>$headers, 'url'=>$url]);
            return [];
        }
        $cacheTime = $useCache ? 15 : 3;
        commonRedis()->setex($mKey, $cacheTime, $userInfo);
        $this->tzSystemUsers->balance = $userInfo['kymoney'];
        $this->tzSystemUsers->updated_at = time();
        $this->tzSystemUsers->save();

        return ($userInfo && empty($userInfo['error']))? $userInfo:[];
    }

    /**
     * 获取盘口报表信息
     * @return array
     */
    public function getSiteStatics(): array
    {
        $parsed_url = parse_url($this->domain); # Array ( [scheme] => https [host] => ac3868.com )
        $params = [
            '__' => 'historicalReportDate',
            'cbk' => AoZhou5BetService::getCbk($this->tzSystemUsers->cookie),
        ];
        $host = $parsed_url['host'];
        $url = "https://url{$this->line_number}.{$host}";
        $headers = [
            'cookie' => $this->tzSystemUsers->cookie,
        ];
        $staticsInfo = UserApi::siteCommonApi($url, $params, $headers);

        return $staticsInfo;
    }

    /**
     * 获取盘口报表信息
     * @return array
     */
    public function getSiteStaticsInfo(): array
    {
        return $this->getSiteStatics()?:[];
    }

    public function getLineNumber()
    {
        $mKey = CacheKeyService::getLineNumber($this->tzSystemUsers->username);
        if(in_array($this->tzSystemUsers->username, ['aa30301', 'aa301'])){
            $lineNumber = commonRedis()->get($mKey);
        }

        return $lineNumber ? : $this->line_number;
    }

    /**
     * 请求参数cbk获取
     * @param string $cookies
     * @return mixed|string
     */
    public static function getCbk(string $cookies='')
    {
        $cookiesArr = explode(';', $cookies);
        foreach ($cookiesArr as $value){
            list($key, $value) = explode('=', trim($value));
            if(strlen($value)>32){
                $cbk = $value;
            }
        }
        if(empty($cbk)){
            $cbk = explode('=', trim($cookies))[1];
        }

        return $cbk;

    }

}