<?php
namespace common\service\open\telegram;

use backend\models\SscKjData;
use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\agent\AgentUsersService;
use common\exceptions\InfoException;
use common\helpers\lottery\LotteryBet;
use common\helpers\LotteryType;
use common\helpers\SscMethod;
use common\models\wechat\WechatUser;
use common\service\chat\Tool_Common;
use common\service\helpers\ThirdD;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\jobs\telegram\SendMessageJobs;
use common\service\lottery\aozhou5\jobs\AoZhou5BetJobs;
use common\service\message\Send;
use common\service\ssc\QihaoService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\PlayMethodService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\WechatUserService;
use yii\helpers\Json;

class MessageOperateService  extends BaseService
{
    public $user_id;
    public $platformUser;
    public $robotInfo;
    public $tzSystemUsers;

    public $member_id;

    /** @var string $split 组分割符 */
    public string $split = ',';

    /** @var string $mSplit 金额分割符 */
    public string $mSplit = '/';

    /** @var string $text */
    public string $text = '';

    /** @var array $betData */
    public array $betData=[];
    public $lottery_type;
    /** @var int 单注最低金额 */
    const ONE_MINI_MONEY = 1;

    public function __construct($userId, $fromId)
    {
        $this->user_id = $userId;
        $this->platformUser = WechatUserService::getWechatUsers($userId)[$fromId];
        $this->tzSystemUsers = TzSystemsUsers::findOne(['uid'=>$userId]);
        $this->robotInfo = WechatUserService::getRobotInfo($this->platformUser['robot_wechat']);
        $this->member_id = $this->platformUser['member_id'];
        $this->lottery_type = LotteryType::AZ_LUCKY_5;
        self::validateWechatUser();

        parent::__construct();
    }

    public static function tableName()
    {
        return '{{%bets}}';
    }

    public function validateWechatUser(){
        # 1、好友判断
        if(!$this->platformUser['status'] OR empty($this->platformUser)){
            throw_info($this->platformUser['nickName'].'好友接受消息状态未开启', 50001);
        }
    }

    private function resetText($text): void
    {
        $text = ThirdD::replaceManyNull($text);
        $this->text = str_replace([',', '，', ' ','。', "\n"], ',', trim($text));  # 多个空格替换成单个空格
    }

    /**
     * @return array
     */
    private function getBetData()
    {
        $betData = array_values(array_filter(explode($this->split, $this->text)));
        foreach ($betData as $data){
            list($methodId, $methodName) = SscMethod::getMethod($data);
            $datum = explode($this->mSplit, $data);
            if($methodId == SscMethod::FT_JIAO_ID && strpos((string)$datum[0], '角') === false){
                $datum[0] .= '角';
            }
            if($methodId == SscMethod::FT_ZHENG_ID && strpos((string)$datum[0], '正') === false){
                $datum[0] .= '正';
            }

            $codes = $datum[0];
            $single = $datum[1];
            $count = 1;
            if($methodId == SscMethod::FT_DS_ID){
                $codes = SscMethod::TYPE_DS_OPTIONS[$codes]??$codes;
            }
            if($methodId == SscMethod::FT_FAN_ID){
                $codes = str_replace(['番高', '高番', '高'], '番', $codes);
            }
            if(empty($codes) OR empty($single)){
                throw_info('下单格式错误！', CommonBaseService::CODE_FOR_USER);
            }

            $allMoneys = $count * $single;
            $this->betData[] = [
                'codes' => $codes,
                'single' => $single,
                'count' => $count,
                'all_moneys' => $allMoneys,
                'id' => $methodId,
                'name' => $methodName,
            ];
            if($allMoneys<self::ONE_MINI_MONEY){
                Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '下注金额异常', ['oBetData'=>$betData, 'betData'=>$this->betData, 'text'=>$this->text, 'data'=>$data, 'datum'=>$datum]);
                throw_info('单注最低:'.self::ONE_MINI_MONEY.'，请核实后重新下注', CommonBaseService::CODE_FOR_USER);
            }
        }
    }

    /**
     * 匹配别的操作(主要是上下分) - 非下注逻辑
     * @param $text
     * @return array
     */
    private function matchOtherOperate($text, $message=[]): array
    {
        // 判断数组中是否有匹配的元素
        //$matches = array_intersect(['+', '上', '-', '下'], preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY));
        //todo 待处理逻辑 匹配到了其中一个元素，执行逻辑 A
        switch ($text){
            case strpos($text, '查') !== false: // 查余额
                return AgentUsersService::userGetInfo($this->platformUser);
            case strpos($text, '撤') !== false: // 撤单
                return EYunMessageOperateService::operateCancel($text, $this->platformUser);
            #case strpos($text, '+') !== false:
            #case strpos($text, '-') !== false:
            case strpos($text, '上') !== false:
            case strpos($text, '下') !== false:
                # 上下分逻辑
                list($code, $data, $msg) = AgentUsersBalanceService::operateBalanceChange($text, $this->platformUser, $message);
                Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '充值申请处理结果', ['code'=>$code, 'data'=>$data, 'msg'=>$msg]);
                if($code==CommonBaseService::CODE_FOR_IGNORE && !empty($data)){
                    (new Send($this->user_id))->replyAfterAction($this->platformUser, [$msg, $msg.'，申请ID:'.$data['apply_id']."\n".'，输入："'.$data['apply_id'].'通过" 或 "'.$data['apply_id'].'拒绝“'], Send::ACTION_BALANCE);
                    return [CommonBaseService::CODE_FOR_IGNORE, [], '异步处理'];
                }
                return [$code, $code, $msg];
            default:
                $status = (new LotteryBet())->checkLotteryStatus($this->lottery_type);
                Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '盘口状态检测', ['lottery_type'=>$this->lottery_type, 'status'=>$status, 'STATUS_START'=>LotteryBet::STATUS_START]);

                if($status != LotteryBet::STATUS_START){
                    throw_info('后台尚未开盘', CommonBaseService::CODE_FOR_USER);
                }

                break;
        }

        return [0, [], '下注逻辑'];
    }

    /**
     * @param $message
     * @return array
     */
    public function receive($message, $token): array
    {
        list($messageId , $from, $chat, $date, $text) = [$message['message_id'], $message['from'], $message['chat'], $message['date'], $message['text']];
        //p([$message_id , $from, $chat, $date, $text]);

        //$text = "1正/20 2念1/20";
        list($code, $vData, $msg) = $this->matchOtherOperate($text, ['message'=>$message]);
        if(in_array($code, [CommonBaseService::CODE_FOR_USER, CommonBaseService::CODE_FOR_IGNORE])){
            return [$code, $vData, $msg];
        }
        //p($this->platformUser);

        $this->resetText($text);
        try {
            $transaction = static::getDb()->beginTransaction();
            list($code, $data, $msg) = $this->getBetData();
            if($code == CommonBaseService::CODE_FOR_USER){
                throw_info($msg, CommonBaseService::CODE_FOR_USER);
            }
            if(empty($this->betData)){
                throw_info('下注格式错误', CommonBaseService::CODE_FOR_USER);
            }

            list($lotteryType, $lotteryName) = [LotteryType::AZ_LUCKY_5, LotteryType::TYPE_OPTIONS[LotteryType::AZ_LUCKY_5]];
            list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($lotteryType);
            if(SscKjData::findOne(['lottery_type'=>$lotteryType, 'qihao'=>$qiHao])){
                throw_info('未开盘请稍后', CommonBaseService::CODE_FOR_USER);
            }

            $pushSiteData = [];
            $now_time = time();
            //p($this->betData);
            $betOrderId = LotteryType::getOrderId();
            foreach ($this->betData as $method){
                if(empty($method['id'])){
                    throw_info('方式匹配为空，请按正确格式输入', CommonBaseService::CODE_FOR_USER);
                }
                if(empty($method['single']) OR empty($method['count'])){
                    throw_info('金额或号码数量解析异常，请按正确格式输入', CommonBaseService::CODE_FOR_USER);
                }

                $replyMethodName = PlayMethodService::getReplyMethodName($method['name']);
                $oneBetContent = "\n".$replyMethodName.'：'.str_replace([':',','],'',$method['codes']).'各'.$method['single'].'共'.$method['all_moneys'];
                $replyContent = [
                    'replyTxt' => $oneBetContent,
                    'fromUser' => $from['id'],
                    'fromNickName' => $this->platformUser['nickName'],
                    'token' => $token,
                ];
                $robotId = explode(':', $token)[0];
                $Bets = new BetsBackend();
                $setData = [
                    'user_id' => $this->user_id,
                    'wechat_user_id' => $this->platformUser['id'],
                    'order_id' => $betOrderId,
                    'robot_id' => (int)$robotId,
                    'site_account' => $this->tzSystemUsers->account, # 盘口账号
                    'play_method' => $method['id'],
                    'codes' => (string)$method['codes'],
                    'bet_money' => $method['all_moneys'],
                    'single' => $method['single'],
                    'count' => $method['count'],
                    'qihao' => $qiHao,
                    'lottery_type' => $lotteryType,
                    'lottery_name' => $lotteryName,
                    'bet_desc' => $text,
                    'new_msg_id' => (string)$messageId,
                    'reply_type' => $this->platformUser['is_need_confirm']?0:$this->platformUser['reply_type'],
                    'is_need_confirm' => $this->platformUser['is_need_confirm'],
                    'reply_content' => Json::encode($replyContent)??'',
                    'api_code_datas' => $method['apiCodeDatas']?Json::encode($method['apiCodeDatas']):'',
                    'created_at' => $now_time,
                    'updated_at' => $now_time,
                ];
                //p($setData, 0);
                $Bets->setAttributes($setData, false);
                if(!$Bets->save()){
                    Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '消息处理-02', ['user_id'=>$this->user_id, 'text'=>$text, 'member_id'=>$this->member_id, 'setData'=>$setData]);
                    throw_info(Json::encode($Bets->getErrors(), 320));
                }
            }
            # 推送网盘任务：
            $pushSiteData[] = ['orderId'=>$Bets->order_id, 'business_id'=>$Bets->order_id];
            $transaction->commit();;
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '消息处理-异常', ['code'=>$e->getCode(), 'err_msg'=>$e->getMessage(), 'file'=>$e->getFile().'_'.$e->getLine()]);
            if($e->getCode() == CommonBaseService::CODE_FOR_USER){
                return [CommonBaseService::CODE_FOR_USER, [], $e->getMessage()];
            }
        }

        foreach ($pushSiteData as $pushData){
            push_queue_open(AoZhou5BetJobs::class, $pushData);
        }
        $data = [
            'type' => WechatUserService::TYPE_ORDER_BET,
            'text' => $text,
        ];
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '消息处理-成功', [
            'user_id'=>$this->user_id,
            'text'=>$text,
            'fromUser'=>$from['id'],
            'setData'=>$setData,
            'pushSiteData'=>$pushSiteData,
            'data'=>$data,
        ]);

        return [0, $data, '接收成功'];
    }


    public static function getAdminInfo($userId)
    {
        $WechatUser = WechatUser::find()->where(['user_id'=>$userId, 'is_admin'=>WechatUser::MEMBER_TYPE_ADMIN])->asArray()->limit(1)->one();
        if(empty($WechatUser)){
            throw_info('没设置管理员');
        }

        return $WechatUser;
    }

    /**
     * 消息回复前处理
     * @param $user_id
     * @param array $replyTxt '您好，您的申请已通过'
     * @param array $data ['fromUser'=>'wxid_875i1kgd38x122']; 机器人要回复用到相关的id、token等
     * @return bool
     * @throws InfoException
     */
    public function reply($user_id, $replyTxt='', array $data=[]){
        $targetUser = $data['targetId'];# 目标微信好友
        $mkey = md5(__FUNCTION__.'_x1_'.$user_id.'_'.$replyTxt.'_'.$targetUser);
        $incr = \Yii::$app->redis->incr($mkey);
        $switch = \Yii::$app->params['AZ_MESSAGE_SWITCH']??0;
        Tool_Common::log('/telegram/'.__FUNCTION__, 'INFO', '消息回复前处理1', ['user_id'=>$user_id, 'replyTxt'=>$replyTxt, 'data'=>$data,'switch'=>$switch]);
        if(empty($switch)){
            return false;
        }
        if($incr>1){
            return false;
        }
        \Yii::$app->redis->expire($mkey, 2);
        if(empty($targetUser)){
            return '接收的平台好友Id不能为空0';
        }
        if(empty($replyTxt)){
            throw_info('回复消息replyTxt为空');
        }
        $sendData = [
            'user_id' => $user_id,
            'chat_id' => $targetUser, # 谁发就给谁回复，要先判断是否是群聊，判断条件：fromGroup 存在且有值
            //'queue_delay_time' => rand(2, 4), # self::$waitSeconds,
            'content' => $replyTxt, # 测试阶段调试信息 - 用户下注完回复
            'business_id' => $user_id,
            'token' => $data['token'],
        ];
        if(!empty($data['fromGroup'])){
            $sendData['fromGroup'] = $data['fromGroup'];
            $sendData['content'] = '@'.$data['fromUserNickName']."\n". $sendData['content']."\n";
        }
        push_queue(SendMessageJobs::class, $sendData); # TG消息发送

        return true;
    }
}
