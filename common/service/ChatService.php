<?php
namespace common\service;

use common\service\chat\classes\Hsw;
//use common\service\Sock;
class  ChatService{
    public $master;

    public static function send($data = []){

        $data = ['status'=>200, 'msg'=>'测试发送消息'];
        //$hsw = new Hsw();
        //$rst = $hsw->sendMsg($data);
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