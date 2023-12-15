<?php
namespace console\modules\test\controllers;

use backend\models\searchs\wechat\Bets;
use backend\models\thirdD\BetsBackend;
use backend\models\UserSysPlans;
use backend\service\NumService;
use common\service\helpers\ThirdD;
use common\service\thirdD\match\MatchCodeService;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\OperateLotteryService;
use common\service\thirdD\sx\Ssxx3dBetService;
use common\service\thirdD\ThirdDTypeService;
use common\service\wechat\eyun\EYunMessageOperateService;
use common\tools\Util;
use DateTime;
use Yii;
use yii\base\Controller;

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
            $text = '福直027 072 026 062 025 052 各2倍';
            $text = '单032.302=十五倍，';
            $text = '福
12、13、17、18、27、41 各飞200元
豹子全包50
总计1250';
            $text = '福
909  838 直各16组各4';
            //$text = "123456789直2倍";
            $text = "排123 10倍";
            #$betTexts = EYunMessageOperateService::resetMethodText($text); p($betTexts);# 重置匹配文本
            //$betText = EYunMessageOperateService::resetText($text); p($betText);# 重置匹配文本
            //preg_match('/各([' . MethodMatchService::CN_SINGLE_TEXT . ']{1,3})/u', $text, $matches); p($matches);
            #list($code, $data, $msg) = EYunMessageOperateService::getOnePlayMethodG($text); p([$text, $code, $data, $msg]); # 单个规则文本匹配处理
            $MessageService = new EYunMessageOperateService($user_id=21);
            $rst = $MessageService->receive($text, $fromUser='wxid_875i1kgd38x122'); p($rst);
            $betCodes = Ssxx3dBetService::resetOneZhiXuanFuShi($betCodes='1246;5678');p($betCodes);
            $qihao = Util::getBeforeNumQihao($dateString, $n=2);
            $betRow = BetsBackend::findOne('4183');
            list($code, $data, $msg) = Ssxx3dBetService::postToSite($betRow);p([$code, $data, $msg]);
            echo $qihao;
        } catch (\Exception $e) {
            p($e->getMessage());
        }

        return [];
    }
}
