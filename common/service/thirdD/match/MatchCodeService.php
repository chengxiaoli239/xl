<?php
namespace common\service\thirdD\match;

use common\service\thirdD\CommonBaseService;
use common\tools\Tool_Common;

class MatchCodeService extends CommonBaseService
{
    public static function getExplainData($text=''): array
    {
        try {
            $params = ['textfield' => $text];
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'INFO', '号码数据匹配0', ['text'=>$text]);
            $domain = \Yii::$app->params['EXPLAIN_CODE_API'];
            $result = \common\open\thirdD\api\MatchCodeApi::push($domain, $params);
            Tool_Common::log('/matchCode/'.__FUNCTION__, 'INFO', '号码数据匹配', ['text'=>$text, 'result'=>$result]);
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
