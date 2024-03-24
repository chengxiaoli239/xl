<?php
# 开彩网
namespace common\kj\ssc;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\CurlService;
use backend\service\Lucky5\LuckyBaseService;
use common\helpers\LotteryType;
use common\kj\BaseKj;
use common\service\CommonService;
use common\service\proxy\ProxyBaseService;
use common\service\ssc\QihaoService;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use  yii;
use yii\helpers\Json;

class Aozhou extends BaseKj {
    public static int $lottery_type = LotteryType::AZ_LUCKY_5;
    CONST SUCCESS_CODE = 20000;

    /**
     * @desc 澳洲幸运五，官网：https://1680632.com/view/aozxy5/ssc_index.html
     * @param string $returnType
     * @return array|bool
     */
    public static function getLucky5(string $returnType = 'json', $is_auto = 1){
        try {
            $lottery_type = self::$lottery_type;
            $kjData = self::getCurrentKjData($lottery_type, $current_qihao);
            if($is_auto==1){
                self::lockGrab($lottery_type, $seconds=120);
            }

            list($currentQiHao, $nextQiHao) = QihaoService::getKjQiHao($lottery_type);
            if($is_auto==2 OR !$kjData) {
                try {
                    $domain = BaseKj::getApiHostByRoute('/kj/aozhou/lucky5');

                    // 创建 CookieJar 来存储 cookie
                    $cookieJar = new CookieJar();
                    // 创建 Guzzle 客户端
                    $client = new Client(['cookies' => $cookieJar]);
                    // 第一个请求的 URL
                    $firstUrl = $domain.'/view/aozxy5/ssc_index.html';

                    $now_time = time();

                    // 第二个请求的 URL
                    $secondUrl = $domain.'/api/CQShiCai/getBaseCQShiCaiList.do?lotCode=10010';

                    // 设置请求头，包括 Cookie
                    $headers = [
                        'Accept' => 'application/json, text/javascript, */*; q=0.01',
                        'Accept-Encoding' => 'gzip, deflate, br, zstd',
                        'path' => '/api/CQShiCai/getBaseCQShiCaiList.do?lotCode=10010',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                        'Referer' => $firstUrl, // 使用第一个请求的 URL 作为 Referer
                        #'Cookie' => $cookie, // 使用从第一个响应中提取的 Cookie
                        'Cookie' => '_ga_8GZN0CR7N8=GS1.1.'.$now_time.'.1.0.'.$now_time.'.0.0.0', // 使用从第一个响应中提取的 Cookie
                    ];

                    // 发起第二个 GET 请求
                    $response = $client->request('GET', $secondUrl, [
                        'headers' => $headers,
                    ]);

                    // 获取响应内容
                    $body = $response->getBody()->getContents();
                    $content = Json::decode($body);
                    if($content['errorCode']==0 && $content['result']['businessCode']==0){
                        $kjData = $content['result']['data'][0];
                        $qihao = $kjData['preDrawIssue'];
                        $opencode = $kjData['preDrawCode'];
                    }else{
                        throw_info($content['message']??'查询异常');
                    }

                    $kjData = ['expect'=>$qihao, 'opencode'=>$opencode, 'opentime'=>$kjData['preDrawTime']];
                    Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '开奖数据网盘抓取-正常', ['lottery_type'=>self::$lottery_type, LotteryType::getName($lottery_type), 'domain'=>$domain, 'kjData'=>$kjData]);
                }catch (\Exception $e){
                    Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', '开奖数据网盘获取-异常', ['lottery_type'=>self::$lottery_type,'name'=>LotteryType::getName($lottery_type), 'err_msg'=>$e->getMessage()]);
                }
            }else{
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', LotteryType::getName($lottery_type).'数据抓取-缓存', ['lottery_type'=>self::$lottery_type, LotteryType::getName($lottery_type), 'cq'=>$current_qihao, 'kjData'=>$kjData, 'is_auto'=>$is_auto]);
            }
        }catch (\Exception $e){
            $kjData = self::getCurrentKjData($lottery_type);
            Tool_Common::log('/kj_data/'.__FUNCTION__, 'ERR', LotteryType::getName($lottery_type).'数据抓取-异常', ['lottery_type'=>self::$lottery_type, 'cq'=>$current_qihao, 'currentQiHao'=>$currentQiHao, 'kjData'=>$kjData, 'err_msg'=>$e->getMessage()]);
        }
        if(empty($kjData)){
            return false;
        }
        return self::extracted($kjData, $lottery_type, $returnType, $is_auto);
    }
}
