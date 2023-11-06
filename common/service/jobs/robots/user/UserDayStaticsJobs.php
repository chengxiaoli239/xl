<?php
namespace common\service\jobs\statics_3d;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\WechatUserService;
use yii\helpers\Json;

class UserDayStaticsJobs extends CommonJob {

    public static function getName($params) {
        self::$name = '30用户日报表计算';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        try {
            $wechat_user_id = $params['wechat_user_id']; # 系统用户id
            if(empty($wechat_user_id)){
                throw_info('系统代理id为空');
            }
            $type = $params['type'];
            if(!in_array($type, WechatUserService::$s['balance_type'])){
                throw_info('业务类型错误type：'.$type);
            }
            // todo 报表计算：今日投分，中奖、盈利、上分、下分 => lt_static_3d_user_profits_day

            Tool_Common::log('/statics_3d/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params]);
        }catch (\Exception $e){
            throw_info($e->getMessage());
        }

        return '客户日报表统计';
    }

}
