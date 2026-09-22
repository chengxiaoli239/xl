<?php
namespace common\service\proxy;

/** Local, dedicated Mihomo listener; upstream node credentials never enter Yii. */
class ProxyMihomoService
{
    const TYPE = 4;
    const ADDRESS = '127.0.0.1:1'; // Unconfigured account fails closed, never a shared default node.
    const FIRST_PORT = 18100;
    const LAST_PORT = 18115;

    public static function address($port): string
    {
        $port = filter_var($port, FILTER_VALIDATE_INT);
        return $port !== false && $port >= self::FIRST_PORT && $port <= self::LAST_PORT
            ? '127.0.0.1:'.$port : self::ADDRESS;
    }

    public static function isNodeAddress($address): bool
    {
        return $address === self::ADDRESS || (is_string($address)
            && preg_match('/^127\.0\.0\.1:(1810[0-9]|1811[0-5])$/D', $address) === 1);
    }

    public static function setProxy($ch, $port = 0)
    {
        // Explicit proxy + empty bypass list: connection failures must never go DIRECT.
        $address = self::address($port);
        curl_setopt($ch, CURLOPT_PROXY, $address);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_NOPROXY, '');
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, '');
        return $address;
    }

    public static function isListening($port = 0): bool
    {
        if(self::address($port) === self::ADDRESS){
            return false;
        }
        $socket = @fsockopen('127.0.0.1', (int)$port, $errno, $error, 1);
        if (!$socket) {
            return false;
        }
        fclose($socket);
        return true;
    }

    public static function manager($action, array $data = []): array
    {
        if(!in_array($action, ['list', 'preview', 'import', 'start'], true)){
            throw new \RuntimeException('节点操作无效');
        }
        $secret = @file_get_contents('/etc/xl-node-manager/api-secret');
        if($secret === false || strlen(trim($secret)) < 32){
            throw new \RuntimeException('独立节点服务尚未配置');
        }
        $ch = curl_init('http://127.0.0.1:17990/'.$action);
        curl_setopt_array($ch, [
            CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>json_encode($data ?: new \stdClass()),
            CURLOPT_HTTPHEADER=>['Content-Type: application/json', 'Authorization: Bearer '.trim($secret)],
            CURLOPT_PROXY=>'', CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>2,
            CURLOPT_TIMEOUT=>60, CURLOPT_FOLLOWLOCATION=>false,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result = is_string($body) ? json_decode($body, true) : null;
        if($status !== 200 || !is_array($result)){
            throw new \RuntimeException('节点操作失败，请检查服务、YAML 格式、所选协议或重新预览；原账号设置未改变');
        }
        return $result;
    }
}
