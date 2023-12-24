<?php
# 开彩网
namespace common\kj\ssc;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\CurlService;
use backend\service\Lucky5\LuckyBaseService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use yii\helpers\Json;

class Thirdd extends BaseKj {
    public static $lottery_type = 26; # 福彩
    #public static $lottery_type = 27; # 排列三
    CONST SUCCESS_CODE = 20000;

    /**
     * @desc 福彩3D，官网：http://www.cwl.gov.cn/ygkj/wqkjgg/
     * @param string $returnType
     * @return array|bool
     */
    public function getFuCai3d(string $returnType = 'json', $is_auto = 1){
        try {
            $lottery_type = self::$lottery_type;
            $dateHI = date('H:i');
            $seconds = ('00:00'<$dateHI && $dateHI<'21:00') ? 600 : 300;
            if($is_auto==1){
                self::lockGrab($lottery_type, $seconds);
            }

            if($is_auto==2 OR !$kjData = Thirdd::getCurrentKjData($lottery_type)) {
                try {
                    $domain = BaseKj::getApiHostByRoute('/kj/thirdd/fu-cai');
                    // 设置请求头
                    $headers = [
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Accept-Language' => 'zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
                        'Connection' => 'keep-alive',
                        'Host' => 'www.cwl.gov.cn',
                        'Upgrade-Insecure-Requests' => 1,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/118.0',
                    ];

                    // 创建 CookieJar 来存储 cookie
                    $cookieJar = new CookieJar();
                    // 创建 Guzzle 客户端
                    $client = new Client(['cookies' => $cookieJar]);
                    // 第一个请求的 URL
                    $firstUrl = $domain.'/ygkj/wqkjgg/';

                    // 发起第一个 GET 请求
                    $response = $client->request('GET', $firstUrl, $headers);

                    // 获取响应头中的 Set-Cookie
                    $setCookie = $response->getHeader('Set-Cookie');

                    // 提取需要的 Cookie
                    $cookie = reset($setCookie); // 获取第一个 Set-Cookie

                    // 第二个请求的 URL
                    $secondUrl = $domain.'/cwl_admin/front/cwlkj/search/kjxx/findDrawNotice?name=3d&issueCount=&issueStart=&issueEnd=&dayStart=&dayEnd=&pageNo=1&pageSize=30&week=&systemType=PC';

                    // 设置请求头，包括 Cookie
                    $headers = [
                        'Accept' => 'application/json, text/javascript, */*; q=0.01',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
                        'Referer' => $firstUrl, // 使用第一个请求的 URL 作为 Referer
                        'Cookie' => $cookie, // 使用从第一个响应中提取的 Cookie
                    ];

                    // 发起第二个 GET 请求
                    $response = $client->request('GET', $secondUrl, [
                        'headers' => $headers,
                    ]);

                    // 获取响应内容
                    $body = $response->getBody()->getContents();
                    $content = Json::decode($body);
                    if($content['state']==0){
                        $kjData = $content['result'][0];
                        $qihao = $kjData['code'];
                        $opencode = $kjData['red'].',0,0';
                    }else{
                        throw_info($content['message']??'查询异常');
                    }

                    $kjData = ['expect'=>$qihao, 'opencode'=>$opencode, 'opentime'=>substr($kjData['date'], 0, 10).' 21:30:00'];
                    Tool_Common::log('/data/'.__FUNCTION__, 'INFO', '号码网盘抓取-幸运网1', ['domain'=>$domain, 'kjData'=>$kjData]);
                }catch (\Exception $e){
                    Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '网盘开奖数据获取异常', ['lottery_type'=>self::$lottery_type, 'err_msg'=>$e->getMessage()]);
                }
            }
        }catch (\Exception $e){
            Tool_Common::log('/datas/'.__FUNCTION__, 'ERR', '数据抓取异常', ['err_msg'=>$e->getMessage()]);
            $kjData = Thirdd::getCurrentKjData($lottery_type);
        }
        return self::extracted($kjData, $lottery_type, $returnType, $is_auto);
    }


}
