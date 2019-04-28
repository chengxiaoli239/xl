<?php
# 开彩网
namespace common\kj\qxc;
use backend\service\CurlService;
use  yii;

class QxcTcw{

    public static function getLotteryNo($returnType = 'json'){

        //$url='http://wd.apiplus.net/tef05c6c66079ff29k/cqssc-3.json';
        $url='http://www.lottery.gov.cn/historykj/history.jspx?_ltype=qxc';
        //$content = file_get_contents($url);
        $content = CurlService::httpGet($url);
        $preg = '/<td width="40" height="23" align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)" class="red">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<\/tr>/is'; // 这里是表达式，大神看看
        preg_match_all($preg,$content,$matches);
        $qihao = $matches[2][0];
        $kjdata = $matches[5][0];
        if(!$kjdata) return false;
        $date = $matches[53][0];
        $kjData = ['expect'=>'20'.$qihao, 'opencode'=>"$kjdata[0],$kjdata[1],$kjdata[2],$kjdata[3],$kjdata[4],$kjdata[5],$kjdata[6]", 'date'=>$date];

        $expect = $kjData['expect'];
        $opencode = $kjData['opencode'];
        $opentime = strtotime($kjData['date']);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            return ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
    }

    /**
     * @desc 批量获取qxc开奖号码
     * @param $type
     * @return array
     */
    public static function getBatchLotteryNo($type = json){
        $m = \Yii::$app->cache;
        $mkey = 'BATCH_TCW_PAGE_V4';
        if($page = $m->get($mkey)){
            $page = $page - 1;
            $page = $page != 110 ? $page : 109;
        }else{
            $page = 112;
        }
        if($page<0) return false;

        $url = 'http://www.lottery.gov.cn/historykj/history_'.$page.'.jspx?_ltype=qxc';
        $content = CurlService::httpGet($url);
        $preg = '/<td width="40" height="23" align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)" class="red">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<td align="center" bgcolor="(.*?)">(.*?)<\/td>(.*?)<\/tr>/is'; // 这里是表达式，大神看看
        preg_match_all($preg,$content,$matches);
        //p($content);
        $len = count($matches[0]);
        $kjDatas = [];
        for ($n=$len; $n>0; $n-- ){
            $key = $n - 1;
            $qihao = $matches[2][$key];
            $kjdata = $matches[5][$key];
            $date = $matches[53][$key];
            $kjDatas['opencode'][] = ['qihao'=>'20'.$qihao, 'codes'=>"$kjdata[0],$kjdata[1],$kjdata[2],$kjdata[3],$kjdata[4],$kjdata[5],$kjdata[6]", 'date'=>$date];
        }
        if($kjDatas) $m->set($mkey, $page, 2*3600);

        return $kjDatas;
    }

}
