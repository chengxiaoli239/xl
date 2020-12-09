<?php
# 开彩网
namespace common\kj\qxc;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class QxcTcw{
    public static $lottery_type = 1; # 七星彩

    public static function getLotteryNo($returnType = 'json', $is_auto = 1){

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
    public static function getBatchLotteryNo($type = json, $is_auto = 1){
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

    /**
     * @desc 中国体彩网 - 七星彩
     * @return json|xml
     */
    public static function QixingCaiBatch(){
        $datas = self::QixingCaiBatchDatas();

        return $datas;
    }

    /**
     * @desc 中国体彩网 - 七星彩
     * @return json|xml
     */
    public static function QixingCaiBatchDatas(){

        $m = \Yii::$app->cache;
        $mkey = 'QixingCaiBatch_page_2';
        $page = $m->get($mkey) ? : 84;
        $page < 2 && $page = 1;

        $running_status_key = 'QixingCaiBatch_status';
        if($status = $m->get($running_status_key)) return ['status'=>300, 'msg'=>'有在执行的任务，请稍后'];
        $m->set($running_status_key, 1, 300);

        $domain = BaseKj::getApiHostByRoute('/kj/qxc/qxc-batch');
        $url = $domain.'/gateway/lottery/getHistoryPageListV1.qry?gameNo=04&provinceId=0&pageSize=30&isVerify=1&pageNo='.$page; # limit 数量

        $headers = [
            "Accept: application/json, text/javascript, */*; q=0.01",
            "Accept-Encoding: gunzip, deflate, br",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Connection: keep-alive",
            "Host: webapi.sporttery.cn",
            "Origin: https://static.sporttery.cn",
            "Referer: https://static.sporttery.cn/",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-site",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36                ",
        ];

        $content = CurlService::httpGet($url, $headers);
        if(isset($content['value']['list']) && !$datas = $content['value']['list']);
        //$datas = array_reverse($data);

        $rstData = [];
        foreach ($datas as $data){
            $expect = '20'.$data['lotteryDrawNum'];
            $opencode = str_replace(' ', ',', $data['lotteryDrawResult']);
            $opentime = $data['lotterySaleEndtime'];
            $rstData[] = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $m->set($mkey, $page-1, 24*3600);
        $m->delete($running_status_key); # 跑完任务删除key

        $logArr = ['page'=>$page, 'data'=>$rstData];
        Tool_Common::log('qxc_batch', 'INFO', '号码抓取-九九网', $logArr);

        return $rstData;
    }
}
