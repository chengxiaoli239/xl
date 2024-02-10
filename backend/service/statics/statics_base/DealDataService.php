<?php
namespace backend\service\statics\statics_base;

use backend\models\DataDealStatus;
use backend\models\LotteryDataDealStatus;
use backend\service\BaseService;
use backend\service\HN0898Service;
use backend\service\SscDataService;
use common\tools\Tool_Common;

class DealDataService extends BaseService
{
    public static array $dealDataStatusFields = [
        'status' => '全局状态',
        'static4dPerDateProfits_status' => 'A每天四定利润统计',
        'updateDs_status' => 'B单双处理状态',
        'updateDsYL_status' => 'C单双遗漏处理状态',
        'update3NumYL_status' => 'D开奖三字现处理状态',
        'updateSdHzYL_status' => 'E和值遗漏状态',
        'opProfitsPlans_status' => 'F投注计划处理状态',
    ];

    /**
     * @param $lottery_type
     * @param string $qihao
     * @param string $field
     * @return DataDealStatus|null
     * @throws \Exception
     */
    public static function judgeDealTaskStatus($lottery_type, string $qihao='', string $field='updateDs_status'): ?DataDealStatus
    {
        if(empty($qihao)){
            $qihao = HN0898Service::getCurrentQihao($lottery_type);
        }
        Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '逐期统计', ['lottery_type'=>$lottery_type, 'qihao'=>$qihao, 'field'=>$field]);
        $DataDealStatus = DataDealStatus::findOne(['lottery_type'=>$lottery_type, 'qihao'=>$qihao]);
        $key = 'judgeDealTaskStatus_'.$lottery_type.'_'.$qihao;
        if(empty($DataDealStatus)){
            $num = \Yii::$app->redis->incrby($key, 1);
            \Yii::$app->redis->expire($key, 120);
            if($num>5){
                SscDataService::insertDealDataTask($lottery_type, $qihao); # 数据处理任务写入
            }
            throw new \Exception('无任务记录'.$lottery_type.'_'.$qihao.'_num:'.$num);
        }
        if($DataDealStatus->$field == 1){
            throw new \Exception('已经处理过数据'.self::$dealDataStatusFields[$field]);
        }

        return $DataDealStatus;
    }

    /**
     * @desc 处理数据任务状态
     * @param $DataDealStatus
     * @param string $field
     * @param int $status
     * @return bool
     */
    public static function dealDataRecord($DataDealStatus, $field='status', $status=0, $dealDesc=[]): bool
    {
        if(empty($DataDealStatus)){
            return false;
        }

        try {
            if($DataDealStatus->$field == 1){
                throw new \Exception('已经处理过数据');
            }
            if($DataDealStatus->lottery_type == 8 && substr($DataDealStatus->next_qihao, -3, 3) == '109'){
                $status = SscDataService::DEAL_DATA_STATUS_SUCCESS;
            }
            $DataDealStatus->$field = $status;
            $all_status = SscDataService::DEAL_DATA_STATUS_SUCCESS;
            $all_keys = array_keys(SscDataService::$dealDataStatusFields);
            foreach ($all_keys as $key){
                if($key == 'status') continue;
                if(!in_array($DataDealStatus->$key, [SscDataService::DEAL_DATA_STATUS_SUCCESS, SscDataService::DEAL_DATA_STATUS_NOT_NEED_DEAL])){
                    $all_status = 0;
                }
            }
            $DataDealStatus->status = $all_status; # 所有任务状态
            $DataDealStatus->updated_at = time();
            $DataDealStatus->{$field.'_desc'} = json_encode($dealDesc, 320);
            $DataDealStatus->save();
        }catch (\Exception $e){
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '处理数据任务状态', ['lottery_type'=>$DataDealStatus->lottery_type, 'next_qihao'=>$DataDealStatus->next_qihao, 'qihao'=>$DataDealStatus->qihao, 'field'=>$field, 'status'=>$status, 'dealDesc'=>$dealDesc, 'err_msg'=>$e->getMessage(), 'line'=>$e->getLine(), 'file'=>$e->getFile()]);
            return false;
        }

        return true;
    }

    /**
     * @desc 记录处理数据开关
     * @param int $lottery_type
     * @return bool
     */
    public static function insertLotteryDealDataStatus(int $lottery_type=DEFAULT_LOTTERY_TYPE): bool
    {
        try {
            if(empty($lottery_type)){
                throw_info('彩种类型lottery_type不能为空');
            }

            $DataDealStatus = LotteryDataDealStatus::findOne(['lottery_type'=>$lottery_type]);
            if(!empty($DataDealStatus)){
                throw new \Exception('数据处理开关记录已存在'.$lottery_type);
            }

            $now_time = time();
            $setDatas = [
                'status' => 0, # 所有数据处理状态
                'lottery_type' => $lottery_type,
                'static4dPerDateProfits_status' => 0, # A每天四定利润统计状态
                'updateDs_status' => 0, # B单双处理状态
                'updateDsYL_status' => 0, # C单双遗漏处理状态
                'update3NumYL_status' => 0, # D单双遗漏处理状态
                'updateSdHzYL_status' => 0, # E单双遗漏处理状态
                'opProfitsPlans_status' => 0, # F投注计划处理状态
                'created_at' => $now_time,
                'updated_at' => $now_time,
            ];
            $LotteryDataDealStatus = new LotteryDataDealStatus();
            $LotteryDataDealStatus->setAttributes($setDatas);
            if(!$LotteryDataDealStatus->save()){
                throw new \Exception(json_encode($LotteryDataDealStatus->getErrors(), 320));
            }

        }catch (\Exception $e){
            Tool_Common::log('/data/'.__FUNCTION__, 'ERR', '数据处理控制开关写入异常', ['lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()]);
            return false;
        }

        return true;
    }
}
