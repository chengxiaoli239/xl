<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use  yii;

class RemoteHtmlService extends BaseService{

    /**
     * @decription 获取远程html内容
     * @param $url
     */
    static public function getRemoteHtmlContent($url, $header = []){
        //p([$url, $header]);
        //$html_data = file_get_contents($url, false);
        $html_data = CurlService::httpGet($url,$header);

        return $html_data;
    }



}