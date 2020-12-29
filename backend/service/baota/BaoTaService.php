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
        p($rstLogin);
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
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers];
        //$data = CurlService::postCurl($url, $post_data, $headers);
        $data = BaseService::postCommonCurl($url, $post_data, $headers);
        if($data['rstData']['status'] == 1){
            $cookies = $data['cookie'][1];
            $request_token = explode(';', $cookies[0])[0];
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

    public static function getCronTabs($id=1){
        $BtSystemConfigs = BtSystemConfigs::findOne($id);

        $url = $BtSystemConfigs->domain.'/crontab?action=GetCrontab';
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: 0",
            //"Cookie: pro_end=-1; ltd_end=-1; serverType=nginx; order=id%20desc; memSize=3783; BT_PANEL_6=52dc13a9-8257-4dc1-8c5a-8ac0b99cb555.znL35WT_7FeNf-lFXjCRowS3_wk; request_token=Sq28QYqyyUFVoNh7PmkgcGofB3qA4Ex3oMiOUuO2mqgsKZ2v",
            $BtSystemConfigs->cookie,
            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain,
            "Referer: ".$BtSystemConfigs->domain."/crontab",

            $BtSystemConfigs->user_agent,
            "x-cookie-token: Sq28QYqyyUFVoNh7PmkgcGofB3qA4Ex3oMiOUuO2mqgsKZ2v",
            "x-http-token: cOw0ry65mWMNRos2s6wgG2Qxd761jIlkSSHg4lKXbGzJLhY0",
            "X-Requested-With: XMLHttpRequest",
        ];
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers];
        $data = CurlService::postCurl($url, [], $headers);
        p($data);

    }

}