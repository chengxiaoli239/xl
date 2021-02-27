<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace common\service\webot;
use backend\models\wx\WebotConfigs;
use common\general\helpers\Curl;
use  yii;
use common\tools\Util;

class BaseService
{
    public static $webotConfigs;
    public static function __init($uid = 1){
        $WebotConfigs = WebotConfigs::find()->where(['uid'=>$uid])->one();
        self::$webotConfigs = $WebotConfigs;
    }

    /**
     * @param $url
     * @param array $headers
     * @param array $data
     * @param int $timeout
     * @param bool $CA
     * @return bool|string
     */
    public static function sendCurlPost($url, $headers=[], $data = [], $timeout = 30, $CA = false) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data, 320),
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($curl);
        $rst = json_decode($response, 320);
        curl_close($curl);

        return $rst;
    }

    public static function postCurl($url, $headers=[], $post_data=[], $timeout=30){
        $curl = new Curl();
        $curl->setOptions([
            'CURLOPT_URL' => $url,
            'CURLOPT_HEADER' => 0,
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_TIMEOUT' => $timeout,
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_POST' => 1,
            'CURLOPT_POSTFIELDS' => $post_data,
            'CURLOPT_SSL_VERIFYPEER' => 0,
            'CURLOPT_SSL_VERIFYHOST' => 0,
        ]);

        $errno = $curl->getError();
        $rst = $curl->post($url, $post_data);

        return $rst;
    }

    /**
     * @desc 数据拆解 每组20个...
     * @param $codes ['8,9,9,9','9,8,9,9','9,9,8,9','9,9,9,8']
     * @param int $length
     * @return mixed
     */
    public static function splitDatas($datas, $length = 300){

        $rstDatas = array_chunk($datas, $length);

        return $rstDatas;
    }

}