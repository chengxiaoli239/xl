<?php
namespace common\service\open\telegram;

use backend\models\DataDealStatus;
use backend\models\thirdD\BetsBackend;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\SscDataService;
use common\helpers\LotteryType;
use common\helpers\SscMethod;
use common\service\chat\Tool_Common;
use common\service\ssc\QihaoService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\jobs\SsxxBetJobs;
use common\service\thirdD\PlayMethodService;
use common\service\wechat\WechatUserService;
use yii\helpers\Json;

class MessageOperateService  extends BaseService
{
    public $user_id;
    public $platformUser;

    public $member_id;

    /** @var string $split 组分割符 */
    public string $split = ',';

    /** @var string $mSplit 金额分割符 */
    public string $mSplit = '/';

    /** @var string $text */
    public string $text = '';

    /** @var array $betData */
    public array $betData=[];
    /** @var int 单注最低金额 */
    const ONE_MINI_MONEY = 20;

    public function __construct($userId, $fromId)
    {
        $this->user_id = $userId;
        $this->platformUser = WechatUserService::getWechatUsers($userId)[$fromId];
        $this->member_id = $this->platformUser['member_id'];
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
        $this->text = str_replace([',', '，', ' ','。', "\n"], ',', trim($text));
    }

    /**
     * @return void
     */
    private function getBetData(): void
    {
        $betData = explode($this->split, $this->text);
        foreach ($betData as $datum){
            list($methodId, $methodName) = SscMethod::getMethod($datum);
            $datum = explode($this->mSplit, $datum);
            $allMoneys = 1 * $datum[1];
            $this->betData[] = [
                'codes' => $datum[0],
                'single' => $datum[1],
                'count' => 1,
                'all_moneys' => $allMoneys,
                'id' => $methodId,
                'name' => $methodName,
            ];
            if($allMoneys<20){
                throw_info('单注最低:'.self::ONE_MINI_MONEY.'，请核实后重新下注', CommonBaseService::CODE_FOR_USER);
            }
        }
    }

    /**
     * 匹配别的操作(主要是上下分) - 非下注逻辑
     * @param $text
     * @return array
     */
    private static function matchOtherOperate($text): array
    {
        // 判断数组中是否有匹配的元素
        $matches = array_intersect(['+', '上', '-', '下'], preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY));
        if (!empty($matches)) {
            //todo 待处理逻辑
            // 匹配到了其中一个元素，执行逻辑 A
            switch (current($matches)){
                case '+':
                case '上':
                    # 上分逻辑
                    break;
                case '-':
                case '下':
                    # 下分逻辑
                    break;
            }
            return [CommonBaseService::CODE_FOR_USER, [], '申请成功待审核'];
        }

        return [0, [], '下注逻辑'];
    }

    /**
     * @param $message
     * @return array
     */
    public function receive($message): array
    {
        list($message_id , $from, $chat, $date, $text) = [$message['message_id'], $message['from'], $message['chat'], $message['date'], $message['text']];
        //p([$message_id , $from, $chat, $date, $text]);

        $text = "1正/20 2念1/20";
        list($code, $vData, $msg) = self::matchOtherOperate($text);
        if($code == CommonBaseService::CODE_FOR_USER){
            return [$code, $vData, $msg];
        }
        //p($this->platformUser);

        $this->resetText($text);
        try {
            $transaction = static::getDb()->beginTransaction();
            $this->getBetData();
            if(empty($this->betData)){
                return [CommonBaseService::CODE_FOR_USER, [], '下注格式错误'];
            }

            list($lotteryType, $lotteryName) = [LotteryType::AZ_LUCKY_5, LotteryType::TYPE_OPTIONS[LotteryType::AZ_LUCKY_5]];
            list($currentKjQiHao, $qiHao) = QihaoService::getKjQiHao($lotteryType);

            $oneAllMoneys = 0.00;
            $oneAllCounts = 0;
            $pushSiteData = [];
            $now_time = time();
            //p($this->betData);
            $oneReplyTxt = '【课号】'.$lotteryName.$qiHao;
            $betContent = "\n【内容】";
            $betOrderId = LotteryType::getOrderId();
            foreach ($this->betData as $method){
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
                    'fromUser' => $from['id'],
                    'fromNickName' => $this->platformUser['nickName'],
                    'fromGroup' => $messageData['fromGroup'],
                ];
                $Bets = new BetsBackend();
                $setData = [
                    'user_id' => $this->user_id,
                    'wechat_user_id' => $this->platformUser['id'],
                    'order_id' => $betOrderId,
                    'play_method' => $method['id'],
                    'codes' => (string)$method['codes'],
                    'bet_money' => $method['all_moneys'],
                    'single' => $method['single'],
                    'count' => $method['count'],
                    'qihao' => $qiHao,
                    'lottery_type' => $lotteryType,
                    'lottery_name' => $lotteryName,
                    'bet_desc' => $text,
                    'new_msg_id' => $messageData['newMsgId'],
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
                    //var_dump('1111');
                    Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '消息处理-02', ['user_id'=>$this->user_id, 'text'=>$text, 'member_id'=>$this->member_id, 'setData'=>$setData]);
                    throw_info(Json::encode($Bets->getErrors(), 320));
                }
                //var_dump('id'.$method['codes'].'_'.$Bets->id);
                $oneAllMoneys += $method['all_moneys']; # 总投
                $oneAllCounts += $method['count']; # 总投

                $betContent .= "\n".$oneBetContent;

                if(!$this->platformUser['is_need_confirm']){ # 无需确认即可直接上盘口
                    # 推送网盘任务：
                    $pushSiteData[] = ['betRowId'=>$Bets->id, 'orderId'=>$Bets->order_id, 'business_id'=>$Bets->order_id];
                }

                $oneReplyTxt .= str_replace(';', ',', $betContent);
                $oneReplyTxt .= ("\n【单号】".$betOrderId);
                if($this->platformUser['is_need_confirm']){
                    $oneReplyTxt .= ("\n【合计】 共".$oneAllCounts."组，共".$oneAllMoneys.'咪');
                    $oneReplyTxt .= ("\n【状态】 待确认");
                }else{
                    $oneReplyTxt .= ("\n【成功】√  共".$oneAllCounts."组，共".$oneAllMoneys.'咪');
                    $vData = AgentUsersBalanceService::updateBalance((string)$betOrderId, $oneAllMoneys, $this->member_id, WechatUserService::TYPE_ORDER_BET); # 下单扣减
                    $oneReplyTxt .= ("\n【剩余】".$vData['balance'].'咪');
                }
                if($this->platformUser['is_need_confirm']==BetsBackend::NEED_CONFIRM_YES OR $this->platformUser['reply_type']==BetsBackend::REPLY_TYPE_QUICK){
                    # 即时回复
                    $replyTxts[] = ['order_ids'=>[$betOrderId], 'replyTxt'=>$oneReplyTxt];
                }

                $allMoneys += $oneAllMoneys;
            }
            $transaction->commit();;
            //p([$message, $text, $this->betData]);
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '消息处理-异常', ['code'=>$e->getCode(), 'err_msg'=>$e->getMessage(), 'file'=>$e->getFile().'_'.$e->getLine()]);
            if($e->getCode() == CommonBaseService::CODE_FOR_USER){
                return [CommonBaseService::CODE_FOR_USER, [], $e->getMessage()];
            }

        }
        //p('llll');

        $logArr = ['user_id'=>$this->user_id, 'text'=>$text, 'fromUser'=>$from['id'], 'setData'=>$setData, 'replyTxts'=>$replyTxts, 'pushSiteData'=>$pushSiteData];
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '消息处理-成功', $logArr);
        foreach ($pushSiteData as $pushData){
            push_queue_open(SsxxBetJobs::class, $pushData);
        }
        $data = [
            'type' => WechatUserService::TYPE_ORDER_BET,
            'text' => $text,
            'replyTxts' => $replyTxts,
            'allMoneys' => $allMoneys,
        ];

        return [0, $data, '接收成功'];
    }



}
