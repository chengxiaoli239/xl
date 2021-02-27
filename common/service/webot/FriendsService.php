<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace common\service\webot;
use common\service\chat\Tool_Common;

class FriendsService extends BaseService
{
    /**
     * @desciption webot获取微信二维码
     * @param $uid
     * @param $wcId
     * @param int $type
     * @return array
     */
    public static function getContactDetail($uid, $wcIdArrs){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        self::__init($uid);
        $config = self::$webotConfigs;
        $url = $config->base_url.'/getContact';

        $friendNums = self::splitDatas($wcIdArrs, $nums=20); # 2500一次
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $rst['data'] = [];
        foreach ($friendNums as $wcIds){
            $post_datas = [
                'wId' => $config->wId, # 登录实例id
                'wcId' => implode(',', $wcIds), # 微信好友id
            ];
            $rstData = BaseService::sendCurlPost($url, $headers, $post_datas);
            $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
            Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot获取微信二维码', $logArr);
            if($rstData['code'] == 1000 && !empty($rstData['data'])){
                $rst['data'] = array_merge($rst['data'], $rstData['data']);
            }
            sleep(3);
        }

        return $rst;
    }

}