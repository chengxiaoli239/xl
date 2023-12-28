<?php

namespace common\service\wechat\eyun;

use backend\models\thirdD\BetsBackend;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\agent\AgentUsersService;
use backend\service\BetService;
use backend\service\HN0898Service;
use common\models\eyun\RobotUser;
use common\models\thirdD\BetOrderId;
use common\models\wechat\WechatUser;
use common\service\BaseService;
use common\service\chat\Tool_Common;
use common\service\helpers\ThirdD;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\jobs\SsxxBetJobs;
use common\service\thirdD\match\MatchCodeService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\Odds3dService;
use common\service\thirdD\PlayMethodService;
use common\service\thirdD\ThirdDTypeService;
use common\service\wechat\WechatUserService;
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

    # 非聊天记录需要同步的类型
    const MESSAGE_SYNC_OPTIONS = [
        self::MESSAGE_FRIEND_INFO_CODE,
    ];

    public static $methodDatas = [];
    public static $aliasNameToOriginName = [];
    public static $gLotteryType = 26;
    public static $gLotteryName = '福';

    public static function tableName()
    {
        return '{{%wechat_user}}';
    }


    public function __construct($user_id='')
    {
        $this->user_id = $user_id;
        self::$methodDatas = PlayMethodService::getAllMethodsAndAliasName($indexByKey=1, $aliasNameToOriginName);
        self::$aliasNameToOriginName = $aliasNameToOriginName;
        parent::__construct($user_id);
    }


    public function setMemberInfo($fromUser=''){
        $memberInfo = WechatUser::find()->where(['user_id'=>$this->user_id, 'userName'=>$fromUser])->asArray()->one();
        if(empty($memberInfo)){
            throw_info('会员信息为空：'.$this->user_id.'_'.$fromUser);
        }
        $this->wechatUser = $memberInfo;
        $this->member_id = $memberInfo['id'];
        return $this->wechatUser;
    }



    /**
     * 接受消息校验
     * @param string $user_id
     * @param string $text
     * @throws \common\exceptions\InfoException
     */
    public static function validateReceive($user_id='', $text=''): array
    {
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
    public static function resetMethodText($text): array
    {
        $texts = [];
        $twoHH = ThirdD::getTwoEOL(); # 双换行
        $ts = explode($twoHH, $text);
        $ts = array_filter($ts); # 去除空行
        foreach ($ts as $t){
            $flag = true;  # 是否直组
            $splits = array_filter(explode("\n", $t));
            Tool_Common::log('/match/'.__FUNCTION__, 'INFO', '重置匹配文本01', ['t0'=>$t, 'ttss'=>$splits]);
            //var_dump('ddd', (strpos($t, '直')===false && strpos($t, '组')===false && strpos($t, '单')===false));
            if(
                strpos($t, '拖') !== false OR
                strpos($t, '飞') !== false OR
                strpos($t, '跨') !== false OR
                strpos($t, '对') !== false OR
                strpos($t, '定') !== false OR
                strpos($t, '位') !== false OR
                strpos($t, '复式') !== false OR
                strpos($t, '全包') !== false OR
                strpos($t, '值') !== false OR
                (strpos($t, '直') ===false && strpos($t, '组')===false && strpos($t, '单')===false)
            ){
                $mType = 10;
                $flag = false;
            }else{
                $replaceText = $t; # 仅用于匹配区分直组前处理
                list($originText, $singleCnTxt) = MethodMatchService::replaceSingleText($replaceText); # 匹配号码前倍数字符先替换为空
                if(strpos($replaceText, '直') !== false OR (strpos($replaceText, '单') !== false && strpos($replaceText, '值') === false)){
                    # 直选
                    #$texts[] = \common\service\helpers\ThirdD::multiKongHangToOneSpace($t); # 直、组类型直接合并为一行
                    $mType = 11;
                }else{
                    $flag1 = strpos($replaceText, '组') !== false && preg_match('/\d{4,}/', $replaceText, $matches1); #匹配 组三组六4-9码，此处还差组三两码、组三三码
                    if($flag1){
                        $mType = 1;
                        $flag = false;
                        #$texts = array_merge($texts, $splits);
                    }else{
                        # 此处匹配组三两码、组三三码
                        if(strpos($replaceText, '组') !== false && preg_match_all('/(\d{2,3})/u', $replaceText, $matches)){
                            # 组三有重复号码则为组选
                            $flag2 = \common\service\helpers\ThirdD::judgeCodesRepeat($matches[0][0], $sortCode); # 判断号码是否有重复，重复则为组三
                            $mType = 2;
                            if(strlen($matches[0][0])==2 OR (!$flag2 && strpos($replaceText, '组三') !== false)){ #  len:2 组三两码 或 组三多吗
                                $mType = 3;
                                $flag = false; # 组三多吗
                            }
                        }else{
                            $mType = 4;
                            # 非直选
                            $flag = false;
                        }
                    }
                }
            }
            $logArr0 = ['t'=>$t, 'replaceText'=>$replaceText, 'mType'=>$mType, 'texts'=>$texts];
            Tool_Common::log('/match/'.__FUNCTION__, 'INFO', '重置匹配文本02', $logArr0);
            if(!$flag){
                # 非直组
                $texts = array_merge($texts, $splits);
            }else{
                $texts[] = \common\service\helpers\ThirdD::multiKongHangToOneSpace($t); # 直、组类型直接合并为一行
            }
            $logArr0 = ['t'=>$t, 'mType'=>$mType, 'texts'=>$texts];
            Tool_Common::log('/match/'.__FUNCTION__, 'INFO', '重置匹配文本03', $logArr0);
        }
        $texts = array_unique($texts); # 合并之后的号码文本，直组可多行，其它规则单行
        foreach ($texts as $k=>$txt){
            if($k==0) continue;
            if(
                # 此行匹配到'拖' && 没匹配到组三或组六 且上一行能匹配到 （拖 && 组三或组六）
                (strpos($txt, '拖') !== false && strpos($txt, '组六')===false && strpos($txt, '组三')===false) &&
                ((strpos($texts[$k-1], '组六')===false OR strpos($texts[$k-1], '组三')===false))
            ){
                $texts[$k] = (strpos($texts[$k-1], '组三')!==false) ? '组三'.$txt : '组六'.$txt;
            }
        }
        $logArr = ['text'=>$text, 'texts'=>$texts, 'ts'=>$ts];
        Tool_Common::log('/match/'.__FUNCTION__, 'INFO', '重置匹配文本11', $logArr);
        return $texts;
    }

    /**
     * 重置匹配文本
     * @param $text
     */
    public static function resetText($text): string
    {
        var_dump(0, $text);
        #$text = str_replace('。', '#', $text); # 玩法之间分隔符
        $text = str_replace('组6 ', '组六 ', $text); # 同义词
        //$text = str_replace('各', ' 各', $text); # 同义词
        #$text = str_replace('组3 ', '组三 ', $text); # 同义词
        //$text = str_replace('倍组选 ', '倍组', $text); # 同义词
        var_dump(1, $text);
        $text = str_replace('组选 ', '组 ', $text); # 同义词
        $text = str_replace('直选 ', '直 ', $text); # 同义词
        $text = str_replace('二吗 ', '二码 ', $text); # 同义词
        var_dump(2, $text);
        $text = str_replace('，', ' ', $text); # 中文逗号
        $text = str_replace('：', '', $text); # 中文冒号
        $text = str_replace(':', '', $text); # 中文冒号
        var_dump(3, $text);
        $text = str_replace('一单', '一直', $text); # 中文冒号，
        $text = str_replace('组一直一', '一直一组', $text); # 中文冒号，

        # '=100' 替换成 '各100'
        if(preg_match_all('/=\s*(\d+)/', $text, $ms)){
            foreach ($ms[0] as $k=>$m){
                $text = str_replace($m, '各'.$ms[1][$k], $text);
            }
        }
        # '=三' 替换成 '各三'
        if(preg_match_all('/=\s*(['.MethodMatchService::CN_SINGLE_TEXT.'])/u', $text, $ms)){
            foreach ($ms[0] as $k=>$m){
                $text = str_replace($m, '各'.$ms[1][$k], $text);
            }
        }

        var_dump(4, $text);
        $text = str_replace(['共计', '总计', '计', '='], '共', $text); # 同义词替换
        $text = str_replace(['块', '米', '咪'], '元', $text); # 同义词替换
        $text = str_replace(['托', '脱'], '拖', $text); # 同义词替换
        //$text = str_replace(['、', "\n"], ' ', $text); # 同义词替换
        $text = str_replace(['各打', '各买', "打", "买"], '各', $text); # 同义词替换
        var_dump(5, $text);
        if((strpos($text, '直')===false OR strpos($text, '组')===false) && preg_match_all('/各(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})/u', $text, $ms)){
            foreach ($ms[1] as $k=>$m){
                $text = str_replace($ms[0][$k], '各'.ThirdD::cn2num($m), $text);
            }
        }
        var_dump(5.1, $text);
        #if( preg_match_all('/(直|组六|组三)\s*(['.MethodMatchService::CN_SINGLE_TEXT.']{1,3})倍/u', $text, $ms) ){ # 五十倍：50倍，组六两倍组三四倍
        #    foreach ($ms[0] as $k=>$m){
        #        if($m){
        #            $text = str_replace($m, $ms[1][$k].ThirdD::cn2num($ms[2][$k]).'倍', $text);
        #        }
        #    }
        #}
        //p(['ddd', $text, $ms]);
        if(preg_match('/\d+注/', $text, $ms) && (
            strpos($text, '直') !== false OR strpos($text, '组') !== false OR strpos($text, '单') !== false
        )){
            $text = str_replace($ms[0], '', $text); # 直组很多组备注 xx注  为了避免影响匹配，去掉
        }

        # 特殊倍数匹配
        $text = str_replace(['一倍10元', '一倍十元'], '各10元', $text); # 同义词替换
        $text = str_replace(['一倍20元', '一倍二十元'], '各20元', $text); # 同义词替换

        var_dump(6, $text);
        $text = ThirdD::replaceManyNull($text); # 多个空格替换成单个空格
        if(preg_match('/个(\d+)元/', $text, $matches)){
            $text = str_replace($matches[0], '各'.$matches[1].'元', $text);
        }
        var_dump(7, $text);
        if(preg_match_all('/各([\p{Han}一二三四五六七八九十]{1,3})元/', $text, $matches3)){
            foreach ($matches3[1] as $m){
                $s = ThirdD::cn2num($m); # 中文转数字
                #p([$text, $matches3[0], $s]);
                $text = str_replace('各'.$m.'元', '各'.$s.'元', $text);
                #$text = str_replace('共', '各'.$s.'元共', $text);
            }
        }
        var_dump(8, $text);
        /*
        $allTmpMoney = 0.00;
        if(!preg_match('/各(\d+)/', $text, $matches)){ # 没匹配到倍数，做兼容处理
            if(preg_match('/共(\d+)/', $text, $matches2)){
                $allTmpMoney = $matches2[1];
                if(preg_match('/(\d+)元/', $text, $matches4)){
                    //$text = str_replace($matches4[0], '', $text); // todo 福62.472.482.492 全部3元直 2元组 共70，其中3元会被替换掉，异常
                    $text = str_replace('共', '各'.$matches4[0].'共', $text);
                    $text = rtrim($text, '共');
                }
            }
            //p('..'.$text);
        }else{
            if(preg_match('/共(\d+)/', $text, $matches2)){
                $allTmpMoney = $matches2[1];
                var_dump(222, $matches2);
                if(preg_match('/(\d+)元/', $text, $matches4)){
                    var_dump(444, $matches4);
                    $text = str_replace($matches4[0], '', $text);
                    $text = str_replace('共', '各'.$matches4[0].'共', $text);
                    $text = rtrim($text, '共');
                }
            }
        }
        */
        var_dump(9, $text);

        $text = str_replace('复试', '复式', $text);
        if(preg_match('/复式(\d{3,})/', $text, $matches5)){
            $len = strlen($matches5[1]);
            $changeNameArr = array_flip(ThirdDTypeService::SINGLE_ASSCIATE); // p($changeNameArr);
            $text = str_replace('复式', '复式'.$changeNameArr[$len], $text);
        }

        $matchesLotteryTypes = ThirdDTypeService::getLotteryTypes($text, ThirdDTypeService::getThirdDAlias());
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
        $texts = implode(MethodMatchService::METHOD_SPLIT_FLAG, $texts);
        #p([$texts, $allTmpMoney, $perAllMoney]);
        $texts = strtr($texts, ['各各'=>'各', '共共'=>'共']);
        $texts = str_replace('x', MethodMatchService::METHOD_FIXED_FLAG, $texts);

        return trim($texts, '#');
    }

    /**
     * 数据文字匹配，及转换
     * @param string $text
     * @return array
     */
    public function matchData(string $text=''): array
    {
        //$text = str_replace(' ', ' ', trim($text)); # 中文空格替换成英文空格
        try {
            switch (true){
                case strpos($text, '查') !== false: // 撤单
                    return AgentUsersService::userGetInfo($this->wechatUser);
                case strpos($text, '撤') !== false: // 撤单
                    return EYunMessageOperateService::operateCancel($text, $this->wechatUser);
                case strpos($text, '上') !== false OR strpos($text, '下') !== false: // 上下分
                    return AgentUsersBalanceService::operateBalanceChange($text, $this->wechatUser);
                default:
                    $switch = BetService::getConfig('match_from_type_api_switch')??0;
                    $stepText = ['originText'=>$text];
                    if($switch==1){
                        list($code, $data, $msg) = MatchCodeService::getCodeDatas($text);
                        if($code!=0 OR empty($data['dataGroups'])){
                            $oText = "\n\nXXXXXX原文XXXXXX\n".$text."\nXXXXXXXXXXXXXXX";
                            return [CommonBaseService::CODE_FOR_USER, [], ($msg??'匹配异常').$oText];
                        }
                        $dataGroups = $data['dataGroups'];
                    }else{
                        $betTexts = EYunMessageOperateService::resetMethodText($text); # 重置匹配文本
                        Tool_Common::log('/bet_3d/'.__FUNCTION__, 'INFO', '解析日志-00', ['switch'=>$switch, 'text'=>$text, 'betTexts'=>$betTexts, 'counts'=>count($betTexts)]);
                        $dataGroups = [];
                        foreach ($betTexts as $k1=>$betText){
                            list($code, $data, $msg) = EYunMessageOperateService::getOnePlayMethodG($betText); # 单个规则文本匹配处理
                            Tool_Common::log('/bet_3d/'.__FUNCTION__, 'INFO', '解析日志-001', ['betText'=>$betText, 'code'=>$code, 'msg'=>$msg]);
                            if($code>0){
                                if($code == CommonBaseService::CODE_FOR_USER){
                                    throw_info($msg, $code);
                                }
                                continue;
                            }
                            $dataGroups['betCodeContents'][$data['lottery_type']][] = $data['g'];
                        }
                    }

                    break;
            }
        }catch (\Exception $e){
            Tool_Common::log('/wechat/'.__FUNCTION__, 'ERR', '消息接收处理异常', ['text'=>$text, 'betText'=>$betText, 'err_msg'=>$e->getMessage().'_'.$e->getFile().'_'.$e->getLine()]);
            if($e->getCode() == CommonBaseService::CODE_FOR_USER){
                return [CommonBaseService::CODE_FOR_USER, [], $e->getMessage()];
            }
            return [30001, [], $e->getMessage()];
        }
        #p($dataGroups);
        $data = [
            'type' => WechatUserService::TYPE_ORDER_BET,
            'stepText' => $stepText,
            'dataGroups' => $dataGroups,
        ];
        return [0, $data, '处理成功'];
    }

    /**
     * 单个规则文本处理
     * @param string $betText
     * @return array
     * @throws \common\exceptions\InfoException
     */
    public static function getOnePlayMethodG(string $betText='', $user_id=0): array
    {
        $betText = trim($betText, "\r\n");
        $betText = EYunMessageOperateService::resetText($betText); # 重置匹配文本
        # 重置一下格式方便处理：福+玩法+号码+各x元
        #EYunMessageOperateService::resetBetText($betText);
        $g = [];
        $g['betText'] = $betText;
        list($lottery_type, $lottery_name, $matchTexts, $isEmpty) = ThirdDTypeService::getLotteryType($betText);
        #var_dump('1lottery_type:'.$lottery_type, $isEmpty);
        if($isEmpty){
            # 彩种匹配为空则取上次匹配的结果
            $lottery_type = self::$gLotteryType;
            $lottery_name = self::$gLotteryName;
        }
        self::$gLotteryType = $lottery_type;
        self::$gLotteryName = $lottery_name;

        try {
            list($playMethod, $codes, $count) = ThirdDTypeService::getPlayMethodAndCodes($betText);
        }catch (\Exception $e){
            return [$e->getCode(), [], $e->getMessage()];
        }
        $logArr = ['lottery_type'=>$lottery_type, 'matchTexts'=>$matchTexts, 'betText'=>$betText, 'playMethod'=>$playMethod, 'codes'=>$codes, 'count'=>$count, 'isEmpty'=>$isEmpty];
        //p($logArr);
        Tool_Common::log('/bet_3d/'.__FUNCTION__, 'INFO', '解析日志-01', $logArr);
        if(empty($playMethod)){
            return [10001, [], '匹配不到玩法则忽略'];
        }
        $g['codes'] = $codes;
        # 跨度、组三组六混合情况
        $playMethodKd = $playMethod[0];
        $betText = str_replace($playMethodKd['name'], ' ', $betText);
        $singleData = ThirdDTypeService::getMoneys($betText, $playMethodKd['name'], $playMethod);
        $single = $singleData['single'];
        $logArr = ['betText' => $betText, 'singleData' => $singleData, 'playMethod' => $playMethod];
        Tool_Common::log('/bet_3d/' . __FUNCTION__, 'INFO', '解析日志-02', $logArr);
        foreach ($playMethod as $k => $pm) {
            $single = $pm['single'];
            if (empty($single) && ($singleData['single_cn_text'] == '元' or $singleData['single_txt'] == '元') && !empty($singleData['single'])) {
                $single = $singleData['single'];
            }
            if (empty($single) && ($singleData['single_cn_text'] == '元' or $singleData['single_txt'] == '元') && !empty($singleData['single_cn'])) {
                $Odds = Odds3dService::getOdds($user_id, $pm['id']); # 玩法赔率
                $single = $Odds['money'] * $singleData['single_cn'];
            }

            $all_moneys = $single * $pm['count'];
            $playMethod[$k]['codes'] = $pm['codes'];
            $playMethod[$k]['single'] = $single;
            $playMethod[$k]['count'] = $pm['count'];
            $playMethod[$k]['all_moneys'] = $all_moneys;
            $playMethod[$k]['codesData'] = $pm['name'];
            //$playMethod[$k]['playMethod'] = $pm;
            if($all_moneys>1000){
                Tool_Common::log('/matchCodes/'.__FUNCTION__, 'INFO', '匹配金额异常', ['betText'=>$betText, 'playMethod'=>$playMethod]);
                return [CommonBaseService::CODE_FOR_USER, [], '匹配金额异常，号码与玩法需空格隔开'];
            }
        }
        $g['lottery_type'] = $lottery_type;
        $g['lottery_name'] = $lottery_name;
        $g['single'] = $single;
        $g['all_moneys'] = $all_moneys;
        $g['playMethod'] = $playMethod;
        #p(['g'=>$g, 'singleData'=>$singleData, 'betText'=>$betText], 0);
        if(empty($g['single']) OR empty($g['all_moneys'])){
            return [CommonBaseService::CODE_FOR_USER, [], '匹配倍数或金额异常'];
        }
        //var_dump('========='.$lottery_type.'=======');
        return [0, ['text'=>$betText, 'lottery_type'=>$lottery_type, 'g'=>$g], '匹配结束000'];
    }

    /**
     * 处理撤单匹配
     * @param string $text
     * @return array
     */
    public static function operateCancel(string $text='', $wechatUser=[]): array
    {
        try {
            if (preg_match('/(\d+)/', $text, $matches)) {
                $orderId = $matches[0];

                $Bets = BetsBackend::findOne(['order_id'=>$orderId, 'wechat_user_id'=>$wechatUser['id']]);
                if(empty($Bets)){
                    throw_info('单号：'.$orderId.'无记录', CommonBaseService::CODE_FOR_USER);
                }
                if($Bets->status==CommonBaseService::STATUS_LT_SUCCESS){
                    throw_info($orderId.'订单已完成，无法撤单', CommonBaseService::CODE_FOR_USER);
                }
                if($Bets->status==CommonBaseService::STATUS_LT_CANCEL){
                    throw_info($orderId.'订单已是撤单状态，无需重复处理', CommonBaseService::CODE_FOR_USER);
                }
                $orderBetMoney = BetsBackend::find()->select(['orderBetMoney'=>'SUM(bet_money)'])
                    ->where(['order_id'=>$orderId, 'wechat_user_id'=>$wechatUser['id']])
                    ->groupBy(['order_id'])->scalar();
                $vData = AgentUsersBalanceService::updateBalance((string)$orderId, $orderBetMoney, $wechatUser['id'], WechatUserService::TYPE_ORDER_CANCEL); # 撤单返还
                #$Bets->status = 3; # 已撤单
                BetsBackend::updateAll(['status'=>CommonBaseService::STATUS_LT_CANCEL], ['order_id'=>$orderId]);
                if(!$Bets->save()){
                    throw_info(Json::encode($Bets->getErrors()));
                }

            }else{
                throw_info('操作异常');
            }
        }catch (\Exception $e){
            $err_msg = ($e->getCode() == CommonBaseService::CODE_FOR_USER) ? $e->getMessage() : '撤单异常';
            return [CommonBaseService::CODE_FOR_USER, ['type'=>WechatUserService::TYPE_ORDER_CANCEL], $err_msg];
        }
        return [CommonBaseService::CODE_FOR_USER, ['type'=>WechatUserService::TYPE_ORDER_CANCEL], $orderId.'撤单成功，余分：'.$vData['balance']];
    }

    public static function preValidateTime($lottery_type=26){
        $dataHI = date('H:i');
        switch ($lottery_type){
            case 26: # 福
                if('21:10'<=$dataHI && $dataHI<='23:59'){
                    throw_info('停盘时间', CommonBaseService::CODE_FOR_USER);
                }
                break;
            case 27: # 排
                if('21:20'<=$dataHI && $dataHI<='23:59'){
                    throw_info('停盘时间', CommonBaseService::CODE_FOR_USER);
                }
                break;
        }
    }

    /**
     * 消息处理后的业务处理
     * @param string $text
     * @param string $fromUser 发送者的微信id
     * @param string $messageData 消息体内容
     */
    public function receive(string $text='', string $fromUser='', $messageData=[]): array
    {
        try {
            $transaction = static::getDb()->beginTransaction();
            # 校验
            list($code, $vdata, $msg) = self::validateReceive($this->user_id, $text);
            $this->setMemberInfo($fromUser);

            $text = $vdata['text'];
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-01', ['user_id'=>$this->user_id, 'text'=>$text]);
            list($code, $data, $msg) = $this->matchData($text);
            if($code == CommonBaseService::CODE_FOR_USER){
                $transaction->commit();
                return [$code, $data, $msg];
            }
            //p([$code, $data, $msg]);
            if($code>0){
                throw_info($msg);
            }

            $betOrderId = ThirdDTypeService::getOrderId();
            if(empty($betOrderId)){
                throw_info('单号生成失败');
            }
            $betCodeContents = $data['dataGroups']['betCodeContents'];
            //p($betCodeContents);
            if(empty($betCodeContents)){
                return [CommonBaseService::CODE_FOR_USER, [], '匹配异常323:请按格式输入'];
            }
            //p($betCodeContents);
            $now_time = time();
            $allMoneys = 0.00;
            $allCounts = 0;
            $replyTxts = [];
            $pushSiteDatas = [];
            foreach ($betCodeContents as $lottery_type=>$contents){
                # 校验开盘关盘
                //self::preValidateTime($lottery_type);

                $qihao = (string)HN0898Service::getQihao($lottery_type);
                $oneReplyTxt = '【课号】'.$contents[0]['lottery_name'].$qihao;
                $betContent = "\n【内容】";

                $oneAllMoneys = 0.00;
                $oneAllCounts = 0;
                foreach ($contents as $playMethods){
                    //p(['playMethod'=>$playMethods]);
                    $lottery_name = $playMethods['lottery_name'];
                    foreach ($playMethods['playMethod'] as $method){
                        if(empty($method['id'])){
                            throw_info('方式匹配为空，请按正确格式输入', CommonBaseService::CODE_FOR_USER);
                        }
                        if(empty($method['single']) OR empty($method['count'])){
                            throw_info('金额或号码数量解析异常，请按正确格式输入', CommonBaseService::CODE_FOR_USER);
                        }

                        $replyMethodName = PlayMethodService::getReplyMethodName($method['name']);
                        $oneBetContent = $replyMethodName.':'.str_replace([':',','],'',$method['codes']).'各'.$method['single'].'共'.$method['all_moneys'];
                        $replyContent = [
                            'replyTxt' => $oneBetContent,
                            'fromUser' => $fromUser,
                            'fromNickName' => $this->wechatUser['nickName'],
                            'fromGroup' => $messageData['fromGroup'],
                        ];
                        $Bets = new BetsBackend();
                        $setData = [
                            'user_id' => $this->user_id,
                            'wechat_user_id' => $this->member_id,
                            'order_id' => $betOrderId,
                            'play_method' => $method['id'],
                            'codes' => (string)$method['codes'],
                            'bet_money' => $method['all_moneys'],
                            'single' => $method['single'],
                            'count' => $method['count'],
                            'qihao' => $qihao,
                            'lottery_type' => $lottery_type,
                            'lottery_name' => $lottery_name,
                            'bet_desc' => $text,
                            'new_msg_id' => $messageData['newMsgId'],
                            'reply_type' => $this->wechatUser['reply_type'],
                            'reply_content' => Json::encode($replyContent),
                            'api_code_datas' => $playMethods['apiCodeDatas']?Json::encode($playMethods['apiCodeDatas']):'',
                            'created_at' => $now_time,
                            'updated_at' => $now_time,
                        ];
                        //p($setData, 0);
                        $Bets->setAttributes($setData, false);
                        if(!$Bets->save()){
                            //var_dump('1111');
                            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-02', ['user_id'=>$this->user_id, 'text'=>$text, 'member_id'=>$this->member_id, 'setData'=>$setData]);
                            throw_info(Json::encode($Bets->getErrors(), 320));
                        }
                        //var_dump('id'.$method['codes'].'_'.$Bets->id);
                        $oneAllMoneys += $method['all_moneys']; # 总投
                        $oneAllCounts += $method['count']; # 总投

                        $betContent .= "\n".$oneBetContent;

                        # 推送网盘任务：
                        $pushSiteDatas[] = ['betRowId'=>$Bets->id, 'orderId'=>$Bets->order_id, 'business_id'=>$Bets->order_id];
                    }
                }

                $vData = AgentUsersBalanceService::updateBalance((string)$betOrderId, $oneAllMoneys, $this->member_id, WechatUserService::TYPE_ORDER_BET); # 下单扣减
                $oneReplyTxt .= str_replace(';', ',', $betContent);
                $oneReplyTxt .= ("\n【单号】".$betOrderId);
                $oneReplyTxt .= ("\n【成功】√  共".$oneAllCounts."组，共".$oneAllMoneys.'咪');
                $oneReplyTxt .= ("\n【剩余】".$vData['balance'].'咪');

                if($this->wechatUser['reply_type']==BetsBackend::REPLY_TYPE_QUICK){
                    # 即时回复
                    $replyTxts[] = ['order_ids'=>[$betOrderId], 'replyTxt'=>$oneReplyTxt];
                }

                $allMoneys += $oneAllMoneys;
            }
            $transaction->commit();
            push_queue_fast(UserDayStaticsJobs::class, ['user_id'=>$this->user_id, 'type'=>$data['type'], 'msg'=>'下单/撤单之后计算', 'wechat_user_id'=>$this->member_id]);
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-异常', ['user_id'=>$this->user_id, 'text'=>$text, 'fromUser'=>$fromUser, 'err_code'=>$e->getCode(), 'err_msg'=>$e->getMessage().$e->getFile().$e->getLine()]);
            # 用户输入错误提示
            if($e->getCode() == CommonBaseService::CODE_FOR_USER){
                return [$e->getCode(), [], $e->getMessage()];
            }
            # 其它情况处理异常，直接抛异常
            throw_info($e->getMessage());
        }

        $logArr = ['user_id'=>$this->user_id, 'text'=>$text, 'fromUser'=>$fromUser, 'setData'=>$setData, 'replyTxts'=>$replyTxts, 'pushSiteDatas'=>$pushSiteDatas];
        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '消息处理-成功', $logArr);
        foreach ($pushSiteDatas as $pushSiteData){
            $second = BetService::getConfig('HOLD_ORDER_SECONDS')??120;
            $pushSiteData['queue_delay_time'] = $second;
            push_queue_open(SsxxBetJobs::class, $pushSiteData);
        }
        $data = [
            'type' => WechatUserService::TYPE_ORDER_BET,
            'text' => $text,
            'replyTxts' => $replyTxts,
            'allMoneys' => $allMoneys,
        ];

        return [0, $data, '接收成功'];
    }

    /**
     * 发送前校验
     * @param string $wcId
     * @param string $content
     */
    private function validatePreSend(string $wcId='', $content=''){
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
            if(empty($content)){
                throw_info('发送消息不能为空');
            }
            $this->validatePreSend($wcId, $content, $atIds);
            $url = $this->base_url . '/sendText';
            $params = [
                'wId' => $this->wId,
                'wcId' => trim($wcId), # 好友微信id/群id,多个好友/群 以","分隔每次最多支持20个微信/群号,记得本接口随机间隔300ms-1500ms，频繁调用容易导致掉线
                'content' => $content,
            ];
            if(!empty($atIds)){
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

    /**
     * 搜索好友
     * @param string $wcId
     * @return bool|mixed|null
     */
    public function searchUser($wcId=''){

        try {
            $url = $this->base_url . '/searchUser';
            $params = [
                'wId' => $this->wId,
                'wcId' => trim($wcId), # 好友微信id/群id,多个好友/群 以","分隔每次最多支持20个微信/群号,记得本接口随机间隔300ms-1500ms，频繁调用容易导致掉线
            ];
            $response = $this->request($url, $params, $this->headers);
        }catch (\Exception $e){
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '发送文本消息-异常', ['url'=>$url, 'params'=>$params, 'err_msg'=>$e->getMessage()]);
            return ['code'=>30001, 'message'=>$e->getMessage()];
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '发送文本消息', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }

    /**
     * 文本消息发送
     * @param string $wcId 私聊则位用户的微信id，群里则位群里id
     * @param string $content
     * @param array $atIds 私聊不传，群里at传用户微信id
     * @return bool|mixed|null
     */
    public function sendFile($wcId='', $filePath='', $fileName=''){

        try {
            //$this->validatePreSend($wcId, $content);
            $url = $this->base_url . '/sendFile';
            $params = [
                'wId' => $this->wId,
                'wcId' => trim($wcId), # 好友微信id/群id,多个好友/群 以","分隔每次最多支持20个微信/群号,记得本接口随机间隔300ms-1500ms，频繁调用容易导致掉线
                'path' => $filePath,
                'fileName' => $fileName,
            ];
            $response = $this->request($url, $params, $this->headers);
        }catch (\Exception $e){
            Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '发送文本消息-异常', ['url'=>$url, 'params'=>$params, 'err_msg'=>$e->getMessage()]);
            return ['code'=>30001, 'message'=>$e->getMessage()];
        }

        Tool_Common::log('/eyun/'.__FUNCTION__, 'INFO', '发送文本消息', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }
}
