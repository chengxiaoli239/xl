<?php
namespace backend\service\Mbs188\tennis;

use backend\models\Matchs;
use backend\service\Mbs188\Mbs188BaseService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class TennisService extends Mbs188BaseService { #
    public static $baseUrl = 'https://landing-mbs.188sbk.com';

    /**
     * @param int $game_type 33:网球
     * @return bool|mixed|string
     */
    public static function getGameData($game_type = 33){
        $v = (int)(microtime(true) * 1000);
        $_ = $v + 60 * 40;
        $url = self::$baseUrl.'/zh-cn/Service/CentralService?GetData&ts='.$v;

        $headers = [
            'Accept: */*',
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Connection: keep-alive',
            'Content-Length: 420',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Cookie: ASP.NET_SessionId=s211e4jzggak2ci0yz0vwgwx; mc=; sb188cashlv=2183401226.20480.0000; historyUrl=%2Fm%2Fzh-cn%2Fsports%2Ffootball%2Fin-play%2Ffull-time-asian-handicap-and-over-under%3Fsc%3DADGBCE%26u%3Dhttps%3A%2F%2Fm.188xiaoba.net%26c%3D44%26allowracing%3Dfalse%26reg%3DChina%26rc%3DCN; timeZone=480; settingProfile=OddsType=2&NoOfLinePerEvent=1&SortBy=1&AutoRefreshBetslip=True; fav3=; HighlightedSport=; _ga=GA1.2.1556568454.1600441259; _gid=GA1.2.368257017.1600441259; CCDefaultMbPlay=; CCCurrentMbPlay=; CCDefaultBgPlay=; CCEnlargeStatus=false; BS@Cookies=5084168122%234274292%23100%23false%230_0%231.56%23null%230%3A0%23true%23true%23%234274292%23false%23',
            'Host: landing-mbs.188sbk.com',
            'Origin: https://landing-mbs.188sbk.com',
            'Referer: https://landing-mbs.188sbk.com/zh-cn/sports/tennis/competition/full-time-asian-handicap-and-over-under',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.92 Safari/537.36',
            'X-Requested-With: XMLHttpRequest'
        ];
        $post_data = [
            'IsFirstLoad' => true,
            'VersionL'=> -1,
            'VersionU' => 0,
            'VersionS' => -1,
            'VersionF' => -1,
            'VersionH' => 0,
            'VersionT' => -1,
            'IsEventMenu' => false,
            'SportID' => 1,
            'CompetitionID' => -1,
            'reqUrl' => '/zh-cn/sports/tennis/competition/full-time-asian-handicap-and-over-under',
            'oIsInplayAll' => false,
            'oIsFirstLoad' => true,
            'oSortBy' => 1,
            'oOddsType' => 0,
            'oPageNo' => 0,
            'hisUrl' => '/zh-cn/sports/tennis/competition/full-time-asian-handicap-and-over-under',
            'LiveCenterEventId' => 0,
            'LiveCenterSportId' => 0,
        ];

        //$data = self::httpPost($url, $post_data, $headers);
        $data = self::httpPost($url, http_build_query($post_data), $headers);

        return $data;
    }

    /**
     * @param array $data
     * @param int $type 1独赢
     * @param int $game_type 29:篮球，33:网球
     * @return array|bool|mixed|string
     */
    public static function getGames($data = [], $type = 1, $game_type = 33){
        $rstData = ['status' => 200, 'msg' => '操作成功'];
        $gameTypes = [3 => '棒球', 29 => '足球', 33 => '网球'];
        if (empty($data)) {
            $data = self::getGameData($game_type);
        }
        p($data);

    }
}