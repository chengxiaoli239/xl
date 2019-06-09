<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */
namespace backend\service;

use backend\models\SystemConfig;
use common\service\CommonService;
use common\tools\Tool_Common;
use  yii;

class CurlService extends BaseService{

    public static $postHeaders = [
        //'User-Agent: Mozilla/5.0 (Linux; Android 8.0; Pixel 2 Build/OPD3.170816.012) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Mobile Safari/537.36',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36',
        //'Accept-Language:zh-CN,zh;q=0.9,en;q=0.8',
        'Accept-Language:zh-CN,zh;q=0.9',
        'Connection:keep-alive',
        'Accept-Encoding: gunzip, deflate',
        'X-Requested-With: XMLHttpRequest',
    ];

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function httpPost($url,$post_data = [],$header=[]){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 15;
        #$cookiefile = Yii::getAlias('@common')."/lib/0898.com/cookie_file.txt";
        $header = array_merge(self::$postHeaders,$header);
        //if(strpos($url, 'index.aspx')){ p([$url,$post_data, $header],0); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        //设置post方式提交
        curl_setopt($ch, CURLOPT_POST, 1);

        # cookie
        #curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
        #curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
        # cookie

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($ch);
        $errno = curl_errno( $ch );
        if($errno && strstr($url, 'BatchBet') OR strstr($url, 'MultipleBet')){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$header, 'rst'=>$data, 'errno'=>$errno];
            //p($logArr);
            Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/httpPostError','INFO','httpPost请求', $logArr);
        }

        //if(strpos($url, 'BatchBet')){ p(['url'=>$url, 'header'=>$header,'post_data'=>$post_data,'rstData'=>$data,curl_close($ch),$errno]); }
        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if($data == 'ok'){
            return 'ok';
        }
        $rstData = json_decode($data, TRUE);
        //p([$data, $rstData, $post_data, $header]);

        return $rstData;
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function httpGet($url,$header=[],$timeout = 30){
        $header = array_merge(self::$postHeaders,$header);
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
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);
        //if(strpos($url, 'AcceptAgreement') !== false){ p([$header, $url, $data]); }
        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if(!self::is_json($data)){
            return $data;
        }

        return json_decode($data, true);
    }

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    public static function httpGetSSL($url,$header=[],$timeout = 30){
        #$cookiefile = Yii::getAlias('@common')."/lib/0898.com/cookie_file.txt";
        $header = array_merge(self::$postHeaders,$header);
        //p([$url, $header]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //curl_setopt($ch, CURLOPT_SSLVERSION, 3);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER,0);

        $data = curl_exec($ch);
        if(curl_close($ch)) {
            echo 'Curl error: ' . curl_error($ch) . "&lt;br&gt;\n\r";
        }
        if(!self::is_json($data)){
            return $data;
        }

        return json_decode($data, true);
    }


    /**
     * @description
     * @param $string
     * @return bool
     */
    public static function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

     /**
     * @decription 获取 Response Headers
     * @param $url
     **/
    public static function httpGetResponseHeaders($url){
        //$url = 'https://'.Yii::$app->params['domain'].'/code2.aspx';
        $oCurl = curl_init();
        // 设置请求头, 有时候需要,有时候不用,看请求网址是否有对应的要求
        $header[] = "Content-type: application/x-www-form-urlencoded";
        $user_agent = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.1750.146 Safari/537.36";

        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER,$header);

        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);

        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
        curl_setopt($oCurl, CURLOPT_HEADER, true);

        // 是否不需要响应的正文,为了节省带宽及时间,在只需要响应头的情况下可以不要正文
        //curl_setopt($oCurl, CURLOPT_NOBODY, true);
        // 使用上面定义的 ua
        curl_setopt($oCurl, CURLOPT_USERAGENT,$user_agent);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        // 不用 POST 方式请求, 意思就是通过 GET 请求
        curl_setopt($oCurl, CURLOPT_POST, false);

        $sContent = curl_exec($oCurl);
        // 获得响应结果里的：头大小
        $headerSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        // 根据头大小去获取头信息内容
        $header = substr($sContent, 0, $headerSize);

        curl_close($oCurl);

        return $header;
    }

    /**
     *curl get请求
     */
    public static function curl_get_cookie($url,$header = []){
        $header = array_merge(self::$postHeaders,$header);
        if(strpos($url, 'code2')){
            //p([$url,$header]);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);//登陆后要从哪个页面获取信息
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, 3);

        $content = curl_exec($curl);
        preg_match("/set\-cookie:([^\r\n]*)/i", $content, $matches);
        //p(['content'=>$content, 'errno'=>curl_error($curl)]);
        $cookie = $matches[1];
        $logArr = ['content'=>$content, 'cookie'=>$cookie];
        if(curl_error($curl)>0){
            $logArr = array_merge($logArr,[ 'errno'=>curl_error($curl), 'error'=>curl_error($curl)]);
            Tool_Common::log('curl_get_cookie', 'INFO', '获取cookie', $logArr);
        }
        //$cookie = str_replace(' ASP.NET_SessionId=','',$cookie);
        //$cookie = str_replace('; path=/; HttpOnly','',$cookie);

        return $cookie;
    }
}