<?php
/**
 * Desc 下注异常处理service
 * Date: 2018/12/10
 * Time: 17:28
 */

namespace backend\service\plans;

use backend\models\BetErrorPlansTask;
use backend\models\SscKjData;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\OpKjService;
use backend\service\StaticService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class BetErrorPlansTaskService extends BaseService
{

    public static function recordPlanTask($uid='',$account='', $plan_id='', $qihao='', $bet_sort_key=0, $bet_codes='', $tz_type = '', $bet_url='', $bet_headers=[], $post_datas='', $single = 0.1, $bet_moneys='', $playway = 3, $tz_system_id='', $error_rst = [], $lottery_type=DEFAULT_LOTTERY_TYPE, $status=0){
        $where = ['uid'=>$uid, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'plan_id'=>$plan_id, 'bet_sort_key'=>$bet_sort_key];
        if($r = BetErrorPlansTask::findOne($where)){
            return ['status'=>300, 'msg'=>'记录已存在:'.$uid.'_'.$lottery_type.'_'.$qihao.'_'.$plan_id.'_'.$bet_sort_key];
        }

        if(SscKjData::findOne(['lottery_type'=>$lottery_type, 'qihao'=>$qihao])){
            return ['status'=>300, 'msg'=>'开机记录已存在:'.$lottery_type.'_'.$qihao];
        }
        $plan = UserSysPlans::findOne($plan_id);

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
        $where = ['uid'=>$uid, 'lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'plan_id'=>$plan_id, 'bet_sort_key'=>$bet_sort_key];
        if(!$BetErrorPlansTask = BetErrorPlansTask::findOne($where)){
            $BetErrorPlansTask = new BetErrorPlansTask();
        }
        $isUnusual = BetErrorPlansTaskService::getStatusIsUnusual($playway, $single);
        $setData = [
            'uid' => $uid,
            'account' => $account,
            'plan_id' => $plan_id,
            'bet_direct' => $plan->bet_direct??UserSysPlans::BET_DIRECT_Z,
            'is_local_bet' => (int)$TzSystemsUsers->is_local_bet,
            'bet_sort_key' => $bet_sort_key,
            'qihao' => (string)$qihao,
            'codes' => json_encode($bet_codes, 320),
            'tz_type' => $tz_type,
            'tz_system_id' => $tz_system_id,
            'bet_url' => $bet_url,
            'bet_headers' => json_encode($bet_headers, 320),
            'post_datas' => $post_datas,
            'bet_money' => $bet_moneys,
            'lotteryclass' => BetService::lotteryClass($playway),
            'single' => $single,
            'playway' => $playway,
            'status' => ($status != 0) ? $status : ($isUnusual ? 4 : 0),
            'lottery_type' => $lottery_type,
            'error_desc' => json_encode($error_rst, 320),
            'updated_at' => time(),
            'created_at' => time(),
        ];

        $BetErrorPlansTask->setAttributes($setData);
        $flag = $BetErrorPlansTask->save();
        if(!$flag){
            $err_msg = json_encode($BetErrorPlansTask->getErrors(), 320);
            $logArr = ['setData'=>$setData, 'error'=>$err_msg];
            Tool_Common::log('/bet_errors/recordPlanTask', 'ERR', '下注失败记录异常', $logArr);
        }

        return $BetErrorPlansTask->id;
    }

    /**
     * @param $playway
     * @param string $single
     * @return bool
     */
    public static function getStatusIsUnusual($playway, $single=''){
        $flag = false;
        if($playway == 1 && $single == 0.1){ # 二定异常倍数
            $flag = true;
        }

        return $flag;
    }

}