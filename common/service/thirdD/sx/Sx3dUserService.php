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
            if(!self::checkIsLogin($TzSystemsUser, $siteUserInfo)){
                # 1、获取cookie
                $result = SiteOauthApi::loginPage($domain);
                if(empty($result['cookie'])){
                    throw_info('登陆过程获取cookie异常');
                }
                var_dump('cookie：'.$result['cookie']);
                $TzSystemsUser->balance = $siteUserInfo['credits_remaining'];
                $TzSystemsUser->cookie = $result['cookie'];
                $TzSystemsUser->save();

                # 2、获取验证码
                $headers = [
                    'Cookie' => $TzSystemsUser->cookie,
                ];
                $result = SiteOauthApi::getCaptcha($domain, $headers);
                if($fileContent = $result['fileContent']){
                    $fileName = '/www/log/'.\Yii::$app->params['LOG_PATH'].'/'.date('Ymd'). '/'. $TzSystemsUser->id.'_'.$result['cookie'].'.png';
                    file_put_contents($fileName, $fileContent);
                }
                # 3、执行登陆操作
                $params = [
                    'username' => $TzSystemsUser->account,
                    'password' => $TzSystemsUser->password,
                    'loginseccodeverify' => 0,
                ];
                SiteOauthApi::actLogin($domain, $headers, $params);

                $result = ['TzSystemsUserId'=>$TzSystemsUser->id, 'msg'=>'登陆成功'];
            }else{
                $TzSystemsUser->balance = $siteUserInfo['credits_remaining'];
                $TzSystemsUser->save();
                //var_dump('已是登陆状态', date('Y-m-d H:i:s'));
                $result = ['TzSystemsUserId'=>$TzSystemsUser->id, 'msg'=>'已是登陆状态'];
            }
        }catch (\Exception $e){
            #var_dump('异常：'.$e->getMessage().'_'.$e->getFile().'_'.$e->getLine());
            Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '登陆异常', ['err_msg'=>$e->getMessage()]);
            $result = ['TzSystemsUserId'=>$TzSystemsUser->id, 'msg'=>$e->getMessage()];
        }
        //var_dump($result);

        return $result;
    }

    public static function checkIsLogin($TzSystemsUser, &$siteUserInfo=[]): bool
    {
        $domain = $TzSystemsUser->ssc_domain;
        $cookie = $TzSystemsUser->cookie;
        $tz_system_id = $TzSystemsUser->tz_system_id;
        $flag = true;
        $headers = [
            'Cookie' => $cookie,
            'X-Requested-With' => 'XMLHttpRequest',
        ];
        $result = \common\open\thirdD\api\SiteUserApi::getUserInfo($domain, $headers);
        $logArr = ['tz_system_id'=>$tz_system_id, 'uid'=>$TzSystemsUser->uid];
        if(empty($result) OR strpos($result['m'], '登录') !== false){
            $flag = false;
            //$logArr['result'] = $result;
        }else{
            $siteUserInfo = $result;
        }
        $logArr['flag'] = $flag;
        $result2 = \common\open\thirdD\api\SiteUserApi::getAppNews($domain, $headers);
        //$logArr['result2'] = $result2;
        Tool_Common::log('/user/'.__FUNCTION__, 'INFO', '保持登陆请求', $logArr);

        return $flag;
    }
}
