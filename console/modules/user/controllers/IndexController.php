<?php
namespace console\modules\user\controllers;

use backend\service\statics\statics_3d\Statics3dUserDataService;
use common\service\index\CrontabIndexService;
use common\service\thirdD\ReplyService;
use common\service\thirdD\sx\Ssxx3dBetService;
use Yii;
use yii\base\Controller;
use common\tools\Tool_Common;
use yii\console\ExitCode;


class IndexController extends Controller
{
    /**
     * @desc 自动登录
     * /www/server/php/74/bin/php /www/wwwroot/lt/lottery_xl/xl/yii user/index/auto-login
     */
    public function actionAutoLogin(): int
    {
        CrontabIndexService::autoLogin();

        return ExitCode::OK;
    }

    /**
     * @desc 打包回复
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii user/index/package-reply
     */
    public function actionPackageReply(): array
    {
        $rst1 = ReplyService::packageReply();
        $rst2 = ReplyService::packageReplyUser();

        return [$rst1, $rst2];
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

    /**
     * @desc 数据统计
     * /www/server/php/74/bin/php /www/wwwroot/third/yii user/index/static-recently
     */
    public function actionStaticRecently(): array
    {
        $rst = Statics3dUserDataService::staticsRecently();

        return $rst;
    }
}
