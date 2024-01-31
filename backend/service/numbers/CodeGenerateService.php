<?php
namespace backend\service\numbers;
use backend\service\BaseService;
use yii;
use yii\helpers\Json;

class CodeGenerateService extends BaseService {

    /** 给定号码格式，返回需要的returnNums个数的数组
     * @param string $codeStr - 号码，比如：23456
     * @param int $returnNums - 号码位数，比如：2
     * @return array
     */
    public static function getCode(string $codeStr='', int $returnNums=2): array
    {
        $returnCode = [];
        $len = strlen($codeStr);
        switch ($returnNums){
            case 1:
                for ($i=0; $i<$len; $i++){
                    $returnCode[] = $codeStr[$i];
                }
                break;
            case 2:
                for ($i=0; $i<$len; $i++){
                    for ($j=0; $j<$len; $j++){
                        if($j<=$i) continue;
                        $returnCode[] = $codeStr[$i].$codeStr[$j];
                        $returnCode[] = $codeStr[$j].$codeStr[$i];
                    }
                }
                break;
            case 3:
                for ($i=0; $i<$len; $i++){
                    for ($j=0; $j<$len; $j++){
                        if($j<=$i) continue;
                        for ($k=0; $k<$len; $k++) {
                            if($k<=$j) continue;
                            $returnCode[] = $codeStr[$i].$codeStr[$j].$codeStr[$k];
                            $returnCode[] = $codeStr[$i].$codeStr[$k].$codeStr[$j];
                            $returnCode[] = $codeStr[$j].$codeStr[$i].$codeStr[$k];
                            $returnCode[] = $codeStr[$j].$codeStr[$k].$codeStr[$i];
                            $returnCode[] = $codeStr[$k].$codeStr[$i].$codeStr[$j];
                            $returnCode[] = $codeStr[$k].$codeStr[$j].$codeStr[$i];
                        }
                    }
                }
                break;
        }

        return $returnCode;
    }

}
