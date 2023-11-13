<?php

namespace common\service\thirdD\sx;

use backend\models\wechat\Bets;
use common\helpers\RequestHelper;
use common\models\wechat\WechatUser;
use common\open\thirdD\api\SiteOrderApi;
use common\service\CommonService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\MethodMatchService;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use yii\helpers\Json;

class Ssxx3dBetService extends CommonBaseService
{
    # 盘口信息
    public static array $siteSystemInfo = [];
    # 本地对盘口 玩法ID
    public static array $localToSiteMethodInfo = [];
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
        $user_id = $betRow->user_id;
        $method_id = $betRow->play_method;

        Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖计算22', ['betRowId'=>$betRow->id, 'lottery_type'=>$lottery_type, 'method_id'=>$method_id]);
        //p($method_id);
        try {
            self::$siteSystemInfo = CommonBaseService::getSystemBaseInfo($user_id, $lottery_type); # 盘口信息
            self::$localToSiteMethodInfo = CommonBaseService::getLocalToSiteMethods(self::$siteSystemInfo['system_type_id'], $method_id); #
            $logArr = ['siteSystemInfo'=>self::$siteSystemInfo, 'localToSiteMethodInfo'=>self::$localToSiteMethodInfo];
            Tool_Common::log('/betSite/'.__FUNCTION__, 'INFO', '盘口信息', $logArr);
            $betCodes = $betRow->codes;
            #p(['siteSystemInfo'=>self::$siteSystemInfo, 'localToSiteMethodInfo'=>self::$localToSiteMethodInfo]);
            switch ($method_id){
                case MethodMatchService::METHOD_ID_ZHIXUAN:
                    break;
                case MethodMatchService::METHOD_ID_ZULIU: # 组六
                case MethodMatchService::METHOD_ID_ZUSAN: # 组三
                    break;
                case MethodMatchService::METHOD_ID_DUDAN: # 独胆
                    break;
                case MethodMatchService::METHOD_ID_SHUANGFEN: # 双飞
                case MethodMatchService::METHOD_ID_QUANTUO: # 对子全拖、对子全包
                    $betCodes = '全包';
                    break;
                case MethodMatchService::METHOD_ID_YIMADING: # 一码定
                    // todo codes需要转换格式，拆分成多组号码 <option value="204">一码定位</option>code[0]["actionData"] => "XX1,2XX,X3X"
                    break;
                case MethodMatchService::METHOD_ID_ERMADING: # 二码定
                    // todo codes需要转换格式，拆分成多组号码 <option value="205">二码定位</option>code[0]["actionData"] => "X01,21X,X34"
                    break;
                case MethodMatchService::METHOD_ID_BAOZI_QB: # 豹子全包
                    $betCodes = '全包';
                    break;
                case MethodMatchService::METHOD_ID_ZL_4_MA: # 组六4码 	格式：3拖6789_组六
                case MethodMatchService::METHOD_ID_ZL_5_MA: # 组六5码
                case MethodMatchService::METHOD_ID_ZL_6_MA: # 组六6码
                case MethodMatchService::METHOD_ID_ZL_7_MA: # 组六7码
                case MethodMatchService::METHOD_ID_ZL_8_MA: # 组六8码
                case MethodMatchService::METHOD_ID_ZL_9_MA: # 组六9码
                    break;
                case MethodMatchService::METHOD_ID_ZS_2_MA: # 组三2码
                case MethodMatchService::METHOD_ID_ZS_3_MA: # 组三2码
                case MethodMatchService::METHOD_ID_ZS_4_MA: # 组三4码
                case MethodMatchService::METHOD_ID_ZS_5_MA: # 组三5码
                case MethodMatchService::METHOD_ID_ZS_6_MA: # 组三6码
                case MethodMatchService::METHOD_ID_ZS_7_MA: # 组三7码
                case MethodMatchService::METHOD_ID_ZS_8_MA: # 组三8码
                case MethodMatchService::METHOD_ID_ZS_9_MA: # 组三9码
                    break;
                case MethodMatchService::METHOD_ID_ZL_QB: # 组六全包
                case MethodMatchService::METHOD_ID_ZS_QB: # 组三全包
                    $betCodes = '全包';
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
                    $betCodes = $betCodes.'跨';
                    break;
                case MethodMatchService::METHOD_ID_YMT_ZL_2: # 一码拖2_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_3: # 一码拖3_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_4: # 一码拖4_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_5: # 一码拖5_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_6: # 一码拖6_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_7: # 一码拖7_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_8: # 一码拖8_组六
                case MethodMatchService::METHOD_ID_YMT_ZL_9: # 一码拖9_组六
                    // todo codes转换格式 (1)02 => 1拖02
                    if(preg_match_all('/(\d+)/', $betRow->codes, $matches)){
                        $betCodes = '('.$matches[0][0].')'.$matches[0][1];
                    }
                    break;
                case MethodMatchService::METHOD_ID_YMT_ZS_2: # 一码拖2_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_3: # 一码拖3_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_4: # 一码拖4_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_5: # 一码拖5_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_6: # 一码拖6_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_7: # 一码拖7_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_8: # 一码拖8_组三
                case MethodMatchService::METHOD_ID_YMT_ZS_9: # 一码拖9_组三
                    // todo codes转换格式
                    if(preg_match_all('/(\d+)/', $betRow->codes, $matches)){
                        $betCodes = '('.$matches[0][0].')'.$matches[0][1];
                    }
                    break;
                case MethodMatchService::METHOD_ID_FS_3: # 复式三
                case MethodMatchService::METHOD_ID_FS_4: # 复式四
                case MethodMatchService::METHOD_ID_FS_5: # 复式五
                case MethodMatchService::METHOD_ID_FS_6: # 复式六
                case MethodMatchService::METHOD_ID_FS_7: # 复式七
                case MethodMatchService::METHOD_ID_FS_8: # 复式八
                case MethodMatchService::METHOD_ID_FS_9: # 复式九
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
                    $betCodes = $betCodes.'和值';
                    break;
                case MethodMatchService::METHOD_ID_HZ_DA: # 和值大
                case MethodMatchService::METHOD_ID_HZ_XIAO: # 和值小
                case MethodMatchService::METHOD_ID_HZ_DAN: # 和值单
                case MethodMatchService::METHOD_ID_HZ_SHUANG: # 和值双
                    break;
                case MethodMatchService::METHOD_ID_DW_ZX_FS: # 定位直选复式
                    break;
                case MethodMatchService::METHOD_ID_QD: # 全倒
                    break;
                case MethodMatchService::METHOD_ID_ZX_FS: # 直选复式
                    break;
                default:
                    $err_msg = '未知玩法ID:'.$method_id;
                    $logArr = ['lottery_type'=>$lottery_type, 'betRowId'=>$betRow->id, 'err_msg'=>$err_msg];
                    Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '推送盘口处理异常10', $logArr);
                    return [10003, $logArr, $err_msg];
            }

            $postRst = self::postBet($betRow, $betCodes);

            $resultData = ['betRowId'=>$betRow->id, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'postRst'=>$postRst, 'err_msg'=>'处理结束'];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '推送盘口处理结束99', $resultData);
            var_dump(date('Y-m-d H:i:s ').'处理成功：betRowId:'.$betRow->id.'_method_id:'.$method_id);
        }catch (\Exception $e){
            $logArr = ['betRowId'=>$betRow->id, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'err_msg'=>$e->getMessage()];
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'ERR', '推送盘口处理异常11', $logArr);
            var_dump($e->getMessage());
            return [10004, $logArr, $e->getMessage()];
        }

        return [];
    }

    private static function postBet(object $betRow, $betCodes=''): bool
    {
        $method_id = $betRow->play_method; # 玩法ID
        $user_id = $betRow->user_id; # 用户ID
        $lottery_type = $betRow->lottery_type; # 彩种类型

        $site = self::$siteSystemInfo;
        $methodData = self::$localToSiteMethodInfo;

        #p(['site'=>$site, 'methodData'=>$methodData, 'betRow'=>$betRow]);
        $codes = str_replace(MethodMatchService::ZU_SPLIT_FLAG, ',', $betCodes);
        $post_data = [
            'code'=>[],
            'lujingstat'=>3,
            'action' => 'soonsend',
            #'post_number' => $codes,
            'sizixian' => 0,
            #'zhuan24stat' => 0,
            #'sid' => 'Kx231Z',
            #'inajax' => 1,
        ];
        $post_data['code'][] = [
            "actionData" => $codes,
            "mode" => (string)$betRow['single'],
            "playedId" => $methodData['site_method_id'],
        ];
        $headers = [
            'Cookie' => $site['cookie'],
        ];

        $result = SiteOrderApi::push($site['ssc_domain'], $post_data, $headers);

        $logArr = ['user_id'=>$user_id, 'method_id'=>$method_id, 'post_data'=>$post_data, 'lottery_type'=>$lottery_type, 'result'=>$result];
        Tool_Common::log('/betSite/'.__FUNCTION__, 'INFO', '推网盘', $logArr);

        return true;
    }
}
