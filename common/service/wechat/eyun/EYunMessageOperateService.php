<?php

namespace common\service\wechat\eyun;

use backend\service\HN0898Service;
use common\models\eyun\EyunAuth;
use common\models\eyun\RobotUser;
use common\models\thirdD\BetOrderId;
use common\models\thirdD\Bets;
use common\models\wechat\WechatUser;
use common\service\BaseService;
use common\service\chat\Tool_Common;
use common\service\helpers\ThirdD;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\ThirdDTypeService;
use yii\helpers\Json;

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
     * 接受消息校验
     * @param string $user_id
     * @param string $text
     * @throws \common\exceptions\InfoException
     */
    public static function validateReceive($user_id='', $text=''){
        if(empty($text)){
            throw_info('文字不能为空');
        }
        if(empty($user_id)){
            throw_info('用户id为空');
        }
        $RobotUser = RobotUser::findOne(['user_id'=>$user_id]);
        if(!$RobotUser->status){
            throw_info('账号状态异常');
        }

        $data = [
            'user_id' => $user_id,
            'text' => trim($text),
        ];

        return [0, $data, '校验成功:'];
    }

    /**
     * 数据文字匹配，及转换
     * @param string $text
     * @return array
     */
    public static function matchData($text=''){
        try {
            if(strpos($text, '撤') !== false){
                $data = [];
                if (preg_match('/(\d+)/', $text, $matches)) {
                    $orderId = $matches[0];
                    $data = [
                        'type' => CommonBaseService::B_TYPE_CANCEL,
                        'orderId' => $orderId,
                    ];
                }
                return [0, $data, '处理成功'];
            }
            $dataGroups = [
                'originText' => $text,
            ];

            #$text = str_replace(' ', '', $text); # 中文逗号，
            $text = str_replace('，', ',', $text); # 中文逗号，
            $text = str_replace('：', '', $text); # 中文冒号，
            $text = str_replace('。', '', $text); # 中文句号。
            $text = str_replace('计', '共', $text); # 同义词替换
            $text = ThirdD::replaceManyNull($text); # 多个空格替换成单个空格
            $dataGroups['stepOneText'] = $text;

            $betGroups = explode('|', $text);
            $dataGroups['betGroups'] = $betGroups;
            foreach ($betGroups as $betText){
                $g = [];
                list($lottery_type, $lottery_name, $matchTexts) = ThirdDTypeService::getLotteryType($betText);
                foreach ($matchTexts as $matchText){
                    $betText = trim(str_replace($matchText, '', $betText), ',');
                }
                $g['lottery_type'] = $lottery_type;
                $g['name'] = $lottery_name;

                list($playMethod, $codes, $count) = ThirdDTypeService::getPlayMethodAndCodes($betText);
                #p(['playMethod'=>$playMethod, 'codes'=>$codes, 'count'=>$count]);
                $g['codes'] = $codes;
                if(ThirdD::getMaxDim($playMethod)>1){
                    # 跨度、组三组六混合情况
                    $playMethodKd = $playMethod[0];
                    $betText = str_replace($playMethodKd['name'], '', $betText);
                    $singleData = ThirdDTypeService::getMoneys($betText, $playMethodKd['matchName'], $playMethod);
                    foreach ($playMethod as $k=>$pm){
                        $playMethod[$k]['codes'] = $pm['codes'];
                        $playMethod[$k]['single'] = $singleData['single'];
                        $playMethod[$k]['count'] = $pm['count'];
                        $playMethod[$k]['all_moneys'] = $singleData['single'] * $pm['count'];
                        $playMethod[$k]['codesData'] = $pm['name'];
                        $playMethod[$k]['playMethod'] = $pm;
                    }
                    $g['single'] = $singleData['single'];
                    $g['all_moneys'] = $singleData['single'];
                }else{
                    $betText = str_replace($playMethod['name'], '', $betText);
                    $singleData = ThirdDTypeService::getMoneys($betText, $playMethod['matchName'], $playMethod);
                    $g['codes'] = $codes;
                    $g['single'] = $singleData['single'];
                    $g['count'] = $count;
                    $g['all_moneys'] = $singleData['single'] * $count;
                }
                $g['playMethod'] = $playMethod;
                $g['singleData'] = $singleData;
                #p(['g'=>$g, 'betText'=>$betText]);
            }
            $dataGroups['betCodeContents'][] = $g;
        }catch (\Exception $e){
            if($e->getCode() == ThirdDTypeService::CODE_FOR_USER){
                return [$e->getCode(), [], $e->getMessage()];
            }
            Tool_Common::log('/wechat/'.__FUNCTION__, 'ERR', '消息接收处理异常', ['text'=>$text, 'err_msg'=>$e->getMessage().'_'.$e->getFile().'_'.$e->getLine()]);
            return [30001, [], $e->getMessage()];
        }
        //p($dataGroups);
        $data = [
            'type' => CommonBaseService::B_TYPE_BET,
            'dataGroups' => $dataGroups,
            'lottery_type' => $lottery_type,
        ];
        return [0, $data, '处理成功'];
    }

    /**
     * 消息处理后的业务处理
     * @param string $user_id 代理user.id
     * @param string $text
     * @param string $fromUser 发送者的微信id
     * @return array
     */
    public function receive($user_id='', $text='', $fromUser=''){
        try {
            #p([$user_id, $text]);
            $transaction = static::getDb()->beginTransaction();
            # 校验
            list($code, $vdata, $msg) = self::validateReceive($user_id, $text);
            $member_id = WechatUser::findOne(['user_id'=>$user_id, 'userName'=>$fromUser])->id;

            $text = $vdata['text'];
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-01', ['user_id'=>$user_id, 'text'=>$text]);
            list($code, $data, $msg) = self::matchData($text);
            if($code>0){
                throw_info($msg);
            }
            switch ($data['type']){
                case CommonBaseService::B_TYPE_CANCEL:
                    $orderId = $data['orderId'];
                    $Bets = Bets::findOne(['order_id'=>$orderId]);
                    if(empty($Bets)){
                        throw_info('单号：'.$orderId.'无记录', ThirdDTypeService::CODE_FOR_USER);
                    }
                    if($Bets->status==1){
                        throw_info($orderId.'订单已完成，无法撤单', ThirdDTypeService::CODE_FOR_USER);
                    }
                    if($Bets->status==3){
                        throw_info($orderId.'订单已是撤单状态，无需重复处理', ThirdDTypeService::CODE_FOR_USER);
                    }
                    $Bets->status = 3; # 已撤单
                    if($Bets->save()){
                        $transaction->commit();
                    }else{
                        $transaction->rollBack();
                    }
                    return [0, ['text'=>$text, 'replyTxt'=>$orderId.'撤单完成'], '接收成功'];
                    break;
                default:
                    break;
            }

            $betOrderId = ThirdDTypeService::getOrderId();
            if(empty($betOrderId)){
                throw_info('单号生成失败');
            }
            $betCodeContents = $data['dataGroups']['betCodeContents'];
            #p(['betCodeContents'=>$betCodeContents]);
            if(ThirdD::getMaxDim($betCodeContents[0]['playMethod'])>1){
                $betCodeContents = $betCodeContents[0]['playMethod'];
            }
            $lottery_type = $data['lottery_type'];
            $qihao = HN0898Service::getQihao($lottery_type);
            $now_time = time();
            $allMoneys = 0.00;
            $replyTxt = '【课号】：'.$qihao;
            $replyTxt .= "\n【内容】：" . str_replace('元', '咪', $text);;
            foreach ($betCodeContents as $content){
                if(empty($content['playMethod']['id'])){
                    throw_info('方式匹配为空，请按正确格式输入', ThirdDTypeService::CODE_FOR_USER);
                }
                $Bets = new Bets();
                $setData = [
                    'user_id' => $user_id,
                    'wechat_user_id' => $member_id,
                    'order_id' => $betOrderId,
                    'play_method' => $content['playMethod']['id'],
                    'codes' => $content['codes'],
                    'bet_money' => $content['all_moneys'],
                    'single' => $content['single'],
                    'count' => $content['count'],
                    'qihao' => $qihao,
                    'lottery_type' => $lottery_type,
                    'lottery_name' => $content['name'],
                    'bet_desc' => $text,
                    'created_at' => $now_time,
                    'updated_at' => $now_time,
                ];
                #p($setData);
                $Bets->setAttributes($setData, false);
                if(!$Bets->save()){
                    throw_info(Json::encode($Bets->getErrors(), 320));
                }
                $allMoneys += $content['all_moneys']; # 总投
            }
            $replyTxt .= ("\n【单号】：".$betOrderId);

            $replyTxt .= ("\n【成功】：√  共".$allMoneys.'咪');

            $transaction->commit();
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-成功', ['user_id'=>$user_id, 'text'=>$text, 'fromUser'=>$fromUser, 'setData'=>$setData, 'replyTxt'=>$replyTxt]);
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-异常', ['user_id'=>$user_id, 'text'=>$text, 'fromUser'=>$fromUser, 'err_msg'=>$e->getMessage()]);
            # 用户输入错误提示
            if($e->getCode() == ThirdDTypeService::CODE_FOR_USER){
                return [$e->getCode(), [], $e->getMessage()];
            }
            # 其它情况处理异常，直接抛异常
            throw_info($e->getMessage());
        }

        return [0, ['text'=>$text, 'replyTxt'=>$replyTxt, 'allMoneys'=>$allMoneys], '接收成功'];
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
