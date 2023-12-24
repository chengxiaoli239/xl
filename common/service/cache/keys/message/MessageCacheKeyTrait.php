<?php
namespace common\service\cache\keys\message;

trait MessageCacheKeyTrait
{
    public static function eyun($toUser='', $msgId='', $newMsgId=''): string
    {
        return 'message:eyun:id_' . $toUser.'_'.$msgId.'_'.$newMsgId;
    }
}
