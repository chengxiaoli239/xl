<?php
# 开彩网
namespace common\kj\ssc;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class Lucky5 extends BaseKj {
    public static $lottery_type = 8;

    /**
     * @desc 幸运五星彩
     * @param string $returnType
     * @return array|bool
     */
    public static function getLotteryNo($returnType = 'json'){

        if(true OR !$kjData = self::getCurrentKjData(self::$lottery_type)){
            $datas = self::batchGrab();

            $kjData = ['expect'=>$datas[1][0], 'opentime'=>$datas[2][0], 'opencode'=>$datas[3][0]];
        }

        if(!$kjData) return false;
        $opencode = $kjData['opencode'];
        $opentime = str_replace('/', '-', $kjData['opentime']);
        $expect = $kjData['expect'];
        //p([$opencode, $opentime, $expect]);

        //p([$expect, $kjData,$kjData['opencode']]);
        # 设置开奖数据缓存
        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/getLotteryNo', 'INFO', '号码抓取-7天', $logArr);

        return $rst;
    }

    /**
     * @desc 幸运五星彩
     * @param string $returnType
     * @return array
     */
    public static function getLotteryLucky($returnType = 'json'){

        if(!$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHost(18);
            sleep(3);
            $date = date('Y-m-d');
            if('00:00' < date('H:i:s') && date('H:i:s') < '03:00'){
                $date = date('Y-m-d', time()-86400);
            }
            $url = $domain.'/data/cqssc/lotteryList/'.$date.'.json?t='.time();
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content[0];

            if (!isset($data['issue']) OR !$data) return false;
            $str = substr($data['issue'], 0, 8);
            $kjData['expect'] = str_replace($str, $str . '-', $data['issue']);
            $kjData['opencode'] = implode(',', $data['openNum']);
            $kjData['opentime'] = $data['openDateTime'];
            //p($kjData);
            //$kjData = ['expect'=>20190125060, 'opencode'=>'0,4,1,9,1', 'opentime'=>'2019-01-25 16:00:59', 'opentimestamp'=>1548403259 ]
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache(self::$lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('/WORK/LOG/'.Yii::$app->params['LOG_PATH'].'/'.date('Ymd').'/cqssc', 'INFO', '号码抓取-直播网', $logArr);

        return $rst;
    }

    /**
     * @desc 七天 - 新疆 批量数据出口
     * @return mixed
     */
    public static function batchSevenDay($returnType = 'json'){
        $datas = self::batchGrabSevenDay();
        $qihaos = $datas[1];
        $times = $datas[2];
        $codes = $datas[3];
        $kjDatas = [];
        //$qihaos = array_reverse($qihaos);
        foreach ($qihaos as $key=>$qihao){
            $kjDatas[] = ['expect'=>$qihao, 'opentime'=>$times[$key], 'opencode'=>$codes[$key]];
        }

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            $str = '<?xml version="1.0" encoding="utf-8"?>';
            foreach ($kjDatas as $kjData){
                $str .= '<xml><row expect="'.$kjData['expect'].'" opencode="'.$kjData['opencode'].'" opentime="'.$kjData['opentime'].'" /></xml>';
            }
            ob_end_flush();exit;
        }

        return $kjDatas;
    }

    /**
     * @desc 七天 - 新疆 批量数据
     * @return mixed
     */
    public static function batchGrab(){
        $domain = BaseKj::getApiHost(18);
        $t = microtime(true) * 10000;
        $url = $domain.'/DrawNo/GetDrawNoTable?pageindex=2&_='.$t;
        $mkey = 'batchGrab_lottery_type_6';
        # http://f9.ww99865.xyz:5678/DrawNo/GetDrawNoTable?pageindex=2&_=1570530310790
        p($url);

        $m = \Yii::$app->cache;
        //$content = file_get_contents($url);
        //if($datas = $m->get($mkey)) return $datas;
        $h = str_replace('https://', '', $domain);

        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'Accept-Encoding: gunzip, deflate, br',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Connection: keep-alive',
            'Cookie: Hm_lvt_afe1c3da922eb68bb36abb2f9a4ad0ce=1568795085; Hm_lpvt_afe1c3da922eb68bb36abb2f9a4ad0ce=1568795097',
            'Host: '.$h,
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',
        ];
        $content = CurlService::getCurl($url, $headers);
        $preg = "/<td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td>/ism"; // 这里是表达式，大神看看
        preg_match_all($preg,$content,$matches);

        $datas = $matches;

        $m->set($mkey, $datas, 20 * 60);
        return $datas;
    }

}
