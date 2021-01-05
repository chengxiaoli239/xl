<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service\baota;

use backend\models\BtCrontabs;
use backend\models\BtSystemConfigs;
use backend\service\BaseService;
use backend\service\CurlService;
use backend\service\Juhua\JuHuaBaseService;
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
        if($rstLogin['status']==200){ # 登陆成功跳转首页
            $visitIndex = BaoTaService::visitHomePage($id);
            $getDataList = BaoTaService::getDataList($id);
            $getIndexList = BaoTaService::getIndexList($id);
            $getTaskList = BaoTaService::getTaskList($id);
        }
        //$rst = BaoTaService::getXHttpToken($id);

        return ['BT_PANEL_6'=>$BT_PANEL_6, 'rstLogin'=>$rstLogin, 'rst'=>$rst, 'visitIndex'=>$visitIndex, 'getDataList'=>$getDataList, 'getIndexList'=>$getIndexList, 'getTaskList'=>$getTaskList];
    }

    /**
     * @desc 获取session id
     * @param $id
     * @return mixed
     */
    public static function getBtPanel($id){
        $b = BtSystemConfigs::findOne($id);
        $url = $b->domain;
        if($b->suffix){
            $url = $url.'/'.$b->suffix.'/';
        }
        $headers = [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Cache-Control: max-age=0",
            "Connection: keep-alive",
            "Host: ".str_replace('http://', '', $b->domain),
            "Upgrade-Insecure-Requests: 1",
            $b->user_agent,
        ];
        $data = CurlService::curl_get_cookie($url, $headers);

        return $data;
    }

    /**
     * @desc 远程登陆宝塔
     * @param int $id
     * @param string $session_id
     * @param string $suffix
     * @return array
     */
    public static function remoteLogin($id=1, $session_id=''){
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
            "Accept-Encoding: gunzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: ".strlen($post_data),
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Cookie: ".$session_id,
            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain,
            "Referer: ".$BtSystemConfigs->domain."/".$BtSystemConfigs->suffix."/",
            $BtSystemConfigs->user_agent,
            "X-Requested-With: XMLHttpRequest",
        ];
        $m = \Yii::$app->cache;
        $requestTokenKey = BaoTaService::buildXCookieTokenKey();

        //$data = CurlService::postCurl($url, $post_data, $headers);
        $data = BaseService::postCommonCurl($url, $post_data, $headers);
        $logArr = ['url'=>$url, 'post_data'=>$post_data, 'headers'=>$headers, 'data'=>$data];
        Tool_Common::log('/baota/remoteLogin', 'INFO', '远程登陆宝塔', $logArr);
        if($data['rstData']['status'] == 1){
            $cookies = $data['cookie'][1];
            $request_token = explode(';', trim($cookies[0]))[0];
            $m->set($requestTokenKey, trim(str_replace('request_token=', '', $request_token)), 86400);
            $BT_PANEL_6 = explode(';', trim($cookies[1]))[0];
            //p(['request_token'=>$request_token, 'BT_PANEL_6'=>$BT_PANEL_6]);
            $BtSystemConfigs->cookie = trim($BT_PANEL_6.';'.$request_token);
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

        $url = $BtSystemConfigs->domain.'/crontab?action=GetCrontab';
        $curl = curl_init();
        $headers = [
            'Accept:  */*',
            'Accept-Encoding:  gzip, deflate',
            'Accept-Language:  zh-CN,zh;q=0.9,en;q=0.8',
            'Connection:  keep-alive',
            'Content-Length:  0',
            'Cookie: '.$BtSystemConfigs->cookie,

            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain,
            "Referer: ".$BtSystemConfigs->domain."/crontab",

            'User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36',
            "x-cookie-token: ".BaoTaService::getXCookieToken(),
            "x-http-token: ".BaoTaService::getXHttpTokenVal(),
            'X-Requested-With:  XMLHttpRequest'
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $headers
        ]);

        $data = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($data, true);

        $logArr = ['url'=>$url, 'headers'=>$headers, 'data'=>$data];
        return $data;
    }

    /**
     * @desc 获取计划任务
     * @param int $id
     */
    public static function getCronTabsBak($id=1){
        $BtSystemConfigs = BtSystemConfigs::findOne($id);

        $url = $BtSystemConfigs->domain.'/crontab?action=GetCrontab';
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: 0",
            'pro_end=-1; ltd_end=-1; serverType=nginx; order=id%20desc; memSize=3783; '.$BtSystemConfigs->cookie,
            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain,
            "Referer: ".$BtSystemConfigs->domain."/crontab",

            $BtSystemConfigs->user_agent,
            "x-cookie-token: ".BaoTaService::getXCookieToken(),
            "x-http-token: ".BaoTaService::getXHttpTokenVal(),
            "X-Requested-With: XMLHttpRequest",
        ];
        //$data = CurlService::postCurl($url, '', $headers);
        $data = JuHuaBaseService::postBetCurl($url,[], $headers);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'data'=>$data];
        p($logArr);
        return $data;
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

    /**
     * @desc - x-http-token
     * @param $id
     */
    public static function visitHomePage($id){
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
            $BtSystemConfigs->user_agent,
            "Upgrade-Insecure-Requests: 1",
        ];
        $html = CurlService::getCurl($url, $headers);
        preg_match('/<a style="display:none;" id="request_token_head" token="(.*?)"><\/a>/ism', $html, $matches);

        $m = \Yii::$app->cache;
        $key = self::buildXHttpTokenKey();
        $xHttpToken = $matches[1];
        $m->set($key, trim($xHttpToken), 86400);

        $logArr = ['url'=>$url, 'headers'=>$headers, 'matches'=>$matches, 'xHttpToken'=>$xHttpToken];
        //p($logArr);

        return $xHttpToken;
    }

    /**
     * @desc - 访问任务列表
     * @param $id
     */
    public static function visitCrontabPage($id){
        $BtSystemConfigs = BtSystemConfigs::findOne($id);
        $url = $BtSystemConfigs->domain.'/crontab';//.'/'.$b->suffix;
        $headers = [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Accept-Encoding: gunzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Cookie: ".$BtSystemConfigs->cookie,
            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Referer: ".$url.'/crontab',
            $BtSystemConfigs->user_agent,
            "Upgrade-Insecure-Requests: 1",
        ];
        $html = CurlService::getCurl($url, $headers);
        preg_match('/<a style="display:none;" id="request_token_head" token="(.*?)"><\/a>/ism', $html, $matches);

        $m = \Yii::$app->cache;
        $requestTokenKey = BaoTaService::buildXHttpTokenKey();
        $xHttpToken = $matches[1];
        $m->set($requestTokenKey, $xHttpToken,86400);

        $logArr = ['url'=>$url, 'headers'=>$headers, 'matches'=>$matches, 'xHttpToken'=>$xHttpToken];

        return $xHttpToken;
    }

    /**
     * @desc 获取任务列表
     * @param string $id
     */
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
            "Cookie: pro_end=-1; ltd_end=-1; serverType=nginx; order=id%20desc; memSize=3783; ".$BtSystemConfigs->cookie,
            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain.'/'.$BtSystemConfigs->suffix,
            "Referer: ".$BtSystemConfigs->domain."/crontab",
            $BtSystemConfigs->user_agent,
            "x-cookie-token: ".BaoTaService::getXCookieToken(),
            "x-http-token: ".BaoTaService::getXHttpTokenVal(),
            "X-Requested-With: XMLHttpRequest",
        ];
        $data = CurlService::postCurl($url, http_build_query(['status'=>-3]), $headers);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'data'=>$data];
        return $data;
    }

    /**
     * @desc 获取数据列表
     * @param string $id
     */
    public static function getIndexList($id=''){
        $BtSystemConfigs = BtSystemConfigs::findOne($id);
        $url = $BtSystemConfigs->domain.'/plugin?action=get_index_list';
        $post_data = http_build_query(['status'=>-3]);
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gunzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: ".strlen($post_data),
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Cookie: pro_end=-1; ltd_end=-1; serverType=nginx; order=id%20desc; memSize=3783; ".$BtSystemConfigs->cookie,

            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain,
            "Referer: ".$BtSystemConfigs->domain.'/',
            $BtSystemConfigs->user_agent,
            "x-cookie-token: ".BaoTaService::getXCookieToken(),
            "x-http-token: ".BaoTaService::getXHttpTokenVal(),
            "X-Requested-With: XMLHttpRequest",
        ];
        $data = CurlService::postCurl($url, $post_data, $headers);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'data'=>$data];
        return $data;
    }

    /**
     * @desc 获取数据列表
     * @param string $id
     */
    public static function getDataList($id=''){
        $BtSystemConfigs = BtSystemConfigs::findOne($id);
        $url = $BtSystemConfigs->domain.'/crontab?action=GetDataList';
        $post_data = http_build_query(['type'=>'sites']);
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: ".strlen($post_data),
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Cookie: pro_end=-1; ltd_end=-1; serverType=nginx; order=id%20desc; memSize=3783; ".$BtSystemConfigs->cookie,

            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain,
            "Referer: ".$BtSystemConfigs->domain."/crontab",
            $BtSystemConfigs->user_agent,
            "x-cookie-token: ".BaoTaService::getXCookieToken(),
            "x-http-token: ".BaoTaService::getXHttpTokenVal(),
            "X-Requested-With: XMLHttpRequest",
        ];
        $data = CurlService::postCurl($url, $post_data, $headers);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'data'=>$data];
        p($logArr,0);
        return $data;
    }

    public static function buildXCookieTokenKey(){
        return 'x_cookie_token_key';
    }

    public static function buildXHttpTokenKey(){
        return 'x_http_token_key';
    }

    /**
     * @desc 获取 requset token head
     * @return mixed
     */
    public static function getXHttpTokenVal(){
        $m = \Yii::$app->cache;
        $mkey = BaoTaService::buildXHttpTokenKey();
        $val = $m->get($mkey);

        return $val;
    }

    /**
     * @desc 获取 requset token
     * @return mixed
     */
    public static function getXCookieToken(){
        $m = \Yii::$app->cache;
        $mkey = BaoTaService::buildXCookieTokenKey();
        $val = $m->get($mkey);

        return $val;
    }

    /**
     * @desc 同步宝塔计划任务
     * @param int $id
     * @return array|bool|string
     */
    public static function syncBaoTaCrontabs($id=1){
        $rst = BaoTaService::btLogin($id); # 登陆
        $crontabs = BaoTaService::getCronTabs($id);
        if(empty($crontabs)) return [];
        $crontabs = array_reverse($crontabs);

        foreach ($crontabs as $crontab){
            if(empty($crontab)) continue;
            $setDatas = $crontab;
            $setDatas['type_desc'] = $crontab['type'];
            $setDatas['p_id'] = $crontab['id'];
            $M = BtCrontabs::findOne(['p_id'=>$crontab['id']]);
            $now_time = time();
            if(empty($M)){
                $M = new BtCrontabs();
                $setDatas = array_merge($setDatas, [
                    'created_at' => $now_time
                ]);
            }
            $userInfo = BaoTaService::getUserInfoByCrontabName($setDatas['name']);
            if(!empty($userInfo)){
                $setDatas['uid'] = $userInfo['uid'];
                $setDatas['cron_type'] = 1;
            }

            $setDatas = array_merge($setDatas, [
                'updated_at' => $now_time
            ]);
            //$setDatas['name'] = urlencode($setDatas['name']);

            $M->setAttributes($setDatas);
            if(!$M->save()){
                continue;
                //return ['status'=>300, 'msg'=>current($M->getErrors())];
            }
        }

        return $crontabs;
    }

    /**
     * @desc 根据宝塔的计划任务截取，获取用户id
     * @param $name
     * @return array
     */
    public static function getUserInfoByCrontabName($name){
        $info = [];
        $preg = '/用户【id:(.*?)-(.*?)】- (.*?) - 投注计划/ism';
        preg_match_all($preg, $name, $matches);

        if($matches[1][0]){
            $info = ['uid'=>$matches[1][0], 'username'=>$matches[2][0], 'weixin_name'=>$matches[3][0]];
        }

        return $info;
    }

    /**
     * @desc 宝塔状态同步更新
     * @param $id - 本地用户表的id
     */
    public static function updateCrontabStatus($id){
        $BtCrontabs = BtCrontabs::findOne($id);
        $bt_cron_id = $BtCrontabs->p_id;

        $BtSystemConfigs = BtSystemConfigs::findOne($id=1);
        $url = $BtSystemConfigs->domain.'/crontab?action=set_cron_status';
        $post_data = http_build_query(['id'=>$bt_cron_id]);
        $headers = [
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Content-Length: ".strlen($post_data),
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Cookie: pro_end=-1; ltd_end=-1; serverType=nginx; order=id%20desc; memSize=3783; ".$BtSystemConfigs->cookie,

            "Host: ".str_replace('http://', '', $BtSystemConfigs->domain),
            "Origin: ".$BtSystemConfigs->domain,
            "Referer: ".$BtSystemConfigs->domain."/crontab",
            $BtSystemConfigs->user_agent,
            "x-cookie-token: ".BaoTaService::getXCookieToken(),
            "x-http-token: ".BaoTaService::getXHttpTokenVal(),
            "X-Requested-With: XMLHttpRequest",
        ];
        $data = CurlService::postCurl($url, $post_data, $headers);
        if(!$data['status']){
            return ['status'=>300, 'msg'=>$data['msg']];
        }

        return ['status'=>200, 'msg'=>$data['msg']];
    }

}