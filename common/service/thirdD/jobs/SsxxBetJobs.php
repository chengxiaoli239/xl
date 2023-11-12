<?php
namespace common\service\thirdD\jobs;

use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\thirdD\sx\Ssxx3dBetService;

class SsxxBetJobs extends CommonJob {

    public static function getName($params): string
    {
        self::$name = '40用户下注推送盘口';
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

            list($code, $data, $msg) = Ssxx3dBetService::preBetValidate($betRowId);
            if($code>0){
                throw_info($msg);
            }
            $betRow = $data['betRow']; # object
            list($code, $data, $msg) = Ssxx3dBetService::postToSite($betRow);

            Tool_Common::log('/statics_3d/'.self::class_basename(__CLASS__), 'INFO', self::$name, ['params'=>$params, 'data'=>$data]);
        }catch (\Exception $e){
            Tool_Common::log('/statics_3d/'.self::class_basename(__CLASS__), 'ERR', self::$name, ['params'=>$params, 'err_msg'=>$e->getMessage()]);
            throw_info($e->getMessage());
        }

        return '下注记录推送盘口完成[betRowId:'.$betRowId.']';
    }

}
