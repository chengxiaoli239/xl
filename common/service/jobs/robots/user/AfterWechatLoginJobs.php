<?php
namespace common\service\jobs\robots\user;

use common\models\eyun\RobotUser;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\eyun\EYunBaseService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\service\wechat\RobotUserService;
use yii\helpers\Json;

class AfterWechatLoginJobs extends CommonJob {

    public static function getName($params) {
        self::$name = '微信登录成功后-处理';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        try {
            $wcId = $params['wcId']; # 微信原始id
            $wechatStatus = RobotUserService::WECHAT_STATUS_ONLINE;
            $RobotUser = RobotUser::findOne(['wcId'=>$wcId]);
            if(empty($RobotUser)){
                throw_info('机器人robot_user找不到记录');
            }
            $RobotUser->wechat_status = $wechatStatus;
            $RobotUser->save();

            # 登录成功之后 - 初始化通讯录
            $e = new EYunBaseService($RobotUser->user_id);
            # 初始化通讯录列表（第四步）
            $initAddressListRst = $e->initAddressList();
            # 初始化通讯录列表（第五步）
            $getAddressListRst = $e->getAddressList();
        }catch (\Exception $e){
            return $e->getMessage();
        }

        $logArr = ['params'=>$params, 'initAddressListRst'=>$initAddressListRst, 'getAddressListRst'=>$getAddressListRst];
        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', '微信用户消息', $logArr);

        return '微信登录后台处理成功:';
    }

}
