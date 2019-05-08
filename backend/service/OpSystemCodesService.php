<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\SysPlansCodes;
use common\tools\Tool_Common;
use  yii;

class OpSystemCodesService extends BaseService {

    /**
     * @desc 处理系统三定投注计划号码
     * @param string $qihao
     * @return array
     */
    public static function sysPlansCodes($qihao = ''){

        $SysPlansCodes = SysPlansCodes::find()->where('1=1')->orderBy(['rand()' => SORT_DESC])->all();
        $rst = ['status'=>200, 'msg'=>'操作成功!'];
        $i = 0;
        foreach ($SysPlansCodes as $key=>$plansCode){
            $status = TzService::getSysTemPlansBetStatus($plansCode->playway, $plansCode->code);
            $opData['status'] = $status;
            if($status){
                $i++;
            }
            if($i>20) $opData['status'] = 0;
            $opData['updated_at'] = time();
            $opData['update_time'] = date('Y-m-d H:i:s');
            $plansCode->setAttributes($opData);
            //p($plansCode);
            if(!$flag = $plansCode->save()){
                $logData = ['msg'=>current($plansCode->getErrors()),'attributes'=>$plansCode->attributes];
                $rst_desc = '失败';
                $rst = ['status'=>300, 'msg'=>'计划添加失败'];
            }else{
                $rst_desc = '成功';
                $logData = ['qihao'=>$qihao,'msg'=>'处理成功','status'=>$status, 'opData'=>$opData, 'sys_plans_id'=>$plansCode->id,'i'=>$i];
            }
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/opSystemBetPlans','INFO','定制化投注计划-'.$rst_desc, $logData);
        }

        return $rst;
    }


}