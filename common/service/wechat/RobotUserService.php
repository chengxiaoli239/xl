<?php

namespace common\service\wechat;

use common\models\eyun\EyunAuth;
use common\models\eyun\HistoryRobots;
use common\models\eyun\RobotUser;
use common\service\BaseService;
use common\service\wechat\eyun\EYunBaseService;
use common\tools\Tool_Common;

class RobotUserService extends BaseService
{
    const STATUS_DISABLE = 0; # 禁用
    const STATUS_ACTIVE = 1; # 激活

    const WECHAT_STATUS_OFFLINE = 0;
    const WECHAT_STATUS_ONLINE = 1;

    public static $s = [
        'status' => [
            self::STATUS_DISABLE => '已禁用',
            self::STATUS_ACTIVE => '已激活',
        ],
        'wechat_status' => [
            self::WECHAT_STATUS_OFFLINE => '已掉线',
            self::WECHAT_STATUS_ONLINE => '在线',
        ],
    ];

    /**
     * 是否有在线微信
     * @param int $user_id
     * @param array $HistoryRotot
     * @return bool
     */
    public static function isHasLogined($user_id=0, &$HistoryRotot=[]){
        if($HistoryRotot = HistoryRobots::find()->where(['user_id'=>$user_id, 'wechat_status'=>RobotUserService::WECHAT_STATUS_ONLINE])->one()){
            return true;
        }

        return false;
    }

    /**
     * 切换微信 获取二维码或者下线操作，离线微信则获取二维码，在线登录则退出微信
     * @param $user_id
     * @param $post
     * @return array
     */
    public static function switchWechat($user_id, $post){

        try {
            $returnData = [];
            $flag = RobotUserService::isHasLogined($user_id, $HistoryRotots); # 是否有在线微信
            Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '切换微信0', ['user_id'=>$user_id, 'post'=>$post, 'flag'=>$flag]);
            $wechatId = $post['wechatId'];
            $eyun = new EYunBaseService($user_id, $wechatId);
            switch ($post['switchStatus']){ # 登录、下线操作
                case 1:
                    # 获取二维码 第二步登录
                    if($flag){
                        throw_info('请先退出在线的微信', 40001);
                    } else{
                        # 没有在线则调用获取二维码接口返回二维码供用户扫码
                        $stepOneRst = $eyun->localIPadLogin($wechatId);
                        Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '切换微信1', ['user_id'=>$user_id, 'code'=>$stepOneRst['code']]);
                        if($stepOneRst['code']=='1000'){
                            $returnData = $stepOneRst['data'];
                        }
                    }
                    break;
                case 0:
                    # 下线操作
                    if($flag){
                        $isOnlineRst = $eyun->isOnline(); # {"code":"1000","message":"成功","data":{"isOnline":true}}
                        if($isOnlineRst['code'] == 1000){
                            # 如果有在线记录，则调用下线接口
                            $isOnline = $isOnlineRst['data']['isOnline'];
                            if($isOnline){
                                $offlineRst = $eyun->setOffline([$wechatId]);
                                if($offlineRst['code'] != '1000'){
                                    throw_info($offlineRst['message']??'系统异常');
                                }
                            }
                            list($code, $data, $msg) = self::setLocalOffline($user_id, $wechatId); # 本地设置下线
                            if($code>0){
                                throw_info($msg);
                            }
                            $RobotUser = $data['RobotUser'];
                            $historyRobot = $data['HistoryRobot'];
                            $returnData = [
                                'nickName' => $RobotUser->nickName,
                                'wcId' => $RobotUser->wcId,
                                'message' => $msg,
                            ];
                        }else{
                            throw_info($isOnlineRst['message']??'系统异常');
                        }

                        Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '切换微信2', ['user_id'=>$user_id, 'wechatId'=>$wechatId, 'isOnline'=>$isOnline, 'offlineRst'=>$offlineRst]);

                    }else{
                        throw_info('系统微信已经是下线状态', 40002);
                    }
                    break;
                default:
                    break;
            }
        }catch (\Exception $e){
            Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '切换微信3', ['user_id'=>$user_id, 'post'=>$post, 'err_msg'=>$e->getMessage()]);
            return ['status'=>301, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'data'=>$returnData, 'msg'=>'操作成功'];
    }

    /**
     * 执行微信登录
     * @param int $user_id
     * @param array $post
     * @return array
     */
    public static function actWechatLogin($user_id=0, $post=[]){
        $eyun = new EYunBaseService($user_id, $post['wcId']);
        $actRst = $eyun->getIPadLoginInfo($post['wId']);
        $returnData = [];
        if($actRst['code'] == 1000){
            $returnData = $actRst['data'];
        }else{
            return ['status'=>300, 'data'=>$returnData, 'msg'=>$actRst['data']['message']??'操作异常'];
        }

        return ['status'=>200, 'data'=>$returnData, 'msg'=>'操作成功'];
    }

    /**
     * 设置本地下线
     * @param $user_id
     * @param string $wechatId
     * @return array
     */
    public static function setLocalOffline($user_id, $wechatId=''){
        $HistoryRobot = HistoryRobots::findOne(['user_id'=>$user_id, 'wcId'=>$wechatId]);
        $desc = '操作下线_'.date('Y-m-d H:i:s');
        $setData = [
            'wechat_status' => RobotUserService::WECHAT_STATUS_OFFLINE,
            'desc' => $desc,
            'updated_at' => time(),
        ];
        $HistoryRobot->setAttributes($setData, false);
        $HistoryRobot->save();

        $RobotUser = RobotUser::findOne(['user_id'=>$user_id, 'wcId'=>$wechatId]);
        $RobotUser->setAttributes($setData, false);
        $flag = $RobotUser->save();
        $data = [
            'HistoryRobots'=>$HistoryRobot,
            'RobotUser'=>$RobotUser
        ];

        return [$flag? 0 : 30001, $data, $desc];
    }
}
