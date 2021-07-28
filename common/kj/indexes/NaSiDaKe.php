<?php
# 360 彩票网
namespace common\kj\indexes;
use backend\service\BingDao\BingDaoService;
use backend\service\CurlService;
use common\kj\BaseKj;
use common\tools\Tool_Common;
use  yii;

class NaSiDaKe extends BaseKj{
    public static $lottery_type = 19;
    public static $lottery_types = [
        19 => 'nsdk', # 纳斯达克
        20 => 'dqs',  #  道琼斯
        21 => 'szzs', # 上证指数
        22 => 'szcz', # 深圳成指
    ];

    public static function getLotteryNo($returnType = 'json', $is_auto=1, $lottery_type=19){

        if($is_auto==2 OR !$kjData = self::getCurrentKjData($lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/indexes/nsdk');

            $type_name = self::$lottery_types[$lottery_type];
            $url = $domain.'/cloud-lottery-service-server/gameInfo/lotteryissue/queryHistorys?lotName='.$type_name.'&limit=5&page=1&sidx=open_time&order=desc'; #当前开奖号码

            $rst = CurlService::getCurl($url);

            if($rst['code'] != 200) return false;
            //文本转码
            $datas = $rst['data']['list'][0];
            $data = $datas['result']['numbers'][2];
            $codes = [$data[0], $data[1], $data[2], $data[3], 0];

            if (empty($data)) return false;
            $opencode = implode(',', $codes);
            if($opencode == '0,0,0,0,0') return false;
            //$kjData = ['expect'=>$data['preDrawIssue'], 'opencode'=>$opencode, 'opentime'=>$data['preDrawTime']];
            $kjData = ['expect'=>$datas['issue'], 'opencode'=>$opencode, 'opentime'=>date('Y-m-d H:i:s', (int)($datas['openTime']/1000))];
            //p($kjData);
        }
        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        self::setKjDataCache($lottery_type, $expect, $kjData);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('bingdao_kj', 'INFO', '号码抓取-纳斯达克', $logArr);

        return $rst;
    }
}
