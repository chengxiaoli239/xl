<?php
namespace backend\components;

use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\web\Response;

/** Only block HTML navigation in WeChat and QQ browsers; never touch client/API calls. */
class BrowserAccessGuard implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            $request = $app->request;
            if (!self::shouldBlock(
                (string)$request->getUserAgent(),
                $request->getMethod(),
                (string)$request->getHeaders()->get('Accept', ''),
                (string)$request->getHeaders()->get('Sec-Fetch-Mode', ''),
                $request->getPathInfo(),
                $request->getIsAjax() || $request->getIsPjax()
            )) {
                return;
            }

            $response = $app->response;
            $response->statusCode = 403;
            $response->format = Response::FORMAT_HTML;
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('Vary', 'User-Agent');
            $response->headers->set('X-Robots-Tag', 'noindex');
            $response->content = $app->view->renderFile('@app/views/site/unsupported-browser.php');
            $app->end(0, $response);
        });
    }

    public static function shouldBlock($userAgent, $method, $accept, $fetchMode, $path, $isAjax)
    {
        if ($method !== 'GET' || $isAjax) {
            return false;
        }
        if (!preg_match('~MicroMessenger|M?QQBrowser|(?:^|[\s;(])QQ/\d~i', $userAgent)) {
            return false;
        }
        // Do not intercept API, collection or scheduled-job endpoints, even with a browser User-Agent.
        if (preg_match('~^(?:api|cron|kj|eyunapi|wechat)(?:/|$)~i', ltrim($path, '/'))) {
            return false;
        }
        return stripos($accept, 'text/html') !== false || strtolower($fetchMode) === 'navigate';
    }
}
