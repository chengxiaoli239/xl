<?php
namespace common\service\cache\keys\message;

trait MessageReplyCacheKeyTrait
{
    public static function package($member_id=0): string
    {
        return 'message:reply:member_id_' . $member_id;
    }
}
