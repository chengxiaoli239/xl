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
        if(empty($user_id)){
            throw_info('new消息发送对象user_id为空');
        }
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

    /**
     * 消息处理后的业务处理
     * @param $data
     * @return array
     */
    public function receive($data=[]){

        return [];
    }

    /**
     * 发送前校验
     * @param string $wcId
     * @param string $content
     * @param array $atIds
     */
    private function validatePreSend($wcId='', $content='', $atIds=[]){
        if(empty($wcId)){
            throw_info('发送消息接口wcId微信原始id不能为空');
        }
        if(empty($content)){
            throw_info('发送消息接口content不能为空');
        }
    }
    /**
     * 文本消息发送
     * @param string $wcId 私聊则位用户的微信id，群里则位群里id
     * @param string $content
     * @param array $atIds 私聊不传，群里at传用户微信id
     * @return bool|mixed|null
     */
    public function send($wcId='', $content='', $atIds=[]){

        try {
            $this->validatePreSend($wcId, $content, $atIds);
            $url = $this->base_url . '/sendText';
            $params = [
                'wId' => $this->wId,
                'wcId' => trim($wcId), # 好友微信id/群id,多个好友/群 以","分隔每次最多支持20个微信/群号,记得本接口随机间隔300ms-1500ms，频繁调用容易导致掉线
                'content' => $content,
            ];
            if(!empty($at)){
                $params['at'] = implode(',', $atIds);
            }
            $response = $this->request($url, $params, $this->headers);
        }catch (\Exception $e){
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '发送文本消息-异常', ['url'=>$url, 'params'=>$params, 'err_msg'=>$e->getMessage()]);
            return ['code'=>30001, 'message'=>$e->getMessage()];
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '发送文本消息', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }
}
