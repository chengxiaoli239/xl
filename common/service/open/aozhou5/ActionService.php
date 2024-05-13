<?php

namespace common\service\open\aozhou5;

use common\open\aozhou5\api\UserApi;
use common\service\cache\CacheKeyService;
use common\service\chat\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use yii\helpers\Json;

class ActionService
{
    public string $domain;
    public string $account;
    public string $password;
    public object $tzSystemUsers;
    public string $userAgent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
    public string $securityCode='fa8888';
    public int $line_number = 5; # 这里假设线路号是5
    public function setDomain()
    {

    }
    public function login()
    {
        $params = ['search'=>$this->securityCode];
        $search = UserApi::searchLine($this->domain, $params);

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
            return false;
        }

        // 创建 Guzzle 客户端
        $client = new Client(['cookies' => $cookieJar]);

        $now_time = time();
        //LoginApi::login($params);

        // 设置请求头，包括 Cookie
        $headers = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'User-Agent' => $this->userAgent,
            'Referer' => $firstUrl, // 使用第一个请求的 URL 作为 Referer
            #'Cookie' => $cookie, // 使用从第一个响应中提取的 Cookie
        ];

        // 发起第二个 GET 请求
        $response = $client->request('GET', $secondUrl, [
            'headers' => $headers,
        ]);

        // 获取响应内容
        $body = $response->getBody()->getContents();
        $code = $body;

        $params[RequestOptions::FORM_PARAMS] = [
            '__' => 'memberLogin',
            'userName' => $this->account,
            'password' => $this->password,
            'validateCode' => $code,
            'host' => "url{$this->line_number}.{$host}"
        ];
        $requestBody = http_build_query($params[RequestOptions::FORM_PARAMS]);
        $headers = [
            'Accept' => '*/*',
            'Accept-Encoding' => 'deflate',
            'Accept-Language' => 'zh-CN,zh;q=0.9',
            'Connection' => 'keep-alive',
            'Content-Length' => strlen($requestBody),
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cookie' => 'code='.$code,
            //'Host' => "url{$line_number}.{$host}",
            'Origin' => "{$parsed_url['scheme']}://url{$this->line_number}.{$host}",
            'Referer' => "{$parsed_url['scheme']}://url{$this->line_number}.{$host}/member/",
            'Sec-Ch-Ua' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'User-Agent' => $this->userAgent,
        ];
        $params[RequestOptions::HEADERS] = $headers;
        $params['verify'] = false;
        //p(['thirdUrl'=>$thirdUrl, 'params'=>$params]);
        $response = $client->request('POST', $thirdUrl, $params);
        $body = $response->getBody()->getContents();

        // 获取响应中设置的 Cookie start
        $setCookieHeaders = $response->getHeader('Set-Cookie');
        $cookieStr = trim(explode(';', $setCookieHeaders[1])[0]);
        $this->tzSystemUsers->cookie = $cookieStr;
        $this->tzSystemUsers->updated_at = time();
        $this->tzSystemUsers->user_agent = 'User-Agent: '.$this->userAgent;
        $this->tzSystemUsers->save();
        // 获取响应中设置的 Cookie end

        $result = Json::decode($body);
        Tool_Common::log('/aozhou5/'.__FUNCTION__, 'ERR', '登录结束', ['params'=>$params, 'code'=>$code, 'result'=>$result, 'cookieStr'=>$cookieStr]);

        return $result;
    }

    /**
     * 获取用户信息
     * @return bool
     */
    public function getUserInfo($useCache=0): array
    {
        $parsed_url = parse_url($this->domain); # Array ( [scheme] => https [host] => ac3868.com )
        $cookie = explode('=', $this->tzSystemUsers->cookie)[1];
        $params = [
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
            'User-Agent' => str_replace('User-Agent:', '', $this->tzSystemUsers->user_agent),
            'cookie' => $this->tzSystemUsers->cookie,
            'origin' => "https://url{$this->line_number}.{$host}",
            'referer' => "https://url{$this->line_number}.{$host}/member/",
            'sec-ch-ua' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
            'Origin' => "{$parsed_url['scheme']}://url{$this->line_number}.{$host}",
            'Referer' => "{$parsed_url['scheme']}://url{$this->line_number}.{$host}/member/",
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $url = "https://url{$this->line_number}.{$host}";

        $mKey = CacheKeyService::userSiteInfo($this->tzSystemUsers->uid).'_'.$useCache;
        $userInfo = commonRedis()->get($mKey);
        if(!empty($userInfo)){
            return $userInfo;
        }
        $userInfo = UserApi::getUserInfo($url, $params, $headers);
        Tool_Common::log('/aozhou5/'.__FUNCTION__, 'INFO', '获取用户信息', ['username'=>$this->tzSystemUsers->username, 'account'=>$this->tzSystemUsers->account, 'balance'=>$userInfo['balance'], 'userInfo'=>$userInfo]);
        if(!isset($userInfo['balance'])){
            Tool_Common::log('/aozhou5/'.__FUNCTION__, 'INFO', '获取用户信息-异常', ['username'=>$this->tzSystemUsers->username, 'account'=>$this->tzSystemUsers->account, 'balance'=>$userInfo['balance'], 'userInfo'=>$userInfo, 'headers'=>$headers, 'url'=>$url]);
            return [];
        }
        $cacheTime = $useCache ? 15 : 3;
        commonRedis()->setex($mKey, $cacheTime, $userInfo);
        $this->tzSystemUsers->balance = $userInfo['balance'];
        $this->tzSystemUsers->updated_at = time();
        $this->tzSystemUsers->save();

        return ($userInfo && empty($userInfo['error']))? $userInfo:[];
    }
}