<?php
/**
 * @desc 验证码接口类
 */

namespace common\service;
use Yii;
use common\tools\Tool_Common;
use backend\service\CurlService;
class CaptchaCodeService{
    private static $pubilcParams = [];

    public static function juHe($file = ''){
        if(!$file) return false;
        header("Content-type:text/html;charset=utf-8");
        $ch = curl_init('http://op.juhe.cn/vercode/index');
        $cfile = curl_file_create($file, 'image/png', "pic.png");
        $start_time = microtime(true);
        $data = [
            'key' => \Yii::$app->params['JUHE_KEY'], // '4cf9ceff85b685abb8cb04abf9bb76cd', //请替换成您自己的key
            'codeType' => '6001', // 验证码类型代码，请在https://www.juhe.cn/docs/api/id/60/aid/352查询
            'image' => $cfile,
        ];
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';
        //$curl_errno = curl_errno($ch);
        //$curl_error = curl_error($ch);
        curl_close($ch);

        $rstData = json_decode($response, true);
        $logData = ['post_data'=>$data, 'rst'=>$response,'filename'=>$file, 'consume_time'=>$consume_time, 'rstData'=>$rstData];
        Tool_Common::log('/WORK/LOG/lottery/'.date('Ymd').'/getCaptchaCode','INFO','验证码接口-聚合', $logData);
        if($rstData['error_code'] != 0)
            return ['status'=>300, 'code'=>$rstData['reason']];

        return ['status'=>200, 'code'=>$rstData['result']];
    }

    /**
     * @desc 万维易源配置
     */
    public static function _initDataShowApi(){
        self::$pubilcParams = [
            'showapi_appid' => \Yii::$app->params['SHOW_API_APPID'],
        ];
    }

    /**
     * @desc 尖叫数据验证码接口
     * @param $file
     */
    public static function jianjiao($file){

        $url = "http://apigateway.jianjiaoshuju.com/api/v_1/yzm.html";
        $method = "POST";
        $appcode = "C84FC4551728E118E498237969E4E540";
        $appKey = "AKID6f98836396b4221a165c1c20e7440fd8";
        $appSecret = "6c8f04e47b3ebd6bd533d08354665556";
        $headers = [];
        $start_time = microtime(true);
        array_push($headers, "appcode:" . $appcode);
        array_push($headers, "appKey:" . $appKey);
        array_push($headers, "appSecret:" . $appSecret);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
        $querys = "";
        $v_type = 'n4';

        $fp = fopen($file, 'rb', 0);
        $v_pic = base64_encode(fread($fp,filesize($file)));
        $bodys = "v_pic=".$v_pic."&v_type=".$v_type;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$url, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        $data = curl_exec($curl);

        $rstData = json_decode($data, true);
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';
        $logData = ['file'=>$file, 'url'=>$url, 'consume_time'=>$consume_time, 'rstData'=>$rstData];
        Tool_Common::log('/WORK/LOG/lottery/'.date('Ymd').'/getCaptchaCode','INFO','验证码接口-尖叫数据', $logData);

        return ['status'=>200, 'code'=>$rstData['v_code']];
    }

    /**
     * @desc 万维易源API
     * @param string $filename
     */
    public static function showApi($file = ''){
        self::_initDataShowApi();
        header("Content-Type:text/html;charset=UTF-8");
        date_default_timezone_set("PRC");
        //$showapi_appid = 'xxxxxxxxxx';  //替换此值,在官网的"我的应用"中找到相关值
        $showapi_secret = \Yii::$app->params['SHOW_API_SIGN'];  //替换此值,在官网的"我的应用"中找到相关值

        $start_time = microtime(true);
        $fp = fopen($file, 'rb', 0);
        $img_base64 = base64_encode(fread($fp,filesize($file)));
        //$img_base64 = urlencode($b_base64) ;
        $paramArr = [
            //'showapi_appid'=> $showapi_appid,
            'img_base64' => $img_base64,
            'typeId' => "14",
            'convert_to_jpg' => "0",
            'needMorePrecise' => "0"
            //添加其他参数
        ];
        $paramArr = array_merge(self::$pubilcParams, $paramArr);

        $param = self::createShowApiParam($paramArr,$showapi_secret);
        $url = 'http://route.showapi.com/184-5?'.$param;

        $result = file_get_contents($url);
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';

        $rstData = json_decode($result, true);

        if($rstData['showapi_res_code'] != 0){
            $rst = ['status'=>300, 'msg'=>$rstData['showapi_res_error']];
        } else{
            $rst = ['status'=>200, 'code'=>$rstData['showapi_res_body']['Result']];
        }
        $logData = ['file'=>$file, 'url'=>$url, 'consume_time'=>$consume_time, 'rstData'=>$rstData];
        Tool_Common::log('/WORK/LOG/lottery/'.date('Ymd').'/getCaptchaCode','INFO','验证码接口-万维易源', $logData);

        return $rst;
    }

    /**
     * @desc 创建参数(包括签名的处理)
     * @param $paramArr
     * @param $showapi_secret
     * @return string
     */
    public static function createShowApiParam ($paramArr, $showapi_secret) {
        $paraStr = "";
        $signStr = "";
        ksort($paramArr);
        foreach ($paramArr as $key => $val) {
            if ($key != '' && $val != '') {
                $signStr .= $key.$val;
                $paraStr .= $key.'='.urlencode($val).'&';
            }
        }
        $signStr .= $showapi_secret;//排好序的参数加上secret,进行md5
        $sign = strtolower(md5($signStr));
        $paraStr .= 'showapi_sign='.$sign;//将md5后的值作为参数,便于服务器的效验
        //echo "排好序的参数:".$signStr."\r\n";
        return $paraStr;
    }

    /**
     * @desc 超级鹰接口文档：http://www.chaojiying.com/api-5.html
     * @param $user
     * @param $pass
     * @return mixed
     */
    public static function chaojiying($file, $codetype = '1902'){
        $url = 'http://upload.chaojiying.net/Upload/Processing.php' ;

        $fp = fopen($file, 'rb', 0);
        $v_pic = base64_encode(fread($fp,filesize($file)));
        $fields = [
            'user' => '15008080609' ,
            'pass2' => md5('0654321') ,
            'softid' => '899443' , # 软件KEY:d827ffe1c47080e5329a3bdb696514ca
            'codetype' => $codetype ,
            //'userfile'=>"@$userfile" ,  //注意,当PHP版本高于5.5后，此行可能无效要改为下一行
            //'userfile'=> new CURLFile(realpath($userfile)),
            'file_base64' =>$v_pic,
        ];
        $start_time = microtime(true);

        $ch = curl_init() ;
        curl_setopt($ch, CURLOPT_URL,$url) ;
        curl_setopt($ch, CURLOPT_POST,count($fields)) ;
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
        curl_setopt($ch, CURLOPT_REFERER,'') ;
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3') ;
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));  //加入这行是为了让 curl 一次发送POST包,防止发送包里出现 Expect:100-continue 造成CDN节点返回417错误
        $result = curl_exec($ch); # 实例{"err_no":0,"err_str":"OK","pic_id":"1662228516102","pic_str":"8vka","md5":"35d5c7f6f53223fbdc5b72783db0c2c0"}
        curl_close($ch) ;
        $rstData = json_decode($result, true);
        if($rstData['err_no'] != 0){
            $rst = ['status'=>300, 'msg'=>'识别错误'.$rstData['err_str']];
        } else{
            $rst = ['status'=>200, 'code'=>$rstData['pic_str']];
        }
        $end_time = microtime(true);
        $consume_time = ($end_time-$start_time).'s';
        $logData = ['file'=>$file, 'url'=>$url, 'consume_time'=>$consume_time, 'rstData'=>$rstData, 'code'=>$rstData['pic_str']];
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getCaptchaCode','INFO','验证码接口-超级鹰', $logData);

        return $rst ;
    }
}