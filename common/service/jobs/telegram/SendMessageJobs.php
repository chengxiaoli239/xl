<?php
namespace common\service\jobs\telegram;

use backend\service\BetService;
use common\open\telegram\api\MessageApi;
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
            $date = date('Y-m-d H:i:s');
            $switch = BetService::getConfig('telegram_send_message_status')??0;
            if(!$switch && $date<'2024-04-27:17:30:00'){
                return '频次太高暂停发送';
            }
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
                'text' => $params['content'],
                'token' => $params['token'],
            ];
            $s1 = microtime(true);
            $content = MessageApi::sendMessage($data, $headers);
            /*
            $request = new Request('POST', $config['API'].'/bot'.$params['token'].'/sendMessage', $headers, Json::encode($data));
            $response = $client->sendAsync($request)->wait();

            $body = $response->getBody();
            $content = Json::decode($body);
            */
            $s2 = microtime(true);
            $c = ($s2-$s1).'s';
            Tool_Common::log('/tg_message/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'config'=>$config, 'data'=>$content, 'c'=>$c]);
        }catch (\Exception $e){
            Tool_Common::log('/tg_message/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'config'=>$config, 'err_msg'=>$e->getMessage()]);
            throw_info($e->getMessage());
        }

        return 'TG消息发送完成[chat_id:'.$params['chat_id'].', name:'.($params['name']??'').']';
    }

}
