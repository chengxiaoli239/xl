<?php

namespace common\service\lottery\aozhou5;

use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\models\wechat\Bets;
use backend\service\agent\AgentUsersBalanceService;
use common\helpers\RequestHelper;
use common\models\wechat\WechatUser;
use common\open\aozhou5\api\OrderApi;
use common\open\aozhou5\api\UserApi;
use common\service\CommonService;
use common\service\open\ActionBaseService;
use common\service\open\aozhou5\ActionService;
use common\service\open\telegram\MessageOperateService;
use common\service\ssc\QihaoService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\jobs\SsxxBetJobs;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\Odds3dService;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use yii\helpers\Json;

class AoZhou5BetService extends CommonBaseService
{
    # 盘口信息
    public static array $siteSystemInfo = [];
    # 本地对盘口 玩法ID
    public static array $localToSiteMethodInfo = [];
    public static array $platformUser = [];
    public static function preBetValidate($betRowId): array
    {
        try {
            $betRow = Bets::findOne($betRowId);
            return [0, ['betRow'=>$betRow], '校验成功']; # 测试
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
            list($code, $data, $msg) = AoZhou5BetService::preBetValidate($betRowId);
            if($code>0){
                throw_info($msg, $code);
            }
            $betRow = $data['betRow']; # object
            $lottery_type = $betRow->lottery_type;
            $qiHao = $betRow->qihao;
            $user_id = $betRow->user_id;
            $method_id = $betRow->play_method;
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推盘口', ['betRowId'=>$betRow->id, 'lottery_type'=>$lottery_type, 'method_id'=>$method_id]);

            self::$siteSystemInfo = CommonBaseService::getSystemBaseInfo($user_id, $lottery_type); # 盘口信息
            self::$localToSiteMethodInfo = CommonBaseService::getLocalToSiteMethods($method_id, self::$siteSystemInfo['system_type_id'], $betRow->codes); #
            self::$platformUser = WechatUser::find()->where(['id'=>$betRow->wechat_user_id])->asArray()->limit(1)->one();

            Tool_Common::log('/betSite/'.__FUNCTION__, 'INFO', '盘口信息', [
                'method_id'=>$method_id,
                'siteSystemInfo'=>self::$siteSystemInfo,
                'localToSiteMethodInfo'=>self::$localToSiteMethodInfo,
            ]);
            $betCodes = $betRow->codes;
            //p(['method_id'=>$method_id, 'betCodes'=>$betCodes, 'siteSystemInfo'=>self::$siteSystemInfo, 'localToSiteMethodInfo'=>self::$localToSiteMethodInfo]);
            $postRst = self::postBet($betRow, $betCodes);

            # 下单扣减
            AgentUsersBalanceService::updateBalance((string)$betRowId, $betRow->bet_money, $betRow->wechat_user_id, WechatUserService::TYPE_ORDER_BET);
            $resultData = ['betRowId'=>$betRow->id, 'betQiHao'=>$qiHao, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'postRst'=>$postRst, 'err_msg'=>'处理结束'];
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '推送盘口处理结束99', $resultData);
            var_dump(date('Y-m-d H:i:s ').'处理成功：betRowId:'.$betRow->id.'_method_id:'.$method_id);
        }catch (\Exception $e){
            $err_msg = $e->getMessage();
            $logArr = ['betRowId'=>$betRow->id, 'method_id'=>$method_id, 'lottery_type'=>$lottery_type, 'errCode'=>$e->getCode(), 'err_msg'=>$err_msg.$e->getFile().$e->getLine()];
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '推送盘口处理异常11', $logArr);
            var_dump($err_msg);
            $betRow->push_status = ($e->getCode() > SsxxBetJobs::INVALID_STATUS_CODE) ? BetsBackend::PUSH_STATUS_CANNOT : BetsBackend::PUSH_STATUS_FAIL;
            $betRow->push_desc = $err_msg;
            $betRow->save();
            //throw_info($e->getMessage(), $e->getCode());
            return [10004, $logArr, $err_msg];
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

        //p(['site'=>$site, 'methodData'=>$methodData, 'betRow'=>$betRow]);
        $codes = str_replace(MethodMatchService::ZU_SPLIT_FLAG, ',', $betCodes);
        if(empty($codes) && $codes !== '0'){
            throw_info('推送盘口异常:号码为空');
        }
        $Odds = Odds3dService::getOdds($betRow->user_id, $betRow->play_method); # 玩法赔率
        if(!isset($Odds['odds'])){
            throw_info('赔率获取异常user_id:'.$betRow->user_id.'_play_method:'.$betRow->play_method);
        }
        $postData1 = [
            '__'=>'isAutoOdds',
            'gameId'=>601,
            'rebate' => 'A',
            'data' => [
                [$methodData['site_method_id'], $Odds['odds'], (string)floatval($betRow->bet_money)], // 赔率待处理
            ],
            #'cbk' => '0a2016edb310cd7c3a6afae7ee88ed8077d9aa29853867b9b9e0e735eaf8bb470fcc5bc44796ce782116ccb2ab2631ae08fa23f414c7e6c6',
            'cbk' => explode('=', trim($site['cookie']))[1],
        ];
        $objectClass = ActionBaseService::getClass($site['tz_system_id']);
        $objectClass->domain = $site['ssc_domain'];
        $objectClass->tzSystemUsers = TzSystemsUsers::findOne($site['id']);
        $parsed_url = parse_url($site['ssc_domain']); # Array ( [scheme] => https [host] => ac3868.com )
        $url = "https://url{$objectClass->line_number}.{$parsed_url['host']}";

        $result1 = OrderApi::push($url, $postData1);

        list($currentQiHao, $nextQiHao) = QihaoService::getKjQiHao(self::LOTTERY_TYPE_AOZHOU5);
        $postData2 = [
            '__'=>'bettingSingle',
            'data' => Json::encode([
                'gameId'=>601,
                'pusId' => 8,
                'openingNum' => (int)$nextQiHao,
                'rebate' => 'A',
                'data' => [
                    [$methodData['site_method_id'], $Odds['odds'], (string)floatval($betRow->bet_money)], // 赔率待处理
                ]
            ]),
            'cbk' => explode('=', trim($site['cookie']))[1],
        ];
        $result2 = OrderApi::pushBettingSingle($url, $postData2);
        $logArr = ['betRowId'=>$betRowId, 'user_id'=>$user_id, 'method_id'=>$method_id, 'methodData'=>$methodData, 'post_data1'=>$postData1, 'post_data2'=>$postData2, 'lottery_type'=>$lottery_type, 'result1'=>$result1, 'result2'=>$result2];
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推网盘10', $logArr);

        if(!empty($result2['error'])){ # 错误码：2成功、9918 登录超时....
            $logArr['result'] = $result2;
            Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推网盘20', $logArr);
            throw_info($result2['error']??'推送盘口异常', 30001);
        }
        $objectClass->getUserInfo(); # 同步余额
        Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '推网盘30', ['result2'=>$result2]);

        return true;
    }

    public static function postRecordToSite(): array
    {
        $where = [
            'AND',
            ['=', 'push_status', BetsBackend::PUSH_STATUS_FAIL],
            ['>=', 'created_at', time()-1800]
        ];
        $BetsQuery = BetsBackend::find()->select(['id', 'order_id'])->where($where)->limit(100)->orderBy(['id'=>SORT_DESC]);
        //$sql = $BetsQuery->createCommand()->getRawSql();var_dump($sql);
        $Bets = $BetsQuery->asArray()->all();
        foreach ($Bets as $bet){
            try {
                $result = AoZhou5BetService::postToSite($bet['id']);
                Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'INFO', '异常数据补上盘', ['id'=>$bet['id'], 'order_id'=>$bet['order_id'], 'result'=>$result]);
            }catch (\Exception $e){
                Tool_Common::log('/bet_aozhou5/'.__FUNCTION__, 'ERR', '异常数据补上盘-异常', ['id'=>$bet['id'], 'order_id'=>$bet['order_id'], 'err_msg'=>$e->getMessage()]);
            }
        }
        var_dump('执行结束 '.date('Y-m-d H:i:s').' 补打'.count($Bets).'条');

        return [0, '操作成功'];
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
