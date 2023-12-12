<?php
namespace common\service\thirdD\match;

use common\service\thirdD\CommonBaseService;

class MatchCodeService extends CommonBaseService
{
    public static function getExplainData($text=''): array
    {
        try {
            $params = ['textfield'=>$text];
            $domain = \Yii::$app->params['EXPLAIN_CODE_API'];
            $result = \common\open\thirdD\api\MatchCodeApi::push($domain, $params);
        }catch (\Exception $e){
            return ['code'=>$e->getCode(), 'msg'=>$e->getMessage()];
        }

        return $result;
    }

    public static function getCodeData($text=''): array
    {
        $codeDatas = MatchCodeService::getExplainData($text);

        return $codeDatas;
    }
}
