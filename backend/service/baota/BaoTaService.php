<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service\baota;

use backend\models\BtSystemConfigs;
use backend\service\BaseService;
use backend\service\CurlService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class BaoTaService extends BaseService { #

    /**
     * @desc 宝塔登陆
     * @return array|bool|void
     */
    public static function btLogin($id=1){
        $BT_PANEL_6 = BaoTaService::getBtPanel($id);
        $rstLogin = BaoTaService::remoteLogin($id, $BT_PANEL_6);
        $rst = BaoTaService::getXHttpToken($id);

        return ['BT_PANEL_6'=>$BT_PANEL_6, 'rstLogin'=>$rstLogin, 'rst'=>$rst];
    }

    /**
     * @desc 获取session id
     * @param $id
     * @return mixed
     */
    public static function getBtPanel($id){
        $b = BtSystemConfigs::findOne($id);
        //$url = $b->domain.'/public?name=app&fun=login_qrcode';
        $url = $b->domain.'/ccbb48f1/';
        $headers = [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Cache-Control: max-age=0",
            "Connection: keep-alive",
            "Host: 39.109.117.27:8888",
            "Upgrade-Insecure-Requests: 1",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36            ",
        ];
        $data = CurlService::curl_get_cookie($url, $headers);

        return $data;
    }

    public static function remoteLogin($id=1, $session_id='', $suffix='ccbb48f1'){
        if(empty($session_id)) return ['status'=>400, 'msg'=>'BT_PANEL_6不能为空'];
        $BtSystemConfigs = BtSystemConfigs::findOne($id);

        $url = $BtSystemConfigs->domain.'/login';
        $post_data = [
            'username' => $BtSystemConfigs->account,
            'password' => md5(md5($BtSystemConfigs->password).'_bt.cn'),
        ];
        $post_data = http_build_query($post_data);
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: ".strlen($post_data),
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Cookie: ".$session_id,
            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain,
            "Referer: ".$BtSystemConfigs->domain."/".$suffix."/",
            $BtSystemConfigs->user_agent,
            "X-Requested-With: XMLHttpRequest",
        ];
        $m = \Yii::$app->cache;
        $requestTokenKey = BaoTaService::buildRequestTokenKey();

        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers];
        //$data = CurlService::postCurl($url, $post_data, $headers);
        $data = BaseService::postCommonCurl($url, $post_data, $headers);
        if($data['rstData']['status'] == 1){
            $cookies = $data['cookie'][1];
            $request_token = explode(';', $cookies[0])[0];
            $m->set($requestTokenKey, str_replace('request_token=', '', $request_token), 86400);
            $BT_PANEL_6 = explode(';', $cookies[1])[0];
            $BtSystemConfigs->cookie = trim($request_token.';'.$BT_PANEL_6);
            $r = $BtSystemConfigs->save();
            if(!$r){
                return ['status'=>300, 'msg'=>$BtSystemConfigs->getFirstErrors()];
            }
        }else{
            return ['status'=>300, 'msg'=>$data['msg']];
        }

        return ['status'=>200, 'msg'=>'登陆成功'];
    }

    /**
     * @desc 获取计划任务
     * @param int $id
     */
    public static function getCronTabs($id=1){
        $BtSystemConfigs = BtSystemConfigs::findOne($id);
        $requestToken = self::getRequestToken();
        $xHttpToken = self::getXHttpTokenVal();

        $url = $BtSystemConfigs->domain.'/crontab?action=GetCrontab';
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: 0",
            $BtSystemConfigs->cookie.'; pro_end=-1; ltd_end=-1; serverType=nginx; order=id%20desc; memSize=3783',
            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain.'/'.$BtSystemConfigs->suffix,
            "Referer: ".$BtSystemConfigs->domain."/crontab",

            $BtSystemConfigs->user_agent,
            "x-cookie-token:".$requestToken,
            "x-http-token: ".$xHttpToken,
            "X-Requested-With: XMLHttpRequest",
        ];
        $data = CurlService::postCurl($url, '', $headers);
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'data'=>$data];
        p($logArr);

    }

    /**
     * @desc - x-http-token
     * @param $id
     */
    public static function getXHttpToken($id){
        $BtSystemConfigs = BtSystemConfigs::findOne($id);
        $url = $BtSystemConfigs->domain;//.'/'.$b->suffix;
        $headers = [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Accept-Encoding: gunzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Cookie: ".$BtSystemConfigs->cookie,
            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Referer: ".$url.'/'.$BtSystemConfigs->suffix,
            "Upgrade-Insecure-Requests: 1",
            $BtSystemConfigs->user_agent,
        ];
        $html = CurlService::getCurl($url, $headers);
        preg_match('/<a style="display:none;" id="request_token_head" token="(.*?)"><\/a>/ism', $html, $matches);

        $m = \Yii::$app->cache;
        $key = self::buildXHttpTokenKey();
        $xHttpToken = $matches[1];
        $m->set($key, trim($xHttpToken), 86400);

        $logArr = ['url'=>$url, 'headers'=>$headers, $matches];

        return $xHttpToken;
    }

    public static function getTaskList($id=''){
        $BtSystemConfigs = BtSystemConfigs::findOne($id);
        $url = $BtSystemConfigs->domain.'/task?action=get_task_lists';
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: 9",
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Cookie: pro_end=-1; ltd_end=-1; serverType=nginx; order=id%20desc; memSize=3783; BT_PANEL_6=b6026ed3-8e00-4935-9577-82c770787b03.6ZupPopbvl8E_L_A6-RF9cgBUYE; request_token=zWm49Y46yoCvO51mQouKMe8amxtHQPUli03j5nRHxbpNfIXX",
            "Host: 39.109.117.27:8888",
            "Origin: http://39.109.117.27:8888",
            "Referer: http://39.109.117.27:8888/crontab",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36",
            "x-cookie-token: ".BaoTaService::getRequestToken(),
            "x-http-token: ".BaoTaService::getXHttpTokenVal(),
            "X-Requested-With: XMLHttpRequest",
        ];
        $data = CurlService::postCurl($url, http_build_query(['status'=>-3]), $headers);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'data'=>$data];
        p($logArr);

        p($data);
    }

    public static function buildXHttpTokenKey(){
        return 'request_token_head';
    }

    public static function buildRequestTokenKey(){
        return 'request_token_key';
    }

    /**
     * @desc 获取 requset token head
     * @return mixed
     */
    public static function getRequestToken(){
        $m = \Yii::$app->cache;
        $mkey = BaoTaService::buildRequestTokenKey();
        $val = $m->get($mkey);

        return $val;
    }

    /**
     * @desc 获取 requset token
     * @return mixed
     */
    public static function getXHttpTokenVal(){
        $m = \Yii::$app->cache;
        $mkey = BaoTaService::buildXHttpTokenKey();
        $val = $m->get($mkey);

        return $val;
    }

}