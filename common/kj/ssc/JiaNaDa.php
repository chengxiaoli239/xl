<?php
# 开彩网
namespace common\kj\ssc;
use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\BingDao\BingDaoService;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\service\CommonService;
use common\tools\Tool_Common;
use  yii;

class JiaNaDa extends BaseKj {
    public static $lottery_type = 16;

    /**
     * @desc 加拿大
     * @param string $returnType
     * @return array
     */
    public static function getLottery($returnType = 'json', $is_auto = 1){

        if($is_auto==2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/jia-na-da/index');

            $url = $domain.'/index/isopen'; #当前开奖号码
            # 当前开奖链接：https://icelot.20191030pro.com/kaijiangWeb/?a=kaijiangWeb.drawResult&m=getActiveDrawInfo

            $headers = [
                ":authority: wap.dashen28.com",
                ":method: GET",
                ":path: /jianada28/",
                ":scheme: https",
                "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
                "accept-encoding: gunzip, deflate, br",
                "accept-language: zh-CN,zh;q=0.9,en;q=0.8",
                "cookie: __cfduid=d86e8efa5b9b45e5dcaac6c47e45ff7711609328410; Hm_lvt_1061ce639e989fce9bf43061a654b71f=1609328412; PHPSESSID=cmu3l3epl4j652cti41cl9cri0; Hm_lpvt_1061ce639e989fce9bf43061a654b71f=1609343672",
                "referer: https://wap.dashen28.com/beijing28/",
                "sec-fetch-dest: document",
                "sec-fetch-mode: navigate",
                "sec-fetch-site: same-origin",
                "sec-fetch-user: ?1",
                "upgrade-insecure-requests: 1",
                "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36",
            ];
            $rst = self::getCurl($url, $headers);
            //文本转码
            $rst = mb_convert_encoding($rst, 'utf-8','GB2312');
            p($rst);
            $data = $rst['data']['drawInfo']['historyDraw'];

            if (empty($data)) return false;
            $opencode = implode(',', $data['resultList']);
            if($opencode == '0,0,0,0,0') return false;
            //$kjData = ['expect'=>$data['preDrawIssue'], 'opencode'=>$opencode, 'opentime'=>$data['preDrawTime']];
            $kjData = ['expect'=>$data['draw_number'], 'opencode'=>$opencode, 'opentime'=>date('Y-m-d H:i:s')];
            //p($kjData);
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache(BingDaoService::$l_types[$l_type], $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('bingdao_kj', 'INFO', '号码抓取-冰岛', $logArr);

        return $rst;
    }

    /**
     * @desc 加拿大
     * @param string $returnType
     * @return array
     */
    public static function getLotteryCanada($returnType = 'json', $is_auto = 1){

        if($is_auto==2 OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/jia-na-da/index');

            $url = $domain.'/jianada28/lishi/1'; # wap
            //$url = $domain.'/jianada28/'; # wap
            $url = $domain.'https://www.dashen28.com/jianada28/lishi/1'; # wap
            # 当前开奖链接：https://wap.dashen28.com/jianada28/
            //$preg = '/<tr>(.*?)<td>SSC(.*?)&nbsp;&nbsp;&nbsp;&nbsp;<a class="cancelsn" style="cursor:pointer;color:blue;" snid=(.*?)>点击撤单<\/a><\/td>(.*?)<td>(.*?)<\/td>(.*?)<td title="(.*?)" style="cursor:pointer;">(.*?)<a href="\.\.\/user\/sninfo\.aspx\?id=(.*?)" target\=\_blank>详细内容<\/a>(.*?)<\/td>(.*?)<td>(.*?)<\/td>(.*?)<td>(.*?)<\/td>/ism'; // 这里是表达式，大神看看
            $preg = '/<tr>[\r\n](.*?)<td>(.*?)<\/td>(.*?)<td>(.*?)<\/td>(.*?)<td>(.*?)<span class="ball_lucky">(.*?)<\/span>(.*?)<\/td>(.*?)<\/tr>/ism';

            $content = self::getCurl($url);
            preg_match_all($preg, $content, $matches);
            foreach ($matches[0] as $match){
                $preg1 = '/<td>(.*?)</ism';
                preg_match_all($preg1, $match, $matches1);

                if(count($matches1[1]) != 3) return false;
                $data = $matches1[1];
                $data[2] = str_replace(' =', '', trim($data[2]));
                $opencode = str_replace([' + ', '=\r\n'], ',', $data[2]).',0,0';
                if($opencode == '0,0,0,0,0') return false;
                //$kjData = ['expect'=>$data['preDrawIssue'], 'opencode'=>$opencode, 'opentime'=>$data['preDrawTime']];
                $kjData = ['expect'=>$data[0], 'opencode'=>$opencode, 'opentime'=>'2021-'.$data[1]];
                break;
            }
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache(BingDaoService::$l_types[$l_type], $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('bingdao_kj', 'INFO', '号码抓取-冰岛', $logArr);

        return $rst;
    }



    /**
     * @decription post请求根据，接受传递的header头
     * @param $url
     */
    public static function postCurl($url,$post_data = [],$headers=[]){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 15;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["content-type:  application/x-www-form-urlencoded"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_HTTP_VERSION, 2);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $data = curl_exec($ch);
        $errno = curl_errno( $ch );

        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno, 'error'=>curl_error($ch)]; p($logArr);
        if($errno){
            if(isset($post_data['code']) && !empty($post_data['code']))$post_data['code'] = strlen($post_data['code'])>2000 ? substr($post_data['code'], 0, 200) : $post_data['code'];
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno];
            //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求', $logArr);
        }

        //if(strpos($url, 'ajax')){ p(['url'=>$url, 'header'=>$headers,'post_data'=>$post_data,'rstData'=>$data,,$errno]); }
        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if($data == 'ok'){
            return 'ok';
        }
        $rstData = json_decode($data, true); # data : {"Status":1,"Data":{"CompletedStatus":1,"LackStatus":0}}
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);
        if(strpos($data, "\"Status\":1") !== false && strpos($data, "\"CompletedStatus\":1") !== false){ # json解析异常处理
            $rstData['Status'] = 1;
        }

        if(strpos($data, '余额不足')){
            $rstData['Status'] = 0;
        }
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno];
        Tool_Common::log('postCurl','INFO','httpPost请求', $logArr);
        //p(['url'=>$url, 'rstData'=>$rstData, 'data'=>$data, 'post_data'=>$post_data, 'headers'=>$headers, 'errno'=>$errno]);

        return $rstData;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function getCurl($url,$header=[]){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        //$header = array_merge(self::$postHeaders,$header);
        //if(strpos($url, 'GetPeriodsQuery')){ p([$url, $header]); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);
        //if(true OR strpos($url, 'GetInfoByName') !== false){ p(['header'=>$header, 'url'=>$url, 'rst'=>$data]); }
        $errno = curl_errno($ch);
        if($errno>0) {
            $str = 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
            Tool_Common::log('getCurl', 'ERR', 'getCurl获取', ['url'=>$url, 'errno'=>$errno, 'postRst'=>$data, 'error'=>$str]);
            if($errno == 52){
                return ['Status'=>2, 'Data'=>'网盘网络超时，错误码52'];
            }
            return '';
        }

        return $data;
    }
}
