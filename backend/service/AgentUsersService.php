<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\AgentUsers;
use backend\models\CodeTypes;
use common\service\CommonService;
use yii\helpers\ArrayHelper;
use  yii;

class AgentUsersService extends BaseService {

    public static function opPreData(&$post){

        $fields = ['is_tuo', 'is_cha', 'is_chi', 'is_bind'];
        $post = CommonService::opPreStatusFields($post, $fields, $model = 'AgentUsers');

        $data = $post[$model];
        if(!$id = $data['id']){
            $post[$model]['created_at'] = time();
        }
        $post[$model]['updated_at'] = time();

        return $post;
    }

    /**
     * @description 更新计划表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateAgentUsersStatus($id, $status, $uid = '', $field)
    {
        if(!$uid) return ['status'=>300, 'msg'=>'用户id为空'];
        $model = AgentUsers::findOne(['agent_id' => $uid, 'id' => $id]);
        if($model) return ['status'=>301, 'msg'=>'找不到记录'];
        $m = \Yii::$app->cache;
        $mkey = 'updateSysPlansStatus_'.$field.'_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return ['status'=>302, 'msg'=>'正在修改'];

        $model->$field = (int)$status;
        $model->updated_at = time();

        $m->set($mkey, 1, 10);

        $rst = $model->save(false);

        $rstData = ['rst'=>$rst];

        return $rstData;
    }


}