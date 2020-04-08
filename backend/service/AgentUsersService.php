<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\AgentUsers;
use backend\models\AgentUsersBalanceFlows;
use backend\models\CodeTypes;
use common\service\CommonService;
use yii\helpers\ArrayHelper;
use  yii;

class AgentUsersService extends BaseService {

    public static function opPreData(&$post, $agent_id=''){
        if(!$post OR !$agent_id) return false;

        $fields = ['is_tuo', 'is_cha', 'is_chi', 'is_bind'];
        $post = CommonService::opPreStatusFields($post, $fields, $model = 'AgentUsers');

        $data = $post[$model];
        if(!$id = $data['id']){
            $post[$model]['created_at'] = time();
            $post[$model]['token'] = self::getUserToken($post['name'], $agent_id);
        }

        $post[$model]['agent_id'] = $agent_id;
        $post[$model]['updated_at'] = time();

        return $post;
    }

    /**
     * @desc 获取用户token，用于聊天窗口识别用户
     * @param string $name
     * @param string $agent_id
     * @return string
     */
    public static function getUserToken($name = '', $agent_id = '', $randKey = 'MLGB'){
        return substr(md5($name.'_'.$agent_id.'_'.$randKey), 0,12);
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
        if(!$model) return ['status'=>301, 'msg'=>'找不到记录'];
        $m = \Yii::$app->cache;
        $mkey = 'updateSysPlansStatus_'.$field.'_'.$id.'_'.$status;
        //if($rst = $m->get($mkey)) return ['status'=>302, 'msg'=>'正在修改'];
        //p([$id, $status, $uid , $field]);

        $model->$field = (int)$status;
        $model->updated_at = time();
        $model->token = $model->token ? $model->token : self::getUserToken($model->name, $uid);

        $m->set($mkey, 1, 10);

        $rst = $model->save(false);

        $rstData = ['rst'=>$rst];

        return $rstData;
    }

    public static function actUpUserData($post, $agent_id = ''){
        if(!$agent_id) return ['status'=>301, 'msg'=>'不是代理账号，不能修改用户信息'];
        $act = $post['act'];
        $id = $post['id'];

        $rst = ['status'=>200];
        if(in_array($act, ['act-up-balance', 'act-down-balance'])){
            if(!$balance = trim($post['balance'])){
                $rst = ['status'=>302, 'msg'=>'积分不能为空'];
            }else{
                $AgentUsers = AgentUsers::findOne(['agent_id'=>$agent_id, 'id'=>$id]);
                if($act == 'act-up-balance'){
                    $f = '加';
                    $AgentUsers->balance = $AgentUsers->balance + $balance;
                }elseif ($act == 'act-down-balance'){
                    $f = '减';
                    $AgentUsers->balance = $AgentUsers->balance - $balance;
                }

                if(!$flag = $AgentUsers->save()){
                    //$rst = ['status'=>303, 'msg'=>$AgentUsers->getFirstError()];
                    $rst = ['status'=>303, 'msg'=>'用户['.$AgentUsers->name.']'.$f.'分：'.$balance.', 结果：失败'];
                    return $rst;
                }
                $rst['msg'] = '用户['.$AgentUsers->name.']'.$f.'分：'.$balance.', 操作成功&nbsp;<font color="green"><strong>√</strong></font> 当前积分：'.$AgentUsers->balance;
                $rst['balance_now'] = $AgentUsers->balance;
            }

        }elseif ($act == 'act-user-edit'){
            if(empty($post['name'])) return ['status'=>303, 'msg'=>'用户名不能为空'];
            $flag = 0;
            if($AgentUsers = AgentUsers::findOne(['agent_id'=>$agent_id, 'id'=>$post['id']])){
                $AgentUsers->name = trim($post['name']);
                $AgentUsers->token = trim($post['token']);
                $flag = $AgentUsers->save();
            }
            if(!$flag) {
                return ['status'>300, 'msg'=>'删除失败，用户名：'.$AgentUsers->name];
            }
            $rst['msg'] = '成功修改用户名：'.$AgentUsers->name;
            $rst['name_now'] = $AgentUsers->name;
        }elseif ($act == 'act-user-del'){
            $flag = 0;
            if($AgentUsers = AgentUsers::findOne(['agent_id'=>$agent_id, 'id'=>$post['id']])){
                $flag = $AgentUsers->delete();
            }
            if(!$flag) {
                return ['status'>300, 'msg'=>'删除失败，用户名：'.$AgentUsers->name];
            }

            $rst['msg'] = '成功删除用户：'.$AgentUsers->name;
        }

        return $rst;
    }

    /**
     * @desc 审核用积分流水
     * @param $data
     * @param $agent_id
     * @return array
     */
    public static function userFlowsCheck($data, $agent_id = '', $desc = '代理操作'){
        if(!$data['id']) return ['status'=>301, 'msg'=>'缺少参数id'];

        if(empty($agent_id)){
            return ['status'=>302, 'msg'=>'不是代理,无权限'];
        }

        if(!$AgentUsersBalanceFlows = AgentUsersBalanceFlows::findOne(['id'=>$data['id'], 'agent_id'=>$agent_id])){
            return ['status'=>400, 'msg'=>'未找到记录'];
        }

        $status = $data['status']; # 审核状态 0未审核1审核通过2拒绝
        $AgentUsers = AgentUsers::findOne(['id'=>$AgentUsersBalanceFlows->member_id, 'agent_id'=>$agent_id]);
        if($status == 1){
            if($AgentUsersBalanceFlows['type'] == 1){
                $changeBalance = $AgentUsersBalanceFlows->balance; # 1 上分
            }elseif($AgentUsersBalanceFlows['type'] == 2){
                $changeBalance = 0 - $AgentUsersBalanceFlows->balance; # 2 下分
            }
            $after_balance = $AgentUsers->balance + $changeBalance ;
            $AgentUsers->balance = $after_balance;
            $AgentUsers->updated_at = time();
            if(!$flag = $AgentUsers->save()){
                return ['status'=>303, 'msg'=>current($AgentUsers->getFirstError())];
            }

            $AgentUsersBalanceFlows->balance_after = $after_balance;
            $AgentUsersBalanceFlows->status = 1;

        }elseif($status == 2){ # 审核拒绝
            $AgentUsersBalanceFlows->balance_after = $AgentUsers->balance;
            $AgentUsersBalanceFlows->status = 2;
        }

        $AgentUsersBalanceFlows->desc = $desc;
        $AgentUsersBalanceFlows->check_time = (string)time();
        if(!$flag = $AgentUsersBalanceFlows->save()){
            return ['status'=>303, 'msg'=>current($AgentUsersBalanceFlows->getErrors())];
        }

        return ['status'=>200, 'msg'=>'操作成功', 'data'=>['status'=>200, 'msg'=>$desc]];
    }

    /**
     * @desc 类型名称
     * @param $type
     * @return mixed
     */
    public static function getFlowTypeTxt($type){
        $types = self::getFlowtypes();

        return $types[$type];
    }

    public static function getFlowtypes(){

        return [1=> '上分', 2=>'扣分'];
    }

    /**
     * @desc 随机获取用户头像
     * @param $agent_id
     * @return string
     */
    public static function getImages($agent_id){
        $num = rand(2, 13);
        $img = 'static/images/avatar/f1/f_'.$num.'.jpg';

        return $img;
    }
}