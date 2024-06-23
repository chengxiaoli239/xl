<?php
namespace common\service\lottery\aozhou5\jobs;

use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\service\agent\AgentUsersBalanceService;
use backend\service\BetService;
use common\helpers\LotteryType;
use common\helpers\SscMethod;
use common\models\thirdD\Bets;
use common\models\wechat\WechatUser;
use common\service\cache\CacheKeyService;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\jobs\telegram\MessageReceiveJobs;
use common\service\lottery\aozhou5\AoZhou5BetService;
use common\service\message\Send;
use common\service\open\telegram\MessageOperateService;
use common\service\thirdD\PlayMethodService;
use common\service\wechat\WechatUserService;
use yii\helpers\Json;

class AoZhou5BetJobs extends CommonJob {
    # 无效状态，无需处理
    const INVALID_STATUS_CODE = 40000;

    public static function getName($params): string
    {
        self::$name = '40-用户下注推送盘口0';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params): string
    {
        try {
            $orderId = $params['orderId'];
            if(empty($orderId)){
                return '下注单号ID为空';
            }
            $BetRows = Bets::findAll(['order_id'=>$params['orderId']]);

            $firstBetRow = $BetRows[0];
            $userId = $firstBetRow->user_id;
            $qiHao = $firstBetRow->qihao;

            $replyContent = Json::decode($firstBetRow->reply_content);
            $messageService = new MessageOperateService($userId, $replyContent['fromUser']);
            list($lotteryType, $lotteryName) = [LotteryType::AZ_LUCKY_5, LotteryType::TYPE_OPTIONS[LotteryType::AZ_LUCKY_5]];

            $preContent = "\n【内容】";
            $betContent = '【课号】'.$lotteryName.'-'.$qiHao.$preContent;

            $allMoneys = 0.00;
            $allCount = 0;
            $errContent = '';
            $haveSuccess = 1;
            $TzSystemUsers = TzSystemsUsers::find()->where(['uid'=>$userId])->limit(1)->one();
            foreach ($BetRows as $betRow){
                if(in_array($TzSystemUsers->is_local_bet, [BetsBackend::BET_TYPE_LOCAL_SELENIUM, BetsBackend::BET_TYPE_LOCAL_API])){
                    $betRst = $params['betRst'];
                    # 本地selenium模拟点击、或本地电脑api
                    $code = ($betRow->push_status==BetsBackend::PUSH_STATUS_CANNOT)?10004:0;
                    if($code>0){
                        $msg = $betRst['msg'];
                    }
                }else{
                    list($code, $data, $msg) = AoZhou5BetService::postToSite($betRow->id);
                }
                if($code>0){
                    $haveSuccess = 0;
                    $errContent .= $betRow->codes.'/'.$betRow->single.'：'.$msg."\n";
                    continue;
                }
                $allMoneys += $betRow->bet_money; # 总投
                list($methodId, $methodName) = SscMethod::getMethod($betRow->codes);
                $replyMethodName = PlayMethodService::getReplyMethodName($methodName);
                $oneBetContent = "\n".$replyMethodName.'：'.str_replace([':',','],'', $betRow->codes).'各'.$betRow->single.'共'.$betRow->bet_money;

                //var_dump('id'.$method['codes'].'_'.$Bets->id);
                $allCount += 1; # 总投

                $betContent .= str_replace(';', ',', $oneBetContent);
            }

            $mKey = CacheKeyService::updateBalanceKey($TzSystemUsers->id);
            if($haveSuccess && commonRedis()->setnx($mKey, 1)){
                try {
                    $betMoney = BetsBackend::find()->where(['order_id'=>$params['orderId']])->sum('bet_money');
                    AgentUsersBalanceService::updateBalance((string)$betRow['id'], $betMoney, $betRow->wechat_user_id, WechatUserService::TYPE_ORDER_BET);
                }catch (\Exception $e){
                    BetsBackend::updateAll(['push_status'=>BetsBackend::PUSH_STATUS_CANNOT], ['order_id'=>$params['orderId']]);
                    Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '下注成功，更新余额异常', ['params'=>$params, 'err_msg'=>$e->getMessage()]);
                }
            }

            $WechatUser = WechatUser::findOne($betRow->wechat_user_id);

            if($haveSuccess){
                $betContent .= ("\n【单号】".$orderId);
                $betContent .= ("\n【成功】√  共".$allCount."组，共".$allMoneys.'咪');
            }

            if(!empty($errContent)){
                $betContent .= "\n【失败】".$errContent.' 异常请重新确认';
            }
            $betContent .= ("\n【剩余】".$WechatUser->balance.'咪');
            # 即时回复
            $replyTxt = $betContent;

            (new Send($userId))->replyAfterAction($WechatUser, [$replyTxt, "下注描述\n" . $replyTxt], Send::ACTION_BET); # 发送消息
            //$messageService->reply($userId, $replyTxt, ['targetId'=>$replyContent['fromUser'], 'token'=>$replyContent['token']]); # 回复消息
            push_queue_fast(UserDayStaticsJobs::class, [
                'user_id'=>$userId,
                'type'=>WechatUserService::TYPE_ORDER_BET,
                'msg'=>'下单/撤单之后计算',
                'wechat_user_id'=>$WechatUser->id,
            ]);
            Tool_Common::log('/bet_aozhou5/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'data'=>$data]);
        }catch (\Exception $e){
            $err_msg = $e->getMessage();
            Tool_Common::log('/bet_aozhou5/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'err_msg'=>$err_msg]);
            $BetRow = Bets::findOne(['order_id'=>$orderId]);

            $AdminInfo = MessageOperateService::getAdminInfo($BetRow->user_id);
            # 异常给管理员发信息
            $messageService->reply($BetRow->user_id, [$err_msg], ['targetId'=>$AdminInfo['userName'], 'token'=>$replyContent['token']]); # 回复消息
            throw_info($err_msg);
        }

        return '下注记录推送盘口处理结束[betRowId:'.$orderId.']';
    }

}
