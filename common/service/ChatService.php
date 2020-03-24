<?php
namespace common\service;

use common\service\chat\classes\Hsw;
//use common\service\Sock;
//use Swoole\Http\Client;
class  ChatService{
    public $master;

    public static function send($data = []){

        $data = ['status'=>200, 'msg'=>'测试发送消息'];
        //$hsw = new Hsw();
        //p($hsw);

        # 1
        $client = new Swoole\Client(SWOOLE_SOCK_TCP);
        if (!$client->connect('lt.sm0898.com', \Yii::$app->params['CHAT_PORT'], -1)) {
            exit("connect failed. Error: {$client->errCode}\n");
        }
        $client->send("hello world\n");
        echo $client->recv();
        $client->close();
        p('llll');

        #  2
        $client = new Client('lt.sm0898.com', \Yii::$app->params['CHAT_PORT'], false);

        $client->on('message', function (Client $cli, \Swoole\WebSocket\Frame $frame){
            //接收对方返回的数据
            $data = json_decode($frame->data, true);
            var_dump($data);
        });

        $client->upgrade('/额外参数', function (Client $cli) {
            //推送数据 请求
            $cli->push('推送内容');
        });
        p('cxxx');


        $fp = fsockopen("ws://lt.sm0898.com", 9876);
        p($fp);
        //$rst = $hsw->sendMsg($data);
        //p([\Yii::$app->params['CHAT_DOMAIN'], \Yii::$app->params['CHAT_PORT']]);
        $master = self::WebSocket(\Yii::$app->params['CHAT_DOMAIN'], \Yii::$app->params['CHAT_PORT']);

        p($master);

        //return $rst;
    }

    //传相应的IP与端口进行创建socket操作
    public static function WebSocket($address, $port){
        error_reporting(E_ALL ^ E_NOTICE);
        ob_implicit_flush();


        //连接服务器
        $sk = new Sock($address, $port);
        $rst = $sk->run();
        p($rst);
        /*
        $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);//1表示接受所有的数据包
        socket_bind($server, $address, $port);
        socket_listen($server);
        */
        //$this->e('Server Started : '.date('Y-m-d H:i:s'));
        //$this->e('Listening on   : '.$address.' port '.$port);
        return $server;
    }

}