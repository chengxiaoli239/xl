<?php

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/common/config/bootstrap.php';

use backend\service\Lucky5\Lucky5Service;

$withAttributes = "HTTP/1.1 200 OK\r\n"
    ."Set-Cookie: ASP.NET_SessionId=session-a; path=/; HttpOnly\r\n"
    ."set-cookie: Akamai_Cookie=token-b==; Max-Age=3600; Path=/; httponly\r\n"
    ."Set-Cookie: server_name_session=session-c; Max-Age=3600; Path=/\r\n\r\n";
$expected = 'ASP.NET_SessionId=session-a;Akamai_Cookie=token-b==;server_name_session=session-c';
if (Lucky5Service::extractRequestCookies($withAttributes) !== $expected) {
    throw new RuntimeException('Set-Cookie attributes must not appear in the request Cookie header');
}

$plain = "Set-Cookie: Akamai_Cookie=token-b==\r\n"
    ."Set-Cookie: ASP.NET_SessionId=session-a\r\n"
    ."Set-Cookie: NOTICE_LOGIN_IN=1\r\n";
$expectedPlain = 'Akamai_Cookie=token-b==;ASP.NET_SessionId=session-a;NOTICE_LOGIN_IN=1';
if (Lucky5Service::extractRequestCookies($plain) !== $expectedPlain) {
    throw new RuntimeException('Existing valid cookies must keep their names, values, and order');
}

echo "lucky_response_cookie_regression: OK\n";
