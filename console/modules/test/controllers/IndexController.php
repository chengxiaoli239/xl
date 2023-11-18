<?php
namespace console\modules\test\controllers;

use backend\models\thirdD\BetsBackend;
use common\service\thirdD\MethodMatchService;
use common\service\thirdD\sx\Ssxx3dBetService;
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
            $text = '福528.871.825.835.571.591.209.289.315.317.582.597.682.695.819.419.619.719.089.933 组1倍直1倍
共80元';
            #$text = '百位8 各10元';
            //$methodArr = MethodMatchService::matchDingWei($text, $codes=[], $count); p($methodArr);
            $MessageService = new EYunMessageOperateService($user_id=21);
            $rst = $MessageService->receive($text, $fromUser='wxid_875i1kgd38x122'); p($rst);
            $betRow = BetsBackend::findOne('1177');
            list($code, $data, $msg) = Ssxx3dBetService::postToSite($betRow);p([$code, $data, $msg]);
            $betCodes = Ssxx3dBetService::resetOneZhiXuanFuShi($betCodes='1246;5678');p($betCodes);
            $qihao = Util::getBeforeNumQihao($dateString, $n=2);
            echo $qihao;
        } catch (\Exception $e) {
            p($e->getMessage());
        }

        return [];
    }
}
