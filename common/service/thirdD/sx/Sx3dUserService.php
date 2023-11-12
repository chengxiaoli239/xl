<?php

namespace common\service\thirdD\sx;

use common\open\thirdD\api\SiteOauthApi;
use common\service\chat\Tool_Common;
use common\service\thirdD\CommonBaseService;
use yii\helpers\Json;

class Sx3dUserService extends CommonBaseService
{
    public static function login(object $TzSystemsUser): array
    {
        try {
            $runtimePath = \Yii::$app->runtimePath;
            #p([dirname(__FILE__), $runtimePath]);
            $domain = $TzSystemsUser->ssc_domain;
            if(!self::checkIsLogin($TzSystemsUser)){
                # 1、获取cookie
                #$result = SiteOauthApi::loginPage($domain);
                #if(empty($result['cookie'])){
                #    throw_info('登陆过程获取cookie异常');
                #}
                #var_dump('cookie：'.$result['cookie']);
                #$TzSystemsUser->cookie = $result['cookie'];
                #$TzSystemsUser->save();

                ## 2、获取验证码
                #$headers = [
                #    'Cookie' => $TzSystemsUser->cookie,
                #];
                #$result = SiteOauthApi::getCaptcha($domain, $headers);
                #if($fileContent = $result['fileContent']){
                #    $fileName = $runtimePath.'/'.date('Ymd').'/'.$TzSystemsUser->id.'_'.$result['cookie'].'.png';
                #    file_put_contents($fileName, $fileContent);
                #}
                $headers = [
                    'Cookie' => $TzSystemsUser->cookie,
                ];                                     # 3、执行登陆操作
                $params = [
                    'username' => $TzSystemsUser->account,
                    'password' => $TzSystemsUser->password,
                    'loginseccodeverify' => 0,
                ];
                SiteOauthApi::actLogin($domain, $headers, $params);

                $result = ['TzSystemsUserId'=>$TzSystemsUser->id, 'msg'=>'登陆成功'];
            }else{
                var_dump('已是登陆状态');
                $result = ['TzSystemsUserId'=>$TzSystemsUser->id, 'msg'=>'已是登陆状态'];
            }
        }catch (\Exception $e){
            var_dump('异常：'.$e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '登陆异常', ['err_msg'=>$e->getMessage()]);
            $result = ['TzSystemsUserId'=>$TzSystemsUser->id, 'msg'=>$e->getMessage()];
        }

        return $result;
    }

    public static function checkIsLogin($TzSystemsUser): bool
    {
        $domain = $TzSystemsUser->ssc_domain;
        $cookie = $TzSystemsUser->cookie;
        $flag = true;
        $headers = [
            'Cookie' => $cookie,
            'X-Requested-With' => 'XMLHttpRequest',
        ];
        $result = \common\open\thirdD\api\SiteUserApi::getUserInfo($domain, $headers);
        if(empty($result) OR strpos($result['m'], '登录') !== false){
            $flag = false;
        }

        return $flag;
    }
}
