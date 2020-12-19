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

class TaiWanHuanLe extends BaseKj {
    public static $lottery_type = 15;

    /**
     * @desc 台湾欢乐 https://twhlsx168.com/#/index
     * @param string $returnType
     * @return array
     */
    public static function getLotteryHl($returnType = 'json', $is_auto = 1){

        if(!$is_auto OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/tai-wan-huan-le/index');
            $url = $domain.'/kaijiangWeb/?a=kaijiangWeb.drawResult&m=getActiveDrawInfo'; #当前开奖号码
            # 当前开奖链接：http://f9.ww99865.xyz:5678/Member/GetMemberPrint?_=1570547160015

            $content = BingDaoService::postCurl($url, http_build_query(['lotteryType'=>BingDaoService::$ll_types[self::$lottery_type]]));
            $data = $content;

            if (!isset($data['code']) OR $data['code'] != 200) {
                Tool_Common::log('/kj_data/getLotteryTwHl', 'ERR', '台湾欢乐号码抓取异常', ['url'=>$url, 'content'=>$content]);
            }
            $row = $data['data']['drawInfo']['historyDraw']['resultList'];
            $qihao = $data['data']['drawInfo']['historyDraw']['draw_number'];
            $opencode = implode(',', $row);
            if($opencode == '0,0,0,0,0') return false;
            $kjData = ['expect'=>$qihao , 'opencode'=>$opencode, 'opentime'=>date('Y-m-d H:i:s')];
        }
        if(empty($kjData['opencode'])) return false;
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('luck5', 'INFO', '号码抓取-冰岛相关网', $logArr);

        return $rst;
    }

    /**
     * @desc 冰岛90 - 官网 https://icelot.20191030pro.com/drawResult.html#/detail/6
     * @param string $returnType
     * @param integer $l_type 6:90s 7:3m 8:5m 9:10m
     * @return array
     */
    public static function getLotteryOne($returnType = 'json', $l_type = 6){
        $r_s = [
            6 => '',
            7 => '3m',
            8 => '5m',
            9 => '10m',
            10 => '5m-tw', # 台湾宾果
        ];
        if(!$kjData = self::getCurrentKjData(BingDaoService::$l_types[$l_type])) {
            $domain = BaseKj::getApiHostByRoute('/kj/bing-dao/index'.$r_s[$l_type]);

            $url = $domain.'/kaijiangWeb/?a=kaijiangWeb.drawResult&m=getActiveDrawInfo'; #当前开奖号码
            # 当前开奖链接：https://icelot.20191030pro.com/kaijiangWeb/?a=kaijiangWeb.drawResult&m=getActiveDrawInfo

            $headers = [
                ":authority: icelot.20191030pro.com",
                ":method: POST",
                ":path: /kaijiangWeb/?a=kaijiangWeb.drawResult&m=getActiveDrawInfo",
                ":scheme: https",
                "accept: application/json, text/plain, */*",
                "accept-encoding: gzip, deflate, br",
                "accept-language: zh-CN,zh;q=0.9,en;q=0.8",
                "content-length: 13",
                "content-type: application/x-www-form-urlencoded",
                "origin: https://icelot.20191030pro.com",
                "referer: https://icelot.20191030pro.com/drawResult.html",
                "sec-fetch-dest: empty",
                "sec-fetch-mode: cors",
                "sec-fetch-site: same-origin",
                "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
            ];
            $post_data = ['lotteryType'=>$l_type];
            $rst = self::postCurl($url, http_build_query($post_data), $headers);
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
}
