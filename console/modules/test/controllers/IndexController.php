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
            $text = '006 016 026 036 046 056 060 061 062 063 064 065 066 106 117 127 137 147 157 160 167 171 172 173 174 175 176 177 206 217 228 238 248 258 260 268 271 278 282 283 284 285 286 287 288 306 317 328 339 349 359 360 369 371 379 382 389 393 394 395 396 397 398 399 406 417 428 439 460 471 482 493 506 517 528 539 560 571 582 593 600 601 602 603 604 605 606 610 617 620 628 630 639 640 650 660 671 682 693 711 712 713 714 715 716 717 721 728 731 739 741 751 761 771 782 793 822 823 824 825 826 827 828 832 839 842 852 862 872 882 893 933 934 935 936 937 938 939 943 953 963 973 983 993 直各1元';
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
