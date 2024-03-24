<?php
namespace common\service\lottery\aozhou5\jobs;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\lottery\aozhou5\AoZhou5BetService;
use common\service\thirdD\sx\Ssxx3dBetService;

class AoZhou5BetJobs extends CommonJob {
    # 无效状态，无需处理
    const INVALID_STATUS_CODE = 40000;

    public static function getName($params): string
    {
        self::$name = '40-用户下注推送盘口';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params): string
    {
        try {
            $betRowId = $params['betRowId'];
            if(empty($betRowId)){
                throw_info('下注记录ID为空');
            }

            list($code, $data, $msg) = AoZhou5BetService::postToSite($betRowId);
            if($code>0){
                throw_info($msg, $code);
            }
            Tool_Common::log('/statics_3d/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'data'=>$data]);
        }catch (\Exception $e){
            Tool_Common::log('/statics_3d/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'err_msg'=>$e->getMessage()]);
            throw_info($e->getMessage());
        }

        return '下注记录推送盘口完成[betRowId:'.$betRowId.']';
    }

}
