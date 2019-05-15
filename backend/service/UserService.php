<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\TzSystems;
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
        if($action == 'add'){
            if(!$AuthAssignment = AuthAssignment::findOne(['user_id'=>$admin_id])){
                $AuthAssignment = new AuthAssignment();
            }
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
        }else{
            # 删除用户记录
            //$rst = User::deleteRecord(['admin_id'=>$admin_id]);
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


    public static function saveTzSystemUsers($tz_systems_ids_Arr, $uid, $opType = 'add'){
        //p([$tz_systems_ids_Arr, $uid, $opType]);

        foreach ($tz_systems_ids_Arr as $tz_system_id){
            if($opType == 'add' OR $opType == 'update'){
                $setData = [];
                if(!$TzSystemsUsers = TzSystemsUsers::findOne(['tz_system_id'=>$tz_system_id, 'uid'=>$uid])){
                    $TzSystemsUsers = new TzSystemsUsers();
                    $setData['created_at'] = time();
                }
                $TzSystems = TzSystems::findOne($tz_system_id);
                //$user = AdminModel::findOne($uid);
                $setData['updated_at'] = time();
                $setData = array_merge($setData,[
                    'uid' => $uid,
                    //'ssc_domain' => $TzSystems->ssc_domain,
                    'tz_system_id' => $tz_system_id,
                    'sys_name' => $TzSystems->name,
                    ''
                ]);

                $TzSystemsUsers->setAttributes($setData);
                $rst = $TzSystemsUsers->save();

            }else{
                TzSystemsUsers::deleteAll([['=', 'uid', $uid], ['not in', 'tz_system_id'=>$tz_systems_ids_Arr]]);

            }
        }

    }
}