<?php
namespace common\open\aozhou5\yiFanApi;

use common\open\aozhou5\api\Base;
use common\tools\Tool_Common;
use GuzzleHttp\RequestOptions;


class UserApi extends Base
{
    // 下单订单
    const API_SEARCH_LINE = '/user-search-result.aspx';
    const API_USER_INFO = '/totaldata/action.ashx';

    /**
     * 搜索线路
     * @param string $domain
     * @param array $params 参数
     * @param array $headers
     * @return array
     */
    public static function searchLine(string $domain, array $params, array $headers=[]): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;

        $headers = array_merge([
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            //"X-Requested-With"=> "XMLHttpRequest",
            'Upgrade-Insecure-Requests' => 1,
        ], $headers);
        $data[RequestOptions::QUERY] = $params;
        #$data['verify'] = false;
        //$data['port'] = 38900;
        $result = $object->get(self::API_SEARCH_LINE, $data, $headers);
        //p([$domain, $params, $headers, $result]);

        return $result;
    }

    /**
     * 用户信息
     * @param string $domain
     * @param array $params 参数
     * @param array $headers
     * @return array
     */
    public static function getUserInfo(string $domain, array $params, array $headers=[]): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;

        $headers = array_merge([
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            "X-Requested-With"=> "XMLHttpRequest",
        ], $headers);
        $data[RequestOptions::FORM_PARAMS] = $params;
        $data['verify'] = false;
        $result = $object->post(self::API_USER_INFO, $data, $headers);
        Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '获取用户信息', [
            'apiUrl' => $object->apiUrl,
            'headers' => $headers,
            'postData' => $params,
            'balance' => $result['balance'],
        ]);

        return $result;
    }

    /**
     * 用户信息
     * @param string $domain
     * @param array $params 参数
     * @param array $headers
     * @return array
     */
    public static function siteCommonApi(string $domain, array $params, array $headers=[]): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;

        $headers = array_merge([
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            "X-Requested-With"=> "XMLHttpRequest",
        ], $headers);
        $data[RequestOptions::FORM_PARAMS] = $params;
        $data['verify'] = false;
        $result = $object->post(self::API_USER_INFO, $data, $headers);

        return $result;
    }

}
