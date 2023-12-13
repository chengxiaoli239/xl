<?php
namespace common\open\thirdD;

use common\models\open\SsxxRequestLog;
use common\open\thirdD\api\SiteOauthApi;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use common\helpers\RequestHelper;
use yii\helpers\Json;

class SxThirdDBase extends Component
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
     * @return SxThirdDBase|objec`t
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
        return $this->request('GET', $path, $params, $headers, $options);
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
        return $this->request('POST', $path, $params, $headers, $options);
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
            p(['content'=>$content]);
            if($apiMethod == SiteOauthApi::API_GET_CAPTCHA){
                $fileContent = $content;
                return ['fileContent'=>$fileContent];
            }
            #if($apiMethod == SiteOauthApi::API_ACTION_LOGIN){
            #    $result = $content;
            #    #d(['headers'=>$headers, 'result'=>$result]);
            #}

            if (!empty($content)) {
                $result = Json::decode($content);
            }else{
                throw_info('异常', 30000);
            }

            Tool_Common::log('/out_site/request', 'ERR', '接口请求', ['url'=>$url, 'req'=>$params, /*'result'=>$result*/]);
            $status = SsxxRequestLog::REQUEST_STATUS_SUCCESS;
            return $result;
        } catch(\Exception $e) {
            $status = SsxxRequestLog::REQUEST_STATUS_FAIL;
            $errorMsg = $e->getMessage();
            $code = $e->getCode();
            \common\open\thirdD\api\SiteOrderApi::pushToLog(['err_msg'=>$errorMsg]);

            if (($e instanceof \common\exceptions\InfoException)) {
                $result = $e->data;
            }

            self::resetResult($result);
            Tool_Common::log('/out_site/request', 'ERR', '接口请求', ['url'=>$url, 'req'=>$params, /*'data'=>$result ?? [],*/ 'code'=>$code, 'msg'=>$errorMsg ]);
            throw_info($errorMsg, $code);
        } finally {
            $status = $status ?? SsxxRequestLog::REQUEST_STATUS_FAIL;
            $endtime = microtime(true) * 10000;
            // 记录请求日志
            $result = $result ? Json::encode($result) : '';
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
            SsxxRequestLog::find()->createCommand()->insert(SsxxRequestLog::tableName(), $logData)->execute();
        }
    }

    private static function resetResult(&$result): array
    {
        if(is_array($result)){
            $result = [
                'username'=>$result['username'],
                'credits'=>$result['credits'],
                'credits_use'=>$result['credits_use'],
                'credits_remaining'=>$result['credits_remaining'],
                's'=>$result['s'],
                'm'=>$result['m'],
            ];
        }

        return $result;
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
