<?php
namespace console\modules\test\controllers;

use backend\models\thirdD\BetsBackend;
use backend\models\UserSysPlans;
use backend\service\NumService;
use common\service\helpers\ThirdD;
use common\service\thirdD\MethodMatchService;
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
            #$plan = UserSysPlans::findOne(7636);
            #$filter_dynamic_codes = NumService::getBeforeKjCodesDynamic($plan, [146]);
            #p(count($filter_dynamic_codes));
            //$text = '百:1346,十:3689,个:6789';
            $text = '福
组六2拖01589买60元
3拖01589买60元
4拖01589买60元
6拖01589买60元
7拖01589买60元

组三2拖01589买20元
3拖01589买20元
4拖01589买20元
6拖01589买20元
7拖01589买20元
总计400';
            //$betTexts = EYunMessageOperateService::resetMethodText($text); p($betTexts);# 重置匹配文本
            //$betText = EYunMessageOperateService::resetText($text); p($betText);# 重置匹配文本
            //preg_match('/各([' . MethodMatchService::CN_SINGLE_TEXT . ']{1,3})/u', $text, $matches); p($matches);
            //list($code, $data, $msg) = EYunMessageOperateService::getOnePlayMethodG($text); p([$text, $code, $data, $msg]); # 单个规则文本匹配处理
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
