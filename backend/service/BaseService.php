<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\TzSystemsUsers;
use backend\service\huiyuan\HuiYuanBaseService;
use backend\service\Lucky5\LuckyBaseService;
use backend\service\qilin\QiLinBaseService;
use  yii;
use common\tools\Util;

class BaseService{


    /**
     * @desc 登陆中转
     * @param $id TzSystemsUsers表id
     * @return array|bool
     */
    public static function login($id = ''){
        if(!$id) return ['status'=>300, 'msg'=>'id不能为空'];
        if(!$TzSystemsUser = TzSystemsUsers::findOne($id)){
            return ['status'=>300, 'msg'=>'操作失败:找不到记录'];
        }

        if(empty($TzSystemsUser->account) OR empty($TzSystemsUser->password)){
            return false;
        }
        $tz_system_id = $TzSystemsUser->tz_system_id;
        if(in_array($tz_system_id, [1,2])){
            # 1、0898投注、2、99彩票网
            $rst = HN0898Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            //$rst['rst'][$TzSystemsUser->id] = HN0898Service::synBalance($TzSystemsUser->id);
            //$rst['rst'][$TzSystemsUser->id] = HN0898Service::login($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [3, 7, 9, 10])){
            # 3、重庆7时彩网
            if($tz_system_id == 3){
                $rst = SevenService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }else{
                $rst = LuckyBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
                //$rst = LuckyBaseService::loginWriteCookie($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
        }elseif(in_array($tz_system_id, [6])){
            # 6、会员网
            $rst = HuiYuanBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [8])){
            # 8、麒麟财务系统网
            $rst = QiLinBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }

        return $rst;
    }

    /**
     * @desc 同步余额中转
     * @param $id
     * @return array|bool
     */
    public static function synBalance($id = ''){
        if(!$id) return ['status'=>300, 'msg'=>'id不能为空'];
        if(!$TzSystemsUser = TzSystemsUsers::findOne($id)){
            return ['status'=>300, 'msg'=>'操作失败:找不到记录'];
        }

        if(empty($TzSystemsUser->account) OR empty($TzSystemsUser->password)){
            return false;
        }
        $tz_system_id = $TzSystemsUser->tz_system_id;
        if(in_array($tz_system_id, [1,2])){
            # 1、0898投注、2、99彩票网
            //$rst = HN0898Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            $rst = HN0898Service::synBalance($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [3, 7, 9, 10])){
            # 3、重庆7时彩网
            if(in_array($tz_system_id, [3, 7])){
                $rst = SevenService::synBalance($TzSystemsUser->id);
            }else{
                $rst = LuckyBaseService::synBalance($TzSystemsUser->id);// p($rst);# 同步余额
            }
            //p(['rst'=>$rst]);
            //$rst = LuckyBaseService::synBalance($TzSystemsUser->id); //p($rst);# 同步余额
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
        }elseif(in_array($tz_system_id, [6])){
            # 6、会员网
            $rst = HuiYuanBaseService::synBalance($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [8])){
            # 8、麒麟财务系统网
            $rst = QiLinBaseService::synBalance($TzSystemsUser->id);
        }

        return $rst;
    }

    /**
     * @description
     * @param $string
     * @return bool
     */
    public static function is_json($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}