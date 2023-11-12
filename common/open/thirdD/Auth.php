<?php
namespace common\open\thirdD;

use Yii;
use common\exceptions\SystemException;
use common\models\OpenOauth;
use common\open\chaoti\api\CtOauthApi;
use common\services\cache\CacheKeyService;
use common\tools\Dingtalk;

class Auth
{
    /**
     * 获取授权码
     * @param  array  $params 附加参数
     * @return array
     */
    public static function accessToken(array $params = []): array
    {
        $appId = $params['appId'] ?? '';
        if (empty($appId)) {
            throw_info('APPID不能为空');
        }

        $key = CacheKeyService::chaoti($appId);
        
        if (empty($data = get_cache()->get($key))) {   // 读缓存
            // 读数据库
            $data = OpenOauth::find()
                ->where([
                    'type' => OpenOauth::TYPE_CHAOTI,
                    'open_id' => $appId
                ])
                ->select([
                    'access_token accessToken',
                    'expires_in accessTokenExpiresAt',
                    'refresh_token refreshToken',
                    'refresh_expire_time refreshTokenExpiresAt',
                    'open_id sellerId'
                ])
                ->limit(1)->asArray()->one();
            
            $now = time();
            $accessTokenExpiresAt = $data['accessTokenExpiresAt'] ?? 0;
            $refreshTokenExpiresAt = $data['refreshTokenExpiresAt'] ?? 0;

            if ($accessTokenExpiresAt > $now) {
                // 缓存到 refreshToken 过期
                $exTime = $data['refreshTokenExpiresAt'] - $now - 300;
                get_cache()->setex($key, $exTime, $data);
            }
        }

        if (!empty($data)) {
            $now = time();
            $accessTokenExpiresAt = $data['accessTokenExpiresAt'] ?? 0;
            $refreshTokenExpiresAt = $data['refreshTokenExpiresAt'] ?? 0;
            // accessToken未过期
            if ($accessTokenExpiresAt > $now) {
                return $data;
            }
            // refreshToken未过期，刷新
            if ($refreshTokenExpiresAt > $now) {
                try {
                    $data = CtOauthApi::refreshToken($appId, $data['refreshToken']);
                    $data['accessTokenExpiresAt'] = intval($data['accessTokenExpiresAt'] / 1000);
                    $data['refreshTokenExpiresAt'] = intval($data['refreshTokenExpiresAt'] / 1000);
                    self::saveAccessToken($appId, $data);
            
                    return $data;
                } catch (\Exception $e) {
                    throw_info('未授权');
                }
            }
        }

        $data = CtOauthApi::getAccessToken($appId);
        $data['accessTokenExpiresAt'] = intval($data['accessTokenExpiresAt'] / 1000);
        $data['refreshTokenExpiresAt'] = intval($data['refreshTokenExpiresAt'] / 1000);
        self::saveAccessToken($appId, $data);

        return $data;
    }

    /**
     * 保存 AccessToken
     * @param  string  $appId APPID
     * @param  array    $data    参数
     */
    public static function saveAccessToken($appId, $data)
    {
        $key = CacheKeyService::chaoti($appId);
        $now = time();

        // 缓存到 refreshToken 过期
        $exTime = $data['refreshTokenExpiresAt'] - $now - 300;
        get_cache()->setex($key, $exTime, $data);

        // 入库
        $model = OpenOauth::find()->where([
            'type' => OpenOauth::TYPE_CHAOTI,
            'open_id' => $appId
        ])->one();

        if (empty($model)) {
            $model = new OpenOauth();
            $model->type = OpenOauth::TYPE_CHAOTI;
            $model->open_id = $appId;
        }

        $model->access_token = $data['accessToken'];
        $model->expires_in = $data['accessTokenExpiresAt'];
        $model->refresh_token = $data['refreshToken'];
        $model->refresh_expire_time = $data['refreshTokenExpiresAt'];
        $model->update_time = $now;
        $model->save(false);
    }

}
