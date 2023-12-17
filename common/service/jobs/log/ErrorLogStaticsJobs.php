<?php
namespace common\service\jobs\log;

use backend\service\statics\statics_3d\Statics3dUserDataService;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\WechatUserService;

class ErrorLogStaticsJobs extends CommonJob {

    public static function getName($params): string
    {
        self::$name = '40错误日志搜集';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params): string
    {
        try {
            $errorMsg = $params['err_msg'];
            \common\open\thirdD\api\SiteOrderApi::pushToLog(['err_msg'=>$errorMsg]);

            Tool_Common::log('/err_log/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'data'=>$data]);
        }catch (\Exception $e){
            Tool_Common::log('/err_log/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'err_msg'=>$e->getMessage()]);
            throw_info($e->getMessage());
        }

        return '错误日志搜集结束';
    }

}
