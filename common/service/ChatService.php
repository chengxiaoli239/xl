<?php
namespace common\service;

use common\service\chat\classes\Hsw;
class  ChatService{

    public static function send($data = []){
        $hsw = new Hsw();

        $data = ['status'=>200, 'msg'=>'测试发送消息'];
        $rst = $hsw->sendMsg($data);

        return $rst;
    }

}