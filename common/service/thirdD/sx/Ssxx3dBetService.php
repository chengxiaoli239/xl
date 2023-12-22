<?php

namespace common\service\thirdD\sx;

use backend\models\thirdD\BetsBackend;
use backend\models\wechat\Bets;
use common\helpers\RequestHelper;
use common\models\wechat\WechatUser;
use common\open\thirdD\api\SiteOrderApi;
use common\service\CommonService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\jobs\SsxxBetJobs;
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
                    throw_info('已是撤单状态，无需推送盘口', SsxxBetJobs::INVALID_STATUS_CODE);
                case $wechatUser->is_chi == 1:
                    throw_info('该用户私下吃，无需推送盘口', SsxxBetJobs::INVALID_STATUS_CODE);
                case !empty($codeStr):
                    throw_info('已开奖期号['.$lottery_type.'_'.$qihao.']，禁止推送盘口', SsxxBetJobs::INVALID_STATUS_CODE);
                default:
                    break;
            }
        }catch (\Exception $e){
            return [$e->getCode(), [], $e->getMessage()];
        }

        return [0, ['betRow'=>$betRow], '校验成功'];
    }

    /**
     * 推向盘口
     * @param int $betRowId
     * @return array
     */
    public static function postToSite(int $betRowId): array
    {
        if(empty($betRowId)){
            return [];
        }
        try {
            list($code, $data, $msg) = Ssxx3dBetService::preBetValidate($betRowId);
            if($code>0){
                throw_info($msg, $code);
            }
            $betRow = $data['betRow']; # object
            $lottery_type = $betRow->lottery_type;
            $qihao = $betRow->qihao;
            $user_id = $betRow->user_id;
            $method_id = $betRow->play_method;
            Tool_Common::log('/data_kj/'.__FUNCTION__, 'INFO', '开奖计算22', ['betRowId'=>$betRow->id, 'lottery_type'=>$lottery_type, 'method_id'=>$method_id]);

            self::$siteSystemInfo = CommonBaseService::getSystemBaseInfo($user_id, $lottery_type); # 盘口信息
            self::$localToSiteMethodInfo = CommonBaseService::getLocalToSiteMethods($method_id, self::$siteSystemInfo['system_type_id']); #
            $logArr = ['method_id'=>$method_id, 'siteSystemInfo'=>self::$siteSystemInfo, 'localToSiteMethodInfo'=>self::$localToSiteMethodInfo];
            Tool_Common::log('/betSite/'.__FUNCTION__, 'INFO', '盘口信息', $logArr);
            $betCodes = $betRow->codes;
            //p($betCodes);
            //p(['method_id'=>$method_id, 'siteSystemInfo'=>self::$siteSystemInfo, 'localToSiteMethodInfo'=>self::$localToSiteMethodInfo]);
            switch ($method_id){
                case MethodMatchService::METHOD_ID_ZHIXUAN:
                    break;
                case MethodMatchService::METHOD_ID_ZULIU: # 组六
                case MethodMatchService::METHOD_ID_ZUSAN: # 组三
                    break;
                case MethodMatchService::METHOD_ID_DUDAN: # 独胆
                    break;
                case MethodMatchService::METHOD_ID_SHUANGFEI: # 双飞
                    break;
                case MethodMatchService::METHOD_ID_QUANTUO: # 对子全拖、对子全包
                    if(strpos($betCodes, '全包') !== false){
                        $betCodes = '全包';
                    }
                    break;
                case MethodMatchService::METHOD_ID_DUIZI_QB:
                    $betCodes = '全包';
                    break;
                case MethodMatchService::METHOD_ID_YIMADING: # 一码定
                    // todo codes需要转换格式，拆分成多组号码 <option value="204">一码定位</option>code[0]["actionData"] => "XX1,2XX,X3X"
                    if(strpos($betCodes, 'X')===false) {
                        $betCodes = Ssxx3dBetService::resetOneFixed($betCodes);
                    }
                    break;
                case MethodMatchService::METHOD_ID_ERMADING: # 二码定
                    // todo codes需要转换格式，拆分成多组号码 <option value="205">二码定位</option>code[0]["actionData"] => "X01,21X,X34"
                    if(strpos($betCodes, 'X')===false){
                        $betCodes = Ssxx3dBetService::resetTwoFixed($betCodes);
                    }
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
                case MethodMatchService::METHOD_ID_DW_ZX_FS: # 定位-直选复式
                    $betCodes = Ssxx3dBetService::resetOneFixedZhiXuanFuShi($betCodes);
                    break;
                case MethodMatchService::METHOD_ID_QD: # 全倒
                    $betCodes = Ssxx3dBetService::resetOneQuanDao($betCodes);
                    break;
                case MethodMatchService::METHOD_ID_ZX_FS: # 直选复式
                    $betCodes = Ssxx3dBetService::resetOneZhiXuanFuShi($betCodes);
                    break;
                default:
                    $err_msg = '未知玩法ID:'.$method_id;
                    throw_info($err_msg, 10003);
            }
            $postRst = self::postBet($betRow, $betCodes);

            $resultData = ['betRowId'=>$betRow->id, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'postRst'=>$postRst, 'err_msg'=>'处理结束'];
            Tool_Common::log('/bet_sx/'.__FUNCTION__, 'ERR', '推送盘口处理结束99', $resultData);
            var_dump(date('Y-m-d H:i:s ').'处理成功：betRowId:'.$betRow->id.'_method_id:'.$method_id);
        }catch (\Exception $e){
            $err_msg = $e->getMessage();
            $logArr = ['betRowId'=>$betRow->id, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'err_msg'=>$err_msg];
            Tool_Common::log('/bet_sx/'.__FUNCTION__, 'ERR', '推送盘口处理异常11', $logArr);
            var_dump($err_msg);
            $betRow->push_status = ($e->getCode() > SsxxBetJobs::INVALID_STATUS_CODE) ? BetsBackend::PUSH_STATUS_CANNOT : BetsBackend::PUSH_STATUS_FAIL;
            $betRow->push_desc = $err_msg;
            $betRow->save();
            throw_info($e->getMessage(), $e->getCode());
            //return [10004, $logArr, $e->getMessage()];
        }

        $betRow->push_status = BetsBackend::PUSH_STATUS_SUCCESS;
        $betRow->save();


        return [0, ['resultData'=>$resultData], '推送成功'];
    }

    /**
     * 一码定位号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetOneFixed($dataStr): string
    {
        $codeDatas = explode(MethodMatchService::ZU_SPLIT_FLAG, $dataStr);

        $dataCodes = [];
        foreach ($codeDatas as $codeStr){
            //if(!preg_match_all('/[百十个](\d)/u', str_replace(':','',$datas), $mc)) continue;
            $datas = explode(':', $codeStr);
            switch(true){
                case $datas[0] == '百':
                    for ($i=0; $i<strlen($datas[1]); $i++){
                        $dataCodes[] = $datas[1][$i] . 'XX';
                    }
                    break;
                case '十':
                    for ($i=0; $i<strlen($datas[1]); $i++) {
                        $dataCodes[] = 'X' . $datas[1][$i] . 'X';
                    }
                    break;
                case '个':
                    for ($i=0; $i<strlen($datas[1]); $i++) {
                        $dataCodes[] = 'XX'.$datas[1][$i];
                    }
                    break;
                default:
                    throw_info('匹配异常11');
                    break;
            }
        }
        $dataStr = implode(',', $dataCodes);

        return $dataStr;
    }

    /**
     * 二码定位号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetTwoFixed($dataStr): string
    {
        $datas = explode(',', $dataStr);
        $codeDatas = [];
        $first = explode(':', $datas[0]);
        $codeDatas[$first[0]] = $first[1];

        $second = explode(':', $datas[1]);
        $codeDatas[$second[0]] = $second[1];
        #p($codeDatas, 0);
        $datas = [];
        switch (true){
            case isset($codeDatas['百']) && isset($codeDatas['十']):
                for ($i=0; $i<strlen($codeDatas['百']); $i++){
                    for ($j=0; $j<strlen($codeDatas['十']); $j++){
                        $datas[] = $codeDatas['百'][$i].$codeDatas['十'][$j].'X';
                    }
                }
                break;
            case isset($codeDatas['百']) && isset($codeDatas['个']):
                for ($i=0; $i<strlen($codeDatas['百']); $i++){
                    for ($j=0; $j<strlen($codeDatas['个']); $j++){
                        $datas[] = $codeDatas['百'][$i].'X'.$codeDatas['个'][$j];
                    }
                }
                break;
            case isset($codeDatas['十']) && isset($codeDatas['个']):
                for ($i=0; $i<strlen($codeDatas['十']); $i++){
                    for ($j=0; $j<strlen($codeDatas['个']); $j++){
                        $datas[] = $codeDatas['十'][$i].'X'.$codeDatas['个'][$j];
                    }
                }
                break;
        }


        $dataStr = implode(',', $datas);

        return $dataStr;
    }

    /**
     * 定位直选复式号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetOneFixedZhiXuanFuShi($dataStr): string
    {
        # 福[定位直选复式] => 百:34578,十:34569,个:23467  =>	375.00元
        //p($dataStr);
        $datas = explode(',', $dataStr);
        $codeDatas = [];
        $first = explode(':', $datas[0]);
        $codeDatas[$first[0]] = $first[1];

        $second = explode(':', $datas[1]);
        $codeDatas[$second[0]] = $second[1];

        $third = explode(':', $datas[2]);
        $codeDatas[$third[0]] = $third[1];
        //p([$dataStr, $codeDatas]);
        $datas = [];
        for ($i=0; $i<strlen($codeDatas['百']); $i++){
            for ($j=0; $j<strlen($codeDatas['十']); $j++){
                for ($k=0; $k<strlen($codeDatas['个']); $k++) {
                    $datas[] = $codeDatas['百'][$i] . $codeDatas['十'][$j] . $codeDatas['个'][$k];
                }
            }
        }

        $dataStr = implode(',', $datas);

        return $dataStr;
    }

    /**
     * 全倒 号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetOneQuanDao($dataStr): string
    {
        # 全倒 => 459 678  =>	元
        return self::extracted($dataStr);
    }

    /**
     * 直选复式号码转换
     * @param $dataStr
     * @return string
     */
    public static function resetOneZhiXuanFuShi($dataStr): string
    {
        # 福[直选复式] => 45678  =>	60.00元
        return self::extracted($dataStr);
    }


    private static function postBet(object $betRow, $betCodes=''): bool
    {
        $betRowId = $betRow->id; # 记录ID
        $method_id = $betRow->play_method; # 玩法ID
        $user_id = $betRow->user_id; # 用户ID
        $lottery_type = $betRow->lottery_type; # 彩种类型

        $site = self::$siteSystemInfo;
        $methodData = self::$localToSiteMethodInfo;

        #p(['site'=>$site, 'methodData'=>$methodData, 'betRow'=>$betRow]);
        $codes = str_replace(MethodMatchService::ZU_SPLIT_FLAG, ',', $betCodes);
        if(empty($codes) && $codes !== '0'){
            throw_info('推送盘口异常:号码为空');
        }
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

        $logArr = ['betRowId'=>$betRowId, 'user_id'=>$user_id, 'method_id'=>$method_id, 'methodData'=>$methodData, 'post_data'=>$post_data, 'lottery_type'=>$lottery_type, /*'result'=>$result*/];
        Tool_Common::log('/bet_sx/'.__FUNCTION__, 'INFO', '推网盘10', $logArr);
        $result = SiteOrderApi::push($site['ssc_domain'], $post_data, $headers);
        if($result['s'] != 2){ # 错误码：2成功、9918 登录超时....
            $logArr['result'] = $result;
            Tool_Common::log('/bet_sx/'.__FUNCTION__, 'INFO', '推网盘20', $logArr);
            throw_info($result['m']??'推送盘口异常', 30001);
        }

        return true;
    }

    public static function postRecordToSite(): bool
    {
        $where = [
            'AND',
            ['=', 'push_status', BetsBackend::PUSH_STATUS_FAIL],
            ['>=', 'created_at', time()-1800]
        ];
        $Bets = BetsBackend::find()->select(['id', 'order_id'])->where($where)->asArray()->all();
        foreach ($Bets as $bet){
            $result = Ssxx3dBetService::postToSite($bet['id']);
            Tool_Common::log('/bet_3d/'.__FUNCTION__, 'INFO', '异常数据补上盘', ['id'=>$bet['id'], 'order_id'=>$bet['order_id'], 'result'=>$result]);
        }

        return true;
    }

    /**
     * @param $dataStr
     * @return string
     */
    public static function extracted($dataStr): string
    {
        $dataStrs = explode(MethodMatchService::ZU_SPLIT_FLAG, $dataStr);

        $datas = [];
        foreach ($dataStrs as $dataStr) {
            $len = strlen($dataStr);
            for ($i = 0; $i < $len; $i++) {
                for ($j = 0; $j < $len; $j++) {
                    if ($j == $i) continue;
                    for ($k = 0; $k < $len; $k++) {
                        if ($j == $k or $i == $k) continue;
                        $datas[] = $dataStr[$i] . $dataStr[$j] . $dataStr[$k];
                    }
                }
            }
        }
        $dataStr = implode(',', $datas);
        return $dataStr;
    }
}
