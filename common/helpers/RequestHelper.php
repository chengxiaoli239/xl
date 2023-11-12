<?php
namespace common\helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class RequestHelper
{
    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    
    public static function get($url, $headers = [])
    {
        $options = [
            'headers' => $headers,
            'timeout' => 10,
            'http_errors' => true,
        ];
        $result = (new Client())->get($url, $options)->getBody()->getContents();
        if (is_json($result)) {
            $result = json_decode($result, true);
        }

        return $result;
    }

    /**
     * GET请求
     * @param array|string $url     target URL.
     * @param array|string $data    if array - request data, otherwise - request content.
     * @param array        $headers request headers.
     * @param array        $options request options.
     * @return array
     */
    public static function apiGet($url, $data = null, $headers = [], $options = [])
    {
        return self::request('get', $url, $data, $headers, $options);
    }

    /**
     * POST请求
     * @param array|string $url     target URL.
     * @param array|string $data    if array - request data, otherwise - request content.
     * @param array        $headers request headers.
     * @param array        $options request options.
     * @return array
     */
    public static function post($url, $data = null, $headers = [], $options = []): array
    {
        return self::request('post', $url, $data, $headers, $options);
    }

    /**
     * Create and send an HTTP request.
     * @param string $method  HTTP method.
     * @param string $uri     URI object or string.
     * @param array|string $data    if array - request data, otherwise - request content.
     * @param array $headers request headers.
     * @param array $options Request options to apply. See \GuzzleHttp\RequestOptions.
     * @param int $retryCount  Retry count.
     * @return array
     */
    protected static function request(string $method, string $uri, $data = null, array $headers = [], array $options = [], int $retryCount = 3)
    {
        // p([$method, $uri, $data, $headers, $options]);
        $options = \common\helpers\ArrayHelper::merge(['timeout' => 30], $options);
        $maxReTryCount = max(1, $retryCount);

        $isGet = strtoupper($method) == strtoupper(RequestHelper::METHOD_GET);
        if (!empty($data)) {
            if ($isGet && !isset($options[RequestOptions::QUERY])) {
                $options[RequestOptions::QUERY] = $data;
            }
            if (!$isGet && !isset($options[RequestOptions::BODY])) {
                $headers = \common\helpers\ArrayHelper::merge([
                    'Content-Type' => 'application/json;charset=utf-8',
                ], $headers);
                $options[RequestOptions::BODY] = json_encode($data, 320);
            }
        }
        if (!empty($headers)) {
            $options[RequestOptions::HEADERS] = $headers;
        }
        
        $resp = null;
        for ($i = 0; $i < $maxReTryCount; $i++) {
            try {
                // GuzzleHttp\Psr7\Response
                $resp = (new Client())->request($method, $uri, $options);
                
                $statusCode = $resp->getStatusCode();
                $content = $resp->getBody()->getContents();
            } catch(ClientException $e) {
                $resp = $e->getResponse();
                $content = $resp->getBody()->getContents();
                
                $errorMsg = $e->getMessage();
                $code = $e->getCode();
            } catch(ServerException $e) {
                $resp = $e->getResponse();
                $content = $resp->getBody()->getContents();

                $errorMsg = $e->getMessage();
                $code = $e->getCode();
            } catch(\Exception $e) {
                $errorMsg = $e->getMessage();
                $code = $e->getCode();
                sleep(50);
            }

            if (!empty($content)) {
                break;
            }
        }
        
        return \common\helpers\ReturnHelper::result($code ?? 0, $errorMsg, $content ?? '');
    }

    /**
     * Create and send an HTTP request.
     * @param string $method  HTTP method.
     * @param string $uri     URI object or string.
     * @param array|string $data    if array - request data, otherwise - request content.
     * @param array $headers request headers.
     * @param array $options Request options to apply. See \GuzzleHttp\RequestOptions.
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function requestV2(string $method, string $uri, $data = null, array $headers = [], array $options = []): array
    {
        // p([$method, $uri, $data, $headers, $options]);
        $isGet = strtoupper($method) == strtoupper(RequestHelper::METHOD_GET);
        if (!empty($data)) {
            if ($isGet && !isset($options[RequestOptions::QUERY])) {
                $options[RequestOptions::QUERY] = $data;
                #$uri .= '?'.http_build_query($data);
            }
            if (!$isGet && !isset($options[RequestOptions::BODY])) {
                $options[RequestOptions::BODY] = json_encode($data, 320);
            }
        }
        
        $options[RequestOptions::HEADERS] = ArrayHelper::merge([
            'Content-Type' => 'application/json;charset=utf-8',
        ], $headers);

        $result = [];
        for ($i = 0; $i < 1; $i++) {
            try {
                // GuzzleHttp\Psr7\Response
                $resp = (new Client())->request($method, $uri, $options);
                
                $content = $resp->getBody()->getContents();
            } catch(\Exception $e) {
                $errorMsg = $e->getMessage();
                $code = $e->getCode();

                if (($e instanceof ClientException) || ($e instanceof ServerException)) {
                    $resp = $e->getResponse();
                    $content = $resp->getBody()->getContents();
                }
            }

            if (!empty($content)) {
                $result = Json::decode($content);
                break;
            }
            sleep(50);
        }

        if (!empty($errorMsg)) {
            throw_info($errorMsg, $code, $result);
        }
        
        return $result;
    }

}
