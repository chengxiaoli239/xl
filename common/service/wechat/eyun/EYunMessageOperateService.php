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
            $dataGroups = [
                'originText' => $text,
            ];

            #$text = str_replace(' ', '', $text); # 中文逗号，
            $text = str_replace('，', ',', $text); # 中文逗号，
            $text = str_replace('：', '', $text); # 中文冒号，
            $text = str_replace('。', '', $text); # 中文句号。
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
                    # 跨度情况
                    $playMethodKd = $playMethod[0];
                    $betText = str_replace($playMethodKd['name'], '', $betText);
                    $singleData = ThirdDTypeService::getMoneys($betText, $playMethodKd['matchName'], $playMethod);
                    foreach ($playMethod as $k=>$pm){
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
                    $g['single'] = $singleData['single'];
                    $g['count'] = $count;
                    $g['all_moneys'] = $singleData['single'] * $count;
                }
                $g['playMethod'] = $playMethod;
                #foreach ($playMethods as $playMethod){
                #    $betText = str_replace($playMethod['name'], '', $betText);
                #}

                #p(['singleData'=>$singleData, 'playMethod'=>$playMethod, 'codes'=>$codes, 'count'=>$count]);

                #$codes = explode(' ', explode('各', $betText)[0]);
                $g['singleData'] = $singleData;
                $replaceStrs = array_filter([$playMethod['matchName'], $singleData['single_txt']]);
                Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '调试', ['matchName'=>$playMethod['matchName'], 'text'=>$text, 'single_txt'=>$singleData['single_txt'], 'replaceStrs'=>$replaceStrs, 'codes'=>$codes]);
                foreach ($replaceStrs as $replaceStr){
                    if(strpos($replaceStr, '度')!==false OR
                        strpos($replaceStr, '全包')!==false OR
                        (is_string($codes) && strpos($codes, '和值')!==false)
                    ) continue;
                    $betText = str_replace($replaceStr, '', $betText);
                    $codes = str_replace($replaceStr, '', $codes);
                }
                if(!empty($codes) && is_array($codes)) {
                    $g['codesData'] = implode(';', $codes);
                }elseif (is_string($codes) && (strpos($codes, '拖')!==false OR strpos($codes, '和值')!==false)){
                    $g['codesData'] = trim($codes);
                }elseif (is_string($codes) && strpos($playMethod['name'], '复') !== false){
                    $g['codesData'] = trim($codes);
                }else{
                    $g['codesData'] = trim(str_replace('各', '', $betText));
                }

                #p(['g'=>$g, 'betText'=>$betText]);
            }
            $dataGroups['betCodeContents'][] = $g;
        }catch (\Exception $e){
            if($e->getCode() == ThirdDTypeService::CODE_FOR_USER){
                return [$e->getCode(), [], $e->getMessage()];
            }
            return [30001, [], $e->getMessage().'_'.$e->getFile().'_'.$e->getLine()];
        }
        //p($dataGroups);
        $data = [
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
            list($code, $data, $msg) = self::matchData($text);
            if($code>0){
                throw_info($msg);
            }
            $betOrderId = ThirdDTypeService::getOrderId();
            if(empty($betOrderId)){
                throw_info('单号生成失败');
            }
            $betCodeContents = $data['dataGroups']['betCodeContents'];
            //p(['betCodeContents'=>$betCodeContents]);
            if(ThirdD::getMaxDim($betCodeContents[0]['playMethod'])>1){
                $betCodeContents = $betCodeContents[0]['playMethod'];
            }
            $lottery_type = $data['lottery_type'];
            $qihao = HN0898Service::getQihao($lottery_type);
            $now_time = time();
            $allMoneys = 0.00;
            foreach ($betCodeContents as $content){
                $Bets = new Bets();
                $setData = [
                    'user_id' => $user_id,
                    'wechat_user_id' => $member_id,
                    'order_id' => $betOrderId,
                    'play_method' => $content['playMethod']['id'],
                    'codes' => str_replace(' ', ',', $content['codesData']),
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

            $transaction->commit();
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-成功', ['user_id'=>$user_id, 'text'=>$text, 'fromUser'=>$fromUser, 'setData'=>$setData]);
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-异常', ['user_id'=>$user_id, 'text'=>$text, 'fromUser'=>$fromUser, 'err_msg'=>$e->getMessage()]);
            if($e->getCode() == ThirdDTypeService::CODE_FOR_USER){
                return [$e->getCode(), [], $e->getMessage()];
            }
            return [30001, [], $e->getMessage()];
        }

        return [0, ['text'=>$text, 'allMoneys'=>$allMoneys], '接收成功'];
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
