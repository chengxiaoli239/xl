<?php
namespace common\helpers;

class Code
{

    /**
     * @param string $codes 1,2,3,4
     * @return array [1,2,3,4]
     */
    public static function codeStringToArray(string $codes=''): array
    {
        $codeArr = [];
        for ($i=0; $i<strlen($codes); $i++){
            $codeArr[] = $codes[$i];
        }

        return $codeArr;
    }

}
