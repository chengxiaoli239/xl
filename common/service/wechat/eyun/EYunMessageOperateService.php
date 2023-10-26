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
use common\service\thirdD\MethodMatchService;
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

    # 好友信息
    const MESSAGE_FRIEND_INFO_CODE = '65001'; # 好友信息变更通知

    public function __construct($user_id='')
    {
        parent::__construct($user_id);
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
     * 重置匹配文本
     * @param $text
     */
    public static function resetText($text){
        $text = str_replace('。', '#', $text); # 玩法之间分隔符
        $text = str_replace('，', ' ', $text); # 中文逗号
        $text = str_replace('：', '', $text); # 中文冒号
        $text = str_replace(':', '', $text); # 中文冒号
        #$text = str_replace('。', '', $text); # 中文句号。
        $text = str_replace('一单', '一直', $text); # 中文冒号，
        $text = str_replace('组一直一', '一直一组', $text); # 中文冒号，

        $text = str_replace('总计', '共', $text); # 同义词替换
        $text = str_replace(['计', '='], '共', $text); # 同义词替换
        $text = str_replace('块', '元', $text); # 同义词替换
        $text = str_replace(['、', '-', '.', '*', "\n"], ' ', $text); # 同义词替换
        $text = ThirdD::replaceManyNull($text); # 多个空格替换成单个空格
        if(preg_match('/个(\d+)元/', $text, $matches)){
            $text = str_replace($matches[0], '各'.$matches[1].'元', $text);
        }
        if(preg_match('/各([\p{Han}一二三四五六七八九十]{1,3})元/', $text, $matches3)){
            $t = $matches3[1];
            $s = ThirdD::cnToNums($t); # 中文转数字
            $text = str_replace($matches3[0], '', $text);
            $text = str_replace('共', '各'.$s.'元共', $text);
        }
        $allTmpMoney = 0.00;
        if(!preg_match('/各(\d+)/', $text, $matches)){ # 没匹配到倍数，做兼容处理
            #p($text);
            if(preg_match('/共(\d+)/', $text, $matches2)){
                $allTmpMoney = $matches2[1];
                if(preg_match('/(\d+)元/', $text, $matches4)){
                    $text = str_replace($matches4[0], '', $text);
                    $text = str_replace('共', '各'.$matches4[0].'共', $text);
                    $text = rtrim($text, '共');
                }
            }
        }else{
            if(preg_match('/共(\d+)/', $text, $matches2)){
                $allTmpMoney = $matches2[1];
                if(preg_match('/(\d+)元/', $text, $matches4)){
                    $text = str_replace($matches4[0], '', $text);
                    $text = str_replace('共', '各'.$matches4[0].'共', $text);
                    $text = rtrim($text, '共');
                }
            }
        }

        $text = str_replace('复试', '复式', $text);
        if(preg_match('/复式(\d{3,})/', $text, $matches5)){
            $len = strlen($matches5[1]);
            $changeNameArr = array_flip(ThirdDTypeService::SINGLE_ASSCIATE); // p($changeNameArr);
            $text = str_replace('复式', '复式'.$changeNameArr[$len], $text);
        }

        $matchesLotteryTypes = ThirdDTypeService::getLotteryTypes($text, ThirdDTypeService::getThirdDAlias());
        #p(['text'=>$text, 'matchesLotteryTyeps'=>$matchesLotteryTypes], 0);
        /*
        if(preg_match_all('/(一直|一组|直|组)/', $text, $matches)){
            if(count($matches[0])==2){
                #list($lottery_type, $lottery_name, $matchTexts) = ThirdDTypeService::getLotteryType($text);

                $tmpReplaceTxt = 'methods';
                $tmpStr = str_replace(implode('', $matches[0]), $tmpReplaceTxt, $text);
                #p([$tmpStr, $matches[0]]);
                foreach ($matches[0] as $m){
                    $tmpTexts[] = (strpos($m, '直') !== false) ? str_replace($tmpReplaceTxt, '直选', $tmpStr) : str_replace($tmpReplaceTxt, '组选', $tmpStr);
                }
            }else{
                $tmpTexts[] = $text;
            }
        }else{
            $tmpTexts[] = $text;
        }
        */
        $tmpTexts = [$text];

        if(count($matchesLotteryTypes)==2){
            $texts = [];
            foreach ($tmpTexts as &$tmpText){
                foreach ($matchesLotteryTypes as $lt){
                    $tmpText = str_replace($lt, '', $tmpText);
                }
            }
            foreach ($matchesLotteryTypes as $matchesLotteryType){
                foreach ($tmpTexts as $txt){
                    #p($matchesLotteryType.$tmpText, 0);
                    $texts[] = $matchesLotteryType.$txt;
                }
            }
        }else{
            $texts = $tmpTexts;
        }
        $countTexts = count($texts);
        $perAllMoney = (int)($allTmpMoney/$countTexts);
        foreach ($texts as &$text){
            if($perAllMoney>0){
                $text = str_replace($matches2[0], '共'.$perAllMoney, $text);
            }
        }
        $texts = implode(MethodMatchService::METHOD_SPLIT_FLAG, $texts);
        #p([$texts, $allTmpMoney, $perAllMoney]);

        return $texts;
    }

    /**
     * 数据文字匹配，及转换
     * @param string $text
     * @return array
     */
    public static function matchData($text=''){
        $text = str_replace(' ', ' ', trim($text)); # 中文空格替换成英文空格
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
            $stepText = [
                'originText' => $text,
            ];
            $text = EYunMessageOperateService::resetText($text); # 重置匹配文本
            $stepText['stepOneText'] = $text;

            $betTexts = explode(MethodMatchService::METHOD_SPLIT_FLAG, $text);
            $dataGroups = [];
            #p($betTexts);
            foreach ($betTexts as $k1=>$betText){
                # 重置一下格式方便处理：福+玩法+号码+各x元
                #EYunMessageOperateService::resetBetText($betText);
                $g = [];
                $g['betText'] = $betText;
                list($lottery_type, $lottery_name, $matchTexts) = ThirdDTypeService::getLotteryType($betText);
                foreach ($matchTexts as $matchText){
                    $betText = trim(str_replace($matchText, '', $betText), ',');
                }

                list($playMethod, $codes, $count) = ThirdDTypeService::getPlayMethodAndCodes($betText);
                #p(['betText'=>$betText, 'playMethod'=>$playMethod, 'codes'=>$codes, 'count'=>$count], 0);
                $g['codes'] = $codes;
                if(ThirdD::getMaxDim($playMethod)>1){
                    # 跨度、组三组六混合情况
                    $playMethodKd = $playMethod[0];
                    $betText = str_replace($playMethodKd['name'], '', $betText);
                    $singleData = ThirdDTypeService::getMoneys($betText, $playMethodKd['matchName'], $playMethod);
                    if(empty($singleData['single'])){
                        $singleData['single'] = $singleData['all_moneys']/$count;
                    }
                    foreach ($playMethod as $k=>$pm){
                        $playMethod[$k]['codes'] = $pm['codes'];
                        $playMethod[$k]['single'] = $singleData['single'];
                        $playMethod[$k]['count'] = $pm['count'];
                        $playMethod[$k]['all_moneys'] = $singleData['single'] * $pm['count'];
                        $playMethod[$k]['codesData'] = $pm['name'];
                        $playMethod[$k]['playMethod'] = $pm;
                    }
                    $g['lottery_type'] = $lottery_type;
                    $g['lottery_name'] = $lottery_name;
                    $g['single'] = $singleData['single'];
                    $g['all_moneys'] = $singleData['single'];
                    $g['playMethod'] = $playMethod;
                }else{
                    $betText = str_replace($playMethod['name'], '', $betText);
                    $singleData = ThirdDTypeService::getMoneys($betText, $playMethod['matchName'], $playMethod);
                    if(empty($singleData['single'])){
                        $singleData['single'] = $singleData['all_moneys']/$count;
                    }
                    $g['lottery_type'] = $lottery_type;
                    $g['lottery_name'] = $lottery_name;
                    $g['single'] = $singleData['single'];
                    $g['all_moneys'] = $singleData['single'] * $count;
                    $g['singleData'] = $singleData;

                    $playMethod['codes'] = $codes;
                    $playMethod['single'] = $singleData['single'];
                    $playMethod['count'] = $count;
                    $playMethod['all_moneys'] = $singleData['single'] * $count;
                    $playMethod['playMethod'] = $playMethod;
                    $g['playMethod'][] = $playMethod;
                }
                #p(['g'=>$g, 'betText'=>$betText],0);
                $dataGroups['betCodeContents'][$lottery_type][] = $g;
            }
        }catch (\Exception $e){
            if($e->getCode() == ThirdDTypeService::CODE_FOR_USER){
                return [$e->getCode(), [], $e->getMessage()];
            }
            Tool_Common::log('/wechat/'.__FUNCTION__, 'ERR', '消息接收处理异常', ['text'=>$text, 'err_msg'=>$e->getMessage().'_'.$e->getFile().'_'.$e->getLine()]);
            return [30001, [], $e->getMessage()];
        }
        #p($dataGroups);
        $data = [
            'type' => CommonBaseService::B_TYPE_BET,
            'stepText' => $stepText,
            'dataGroups' => $dataGroups,
        ];
        return [0, $data, '处理成功'];
    }

    /**
     * @param $data
     * @param $text
     * @param $transaction
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function matchCancel($data, $text, $transaction){
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
                #$Bets->status = 3; # 已撤单
                Bets::updateAll(['status'=>3], ['order_id'=>$orderId]);
                if($Bets->save()){
                    $transaction->commit();
                }else{
                    $transaction->rollBack();
                }
                return [ThirdDTypeService::CODE_FOR_USER, ['text'=>$text, 'replyTxt'=>$orderId.'撤单完成'], '接收成功'];
                break;
            default:
                break;
        }
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
            #p([$code, $data, $msg]);
            if($code>0){
                throw_info($msg);
            }

            # 撤单匹配
            list($code, $vd, $msg) = self::matchCancel($data, $text, $transaction);
            if($code == ThirdDTypeService::CODE_FOR_USER){
                return [0, $vd, $msg];
            }

            $betOrderId = ThirdDTypeService::getOrderId();
            if(empty($betOrderId)){
                throw_info('单号生成失败');
            }
            $betCodeContents = $data['dataGroups']['betCodeContents'];
            //p($betCodeContents);
            $now_time = time();
            $allMoneys = 0.00;
            $allCounts = 0;
            $replyTxts = [];
            foreach ($betCodeContents as $lottery_type=>$contents){
                $qihao = HN0898Service::getQihao($lottery_type);
                $replyTxt = '【课号】'.$qihao;
                $replyTxt .= "\n【内容】" . str_replace('元', '咪', $text);;

                #if(ThirdD::getMaxDim($contents['playMethod'])==1){
                #    $contents['playMethod'][] = $contents['playMethod'];
                #}
                foreach ($contents as $playMethods){
                    $lottery_name = $playMethods['lottery_name'];
                    foreach ($playMethods['playMethod'] as $content){
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
                            'lottery_name' => $lottery_name,
                            'bet_desc' => $text,
                            'created_at' => $now_time,
                            'updated_at' => $now_time,
                        ];
                        //p($setData);
                        $Bets->setAttributes($setData, false);
                        if(!$Bets->save()){
                            throw_info(Json::encode($Bets->getErrors(), 320));
                        }
                        $allMoneys += $content['all_moneys']; # 总投
                        $allCounts += $content['count']; # 总投
                    }
                }
            }
            $replyTxt .= ("\n【单号】".$betOrderId);

            $replyTxt .= ("\n【成功】√  共".$allCounts."组，共".$allMoneys.'咪');
            $replyTxts[] = $replyTxt;

            $transaction->commit();
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-成功', ['user_id'=>$user_id, 'text'=>$text, 'fromUser'=>$fromUser, 'setData'=>$setData, 'replyTxts'=>$replyTxts]);
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-异常', ['user_id'=>$user_id, 'text'=>$text, 'fromUser'=>$fromUser, 'err_msg'=>$e->getMessage().$e->getFile().$e->getLine()]);
            # 用户输入错误提示
            if($e->getCode() == ThirdDTypeService::CODE_FOR_USER){
                return [$e->getCode(), [], $e->getMessage()];
            }
            # 其它情况处理异常，直接抛异常
            throw_info($e->getMessage());
        }

        return [0, ['text'=>$text, 'replyTxts'=>$replyTxts, 'allMoneys'=>$allMoneys], '接收成功'];
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
