<?php
namespace common\open\thirdD\api;

use common\tools\Util;
use GuzzleHttp\RequestOptions;

class SiteOauthApi extends SxThirdDBase
{
    # 1、登录页面获取token
    const API_LOGIN_PAGE = '/Login/index';
    # 2、获取验证码
    const API_GET_CAPTCHA = '/Login/captcha';
    # 3、操作登录
    const API_ACTION_LOGIN = '/Login/login';
    # 4、首页
    const API_HOME_INDEX = '/Index/index';

    # 登陆接口

    /**
     * 登陆操作
     * @param array $params
     * @return array
     */
    public static function actLogin(string $domain, array $headers=[], array $params=[]): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;

        $headers = array_merge($headers, [
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            "X-Requested-With"=> "XMLHttpRequest",
        ]);
        $params[RequestOptions::FORM_PARAMS] = $params;
        return $object->post(self::API_ACTION_LOGIN, $params, $headers);
    }


    /**
     * 获取cookie
     * @return array ['cookie'=>'PHPSESSID=022t1amr4r3o520cvffju44kn6']
     */
    public static function loginPage(string $domain): array
    {
        $object = self::createObject();
        $object->apiUrl = $domain;
        return $object->get(self::API_LOGIN_PAGE);
    }

    /**
     * 获取登陆验证码     * @param string $domain
     * @param array $headers
     * @param array $params     * @return array
     */
    public static function getCaptcha(string $domain, array $headers=[], array $params=[]): array
    {
        $headers = array_merge($headers, [
            'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
        ]);
        $params['update'] = Util::generateRandomDecimal(16);
        $object = self::createObject();
        $object->apiUrl = $domain;
        return $object->get(self::API_GET_CAPTCHA, $params, $headers);
    }
}
