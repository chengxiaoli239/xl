<?php
// Standalone PHP 7.4+ regression: no Yii bootstrap, production config, DB or real accounts.
namespace {
    class Yii { public static $app; }
    function throw_info($message) { throw new \RuntimeException($message); }
    function check($condition, $message) {
        if (!$condition) { throw new \RuntimeException($message); }
        echo "PASS: $message\n";
    }
    class MemoryCache {
        public $values = [];
        public function get($key) { return $this->values[$key] ?? false; }
        public function set($key, $value, $ttl) { $this->values[$key] = $value; }
        public function delete($key) { unset($this->values[$key]); }
    }
}
namespace common\tools {
    class Tool_Common { public static function log(...$args) {} }
}
namespace backend\service {
    class PoxyIPService { public static function delProxyUidsKey() {} }
    class BetService {
        public static $enabled = 1;
        public static function getConfig($key) {
            return $key === 'CURL_POXY_STATUS' ? self::$enabled : 'PROXY_TYPE_1';
        }
    }
}
namespace backend\models {
    class TzSystemsUsers {
        const PROXY_TYPE_OPTIONS = [0=>'inherit', 1=>'kuai', 2=>'zhima', 3=>'dailiyun', 4=>'mihomo'];
        public static $rows = [];
        public static $saved = [];
        public $id = 1, $uid = 10001, $tz_system_id = 9, $proxy_type = 4;
        public $proxy_node_port = 18100;
        public $is_use_proxy = 1, $is_proxy_login = 1, $is_proxy_bet = 1, $is_local_bet = 0, $updated_at = 0;
        public function hasAttribute($key) { return property_exists($this, $key); }
        public static function find() { return new AccountQuery(); }
        public static function findOne($where) {
            return self::find()->where(is_array($where) ? $where : ['id'=>$where])->one();
        }
        public function save($validate, $fields) { self::$saved = $fields; return true; }
    }
    class AccountQuery {
        private $where = [];
        public function where($where) { $this->where = $where; return $this; }
        public function andWhere($where) { $this->where += $where; return $this; }
        public function one() {
            foreach (TzSystemsUsers::$rows as $account) {
                foreach ($this->where as $key=>$value) {
                    if ($account->$key != $value) { continue 2; }
                }
                return $account;
            }
            return null;
        }
    }
    class ProxyIpRecords {
        public static function findOne($where) { throw new \RuntimeException('Unexpected proxy DB access'); }
        public static function updateAll(...$args) { throw new \RuntimeException('Unexpected proxy DB write'); }
    }
    class TzSystems {
        public static function findOne($id) { return (object)['lottery_type'=>0]; }
    }
}
namespace backend\models\thirdD { class BetsBackend { const BET_TYPE_SERVER_API = 0; } }
namespace backend\controllers { class BaseController {} }
namespace backend\service\clients {
    class TzSystemUsersService { public static function delTzSystemUserData() {} }
}
namespace yii\web { class Response { const FORMAT_JSON = 'json'; } }
namespace common\service\proxy {
    function file_get_contents($path) { return $path === '/etc/xl-node-manager/api-secret' ? str_repeat('x', 40) : \file_get_contents($path); }
    function curl_exec($ch) {
        if(strpos(\curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), 'http://127.0.0.1:17990/') === 0){
            return json_encode(['nodes'=>[['port'=>18100, 'running'=>true], ['port'=>18101, 'running'=>true]]]);
        }
        return \curl_exec($ch);
    }
    function curl_getinfo($ch, $option) {
        if($option === CURLINFO_HTTP_CODE && strpos(\curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), 'http://127.0.0.1:17990/') === 0){ return 200; }
        return \curl_getinfo($ch, $option);
    }
    function curl_setopt($ch, $option, $value) {
        $GLOBALS['proxy_options'][$option] = $value;
        return \curl_setopt($ch, $option, $value);
    }
    class ProxyKuaiService {
        public static $authCalls = 0;
        public static function getProxyAuth($address) { self::$authCalls++; return []; }
        public static function isUseProxyAuth() { return false; }
    }
}
namespace {
    use backend\models\TzSystemsUsers as Account;
    use backend\service\BaseService;
    use common\service\proxy\ProxyBaseService;
    use common\service\proxy\ProxyMihomoService;
    use common\service\proxy\ProxyKuaiService;

    require __DIR__.'/../backend/service/BaseService.php';
    require __DIR__.'/../backend/service/BaseTZService.php';
    require __DIR__.'/../backend/service/SevenService.php';
    require __DIR__.'/../common/service/proxy/ProxyMihomoService.php';
    require __DIR__.'/../common/service/proxy/ProxyBaseService.php';
    require __DIR__.'/../backend/modules/forum/controllers/TzSystemsUsersController.php';

    Yii::$app = (object)[
        'cache'=>new MemoryCache(),
        'params'=>['KUAI_USERNAME'=>'fake', 'KUAI_PASSWORD'=>'fake'],
        'response'=>(object)['format'=>''],
        'user'=>(object)['id'=>1, 'isGuest'=>false, 'identity'=>(object)['status'=>10]],
        'request'=>new class {
            public $data = ['id'=>1, 'proxy_type'=>1];
            public function post($key) { return $this->data[$key] ?? null; }
        },
    ];
    $mihomo = new Account();
    $kuai = clone $mihomo;
    $kuai->id = 2;
    $kuai->tz_system_id = 3;
    $kuai->proxy_type = 1;
    Account::$rows = [$kuai, $mihomo]; // Same UID, different sites: must not pick first account.
    Yii::$app->cache->set(ProxyBaseService::buildProxyIpKey(1), '127.0.0.1:19001', 60);

    $route = function ($site, $scene) {
        $GLOBALS['proxy_options'] = [];
        $ch = curl_init();
        $result = BaseService::setPoxy($ch, 'https://example.invalid/', 10001, $scene, $site);
        curl_close($ch);
        return $result;
    };
    check($route(9, 'login') === '127.0.0.1:18100', 'site-specific login selects assigned node');
    check($GLOBALS['proxy_options'][CURLOPT_NOPROXY] === '', 'NO_PROXY cannot bypass selected node');
    check($GLOBALS['proxy_options'][CURLOPT_PROXYUSERPWD] === '', 'purchased-proxy credentials are not sent to Mihomo');
    check($route(9, 'bet') === '127.0.0.1:18100', 'interface scene selects same assigned node');
    $second = clone $mihomo;
    $second->id = 3;
    $second->tz_system_id = 10;
    $second->proxy_node_port = 18101;
    Account::$rows[] = $second;
    check($route(10, 'login') === '127.0.0.1:18101', 'second account uses its own node');
    $mihomo->proxy_node_port = 18102;
    check($route(9, 'login') === '127.0.0.1:18102' && $route(10, 'login') === '127.0.0.1:18101', 'changing one account leaves the other node unchanged');
    $mihomo->proxy_node_port = 0;
    check($route(9, 'login') === ProxyMihomoService::ADDRESS, 'unbound account never uses another account node');
    $mihomo->proxy_node_port = 18100;
    check(ProxyKuaiService::$authCalls === 0, 'Mihomo never invokes purchased proxy provider');
    $mihomo->is_proxy_login = 0;
    check($route(9, 'login') === false && !$GLOBALS['proxy_options'], 'disabled login scene preserves direct routing');
    check($route(9, 'bet') === '127.0.0.1:18100', 'login switch does not change interface switch');
    $mihomo->is_proxy_login = 1;
    $mihomo->is_use_proxy = 0;
    check($route(9, 'bet') === false && !$GLOBALS['proxy_options'], 'account master switch remains authoritative');
    $mihomo->is_use_proxy = 1;
    \backend\service\BetService::$enabled = 0;
    check($route(9, 'bet') === false && !$GLOBALS['proxy_options'], 'global switch retains existing behavior');
    \backend\service\BetService::$enabled = 1;
    check($route(3, 'login') === '127.0.0.1:19001' && ProxyKuaiService::$authCalls === 1, 'Kuai route remains unchanged');
    check(ProxyBaseService::getCurrentValidProxyIp(4) === ProxyMihomoService::ADDRESS, 'fixed endpoint requires no DB pool');
    check(ProxyBaseService::getRemoteProxyIp(4)['ip_addr'] === ProxyMihomoService::ADDRESS, 'fixed endpoint never purchases a new IP');
    check(ProxyBaseService::clearCurrentProxyIp(4)['status'] === 200, 'fixed endpoint failure never invalidates purchased IP records');
    check(ProxyBaseService::clearCurrentProxyIp(1, '127.0.0.1:18100')['data']['proxy_type'] === 4,
        'legacy UID-only recovery cannot invalidate another account provider for a Mihomo error');
    check(Yii::$app->cache->get(ProxyBaseService::buildProxyIpKey(1)) === '127.0.0.1:19001', 'Mihomo failure preserves Kuai cache');

    $controller = new \backend\modules\forum\controllers\TzSystemsUsersController();
    Yii::$app->user->id = 2;
    check($controller->actionSetProxyType()['status'] === 403, 'non-admin cannot change routing');
    Yii::$app->user->id = 1;
    Yii::$app->user->identity->status = 0;
    check($controller->actionSetProxyType()['status'] === 403, 'suspended admin cannot change routing');
    Yii::$app->user->identity->status = 10;
    Yii::$app->request->data['proxy_type'] = '4garbage';
    check($controller->actionSetProxyType()['status'] === 400, 'invalid provider is rejected without coercion');
    Yii::$app->request->data['proxy_type'] = 4;
    $mihomo->is_local_bet = 1;
    check($controller->actionSetProxyType()['status'] === 400, 'local-client accounts cannot select server-only node');
    $mihomo->is_local_bet = 0;
    $mihomo->tz_system_id = 12;
    check($controller->actionSetProxyType()['status'] === 400, 'unintegrated legacy adapters cannot select Mihomo');
    $mihomo->tz_system_id = 9;
    Yii::$app->request->data['proxy_node_port'] = 18100;
    check($controller->actionSetProxyType()['status'] === 400, 'missing listener blocks activation for all-lottery site metadata');
    $listener = stream_socket_server('tcp://127.0.0.1:18100', $errno, $error);
    if (!$listener) { throw new \RuntimeException('Cannot bind isolated Mihomo test listener'); }
    check($controller->actionSetProxyType()['status'] === 200 && $mihomo->proxy_type === 4,
        'admin can select Mihomo after listener preparation without altering scene switches');
    fclose($listener);
    $mihomo->tz_system_id = 3;
    check($controller->actionSetProxyType()['status'] === 400, 'Seven login adapter sites cannot select Mihomo');
    $mihomo->tz_system_id = 9;
    \backend\service\SevenService::$tz_system_id = 9;
    $ch = curl_init();
    $blocked = false;
    try { \backend\service\SevenService::setPoxy($ch, 'http://example.invalid/', 10001); }
    catch (\RuntimeException $e) { $blocked = true; }
    curl_close($ch);
    check($blocked, 'unsupported Seven interface fails before any outgoing request');
    Yii::$app->request->data['proxy_type'] = 1;
    check($controller->actionSetProxyType()['status'] === 200, 'admin can restore original provider');
    check(Account::$saved === ['proxy_type', 'updated_at'] && $mihomo->is_use_proxy === 1
        && $mihomo->is_proxy_login === 1 && $mihomo->is_proxy_bet === 1, 'provider save preserves all account switches');
    Yii::$app->request->data['is_use_proxy'] = 0;
    check($controller->actionSetProxyType()['status'] === 200 && $mihomo->is_use_proxy === 0
        && $second->is_use_proxy === 1, 'disabling proxy for one account does not disable another account');

    // Real cURL: unavailable proxy must not reach even a loopback destination with NO_PROXY=*.
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!$server) { throw new \RuntimeException('Cannot create isolated local target'); }
    $address = stream_socket_get_name($server, false);
    $originalNoProxy = getenv('NO_PROXY');
    putenv('NO_PROXY=*');
    $ch = curl_init('http://'.$address.'/');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>2]);
    ProxyMihomoService::setProxy($ch);
    $result = curl_exec($ch);
    check($result === false && curl_errno($ch) === CURLE_COULDNT_CONNECT, 'unavailable node fails closed in real cURL');
    check(@stream_socket_accept($server, 0) === false, 'destination receives no direct connection');
    curl_close($ch);
    fclose($server);
    putenv($originalNoProxy === false ? 'NO_PROXY' : 'NO_PROXY='.$originalNoProxy);
    echo "All proxy regressions passed. No production connections were made.\n";
}
