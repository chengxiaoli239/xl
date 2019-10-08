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
        p($kjData);

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

            $t = microtime(true) * 10000;
            $url = $domain.'/Member/GetMemberPrint&_='.$t; #当前开奖号码
            # 当前开奖链接：http://f9.ww99865.xyz:5678/Member/GetMemberPrint?_=1570547160015
            //$content = file_get_contents($url);
            $content = CurlService::httpGet($url);
            //$data = json_decode($content,320);
            $data = $content;
            d($data);

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
        $datas = self::batchGrab();
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
        $url = $domain.'/DrawNo/GetDrawNoTable?pageindex=2&_='.$t; #当前开奖号码

        $mkey = 'batchGrab_lottery_type_6';
        # http://f9.ww99865.xyz:5678/DrawNo/GetDrawNoTable?pageindex=2&_=1570530310790

        $m = \Yii::$app->cache;
        //if($datas = $m->get($mkey)) return $datas;

        $content = CurlService::getCurl($url);
        p($content);

        $datas = $content;

        $m->set($mkey, $datas, 20 * 60);
        return $datas;
    }

}
