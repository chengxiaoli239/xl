<?php
namespace common\service\jobs\robots\user;

use common\models\eyun\HistoryRobots;
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
            $now_time = time();
            $wcId = $params['wcId']; # 微信原始id
            $user_id = $params['user_id']; # 系统用户id
            $wechatStatus = RobotUserService::WECHAT_STATUS_ONLINE;
            $RobotUser = RobotUser::findOne(['user_id'=>$user_id]);
            if(empty($RobotUser)){
                #throw_info('机器人robot_user找不到记录');
                $RobotUser = new RobotUser();
                $RobotUser->user_id = $user_id;
                $RobotUser->uuid = \Yii::$app->params['E_YUN']['TTUID'];
            }
            $m = \Yii::$app->cache;
            $wIdKey = EYunBaseService::getUserWIdKey($user_id);
            $RobotUser->wcId = $wcId;
            $RobotUser->wId = $m->get($wIdKey); # 登录成功，从第二步获取二维码的同时返回的实例存缓存，这里从缓存取
            $RobotUser->wechat_status = $wechatStatus;
            $RobotUser->save();

            # 记录历史登陆记录
            $whereHistory = ['user_id'=>$user_id, 'wcId'=>$wcId];
            $historyRobots = HistoryRobots::findOne($whereHistory);
            if(empty($historyRobots)){
                $historyRobots = new HistoryRobots();
                $historyRobots->user_id = $user_id;
                $historyRobots->wcId = $wcId;
                $historyRobots->uuid = \Yii::$app->params['E_YUN']['TTUID'];
                $historyRobots->created_at = $now_time;
            }
            $historyRobots->desc = '登录于: '.date('Y-m-d H:i:s');
            $historyRobots->headUrl = $params['headUrl'];
            $historyRobots->smallHeadImgUrl = $params['headUrl'];
            $historyRobots->wechat_status = $wechatStatus;
            $historyRobots->updated_at = $now_time;
            $historyRobots->save();

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
