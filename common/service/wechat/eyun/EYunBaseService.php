<?php

namespace common\service\wechat\eyun;

use common\models\Base;
use common\models\BaseService;
use common\service\chat\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use yii\helpers\Json;

class EYunBaseService  extends BaseService
{
    # 微信原始id （首次登录平台的号传""，掉线后必须传值，否则会频繁掉线！！！） 第三步会返回此字段，记得入库保存
    public $wcId = '';
    # 用户需安装app/pc，且上传app/pc中的字段,若是开发者公司有app/pc也可直接集成sdk至app/pc中，可以做到无需用户上传，且无需下载我司提供的软件
    public $ttuid = '';
    # 接口域名
    public $base_url = '';
    # e云平台账号
    public $account = '';
    # e云平台密码
    public $password = '';

    public function __construct($config = [])
    {
        if(empty($config)){
            $c = \Yii::$app->params['E_YUN'];
            $config = [
                'base_url' => $c['BASE_URL'],
                'account' => $c['ACCOUNT'],
                'password' => $c['PASSWORD'],
                'ttuid' => $c['TTUID'],
            ];
            $this->base_url = $config['base_url'];
            $this->ttuid = $config['ttuid'];
            $this->account = $config['ttuid'];
            $this->password = $config['password'];
        }
        parent::__construct($config);
    }

    public function memberLogin(){
        $url = $this->base_url . '/member/login';
        $client = new Client();
        $params = [
            'account' => $this->account,
            'password' => $this->password,
        ];
        p(['url'=>$url, 'params'=>$params]);
        $response = $client->request('POST', $url, [
            RequestOptions::HEADERS   => [
                'Content-Type' => 'application/json; charset=utf8',
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1'
            ],
            RequestOptions::BODY => Json::encode($params),
        ]);


        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            \Yii::error('Http请求接口出错：' . $response->getReasonPhrase());
            return false;
        }
        $body = $response->getBody()->getContents();
        $response = Json::decode($body) ?: false;

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', 'e云接口请求', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }
}
