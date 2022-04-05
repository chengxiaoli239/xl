<?php
namespace backend\service;

use backend\models\sports\EventsLiveDatas;
use backend\service\clients\TzSystemUsersService;
use backend\service\sports\SportsBaseService;
use common\general\helpers\Curl;
use common\tools\Tool_Common;
use yii\base\Exception;

class FootBallService extends SportsBaseService
{

    public static $data_type = 'FOOTBALL';

    public static $base_url = '';

    public static function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return $t2 . ceil(($t1 * 1000));
    }

    public static function __init__()
    {
        self::$base_url = 'https://eu-offering.kambicdn.org';
    }

    /**
     * 获取unibet网比分数据
     * @return $uuid
     */
    public static function getSorceFromUnibet()
    {
        $base_url = self::$base_url;
        $url = $base_url . '/offering/v2018/ub/listView/all/all/all/all/in-play.json?lang=en_GB&market=ZZ&client_id=2&channel_id=1&ncid=1649080054533&useCombined=true';

        $content = CurlService::getCurl($url);
        p($content);
        //也可以使用正则匹配
        $content = explode(';', $content);

        $content_uuid = explode('"', $content[1]);

        $uuid = $content_uuid[1];
        self::$uuid = $uuid;
        return $uuid;
    }

    /**
     * @desc 记录足球数据
     * @param $datas
     * @return array
     * @throws Exception
     */
    public static function recordFootBallDatas($datas) {

        $dataType = $datas['dataType'];
        $access_token = $datas['access_token'];

        $scoreDatas = $datas['score_data'];
        $TzSystemsUsers = TzSystemUsersService::getTzSystemsUsersByAccessToken($access_token);
        switch ($dataType){
            case 'in-play':
                $rst = FootBallService::recordInPlayFootBallDatas($scoreDatas, $TzSystemsUsers);
                break;
            case 'open':
                $rst = FootBallService::recordOpenFootBallDatas($scoreDatas, $TzSystemsUsers);
                break;
            default:
                throw new Exception('数据不能为空');
                break;
        }

        Tool_Common::log('/sports/'.__FUNCTION__, 'INFO', '比分数据', ['scoreDatas'=>$scoreDatas]);
        return ['status'=>200, 'msg'=>'操作成功', 'data'=>$rst];
    }

    /**
     * @desc in-play 数据
     * @param array $datas
     * @return bool
     * @throws Exception
     */
    public static function recordInPlayFootBallDatas($datas=[], object $TzSystemsUsers ){

        $uid = $TzSystemsUsers->uid;
        if(empty($datas)){
            throw new Exception('数据不能为空');
        }

        $events = $datas['events'];
        foreach ($events as $eventData){
            try {
                $data_type = $eventData['event']['sport'];
                if($data_type != self::$data_type) continue;
                $event = $eventData['event'];
                $betOffers = $eventData['betOffers'];
                $liveData = $eventData['liveData'];
                $where = ['event_id'=>$event['id'], 'group_id'=>$event['groupId']];
                $EventsLiveDatas = EventsLiveDatas::findOne($where);
                $setDatas = [];
                $now_time = time();
                if(empty($EventsLiveDatas)){
                    $EventsLiveDatas = new EventsLiveDatas();
                    $setDatas['created_at'] = $now_time;
                }

                $setDatas = [
                    'uid' => $uid,

                    'event_id' => $event['id'],
                    'event_name' => $event['name'] ? : '',
                    'event_name_en' => $event['englishName'] ? : '',
                    'event_time' => strtotime($event['start']), # 比赛开始时间
                    'group_id' => $event['groupId'],
                    'group_name' => $event['group'],

                    'home_name_en' => $event['homeName'] ? : '', # 主队英文名
                    'way_name_en' => $event['awayName'] ? : '', # 客队英文名
                    'score_home' => (int)$event['score']['home'], # 主队得分
                    'score_way' => (int)$event['score']['way'], # 客队得分
                    'score_who' => $event['score']['who'], # 哪对得分

                    'clock_minute' => $liveData['matchClock']['minute'], # 比赛进行分钟数
                    'clock_second' => $liveData['matchClock']['second'], # 当前分钟秒数
                    'clock_minutesLeftInPeriod' => $liveData['matchClock']['minutesLeftInPeriod'], # 场次剩余分钟
                    'clock_secondsLeftInMinute' => $liveData['matchClock']['secondsLeftInMinute'], # 当前分钟剩余秒数
                    'clock_period' => (int)$liveData['matchClock']['period'], # 当前节数
                    'clock_clock_running' => $liveData['matchClock']['running'], # 是否在进行

                    'statics_football_home_yellowCards' => (string)$liveData['statistics']['football']['home']['yellowCards'], # 主队黄牌数
                    'statics_football_home_redCards' => (string)$liveData['statistics']['football']['home']['redCards'], # 主队红牌数
                    'statics_football_home_corners' => (string)$liveData['statistics']['football']['home']['corners'], # 主队角球数

                    'statics_football_way_yellowCards' => (string)$liveData['statistics']['football']['way']['yellowCards'], # 客队队黄牌数
                    'statics_football_way_redCards' => (string)$liveData['statistics']['football']['way']['redCards'], # 客队红牌数
                    'statics_football_way_corners' => (string)$liveData['statistics']['football']['way']['corners'], # 客队角球数

                    'liveStatistics' => json_encode($liveData['liveStatistics'], 320), # 直播统计

                    'updated_at' => $now_time,
                ];

                $EventsLiveDatas->setAttributes($setDatas);
                if(!$EventsLiveDatas->save()){
                    throw new Exception(json_encode($EventsLiveDatas->getFirstErrors(), 320));
                }
                Tool_Common::log('/sports/'.__FUNCTION__, 'INFO', '比赛记录更新', ['event_id'=>$event['event_id'], 'groupId'=>$event['groupId'], 'event_name'=>$event['event_name']]);
            }catch (\Exception $exception){
                Tool_Common::log('ERR', '/sports/'.__FUNCTION__.'_e', '比赛数据报错失败', ['setDatas'=>$setDatas, 'err_msg'=>$exception->getMessage()]);
                continue;
            }

        }

        return true;
    }

    /**
     * @desc open 数据
     * @param array $datas
     * @return bool
     * @throws Exception
     */
    public static function recordOpenFootBallDatas($datas=[]){

        if(empty($datas)){
            throw new Exception('数据不能为空');
        }

        return true;
    }

    /**
     * @desc 微信心跳包执行任务key
     * @param string $uid
     * @return string
     */
    public static function buildWxSyncCheckTaskKey($uid = '')
    {
        return 'buildWxSyncCheckTaskKey_' . $uid;
    }

}
