<?php
/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace common\service\webot;
use common\service\chat\Tool_Common;

class MsgService extends BaseService
{

    /**
     * @desc 发送文本消息
     * @param $uid
     * @param $wcId
     * @param string $content
     * @return bool|string
     */
    public static function sendText($uid, $wcId, $content='你好，1234我是一条特别快乐的小测试'){
        self::__init($uid);
        $config = self::$webotConfigs;
        $url = $config->base_url.'/iPadLogin';

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$config['authorization'],
        ];
        $post_datas = [
            'wId' => $config->wcId,
            'wcId' => $config->wcId,
            'content' => $content,
        ];
        $rst = BaseService::sendCurlPost($url, $headers, $post_datas);
        $logArr = ['url'=>$url, 'headers'=>$headers, 'post_datas'=>$post_datas, 'rst'=>$rst];
        Tool_Common::log('/wx/'.__FUNCTION__, 'INFO', 'webot获取微信二维码', $logArr);
        if($rst['code'] == 1000 && isset($rst['data']['wId'])){
            $config->wId = $rst['data']['wId'];
            $config->save();
        }

        return $rst;
    }

}