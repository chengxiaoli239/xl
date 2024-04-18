<?php
namespace common\service\lottery\aozhou5\jobs;

use common\models\thirdD\Bets;
use common\service\cache\CacheKeyService;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\jobs\telegram\MessageReceiveJobs;
use common\service\lottery\aozhou5\AoZhou5BetService;
use common\service\open\telegram\MessageOperateService;
use yii\helpers\Json;

class AoZhou5BetJobs extends CommonJob {
    # 无效状态，无需处理
    const INVALID_STATUS_CODE = 40000;

    public static function getName($params): string
    {
        self::$name = '40-用户下注推送盘口';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params): string
    {
        try {
            $betRowId = $params['betRowId'];
            $mKey = CacheKeyService::lotteryBetPostSiteKey($betRowId);
            $num = commonRedis()->incr($mKey);
            if($num>2){
                throw_info('只能尝试一次');
            }
            commonRedis()->expire($mKey, 15);
            if(empty($betRowId)){
                throw_info('下注记录ID为空', 40001);
            }

            list($code, $data, $msg) = AoZhou5BetService::postToSite($betRowId);
            if($code>0){
                throw_info($msg, $code);
            }
            Tool_Common::log('/bet_aozhou5/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'data'=>$data]);
        }catch (\Exception $e){
            $err_msg = $e->getMessage();
            Tool_Common::log('/bet_aozhou5/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'err_msg'=>$err_msg]);
            if($num<3 && strpos($err_msg, '重新下注') !== false){
                $params['queue_delay_time'] = 3;
                push_queue_open(AoZhou5BetJobs::class, $params);
            }else{
                $BetRow = Bets::findOne($betRowId);
                $AdminInfo = MessageOperateService::getAdminInfo($BetRow->user_id);
                $replyContent = Json::decode($BetRow->reply_content);

                MessageReceiveJobs::reply($BetRow->user_id, [$err_msg], ['targetId'=>$AdminInfo['userName'], 'token'=>$replyContent['token']]); # 回复消息
                throw_info($err_msg);
            }
        }

        return '下注记录推送盘口完成[betRowId:'.$betRowId.']';
    }

}
