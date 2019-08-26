<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\TzSystems;
use backend\models\TzSystemsAuth;
use backend\models\TzSystemsUsers;
use common\models\AdminModel;
use common\models\AuthAssignment;
use common\tools\Tool_Common;
use backend\models\User;
use backend\models\UserFollowData;
use  yii;

class UserService extends BaseService {


    /**
     * @decription Yii 控制器初始化方法
     */
    public static function _init(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $time = date("H:i");
    }

    public static function opUser($admin_id, $action, $role){
        $model = User::findOne(['admin_id'=>$admin_id]);
        if($action == 'add'){
            # 添加赔率记录
            if(!$model){
                $User = new User();
                $AdminModel = AdminModel::findOne($admin_id);
                $insertData = [
                    'admin_id'=>$admin_id,
                    'email'=>$AdminModel->email,
                    'account'=>$AdminModel->username,
                    'username'=>$AdminModel->username,
                    'created_at'=> time(),
                    'updated_at'=> time(),
                ];
                $User->setAttributes($insertData);
                $rst = $User->save();

                $AuthAssignment = new AuthAssignment();
                $insertData = [
                    'item_name' => $role,
                    'user_id'=>$admin_id,
                    'created_at'=>time(),
                ];
                $AuthAssignment->setAttributes($insertData);
                $rst = $AuthAssignment->save(false);

                /*
                $TzSystems = TzSystems::findAll([1=>1]);
                foreach ($TzSystems as $TzSystem){
                    $TzSystemsUsers = new TzSystemsUsers();
                    $insertData = [
                        'uid' => $admin_id,
                        'tz_system_id' => $TzSystem->id,
                        'account' => $AdminModel->username,
                        'sys_name' => $TzSystem->name,
                        'status' => 3, # 0 禁用 1启用 3隐藏
                        'updated_at' => time(),
                        'created_at' => time(),
                    ];
                    $TzSystemsUsers->setAttributes($insertData);
                    $TzSystemsUsers->save();
                }
                */
                //p([$insertData,$AuthAssignment->attributes,$rst,$AuthAssignment->getErrors()]);
            }
        }else{
            # 删除用户记录
            $rst = User::deleteRecord(['admin_id'=>$admin_id]);
            //d($rst);
        }

        return $rst;

    }

    /**
     * @desc 预处理表单信息
     * @param $post
     * @return bool
     */
    public static function preOpenData(&$post, $uid){
        if(!$post) return false;

        $post['TzSystemsAuth']['tz_systems_ids'] && $post['TzSystemsAuth']['tz_systems_ids'] = implode(',',$post['TzSystemsAuth']['tz_systems_ids']);
        $post['TzSystemsAuth']['tz_types'] && $post['TzSystemsAuth']['tz_types'] = implode(',',$post['TzSystemsAuth']['tz_types']);

        $post['TzSystemsAuth']['uid'] = $uid;
        $post['TzSystemsAuth']['updated_at'] = time();

        if(!$post['TzSystemsAuth']['id']){
            $post['TzSystemsAuth']['created_at'] = time();
        }

        return $post;
    }


    /**
     * @desc 添加系统和投注方式等权限
     * @param $tz_systems_ids_Arr
     * @param $uid
     * @param string $opType
     */
    public static function saveTzSystemUsers($tz_systems_ids_Arr, $uid, $opType = 'add'){
        //p([$tz_systems_ids_Arr, $uid, $opType],0);

        foreach ($tz_systems_ids_Arr as $tz_system_id){
            if($opType == 'add' OR $opType == 'update'){
                $setData = [];
                if(!$TzSystemsUsers = TzSystemsUsers::findOne(['tz_system_id'=>$tz_system_id, 'uid'=>$uid])){
                    $TzSystemsUsers = new TzSystemsUsers();
                    $setData['created_at'] = time();
                }
                $TzSystems = TzSystems::findOne($tz_system_id);
                $user = AdminModel::findOne($uid);
                $setData['updated_at'] = time();
                $setData = array_merge($setData,[
                    'uid' => $uid,
                    'username' => $user->username,
                    //'ssc_domain' => $TzSystems->ssc_domain,
                    'tz_system_id' => $tz_system_id,
                    'sys_name' => $TzSystems->name,
                    ''
                ]);

                $TzSystemsUsers->setAttributes($setData);
                $rst = $TzSystemsUsers->save();
            }
        }
        $where = ['and', ['=', 'uid', $uid], ['not in', 'tz_system_id', $tz_systems_ids_Arr]];
        //TzSystemsUsers::deleteAll($where); # 逻辑删除
        TzSystemsUsers::deleteRecord($where); # 物理删
        return $rst;
    }

    /**
     * @description 更新用户表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateUserStatus($id, $status)
    {
        if(!$id) return ['status'=>300, 'msg'=>'id为空'];
        $m = \Yii::$app->cache;
        $mkey = 'updateUserStatus_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        $data = AdminModel::findOne($id);
        $data->status = (int)$status;

        $m->set($mkey, 1, 10);

        $rst = $data->save(false);
        $TzSystemsUsers = TzSystemsUsers::findAll(['uid'=>$id]);
        foreach ($TzSystemsUsers as $TzSystemsUser){
            $TzSystemsUser->status = $status == 1 ? 0 : 1;
            $TzSystemsUser->save();
        }

        return $rst;
    }

    public static function getUserDefaultSite($uid){

        $defaultSiteIds = explode(',',TzSystemsAuth::findOne(['uid'=>$uid])->tz_systems_ids);

        return $defaultSiteIds[0];
    }
}