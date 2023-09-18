<?php

namespace common\service\wechat;

use common\models\eyun\EyunAuth;
use common\models\eyun\RobotUser;
use common\service\BaseService;
use common\service\chat\Tool_Common;

class TextTranslateService extends BaseService
{

    public static function translate($text){
        Tool_Common::log('/wechat/'.__FUNCTION__, 'INFO', '文字翻译', ['text'=>$text]);

    }

}
