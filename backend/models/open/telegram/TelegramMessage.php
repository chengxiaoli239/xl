<?php

namespace backend\models\open\telegram;

use Yii;
use common\models\open\telegram\TelegramMessage as CommonTelegramMessage;

class TelegramMessage extends CommonTelegramMessage
{
    const MESSAGE_TYPE_PRIVATE = 'private';
    const MESSAGE_TYPE_GROUP = 'group';
    const MESSAGE_TYPE_OPTIONS = [
        self::MESSAGE_TYPE_PRIVATE => '私聊',
        self::MESSAGE_TYPE_GROUP => '群聊',
    ];

}
