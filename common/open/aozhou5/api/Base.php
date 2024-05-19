<?php
namespace common\open\aozhou5\api;

use common\models\open\aozhou5\Aozhou5RequestLog;
use common\models\open\OutSiteRequestLog;
use common\models\open\SsxxRequestLog;
use common\open\thirdD\api\SiteOauthApi;
use common\open\OpenBase;
use common\service\jobs\log\ErrorLogStaticsJobs;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use yii\helpers\Json;

class Base extends OpenBase
{
    /**
     * @var string api请求地址
     */
    protected string $apiUrl = '';

    /**
     * @var string version
     */
    public $version = '1.0';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        #$this->apiUrl = 'http://af1.ssxx9999.com';
    }

    /**
     * 创建接口实例
     * @param  string $name api名称
     * @return Base
     */
    public static function createObject(string $name = ''): object
    {
        if (empty($name)) {
            $className = static::class;
        }

        return Yii::createObject($className);
    }

    /**
     * GET请求方法
     * @param  string $path    接口路径
     * @param  array  $params  请求参数
     * @param  array  $headers 请求头
     * @param  array  $options 请求选项
     * @return array
     */
    public function get( string $path, array $params = [], array $headers = [], array $options = []): array
    {
        #return $this->request('GET', $path, $params, $headers, $options);
        return $this->requestV2('GET', $path, $params, $headers, $options);
    }

    /**
     * POST 请求方法
     * @param  string $path    接口路径
     * @param  array  $params  请求参数
     * @param  array  $headers 请求头
     * @param  array  $options 请求选项
     * @return array
     */
    public function post( string $path, array $params = [], array $headers = [], array $options = [] ): array
    {
        #return $this->request('POST', $path, $params, $headers, $options);
        return $this->requestv2('POST', $path, $params, $headers, $options);
    }
    
    /**
     * 是否错误
     * @param array $data 接口响应内容
     * @return bool
     */
    public function isError(array $data): bool
    {
        if (empty($data) || $data['code'] != 10000) {
            return true;
        }
        return false;
    }

    /**
     * headers
     * @param array $headers  入参
     */
    protected function setHeaders(array &$headers)
    {
        $headers = ArrayHelper::merge([
            'Content-Type' => 'application/json;charset=utf-8',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        ], $headers);
    }

    /**
     * 请求方法
     * @param string $method  请求方法
     * @param array $params  请求参数
     * @param array $headers 请求头
     * @param array $options 请求选项
     * @return array|string|null
     */
    protected function request(string $method, $apiMethod, array $params = [], array $headers = [], array $options = []): ?array
    {
        $now = microtime(true) * 10000;
        $url = sprintf('%s%s', $this->apiUrl, $apiMethod);
        // 请求参数
        $this->setHeaders($headers);

        try {
            $client = new Client();
            $params['timeout'] = 10;
            $options = array_merge(['headers' => $headers], $params);
            #p([$method, $url, $options]);
            $response = $client->request($method, $url, $options);

            if($apiMethod == SiteOauthApi::API_LOGIN_PAGE){
                # 1、初始获取cookie
                $responseHeaders = $response->getHeaders();
                $cookie = self::getCookie($responseHeaders);

                return $cookie ? ['cookie'=>$cookie] : [];
            }
            $content = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();  // 获取成功响应的状态码
            //p(['content'=>$content]);

            if (!empty($content)) {
                if(is_json($content)){
                    $result = Json::decode($content);
                }else{
                    $result = ['content'=>$content];
                }
            }else{
                throw_info('异常', 30000);
            }

            Tool_Common::log('/aozhou5/request', 'INFO', '接口请求', ['url'=>$url, 'req'=>$params, 'statusCode'=>$statusCode, 'result'=>$result]);
            $status = OutSiteRequestLog::REQUEST_STATUS_SUCCESS;
            return $result;
        } catch(\Exception $e) {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
            //$statusCode = $response->getStatusCode();  // 获取成功响应的状态码
            Tool_Common::log('/aozhou5/request', 'ERR', '接口请求-异常', ['url'=>$url, 'req'=>$params, 'result'=>$result ?? [], 'code'=>$code, 'content'=>$content, 'msg'=>$errorMsg, 'statusCode'=>$statusCode]);
            $status = OutSiteRequestLog::REQUEST_STATUS_FAIL;
            push_queue(ErrorLogStaticsJobs::class, ['err_msg'=>$errorMsg, 'statusCode'=>$statusCode, 'result'=>$result ?? [], 'url'=>$url, 'req'=>$params]);
            if (($e instanceof \common\exceptions\InfoException)) {
                $result = $e->data;
            }

            throw_info($errorMsg, $code);
        } finally {
            $status = $status ?? OutSiteRequestLog::REQUEST_STATUS_FAIL;
            $endtime = microtime(true) * 10000;
            self::resetResult($result);
            // 记录请求日志
            $result = (is_array($result)) ? Json::encode($result) : '';
            $logData = [
                'send_time' => (int)($now / 10000),
                'api_method' => $apiMethod,
                'response_micro_time' => (int)(($endtime - $now) / 10),
                'param' => Json::encode($params),
                'response_data' => $result,
                'headers' => Json::encode($headers),
                'request_method' => $method,
                'full_url' => $url,
                'status' => $status,
                'remark' => $errorMsg ?? '',
            ];
            Aozhou5RequestLog::find()->createCommand()->insert(Aozhou5RequestLog::tableName(), $logData)->execute();
        }
    }

    /**
     * 请求方法
     * @param string $method  请求方法
     * @param array $params  请求参数
     * @param array $headers 请求头
     * @param array $options 请求选项
     * @return array|string|null
     */
    protected function requestV2(string $method, $apiMethod, array $params = [], array $headers = [], array $options = []): ?array
    {
        $now = microtime(true) * 10000;
        $url = sprintf('%s%s', $this->apiUrl, $apiMethod);
        // 请求参数
        $this->setHeaders($headers);

        try {
            $client = new Client();
            $params['timeout'] = 10;
            $options = array_merge(['headers' => $headers], $params);
            #p([$method, $url, $options]);
            $request = new Request($method, $url, $headers);
            $response = $client->sendAsync($request, $options)->wait();
            $content = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();  // 获取成功响应的状态码
            //p(['content'=>$content]);

            if (!empty($content)) {
                if(is_json($content)){
                    $result = Json::decode($content);
                }else{
                    $result = ['content'=>$content];
                }
            }else{
                throw_info('异常', 30000);
            }

            Tool_Common::log('/aozhou5/'.__FUNCTION__, 'INFO', '接口v2请求', ['url'=>$url, 'options'=>$options, 'headers'=>$headers, 'req'=>$params, 'statusCode'=>$statusCode, 'result'=>$result]);
            $status = OutSiteRequestLog::REQUEST_STATUS_SUCCESS;
            return $result;
        } catch(\Exception $e) {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
            //$statusCode = $response->getStatusCode();  // 获取成功响应的状态码
            Tool_Common::log('/aozhou5/'.__FUNCTION__, 'ERR', '接口请求-异常', ['url'=>$url, 'req'=>$params, 'result'=>$result ?? [], 'code'=>$code, 'content'=>$content, 'msg'=>$errorMsg, 'statusCode'=>$statusCode]);
            $status = OutSiteRequestLog::REQUEST_STATUS_FAIL;
            push_queue(ErrorLogStaticsJobs::class, ['err_msg'=>$errorMsg, 'statusCode'=>$statusCode, 'result'=>$result ?? [], 'url'=>$url, 'req'=>$params]);
            if (($e instanceof \common\exceptions\InfoException)) {
                $result = $e->data;
            }

            throw_info($errorMsg, $code);
        } finally {
            $status = $status ?? OutSiteRequestLog::REQUEST_STATUS_FAIL;
            $endtime = microtime(true) * 10000;
            self::resetResult($result);
            // 记录请求日志
            $result = (is_array($result)) ? Json::encode($result) : '';
            $logData = [
                'send_time' => (int)($now / 10000),
                'api_method' => $apiMethod,
                'response_micro_time' => (int)(($endtime - $now) / 10),
                'param' => Json::encode($params),
                'response_data' => $result,
                'headers' => Json::encode($headers),
                'request_method' => $method,
                'full_url' => $url,
                'status' => $status,
                'remark' => $errorMsg ?? '',
            ];
            Aozhou5RequestLog::find()->createCommand()->insert(Aozhou5RequestLog::tableName(), $logData)->execute();
        }
    }

    private static function resetResult(&$result)
    {
        if(is_array($result) && isset($result['username'])){
            $result = [
                'username'=>$result['username'],
                'credits'=>$result['credits'],
                'credits_use'=>$result['credits_use'],
                'credits_remaining'=>$result['credits_remaining'],
                's'=>$result['s'],
                'm'=>$result['m'],
            ];
        }
    }

    /**
     * @param $responseHeaders
     * @return mixed|string
     */
    public static function getCookie($responseHeaders){

        $parsedCookie = '';
        if (isset($responseHeaders['Set-Cookie'])) {
            $setCookieHeader = $responseHeaders['Set-Cookie'];
            $parsedCookie = explode(';', $setCookieHeader[0])[0];
        }
        return $parsedCookie;
    }

}
