<?php

namespace common\service\deepSeek;

use common\service\thirdD\CommonBaseService;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use yii\helpers\Json;

class ChatService extends CommonBaseService
{
    const API_URL = 'https://api.deepseek.com';

    public function chat($message)
    {
        $deepSeekApiKey = 'sk-a58645a3baf24acc9b274387c42c3148';

        // 获取推荐
        $recommendationResponse = $this->sendDeepSeekRequest(self::API_URL.'/recommend', 'POST', ['limit' => 10], ['Authorization' => 'Bearer ' . $deepSeekApiKey, 'Content-Type' => 'application/json']);
        $recommendations = $recommendationResponse['items'];

        // 获取问答
        $qaResponse = $this->sendDeepSeekRequest(self::API_URL.'/qa', 'POST', ['question' => $message], ['Authorization' => 'Bearer ' . $deepSeekApiKey, 'Content-Type' => 'application/json']);
        $answer = $qaResponse['answer'];

        // 显示结果
        echo "Recommendations:\n";
        print_r($recommendations);

        echo "\n\nAnswer to the question '什么是推荐系统？':\n";
        echo $answer . "\n";

        return $answer;
    }

    /**
     * 请求deepSeek接口
     * @param $url
     * @param $method
     * @param $data
     * @param $headers
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendDeepSeekRequest($url, $method = 'GET', $data = [], $headers = []) {
        $client = new Client();

        $response = $client->request($method, $url, [
            'headers' => $headers,
            'json' => $data,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

}
