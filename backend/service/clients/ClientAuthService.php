<?php

namespace backend\service\clients;

use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use common\models\AdminModel;
use Yii;

class ClientAuthService
{
    const MAX_FAILURES = 5;
    const LOCK_SECONDS = 300;

    public static function login(string $username, string $password, string $ip = ''): array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return ['status' => 422, 'msg' => '请输入后台账号和密码'];
        }

        $failureKeys = self::failureKeys($username, $ip);
        $failures = array_map(static function (string $key): int {
            return (int)Yii::$app->cache->get($key);
        }, $failureKeys);
        if (max($failures) >= self::MAX_FAILURES) {
            return ['status' => 429, 'msg' => '登录失败次数过多，请5分钟后再试'];
        }

        $user = AdminModel::findByUsername($username);
        if (!$user || !$user->validatePassword($password)) {
            foreach ($failureKeys as $index => $failureKey) {
                Yii::$app->cache->set($failureKey, $failures[$index] + 1, self::LOCK_SECONDS);
            }
            return ['status' => 401, 'msg' => '后台账号或密码错误'];
        }

        $accounts = self::getAccounts((int)$user->id);
        if (!$accounts) {
            return ['status' => 403, 'msg' => '该后台账号没有可用的本地下注账号'];
        }

        foreach ($failureKeys as $failureKey) {
            Yii::$app->cache->delete($failureKey);
        }
        return [
            'status' => 200,
            'msg' => '登录成功',
            'data' => [
                'username' => $user->username,
                'access_token' => $accounts[0]['access_token'],
                'account' => $accounts[0],
                'accounts' => $accounts,
            ],
        ];
    }

    public static function validateToken(string $accessToken): array
    {
        $accessToken = trim($accessToken);
        if ($accessToken === '') {
            return ['status' => 401, 'msg' => '登录凭证为空'];
        }

        $account = TzSystemsUsers::find()
            ->alias('t')
            ->select([
                't.id',
                't.uid',
                't.username',
                't.account',
                't.sys_name',
                't.access_token',
                't.is_local_bet',
                't.is_auto_login',
                't.is_auto_bet',
            ])
            ->innerJoin(['a' => AdminModel::tableName()], 'a.id = t.uid AND a.status = :adminStatus', [
                ':adminStatus' => AdminModel::STATUS_ACTIVE,
            ])
            ->where(['t.access_token' => $accessToken, 't.status' => 1])
            ->asArray()
            ->one();

        if (!$account) {
            return ['status' => 401, 'msg' => '登录凭证已失效，请重新登录'];
        }

        foreach (['id', 'uid', 'is_local_bet', 'is_auto_login', 'is_auto_bet'] as $field) {
            $account[$field] = (int)$account[$field];
        }

        return ['status' => 200, 'msg' => '凭证有效', 'data' => ['account' => $account]];
    }

    public static function enableLocalBetting(string $accessToken): array
    {
        $accessToken = trim($accessToken);
        if ($accessToken === '') {
            return ['status' => 401, 'msg' => '登录凭证为空'];
        }

        $account = TzSystemsUsers::find()
            ->alias('t')
            ->innerJoin(['a' => AdminModel::tableName()], 'a.id = t.uid AND a.status = :adminStatus', [
                ':adminStatus' => AdminModel::STATUS_ACTIVE,
            ])
            ->where(['t.access_token' => $accessToken, 't.status' => 1])
            ->one();
        if (!$account) {
            return ['status' => 401, 'msg' => '登录凭证已失效，请重新登录'];
        }

        $switchResult = TzSystemUsersService::switchBetLocation($account, BetsBackend::BET_TYPE_LOCAL_API);
        if(($switchResult['status'] ?? 500) !== 200){
            return $switchResult;
        }

        return [
            'status' => 200,
            'msg' => $switchResult['msg'],
            'data' => [
                'account' => [
                    'id' => (int)$account->id,
                    'uid' => (int)$account->uid,
                    'username' => $account->username,
                    'account' => $account->account,
                    'sys_name' => $account->sys_name,
                    'access_token' => $account->access_token,
                    'is_local_bet' => (int)$account->is_local_bet,
                    'is_auto_login' => (int)$account->is_auto_login,
                    'is_auto_bet' => (int)$account->is_auto_bet,
                ],
            ],
        ];
    }

    private static function getAccounts(int $uid): array
    {
        $accounts = TzSystemsUsers::find()
            ->select([
                'id',
                'uid',
                'username',
                'account',
                'sys_name',
                'access_token',
                'is_local_bet',
                'is_auto_login',
                'is_auto_bet',
            ])
            ->where(['uid' => $uid, 'status' => 1])
            ->andWhere(['<>', 'access_token', ''])
            ->orderBy(['is_auto_bet' => SORT_DESC, 'id' => SORT_ASC])
            ->asArray()
            ->all();

        return array_map(static function (array $account): array {
            $account['id'] = (int)$account['id'];
            $account['uid'] = (int)$account['uid'];
            $account['is_local_bet'] = (int)$account['is_local_bet'];
            $account['is_auto_login'] = (int)$account['is_auto_login'];
            $account['is_auto_bet'] = (int)$account['is_auto_bet'];
            return $account;
        }, $accounts);
    }

    private static function failureKeys(string $username, string $ip): array
    {
        return [
            'lucky5_client_login_user_' . hash('sha256', strtolower($username)),
            'lucky5_client_login_ip_' . hash('sha256', $ip),
        ];
    }
}
