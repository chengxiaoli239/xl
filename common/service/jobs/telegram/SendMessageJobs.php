<?php
namespace common\service\jobs\telegram;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use yii\helpers\Json;

class SendMessageJobs extends CommonJob {

    public static function getName($params): string
    {
        self::$name = '101TG发送消息';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params): string
    {
        try {
            $config = \Yii::$app->params['TELEGRAM'];
            $chat_id = $params['chat_id']; # 群/好友id
            if(empty($chat_id)){
                throw_info('群/好友id不能为空');
            }
            $client = new Client();
            $headers = [
                'Content-Type' => 'application/json'
            ];
            $data = [
                "chat_id" => $params['chat_id'],
                'text' => $params['text'],
            ];
            $request = new Request('POST', $config['API'].'/bot'.$params['token'].'/sendMessage', $headers, Json::encode($data));
            $response = $client->sendAsync($request)->wait();

            $body = $response->getBody();
            $content = Json::decode($body);

            Tool_Common::log('/tg_message/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'config'=>$config, 'data'=>$content]);
        }catch (\Exception $e){
            Tool_Common::log('/tg_message/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'config'=>$config, 'err_msg'=>$e->getMessage()]);
            throw_info($e->getMessage());
        }

        return 'TG消息发送完成[chat_id:'.$params['chat_id'].', name:'.$params['name'].']';
    }

}
