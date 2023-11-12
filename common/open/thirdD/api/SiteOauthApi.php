<?php
namespace common\open\thirdD\api;

use common\open\thirdD\SxThirdDBase;

class SiteOauthApi extends SxThirdDBase
{
    # 1、登录页面获取token
    const API_LOGIN_PAGE = '/Login/index';
    # 2、获取验证码
    const API_GET_CAPTCHA = '/Login/captcha?update=0.3772906113638177';
    # 3、操作登录
    const API_ACTION_LOGIN = '/Login/login?action=LogApp';
    # 4、首页
    const API_HOME_INDEX = '/Index/index';

    /**
     * 获取cookie
     * @return array ['cookie'=>'PHPSESSID=022t1amr4r3o520cvffju44kn6']
     */
    public static function loginPage(): array
    {
        return self::createObject()->get(self::API_LOGIN_PAGE);
    }

}
