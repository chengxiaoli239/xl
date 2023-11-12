<?php

namespace common\service\thirdD\sx;

use backend\models\wechat\Bets;
use common\models\wechat\WechatUser;
use common\service\CommonService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\MethodMatchService;
use common\tools\Tool_Common;
use yii\helpers\Json;

class Ssxx3dBetService extends CommonBaseService
{
    public static function preBetValidate($betRowId): array
    {
        try {
            $betRow = Bets::findOne($betRowId);
            $wechatUser = WechatUser::findOne($betRow->wechat_user_id);
            $lottery_type = $betRow->lottery_type;
            $qihao = $betRow->qihao;
            $codeStr = CommonService::getAwardNumberByQihao($qihao, $lottery_type);
            switch (true){
                case $betRow->status == CommonBaseService::STATUS_LT_CANCEL:
                    throw_info('已是撤单状态，无需推送盘口');
                case $wechatUser->is_chi == 1:
                    throw_info('该用户私下吃，无需推送盘口');
                case !empty($codeStr):
                    throw_info('已开奖期号['.$lottery_type.'_'.$qihao.']，禁止推送盘口');
                default:
                    break;
            }
        }catch (\Exception $e){
            return [10001, [], $e->getMessage()];
        }

        return [0, ['betRow'=>$betRow], '校验成功'];
    }

    /**
     * 推向盘口
     * @param object $betRow
     * @return array
     */
    public static function postToSite(object $betRow): array
    {
        $lottery_type = $betRow->lottery_type;
        $qihao = $betRow->qihao;
        $method_id = $betRow->play_method;

        Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖计算22', ['betRowId'=>$betRow->id, 'lottery_type'=>$lottery_type, 'qihao'=>$qh, 'kjCode'=>$kjCode, 'method_id'=>$method_id]);
        //p($method_id);
        try {
            switch ($method_id){
                case MethodMatchService::METHOD_ID_ZHIXUAN:
                    OperateLotteryService::runZhiXuan($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_ZULIU: # 组六
                case MethodMatchService::METHOD_ID_ZUSAN: # 组三
                    OperateLotteryService::runZuXuan($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_DUDAN: # 独胆
                    OperateLotteryService::runDuDan($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_SHUANGFEN: # 双飞
                case MethodMatchService::METHOD_ID_QUANTUO: # 对子全拖
                    OperateLotteryService::runShuangFen($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_YIMADING: # 一码定
                    OperateLotteryService::runYiMaDing($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_ERMADING: # 二码定
                    OperateLotteryService::runErMaDing($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_BAOZI_QB: # 豹子全包
                    OperateLotteryService::runBaoZiQB($betRow, $kjCode);
                    break;
                case MethodMatchService::METHOD_ID_ZL_4_MA: # 组六4码
                case MethodMatchService::METHOD_ID_ZL_5_MA: # 组六5码
                case MethodMatchService::METHOD_ID_ZL_6_MA: # 组六6码
                case MethodMatchService::METHOD_ID_ZL_7_MA: # 组六7码
                case MethodMatchService::METHOD_ID_ZL_8_MA: # 组六8码
                case MethodMatchService::METHOD_ID_ZL_9_MA: # 组六9码
                    OperateLotteryService::runZuLiuXMa($betRow, $kjCode); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_ZS_2_MA: # 组三2码
                case MethodMatchService::METHOD_ID_ZS_3_MA: # 组三2码
                case MethodMatchService::METHOD_ID_ZS_4_MA: # 组三4码
                case MethodMatchService::METHOD_ID_ZS_5_MA: # 组三5码
                case MethodMatchService::METHOD_ID_ZS_6_MA: # 组三6码
                case MethodMatchService::METHOD_ID_ZS_7_MA: # 组三7码
                case MethodMatchService::METHOD_ID_ZS_8_MA: # 组三8码
                case MethodMatchService::METHOD_ID_ZS_9_MA: # 组三9码
                    OperateLotteryService::runZuSanXMa($betRow, $kjCode); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_ZL_QB: # 组六全包
                case MethodMatchService::METHOD_ID_ZS_QB: # 组三全包
                    OperateLotteryService::runZuXuanQuanBao($betRow, $kjCode); # 组选x码
                    break;
                case MethodMatchService::METHOD_ID_KD_0: # 跨度0
                case MethodMatchService::METHOD_ID_KD_1: # 跨度1
                case MethodMatchService::METHOD_ID_KD_2: # 跨度2
                case MethodMatchService::METHOD_ID_KD_3: # 跨度3
                case MethodMatchService::METHOD_ID_KD_4: # 跨度4
                case MethodMatchService::METHOD_ID_KD_5: # 跨度5
                case MethodMatchService::METHOD_ID_KD_6: # 跨度6
                case MethodMatchService::METHOD_ID_KD_7: # 跨度7
                case MethodMatchService::METHOD_ID_KD_8: # 跨度8
                case MethodMatchService::METHOD_ID_KD_9: # 跨度9
                    OperateLotteryService::runKuaDu($betRow, $kjCode); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_YMT_ZL_2: # 一码拖2_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_3: # 一码拖3_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_4: # 一码拖4_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_5: # 一码拖5_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_6: # 一码拖6_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_7: # 一码拖7_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_8: # 一码拖8_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_9: # 一码拖9_组六
                    OperateLotteryService::runYiTuoZuLiu($betRow, $kjCode); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_YMT_ZS_2: # 一码拖2_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_3: # 一码拖3_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_4: # 一码拖4_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_5: # 一码拖5_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_6: # 一码拖6_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_7: # 一码拖7_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_8: # 一码拖8_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_9: # 一码拖9_组三
                    OperateLotteryService::runYiTuoZuSan($betRow, $kjCode); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_FS_3: # 复式三
                case MethodMatchService::METHOD_ID_FS_4: # 复式四
                case MethodMatchService::METHOD_ID_FS_5: # 复式五
                case MethodMatchService::METHOD_ID_FS_6: # 复式六
                case MethodMatchService::METHOD_ID_FS_7: # 复式七
                case MethodMatchService::METHOD_ID_FS_8: # 复式八
                case MethodMatchService::METHOD_ID_FS_9: # 复式九
                    OperateLotteryService::runFuShiX($betRow, $kjCode); # 跨度
                    break;
                case MethodMatchService::METHOD_ID_HZ_0: # 和值0
                case MethodMatchService::METHOD_ID_HZ_1: # 和值1
                case MethodMatchService::METHOD_ID_HZ_2: # 和值2
                case MethodMatchService::METHOD_ID_HZ_3: # 和值3
                case MethodMatchService::METHOD_ID_HZ_4: # 和值4
                case MethodMatchService::METHOD_ID_HZ_5: # 和值5
                case MethodMatchService::METHOD_ID_HZ_6: # 和值6
                case MethodMatchService::METHOD_ID_HZ_7: # 和值7
                case MethodMatchService::METHOD_ID_HZ_8: # 和值8
                case MethodMatchService::METHOD_ID_HZ_9: # 和值9
                case MethodMatchService::METHOD_ID_HZ_10: # 和值10
                case MethodMatchService::METHOD_ID_HZ_11: # 和值11
                case MethodMatchService::METHOD_ID_HZ_12: # 和值12
                case MethodMatchService::METHOD_ID_HZ_13: # 和值13
                case MethodMatchService::METHOD_ID_HZ_14: # 和值14
                case MethodMatchService::METHOD_ID_HZ_15: # 和值15
                case MethodMatchService::METHOD_ID_HZ_16: # 和值16
                case MethodMatchService::METHOD_ID_HZ_17: # 和值17
                case MethodMatchService::METHOD_ID_HZ_18: # 和值18
                case MethodMatchService::METHOD_ID_HZ_19: # 和值19
                case MethodMatchService::METHOD_ID_HZ_20: # 和值10
                case MethodMatchService::METHOD_ID_HZ_21: # 和值21
                case MethodMatchService::METHOD_ID_HZ_22: # 和值22
                case MethodMatchService::METHOD_ID_HZ_23: # 和值23
                case MethodMatchService::METHOD_ID_HZ_24: # 和值24
                case MethodMatchService::METHOD_ID_HZ_25: # 和值25
                case MethodMatchService::METHOD_ID_HZ_26: # 和值26
                case MethodMatchService::METHOD_ID_HZ_27: # 和值27
                    OperateLotteryService::runHeZhi($betRow, $kjCode); # 和值
                    break;
                case MethodMatchService::METHOD_ID_HZ_DA: # 和值大
                case MethodMatchService::METHOD_ID_HZ_XIAO: # 和值小
                case MethodMatchService::METHOD_ID_HZ_DAN: # 和值单
                case MethodMatchService::METHOD_ID_HZ_SHUANG: # 和值双
                    OperateLotteryService::runHeZhiDxDs($betRow, $kjCode); # 和值
                    break;
                case MethodMatchService::METHOD_ID_DW_ZX_FS: # 定位直选复式
                    OperateLotteryService::runHeZhiXuanFuShiDw($betRow, $kjCode); # 直选复式定位
                    break;
                case MethodMatchService::METHOD_ID_QD: # 全倒
                    OperateLotteryService::runQuanDao($betRow, $kjCode); # 全倒
                    break;
                case MethodMatchService::METHOD_ID_ZX_FS: # 直选复式
                    OperateLotteryService::runZhiXuanFuShi($betRow, $kjCode); # 直选复式
                    break;
                default:
                    $err_msg = '未知玩法ID:'.$method_id;
                    $logArr = ['lottery_type'=>$lottery_type, 'betRowId'=>$betRow->id, 'err_msg'=>$err_msg];
                    Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理异常10', $logArr);
                    return [10003, $logArr, $err_msg];
                    break;
            }
            $resultData = ['betRowId'=>$betRow->id, 'idData'=>$idData, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'err_msg'=>'处理结束'];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理结束99', $resultData);
            var_dump(date('Y-m-d H:i:s ').'处理成功：betRowId:'.$betRow->id.'_method_id:'.$method_id);
        }catch (\Exception $e){
            $logArr = ['betRowId'=>$betRow->id, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '开奖处理异常11', $logArr);
            var_dump($e->getMessage());
            return [10004, $logArr, $e->getMessage()];
        }

        return [];
    }
}
