<?php
namespace common\service\jobs\telegram;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use GuzzleHttp\Client;
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
            $chat_id = $params['targetUser']; # 群/好友id
            if(empty($chat_id)){
                throw_info('群/好友id不能为空');
            }
            $config = \Yii::$app->params['TELEGRAM'];
            $client = new Client(['base_uri' => $config['API'].'/bot'.$params['token'].'/']); # 此处机器人token根据不同需要

            $response = $client->post('sendMessage', [
                'json' => [
                    'chat_id' => $params,
                    'text' => $params['text'], # 'Hello, this is a message from your bot!'
                ]
            ]);

            $body = $response->getBody();
            $content = Json::decode($body);

            Tool_Common::log('/tg_message/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'data'=>$content]);
        }catch (\Exception $e){
            Tool_Common::log('/tg_message/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'err_msg'=>$e->getMessage()]);
            throw_info($e->getMessage());
        }

        return 'TG消息发送完成[chat_id:'.$params['chat_id'].', name:'.$params['name'].']';
    }

}
