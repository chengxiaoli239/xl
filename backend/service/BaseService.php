<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\huiyuan\HuiYuanBaseService;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\Lucky5\Lucky5Service;
use backend\service\NineNine\NineNineNewService;
use backend\service\NineNine\NineNineService6;
use backend\service\pingbo\PingBoBaseService;
use backend\service\qilin\BingDaoService;
use backend\service\qilin\QiLinBaseService;
use common\service\CommonService;
use  yii;
use common\tools\Util;

class BaseService{


    /**
     * @desc 登陆中转
     * @param $id TzSystemsUsers表id
     * @return array|bool
     */
    public static function login($id = '', $is_auto = 1){
        if(!$id) return ['status'=>300, 'msg'=>'id不能为空'];
        if(!$TzSystemsUser = TzSystemsUsers::findOne($id)){
            return ['status'=>300, 'msg'=>'操作失败:找不到记录'];
        }

        $tz_system_id = $TzSystemsUser->tz_system_id;
        # 是否有激活的计划
        $hasActivePlan = CommonService::hasPlansActiveSys($tz_system_id);
        if(!$hasActivePlan){
            return false;
        }

        if(empty($TzSystemsUser->account) OR empty($TzSystemsUser->password)){
            return false;
        }
        $not_need_login_tz_system_ids = explode(',', $val = SystemConfig::findOne(['key'=>'ssc_kj_time_period'])->value); # 开奖时间间隔:20分钟
        if(in_array($tz_system_id, $not_need_login_tz_system_ids)){
            return ['status'=>200, 'msg'=>'无需登陆站点', 'balance'=>$TzSystemsUser->balance, 'account'=>$TzSystemsUser->account, 'username'=>$TzSystemsUser->username];
        }

        $flag = BetService::isLogin($TzSystemsUser->uid, $tz_system_id); #
        if($flag && $is_auto){
            return ['status'=>200, 'msg'=>'已经是登录状态', 'balance'=>$TzSystemsUser->balance, 'account'=>$TzSystemsUser->account, 'username'=>$TzSystemsUser->username];
        }
        if(in_array($tz_system_id, [1,2])){
            # 1、0898投注、2、99彩票网
            $rst = HN0898Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [3, 7, 9, 10])){
            # 3、重庆7时彩网
            if($tz_system_id == 3){
                $rst = SevenService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }else{
                $rst = Lucky5Service::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
            }
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
        }elseif(in_array($tz_system_id, [6])){
            # 6、会员网
            $rst = HuiYuanBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [11])){
            # 11、菊花网暂时没对接登录
            //$rst = JuHuaBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [8])){
            # 8、麒麟财务系统网
            $rst = QiLinBaseService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
        }elseif(in_array($tz_system_id, [13])){
            # 9、冰岛
            $rst = \backend\service\BingDao\BingDaoService::login($TzSystemsUser->uid, $TzSystemsUser->tz_system_id);
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

        $tz_system_id = $TzSystemsUser->tz_system_id;
        # 是否有激活的计划
        $hasActivePlan = CommonService::hasPlansActiveSys($tz_system_id);
        if(!$hasActivePlan && !in_array($tz_system_id, [3, 11, 12, 13, 14])){
            return false;
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
                $rst = Lucky5Service::synBalance($TzSystemsUser->id);// p($rst);# 同步余额
            }
        }elseif(in_array($tz_system_id, [4])){
            # 4、7天彩票网
        }elseif(in_array($tz_system_id, [5])){
            # 5、希腊网
        }elseif(in_array($tz_system_id, [6])){
            # 6、会员网
            $rst = HuiYuanBaseService::synBalance($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [11])){
            # 菊花网
            $rst = JuHuaBaseService::synBalance($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [12])){
            # 九九新网
            $rst = NineNineNewService::synBalance($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [8])){
            # 8、麒麟财务系统网
            $rst = QiLinBaseService::synBalance($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [14])){
            # 14、平博网
            $rst = PingBoBaseService::synBalance($TzSystemsUser->id);
        }elseif(in_array($tz_system_id, [13])){
            # 13、冰岛
            $rst = \backend\service\BingDao\BingDaoService::synBalance($TzSystemsUser->id);
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