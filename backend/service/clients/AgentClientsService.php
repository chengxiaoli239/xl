<?php
namespace backend\service\clients;

use backend\models\BetErrorPlansTask;
use backend\models\DataDealStatus;
use backend\models\SscKjData;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\BetService;
use backend\service\Lucky5\Lucky5Service;
use common\kj\ssc\Lucky5;
use common\service\CommonService;
use common\service\jobs\kj_data\GrabKjDatasJob;
use common\tools\RedisLock;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;

class AgentClientsService extends ClientsBaseService{

    /**
     * @desc 客户端开奖数据同步
     * @param array $kjData
     * @param string $access_token
     * @param int $lottery_type
     * @return array
     */
    public static function syncMemberBetLogs($kjData=[], $access_token='', $lottery_type=DEFAULT_LOTTERY_TYPE){

        try {
            $data = [];

            $params = ['lottery_type'=>$lottery_type, 'title'=>BetService::getLotteryName($lottery_type).'_网盘推送bet日志', 'business_id'=>$expect];
            push_queue(GrabKjDatasJob::class, $params);
        }catch (\Exception $e){
            return ['status'=>301, 'data'=>$data, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'data'=>$data, 'msg'=>'数据同步成功'];
    }
}
