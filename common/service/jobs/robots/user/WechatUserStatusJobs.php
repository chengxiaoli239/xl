<?php
namespace common\service\jobs\robots\user;

use common\models\eyun\RobotUser;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\RobotUserService;
use yii\helpers\Json;

class WechatUserStatusJobs extends CommonJob {

    public static function getName($params) {
        self::$name = '微信登录状态同步';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        try {
            $wcId = $params['wcId']; # 微信原始id
            $wechatStatus = RobotUserService::WECHAT_STATUS_ONLINE;
            if($params['messageType']==EYunMessageOperateService::MESSAGE_OFFLINE_CODE){
                $wechatStatus = RobotUserService::WECHAT_STATUS_OFFLINE;
            }
            $RobotUser = RobotUser::findOne(['wcId'=>$wcId]);
            if(empty($RobotUser)){
                throw_info('机器人robot_user找不到记录');
            }
            $RobotUser->wechat_status = $wechatStatus;
            $RobotUser->save();
        }catch (\Exception $e){
            return $e->getMessage();
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', '微信用户消息', ['params'=>$params]);

        return '微信登录状态同步成功:';
    }

}
