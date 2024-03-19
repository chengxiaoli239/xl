<?php
namespace common\service\open\telegram;

class TelegramBaseService  extends \common\service\BaseService
{

    const CHAT_TYPE_PRIVATE = 'private';
    const CHAT_TYPE_GROUP = 'group';
    const CHAT_TYPE_OPTIONS = [
        self::CHAT_TYPE_PRIVATE => '私聊',
        self::CHAT_TYPE_GROUP => '群聊',
    ];

}
