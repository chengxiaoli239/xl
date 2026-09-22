<?php
// PHP 7.4+, real retry decision method; synthetic accounts, no app bootstrap/network/DB.
namespace common\tools {
    class Tool_Common { public static function log(...$args) {} }
}
namespace common\service\proxy {
    class ProxyMihomoService { const TYPE = 4; }
    class ProxyBaseService {
        public static $calls = [];
        public static function getProxyTypeByUid($uid) { throw new \RuntimeException('Ambiguous UID lookup'); }
        public static function getProxyType() { return 2; }
        public static function getCurrentValidProxyIp($type, $scene) {
            self::$calls[] = ['get', $type]; return '192.0.2.1:8888';
        }
        public static function clearCurrentProxyIp($type, $ip) {
            self::$calls[] = ['clear', $type, $ip]; return ['status'=>200];
        }
    }
}
namespace backend\models {
    class TzSystemsUsers {
        public static $rows = [];
        public $uid = 10001, $tz_system_id = 3, $proxy_type = 1;
        public $is_use_proxy = 1, $is_proxy_bet = 1, $is_proxy_login = 1;
        public function hasAttribute($key) { return property_exists($this, $key); }
        public static function find() { return new AccountQuery(); }
    }
    class AccountQuery {
        private $where = [];
        public function where($where) { $this->where = $where; return $this; }
        public function andWhere($where) { $this->where += $where; return $this; }
        public function limit($limit) { return $this; }
        public function all() {
            return array_values(array_filter(TzSystemsUsers::$rows, function ($row) {
                foreach ($this->where as $key=>$value) { if ($row->$key != $value) { return false; } }
                return true;
            }));
        }
    }
}
namespace {
    error_reporting(E_ALL & ~E_DEPRECATED); // Production uses PHP 7.4; legacy PHP 8 deprecations are unrelated.
    require __DIR__.'/../backend/service/BaseBetService.php';
    require __DIR__.'/../backend/service/BaseService.php';
    require __DIR__.'/../backend/service/BetService.php';
    use backend\models\TzSystemsUsers;
    use common\service\proxy\ProxyBaseService;
    $switch = new ReflectionMethod(\backend\service\BetService::class, 'switchProxyAfterBetFailure');
    $switch->setAccessible(true);
    $kuai = new TzSystemsUsers();
    $mihomo = clone $kuai;
    $mihomo->tz_system_id = 9;
    $mihomo->proxy_type = 4;
    TzSystemsUsers::$rows = [$kuai, $mihomo];
    $run = function ($context, $response = ['code'=>309, 'msg'=>'Proxy timeout']) use ($switch) {
        ProxyBaseService::$calls = [];
        return $switch->invoke(null, $response, $context);
    };
    $check = function ($condition, $label) {
        if (!$condition) { throw new RuntimeException($label); }
        echo "PASS: $label\n";
    };
    $run(['uid'=>10001, 'tz_system_id'=>9]);
    $check(ProxyBaseService::$calls === [], 'Mihomo missing-IP error cannot clear another provider');
    $run(['uid'=>10001]);
    $check(ProxyBaseService::$calls === [], 'ambiguous user without site cannot mutate provider state');
    $run(['uid'=>10001, 'tz_system_id'=>99]);
    $check(ProxyBaseService::$calls === [], 'unknown account cannot mutate provider state');
    $run(['uid'=>10001, 'tz_system_id'=>3]);
    $check(ProxyBaseService::$calls === [['get', 1], ['clear', 1, '192.0.2.1:8888']],
        'legacy provider recovery remains available for exact account');
    $kuai->is_use_proxy = 0;
    $run(['uid'=>10001, 'tz_system_id'=>3]);
    $check(ProxyBaseService::$calls === [], 'direct account cannot clear enabled sibling provider');
    $kuai->is_use_proxy = 1;
    $kuai->is_proxy_bet = 0;
    $run(['uid'=>10001, 'tz_system_id'=>3]);
    $check(ProxyBaseService::$calls === [], 'disabled bet scene cannot clear provider');
    $run([], ['code'=>309, 'uid'=>10001, 'tz_system_id'=>9, 'msg'=>'Proxy timeout']);
    $check(ProxyBaseService::$calls === [], 'response site identity scopes recovery too');
    $kuai->is_proxy_bet = 1;
    $kuai->proxy_type = 0;
    $run(['uid'=>10001, 'tz_system_id'=>3]);
    $check(ProxyBaseService::$calls[0] === ['get', 2], 'inherited provider resolves global configuration');
    TzSystemsUsers::$rows = [$kuai];
    $run(['uid'=>10001]);
    $check(ProxyBaseService::$calls[0] === ['get', 2], 'unambiguous legacy UID request retains recovery');
    TzSystemsUsers::$rows = [$kuai, $mihomo];
    ProxyBaseService::$calls = [];
    $result = \backend\service\BetService::requestBetWithRetry(function ($attempt) {
        if ($attempt === 1) { throw new RuntimeException('Proxy timeout'); }
        return ['success'=>true];
    }, ['uid'=>10001, 'tz_system_id'=>9], 2, 0);
    $check($result['success'] && $result['bet_retry_attempts'] === 2 && ProxyBaseService::$calls === [],
        'real retry loop normalizes error and preserves sibling provider');
    echo "All account retry regressions passed. No business requests were made.\n";
}
