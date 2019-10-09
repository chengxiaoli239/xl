<?php
# 开彩网
namespace common\kj\ssc;
use backend\models\TzSystemsUsers;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class Lucky5 extends BaseKj {
    public static $lottery_type = 8;

    /**
     * @desc 幸运五星彩
     * @param string $returnType
     * @return array
     */
    public static function getLotteryLucky($returnType = 'json'){

        if(true OR !$kjData = self::getCurrentKjData(self::$lottery_type)) {
            $domain = BaseKj::getApiHost(18);

            $t = microtime(true) * 10000;
            $url = $domain.'/Member/GetMemberPrint?_='.$t; #当前开奖号码
            # 当前开奖链接：http://f9.ww99865.xyz:5678/Member/GetMemberPrint?_=1570547160015
            $tz_system_users_id = 15;
            $TzSystemsUsers = TzSystemsUsers::findOne($tz_system_users_id);

            $headers = [
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding: gunzip, deflate',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Connection: keep-alive',
                'Cookie: '.$TzSystemsUsers->cookie,
                'Host: '.str_replace('http://', '', $TzSystemsUsers->ssc_domain),
                'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$t,
                $TzSystemsUsers->user_agent,
                'X-Requested-With: XMLHttpRequest',
            ];
            $content = CurlService::getCurl($url, $headers);
            //$data = json_decode($content,320);
            $data = $content;
            //p([$url, $headers, $data]);

            if ($data['Status'] != 1 OR !isset($data['Data']['draw_info'][0])) return false;
            $row = $data['Data']['draw_info'][0];
            $opencode = $row['thousand_no'].','.$row['hundred_no'].','.$row['ten_no'].','.$row['one_no'].','.$row['ball5'];
            $kjData = ['expect'=>$row['period_no'], 'opencode'=>$opencode, 'opentime'=>date('Y-m-d H:i:s')];
            //p($kjData);
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
     * @desc 幸运五星彩 批量数据出口
     * @return mixed
     */
    public static function batch($returnType = 'json'){
        $datas = self::batchGrab();

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            $str = '<?xml version="1.0" encoding="utf-8"?>';
            foreach ($datas as $kjData){
                $str .= '<xml><row expect="'.$kjData['expect'].'" opencode="'.$kjData['opencode'].'" opentime="'.$kjData['opentime'].'" /></xml>';
            }
            echo $str;
            ob_end_flush();exit;
        }

        return $datas;
    }

    /**
     * @desc 幸运五星 批量数据
     * @return mixed
     */
    public static function batchGrab(){
        $domain = BaseKj::getApiHost(18);
        $tz_system_users_id = 15;
        $TzSystemsUsers = TzSystemsUsers::findOne($tz_system_users_id);
        $datas = [];
        for($i=1; $i<=16; $i++){
            //for($i=16; $i>=1; $i--){
            $t = microtime(true) * 10000;
            $url = $domain.'/DrawNo/GetDrawNoTable?pageindex='.$i.'&_='.$t; #当前开奖号码

            $headers = [
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding: gunzip, deflate',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Connection: keep-alive',
                'X-Requested-With: XMLHttpRequest',
                'Cookie: '.$TzSystemsUsers->cookie,
                'Host: '.str_replace('http://', '', $TzSystemsUsers->ssc_domain),
                'Referer: '.$TzSystemsUsers->ssc_domain.'/App/Index?_='.$t,
                $TzSystemsUsers->user_agent,
            ];
            $mkey = 'batchGrab_lottery_type_6_'.$i;
            # http://f9.ww99865.xyz:5678/DrawNo/GetDrawNoTable?pageindex=2&_=1570530310790

            $m = \Yii::$app->cache;
            //if($datas = $m->get($mkey)) return $datas;

            $content = CurlService::getCurl($url, $headers);
            //p([$headers, $url, $content]);
            $data = $content;
            if($data['Status'] == 1 && !empty($data['Data']['Rows'])){
                $rows = $data['Data']['Rows'];
                # $str .= '<xml><row expect="'.$kjData['expect'].'" opencode="'.$kjData['opencode'].'" opentime="'.$kjData['opentime'].'" /></xml>';
                foreach ($rows as $k=>$row){
                    $opencode = $row['thousand_no'].','.$row['hundred_no'].','.$row['ten_no'].','.$row['one_no'].','.$row['ball5'];
                    $datas[] = ['expect'=>$row['period_no'], 'opencode'=>$opencode, 'opentime'=>$row['draw_datetime']];
                }
            }

            $m->set($mkey, $datas, 20 * 60);
        }
        //d($datas);
        return $datas;
    }

}
