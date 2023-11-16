<?php
namespace console\modules\test\controllers;

use backend\models\thirdD\BetsBackend;
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
            $text = '福家独胆7    3倍  30块';
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
