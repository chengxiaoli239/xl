<?php
# 360 彩票网
namespace common\kj\indexes;
use backend\models\KjConfig;
use backend\service\BingDao\BingDaoService;
use backend\service\CurlService;
use common\helpers\lottery\LotteryBet;
use common\helpers\LotteryType;
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
        23 => 'sfytf', # 以太坊3分
        24 => 'tfytf', # 以太坊10分
        25 => 'jsqws', # 江苏七位数
    ];

    public static function getLotteryNo($returnType = 'json', $is_auto=1, $lottery_type=19){

        $type_name = self::$lottery_types[$lottery_type];
        if($is_auto==2 OR !$kjData = self::getCurrentKjData($lottery_type)) {
            $domain = BaseKj::getApiHostByRoute('/kj/indexes/ytf3m');

            $limit = 5;
            $page = 1;
            $KjConfig = KjConfig::findOne(['path'=>'/kj/indexes/batch-jsqws', 'lottery_type'=>$lottery_type, 'enable'=>1]);
            if(!empty($KjConfig)){
                $m = \Yii::$app->cache;
                $mkey_jsqws = 'jsqws_xxx_6';
                $m_page = $m->get($mkey_jsqws);
                $page = $m_page ? (int) $m_page : 10;
                $limit = 50;
            }
            # 当前开奖号码 链接
            $url = $domain.'/cloud-lottery-service-server/gameInfo/lotteryissue/queryHistorys?lotName='.$type_name.'&limit='.$limit.'&page='.$page.'&sidx=open_time&order=desc';

            $rst = CurlService::getCurl($url);

            if($rst['code'] != 200) return false;
            $datas = $rst['data']['list'][0];
            if(in_array($lottery_type, [LotteryType::ETH_3M, LotteryType::ETH_10M])) {
                $data = [];
                $lotteryNum = $rst['data']['list'][0]['lotteryNum'];

                $pattern = '/\d.*?/';
                preg_match_all($pattern, $lotteryNum, $matches);
                $lotteryDatas = array_reverse($matches[0]);
                for ($i = 0; $i < count($lotteryDatas); $i++) {
                    $data[] = $lotteryDatas[$i];
                    if (count($data) == 4) {
                        break;
                    }
                }
                $data = array_reverse($data);
            }elseif ($lottery_type == 25){
                $data = $datas['result']['numbers'];
                Tool_Common::log('/kj_data/'.__FUNCTION__, 'INFO', '号码抓取-'.$type_name, ['lottery_type'=>$lottery_type, 'datas'=>$datas]);
                if(!empty($KjConfig) && $KjConfig->is_batch == 1){
                    $all_kjDatas = [];
                    foreach ($rst['data']['list'] as $row){
                        $all_kjDatas[] = ['expect'=>$row['issue'], 'opencode'=>$row['lotteryNum'].',0', 'opentime'=>date('Y-m-d H:i:s', (int)($row['openTime']/1000))];
                    }

                    $next_page = $page - 1;
                    if($next_page<1) $next_page = 1;
                    $m->set($mkey_jsqws, $next_page, 600);

                    return  $all_kjDatas;
                }
            }else{
                //文本转码
                $data = $datas['result']['numbers'][2];
            }
            $codes = [$data[0], $data[1], $data[2], $data[3], 0];

            if (empty($data)) return false;
            $opencode = implode(',', $codes);
            //if($opencode == '0,0,0,0,0') return false;
            //$kjData = ['expect'=>$data['preDrawIssue'], 'opencode'=>$opencode, 'opentime'=>$data['preDrawTime']];
            $kjData = ['expect'=>$datas['issue'], 'opencode'=>$opencode, 'opentime'=>date('Y-m-d H:i:s', (int)($datas['openTime']/1000))];
            //p($kjData);
        }

        $opencode = $kjData['opencode'];
        $opentime = $kjData['opentime'];
        $expect = $kjData['expect'];

        $setTime = 300;
        try {
            $setTime = (new LotteryBet)->schedule[$lottery_type]['minute']*60;
        }catch (\Exception $e){}

        self::setKjDataCache($lottery_type, $expect, $kjData, $setTime);

        if($returnType == 'xml'){
            header("Content-type: application/xml");
            echo'<?xml version="1.0" encoding="utf-8"?>';
            echo '<xml><row expect="'."$expect".'" opencode="'."$opencode".'" opentime="'."$opentime".'" /></xml>';
            ob_end_flush();exit;
        }else{
            $rst = ['expect'=>$expect, 'opencode'=>$opencode, 'opentime'=>$opentime];
        }
        $logArr = $rst;
        Tool_Common::log('grab_ki_codes', 'INFO', '号码抓取-'.$type_name, array_merge($logArr, ['lottery_type'=>$lottery_type]));

        return $rst;
    }
}
