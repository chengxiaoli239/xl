<?php
/**
 * 通用处理类
 * Enter description here ...
 * @author sam
 * @authors wudean(bj) 乔迁标识
 *
 */
namespace common\tools;
use yii;
use yii\helpers\BaseUrl;
class Thumb extends  BaseUrl{
    /**
     * @param $img
     * @param int $h
     * @param int $w
     * @return mixed|string
     */
    public static function toThumb( $img, $h = 400, $w = 400 ){
        $goods_img = strpos($img, 'http') === false ? 'https://img.mianshui365.com' . $img : $img;
        $goods_img = strpos($img, 'http:') === true ? str_replace('http','https',$goods_img) : $goods_img;

        $goods_img = str_replace("cdn.mianshui365.com", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn2.mianshui365.com", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn2.mianshui365.net", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn.mianshui365.net", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("images.mianshui365.com", "img.mianshui365.com", $goods_img);

        if(strpos($goods_img, "@") === false){
            $goods_img .= "@".$h."h_".$w."w_95q_1wh";
        }
        return $goods_img;
    }

    /**
     * @function 链接生成
     * @param array $params
     * @return string
     */
    public static function toUrl($params = []){
        //p($params);
        if(strstr($_SERVER['HTTP_HOST'],'mitem')){
            $url = Yii::$app->params['M_ITEM_DOMAIN'];
        }else{
            $url = Yii::$app->params['ITEM_DOMAIN'];
        }

        $_c = $params['_c'] ? $params['_c'] : 'goods';
        $_a = $params['_a'] ? $params['_a'] : 'index';
        unset($params['_c']);
        unset($params['_a']);
        //$http_url = $url.'/'.http_build_query($params);
        $http_url = $url.'/index.php?r='.$_c.'/'.$_a.'&'.http_build_query($params);
        if($params['barcode'] OR $params['code']){
            $str = max($params['code'] , $params['barcode']);
            unset($params['code'] , $params['barcode']);
            $http_url = trim($url.'/goods__c'.$str.'.html&'.http_build_query($params),'&');
        }elseif($params['id']){
            $id = $params['id'];
            unset($params['id']);
            $http_url = trim($url.'/goods__id'.$id.'.html&'.http_build_query($params),'&');
        }
        return $http_url;
    }

    public static function  widget($params){
        if($params['key'] === '') return "Error: 没有KEY";
        $m = \Yii::$app->cache;
        $key = $params['key'];
        $html = $params['html'];
        $mkey = "Widget.raw.{$key}";

        if(SiteCacheLevel && !SiteCacheForceRefresh){
            //优先尝试从memcache取出
            $re = $m->get($mkey);
            //if($re) return $re;
        }

        $re = \Yii::$app->db->createCommand("select * from widget where `key`='$key' and del_flag=0")->queryOne();
        if(!$html)$data = json_decode($re['data'],true);
        else $data = $re['data'];
        $m->set($mkey, $data, SiteCacheTime ? SiteCacheTime : 3600 * 4);
        echo $data;
    }
}
