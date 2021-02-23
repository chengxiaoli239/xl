<?php
namespace backend\service\wanbo\tennis;

use backend\service\pingbo\PingBoBaseService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class TennisService extends PingBoBaseService { #
    public static $baseUrl = 'https://cn.man152.com';
    public static $dataBaseApi = 'https://xj-sb-asia-manx.prdasbbwla2.com';

    /**
     * @param int $game_type 33:网球
     * @return bool|mixed|string
     */
    public static function getGameData($game_type = 33){
        $v = microtime(true) * 1000;
        $_ = $v + 60 * 40;
        $url = self::$dataBaseApi.'/sports-service/sv/odds/events?mk=2&sp='.$game_type.'&ot=1&btg=1&o=1&lg=&ev=&d=&l=3&v='.$v.'&me=0&more=false&c=CN&tm=0&g=&pa=0&cl=3&_g=1&_='.$_.'&locale=zh_CN';

        $data = PingBoBaseService::getCurl($url);
        return $data;
    }

    /**
     * @param array $data
     * @param int $type 1独赢
     * @param int $game_type 29:篮球，33:网球
     * @return array|bool|mixed|string
     */
    public static function getGameType($data = [], $type = 1, $game_type = 33){
        $gameTypes = [3=>'棒球', 29=>'足球', 33=>'网球'];
        if(empty($data)){
            $data = TennisService::getGameData($game_type);
        }
        $datas = $data['l']; # 比赛数据
        //p($datas);
        $setDatas = [];
        foreach ($datas as $row){
            $match_id = $row[0]; # 比赛id 33:网球
            $match_name = $row[1]; # 比赛名称：Tennis
            //p([$match_id, $match_name]);
            foreach ($row[2] as $row1){
                $game_id = $row1[0]; # 比赛记录id
                $game_name = $row1[1]; # 联赛名称
                foreach ($row1[2] as $row2){
                    $g_id = $row2[0]; # 比赛场次id
                    $player_1 = $row2[1]; # 选手1
                    $player_2 = $row2[2]; # 选手2
                    //p([$player_1, $player_2]);
                    if($type == 1 && (empty($row2[8][0][1]) OR empty($row2[8][0][1]))){
                        continue; # 独赢
                    }
                    # 全场独赢水位
                    $waters = $row2[8][0][2];
                    $player_1_water = $waters[1]; # 选手一全场独赢水位
                    $player_2_water = $waters[0]; # 选手二全场独赢水位
                    if($type == 1 && (empty($player_1_water) OR empty($player_2_water))){
                        continue; # 独赢赔率为空
                    }
                    $setDatas[] = [
                        'game_type' => $game_type,
                        'game_type_name' => $gameTypes[$game_type],
                        'game_id' => $game_id,
                        'game_name' => $game_name,
                        'g_id' => $g_id,
                        'player_1' => $player_1,
                        'player_1_water' => $player_1_water,
                        'player_2' => $player_2,
                        'player_2_water' => $player_2_water
                    ];
                }
            }
        }
        p($setDatas);

        return $data;
    }

}