<?php

namespace common\service\wechat\eyun;

use common\models\eyun\EyunAuth;
use common\models\eyun\RobotUser;
use common\service\BaseService;
use common\service\chat\Tool_Common;
use common\service\jobs\robots\EYunUserJobs;

class EYunMessageOperateService  extends EYunBaseService
{
    # 离线通知
    const MESSAGE_OFFLINE_CODE = '30000';

    # 私聊
    const MESSAGE_P_TEXT_CODE = '60001'; # 私聊文本
    const MESSAGE_P_TEXT_CANCEL = '60018'; # 撤回消息

    # 群聊
    const MESSAGE_G_TEXT_CANCEL = '80018'; # 撤回消息
    const MESSAGE_G_TEXT_CODE = '80001'; # 群聊文本

    public function __construct($user_id='', $config = [])
    {
        $this->user_id = $user_id;
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
        $eyunAuth = EyunAuth::findOne(1);
        if(!empty($eyunAuth)){
            $headers = [
                'Authorization' => $eyunAuth->authorization,
            ];
            $this->headers = $headers;
        }
        $RobotUser = RobotUser::findOne(['user_id'=>$user_id]);
        if(!empty($RobotUser)){
            $this->wcId = $RobotUser->wcId;
            $this->wId = $RobotUser->wId;
        }
        parent::__construct($config);
    }

    public function wechatStatus(){

    }

    public static function receive($data){

    }

    public function send(){

    }
}
