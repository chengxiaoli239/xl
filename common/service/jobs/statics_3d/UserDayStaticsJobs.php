<?php
namespace common\service\jobs\statics_3d;

use backend\service\statics\statics_3d\Statics3dUserDataService;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\WechatUserService;

class UserDayStaticsJobs extends CommonJob {

    public static function getName($params): string
    {
        self::$name = '30用户日报表计算';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params): string
    {
        try {
            $wechat_user_id = $params['wechat_user_id']; # 系统微信用户表id
            if(empty($wechat_user_id)){
                throw_info('系统代理id为空');
            }
            $type = $params['type'];
            if(!in_array($type, array_keys(WechatUserService::$s['balance_type']))){
                //throw_info('业务类型错误type：'.$type);
            }
            $lottery_type = $params['lottery_type']??0;

            $date = $params['date']??date('Y-m-d');
            list($code, $data, $msg) = Statics3dUserDataService::calculateUserDayData($wechat_user_id, $date, $lottery_type);

            Tool_Common::log('/statics_3d/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'data'=>$data]);
        }catch (\Exception $e){
            Tool_Common::log('/statics_3d/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'err_msg'=>$e->getMessage()]);
            throw_info($e->getMessage());
        }

        return '客户日报表统计完成[wechat_user_id:'.$wechat_user_id.']';
    }

}
