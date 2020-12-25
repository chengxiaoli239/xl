<?php
/**
 * Desc 下注异常处理service
 * Date: 2018/12/10
 * Time: 17:28
 */

namespace backend\service\plans;

use backend\models\BetErrorPlansTask;
use backend\service\BaseService;
use backend\service\BetService;
use backend\service\OpKjService;
use backend\service\StaticService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class BetErrorPlansTaskService extends BaseService {

    public static function recordPlanTask($uid='',$account='', $plan_id='', $qihao='', $bet_sort_key=0, $bet_codes='', $tz_type = '', $bet_url='', $bet_headers=[], $post_datas='', $single = 0.1, $bet_moneys='', $playway = 3, $tz_system_id='', $error_rst = [], $lottery_type=DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $BetErrorPlansTask = new BetErrorPlansTask();
        $setDatas = [
            'uid' => $uid,
            'account' => $account,
            'plan_id' => $plan_id,
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
            'lottery_type' => $lottery_type,
            'error_desc' => json_encode($error_rst, 320),
            'updated_at' => time(),
            'created_at' => time(),
        ];

        $BetErrorPlansTask->setAttributes($setDatas);
        $flag = $BetErrorPlansTask->save();
        if(!$flag){
            $err_msg = json_encode($BetErrorPlansTask->getErrors(), 320);
            $logArr = ['setDatas'=>$setDatas, 'error'=>$err_msg];
            Tool_Common::log('/bet_errors/recordPlanTask', 'ERR', '下注失败记录异常', $logArr);
            $rst = ['status'=>300, 'msg'=>$err_msg];
        }

        return $rst;
    }

    /**
     * @desc 重新下注失败计划
     * @return array
     */
    public static function reBetErrorPlans($lottery_type = []){
        $rst = ['status'=>300, 'msg'=>'操作成功'];
        if(empty($lottery_type)){
            $lottery_types = StaticService::getLotteryTypes();
        }
        foreach ($lottery_types as $lottery_type) {
            $where = ['AND', ['IN', 'status', [0,1]], ['=', 'lottery_type', $lottery_type]];
            $BetErrorPlansTask = BetErrorPlansTask::find($where)->orderBy(['id'=>SORT_ASC])->all();
            p($BetErrorPlansTask);
        }

        return $rst;
    }
}