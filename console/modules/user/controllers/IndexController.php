<?php
namespace console\modules\user\controllers;

use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\baota\BaoTaService;
use backend\service\BaseService;
use backend\service\datas\DatasClearService;
use backend\service\SscDataService;
use common\service\index\CrontabIndexService;
use common\service\proxy\ProxyBaseService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\OperateLotteryService;
use Yii;
use backend\service\OpKjService;
use common\tools\KjDataGet;
use yii\base\Controller;
use common\tools\Tool_Common;
use backend\service\BetService;
use backend\service\StaticService;


class IndexController extends Controller
{
    /**
     * @desc 自动登录
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii user/index/auto-login
     */
    public function actionAutoLogin(): array
    {
        $rst = CrontabIndexService::autoLogin();

        return $rst;
    }
}
