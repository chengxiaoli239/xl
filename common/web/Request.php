<?php
namespace common\web;

use Yii;

class Request extends \yii\web\Request
{
    /**
     * {@inheritdoc}
     * 修复非标准端口（如8090）时hostInfo不包含端口号的问题。
     * 当客户端Host头不含端口时（如部分代理/curl），从SERVER_PORT补全。
     */
    public function getHostInfo()
    {
        $hostInfo = parent::getHostInfo();
        if ($hostInfo !== null && strpos($hostInfo, '://') !== false) {
            $secure = $this->getIsSecureConnection();
            $port = $secure ? $this->getSecurePort() : $this->getPort();
            $defaultPort = $secure ? 443 : 80;
            if ($port !== $defaultPort && strpos(parse_url($hostInfo, PHP_URL_HOST) ?: '', ':') === false) {
                // host不含端口且当前非标准端口 → 追加端口
                $hostInfo = rtrim($hostInfo, '/');
                if (strpos(substr($hostInfo, strpos($hostInfo, '://') + 3), ':') === false) {
                    $hostInfo .= ':' . $port;
                }
            }
        }
        return $hostInfo;
    }
}
