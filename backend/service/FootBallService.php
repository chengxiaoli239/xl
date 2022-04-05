<?php
namespace backend\service;

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
        $access_token = $datas['access_token'];

        $uid = $TzSystemsUsers->uid;
        if(empty($datas)){
            throw new Exception('数据不能为空');
        }

        $events = $datas['events'];
        foreach ($events as $event){
            $data_type = $event['event']['sport'];
            if($data_type != self::$data_type) continue;

            $setDatas = [

            ];

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
