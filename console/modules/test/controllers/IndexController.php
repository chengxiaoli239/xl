<?php
namespace console\modules\test\controllers;

use backend\models\searchs\wechat\Bets;
use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\models\UserSysPlans;
use backend\service\NumService;
use backend\service\statics\statics_3d\Statics3dUserDataService;
use backend\service\StaticService;
use common\service\helpers\ThirdD;
use common\service\thirdD\match\MatchCodeService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\OperateLotteryService;
use common\service\thirdD\sx\Ssxx3dBetService;
use common\service\thirdD\sx\Sx3dUserService;
use common\service\thirdD\ThirdDTypeService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\tools\KjDataGet;
use common\tools\Util;
use DateTime;
use Yii;
use yii\base\Controller;
use yii\helpers\Json;

class IndexController extends Controller
{
    /**
     * @desc 测试
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii test/index/dw
     */
    public function actionDw(): array
    {
        $dateString = '20231114002';

        try {
            $lottery_types = StaticService::getGrabDataLotteryTypes($useCache=0);
            p($lottery_types);
            $betRow = Bets::findOne(26244	);
            list($code, $data, $msg) = OperateLotteryService::operateOne($betRow);
            p([$code, $data, $msg]);
            $rst = KjDataGet::grabOneLotteryKjData($lottery_type=27);p($rst); # 开奖
            $str = 'http://47.107.58.222:8090/wechat/bets/index.html?Bets%5BwechatUserName%5D=wxid_ckgr7i2q9fr522&Bets%5Border_id%5D=&Bets%5Bplay_method%5D=&Bets%5Bqihao%5D=&Bets%5Bstatus%5D=&Bets%5Bpush_status%5D=&Bets%5Blottery_type%5D=';
            p(urldecode($str));
            list($code, $data, $msg) = Statics3dUserDataService::calculateUserDayData($wechat_user_id=250, $date='2023-12-23', [27, 26]);
            p([$code, $data, $msg]);
            $TzSystemsUser = TzSystemsUsers::findOne(42);
            $rst = Sx3dUserService::login($TzSystemsUser);p($rst);
            #$code = Json::decode('{"playedId":200,"playedName":"u76f4u9009","actionData":"213,234,879,342,324,456","bonusProp":900,"actionNum":6,"mode":"18"}');
            #list($localToSiteMethodInfo, $codeData) = MatchCodeService::apiMethodDataToLocalMethodData($code);
            #p([$localToSiteMethodInfo, $codeData]);
            #$betRow = Bets::findOne(6362);
            #list($code, $data, $msg) = OperateLotteryService::operateOne($betRow, $kjCode='2,5,4');p([$code, $data, $msg]);
            //$plan = UserSysPlans::findOne(7653);
            //$filter_dynamic_codes = NumService::getBeforeKjCodesDynamic($plan, [60]); p(count($filter_dynamic_codes));
            //$text = '百:1346,十:3689,个:6789';
            # 福彩 02349/ 组六组三 各20
            # 福彩 02349/ 12345..32457组六  组三各20
            $text = '福彩 02349/ 组六组三 各20';

            ##################### 直、组 #######################
            $text = '福一直二组 369';
            $text = '福直一组二 369';
            $text = '福1直2组 369';
            $text = '福直1组2 369';

            $text = '福一元直二元组 369';
            $text = '福直一元组二元 369';
            $text = '福1元直2元组 369';
            $text = '福直1元组2元 369';

            $text = '福1倍直2倍组 369';
            $text = '福2倍组1倍直 369';
            $text = '福一倍直二倍组 369';
            $text = '福二倍组一倍直 369';
            ##################### 直、组 #######################

            ########### 组六、组三 #############
            $text = '福一元组三二元组六 369'; # --
            $text = '福组三一元组六二元 369'; # --
            $text = '福1元组三2元组六 369';
            $text = '福直1元组2元 369';

            $text = '福1倍组三2倍组六 369';
            $text = '福2倍组六1倍组三 369';
            $text = '福一倍组三二倍组六 369'; # --
            $text = '福二倍组六一倍组三 369'; # --

            $text = '福一元组三二元组六 369'; # --
            $text = '福组三一元组六二元 369'; # --
            $text = '福一倍组三二倍组六 369'; # --
            $text = '福二倍组六一倍组三 369'; # --
            ########### 组六、组三 #############

            $text = '福 二码定百234个456 各10';
            $text = '单032.302=十五倍，';
            $text = '福 6拖12347组三 各9元';
            $text = '复式 123 ，2345，23456，3456789 各100元';
            //$text = "123456789直2倍";
            $text = "体组六组三 1拖2345、23456 各10元

体组六组三 2拖1345、13456 各10元";
            #$betTexts = EYunMessageOperateService::resetMethodText($text); p($betTexts);# 重置匹配文本
            //$betText = EYunMessageOperateService::resetText($text); p($betText);# 重置匹配文本
            //preg_match('/各([' . MethodMatchService::CN_SINGLE_TEXT . ']{1,3})/u', $text, $matches); p($matches);
            #list($code, $data, $msg) = EYunMessageOperateService::getOnePlayMethodG($text); p([$text, $code, $data, $msg]); # 单个规则文本匹配处理
            $MessageService = new EYunMessageOperateService($user_id=22);
            $rst = $MessageService->receive($text, $fromUser='wxid_875i1kgd38x122'); p($rst);
            $betCodes = Ssxx3dBetService::resetOneZhiXuanFuShi($betCodes='1246;5678');p($betCodes);
            $qihao = Util::getBeforeNumQihao($dateString, $n=2);
            list($code, $data, $msg) = Ssxx3dBetService::postToSite($betRowId=4183);p([$code, $data, $msg]);
            echo $qihao;
        } catch (\Exception $e) {
            p($e->getMessage());
        }

        return [];
    }
}
