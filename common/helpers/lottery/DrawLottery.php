<?php
namespace common\helpers\lottery;

class DrawLottery
{
    public static function getGuiDrawData($kjCode='', $codeNum=4): array
    {
        $codes = explode(',', $kjCode);
        if((count($codes)<4 && $codeNum==4) OR (count($codes)<5 && $codeNum==5)){
            throw_info('开奖号码不匹配count:'.count($codes).'<'.$codeNum);
        }
        if(count($codes) == 5 && $codeNum == 4){
            array_pop($codes);
        }

        $heZhi = array_sum($codes);
        $gui = $heZhi%4 ? : 4;
        $ds = ($heZhi%2)? '单' : '双';

        return [$kjCode, $heZhi, (string)$gui, $ds];
    }

}