<?php
namespace console\modules\user\controllers;

use common\service\index\CrontabIndexService;
use common\service\thirdD\ReplyService;
use common\service\thirdD\sx\Ssxx3dBetService;
use Yii;
use yii\base\Controller;
use common\tools\Tool_Common;


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

    /**
     * @desc 打包回复
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii user/index/package-reply
     */
    public function actionPackageReply(): array
    {
        $rst = ReplyService::packageReply();

        return $rst;
    }

    /**
     * @desc 打包回复散客
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii user/index/package-reply-user
     */
    public function actionPackageReplyUser(): array
    {
        $rst = ReplyService::packageReplyUser();

        return $rst;
    }

    /**
     * @desc 上盘口
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii user/index/post-to-site
     */
    public function actionPostToSite(): array
    {
        $rst = Ssxx3dBetService::postRecordToSite();

        return $rst;
    }

}
