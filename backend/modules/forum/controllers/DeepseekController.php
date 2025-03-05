<?php
namespace backend\modules\forum\controllers;

use common\service\deepSeek\ChatService;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class DeepseekController extends Controller
{
    public function actionChat()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $message = Yii::$app->request->post('message');
        
        // TODO: 调用DeepSeek API
        // 这里需要实现与DeepSeek的实际集成
        $response = $this->callDeepSeekAPI($message);
        
        return [
            'status' => 200,
            'message' => $response['reply'],
            'suggestions' => $response['suggestions'] ?? []
        ];
    }

    private function callDeepSeekAPI($message)
    {
        // 实现DeepSeek API调用
        $answer = (new ChatService())->chat($message);
        // 返回示例数据结构
        return [
            'reply' => '根据您的需求，我为您推荐以下方案：',
            'answer' => $answer,
            'suggestions' => [
                [
                    'name' => '三字定位计划',
                    'winRate' => 85,
                    'tz_type' => 2,
                    'playway' => 2,
                    'single' => 1,
                    'plan_type' => 1,
                    // 其他计划参数
                ]
            ]
        ];
    }
}