<?php
namespace common\service\config;

use Yii;
use common\models\config\Config;
use common\models\config\ConfigGroup;
use common\tools\UtilArray;
use yii\helpers\Json;

class ConfigService
{

    const CONFIG_CACHE_KEY = 'sp:config:all';

    public static function configSave($aParams = []): array
    {
        if (empty($aParams['id'])) {
            $Config = Config::findOne([
                'key' => $aParams['key']
            ]);
            if (!empty($Config)) {
                return [100004, '该键名已存在'];
            }
            $Config = new Config();
            $Config->create_time = time();
            $Config->admin_id = $aParams['admin_id'];
        } else {
            $Config = Config::find() ->where([ 'key' => $aParams['key'] ])->andWhere("id<>" . $aParams['id']) ->one();

            if (!empty($Config)) {
                return [100004, '该键名已存在'];
            }

            $Config = Config::findOne($aParams['id']);
            if (empty($Config)) {
                return [100004, '该记录不存在'];
            }
        }

        $Config->name = $aParams['name'];
        $Config->status = $aParams['status'];
        $Config->key = $aParams['key'];
        $Config->value = $aParams['value'];
        $Config->note = $aParams['note'];
        $Config->group_id = $aParams['group_id'];
        $Config->sort = $aParams['sort'];
        $Config->type = $aParams['type'];
        $Config->update_time = time();

        $res = $Config->save();

        if ($Config->status == Config::STATUS_N) {
            commonRedis()->hdel(self::CONFIG_CACHE_KEY, $Config->key);
        }

        if (empty($res)) {
            return [10004, '保存失败'];
        }
        self::updateConfigCache();
        return [0, '保存成功'];
    }

    public static function configDelete(array $ids = array())
    {
        $res = Config::deleteAll([
            'id' => $ids
        ]);
        if (empty($res)) {
            return [100002, '删除失败'];
        }
        self::updateConfigCache();
        return [0, '删除成功'];
    }

    public static function groupSave($aParams = array())
    {
        if (empty($aParams['key'])) {
            $aParams['key'] = md5(uniqid());
        }
        if (empty($aParams['id'])) {
            if (!empty($aParams['key'])) {
                $ConfigGroup = ConfigGroup::findOne([
                    'key' => $aParams['key']
                ]);
                if (!empty($ConfigGroup)) {
                    return [100004, '该键名已存在'];
                }
            }
            $ConfigGroup = new ConfigGroup();
            $ConfigGroup->create_time = time();
            $ConfigGroup->key = $aParams['key'];
            $ConfigGroup->admin_id = $aParams['admin_id'];
        } else {
            $ConfigGroup = ConfigGroup::find()
                ->where([
                    'key' => $aParams['key']
                ])->andWhere("id<>" . $aParams['id'])
                ->one();

            if (!empty($ConfigGroup)) {
                return [100004, '该键名已存在'];
            }
            $ConfigGroup = ConfigGroup::findOne($aParams['id']);
            if (empty($ConfigGroup)) {
                return [100003, '该记录不存在'];
            }
        }

        $ConfigGroup->name = $aParams['name'];
        $ConfigGroup->sort = $aParams['sort'];
        $ConfigGroup->key = $aParams['key'];
        $ConfigGroup->update_time = time();

        $res = $ConfigGroup->save();

        if (empty($res)) {
            return [100004, '分组保存失败'];
        }
        self::updateConfigCache();
        return [0, '分组保存成功'];
    }

    public static function groupDelete(array $ids = array())
    {
        $res = ConfigGroup::deleteAll([
            'id' => $ids
        ]);
        if (empty($res)) {
            return [100002, '删除分组失败'];
        }
        self::updateConfigCache();
        return [0, '删除成功'];
    }


    /**
     * [getConfigByKey description]
     * @param string $configKey [description]
     * @return [type]            [description]
     */
    public static function getConfigByKey(string $configKey)
    {
        $data = get_cache()->hget(self::CONFIG_CACHE_KEY, $configKey);
        if (empty($data)) {
            $data = self::getConfigList($configKey);
            self::updateConfigCache($data);
        }
        return $data['value'] ?? '';
    }

    public static function getConfigByRefundDescKey(string $configKey)
    {
        $data = get_cache()->hget(self::CONFIG_CACHE_KEY, $configKey);
        if (empty($data)) {
            $data = self::getConfigList($configKey);
            self::updateConfigCache($data);
        }
        return $data ?? '';
    }

    /**
     * [getConfigByGroupKey description]
     * @param string $groupKey [description]
     * @return [type]           [description]
     */
    public static function getConfigByGroupKey(string $groupKey)
    {
        $data = self::getConfigList('', $groupKey);
        $l_data = [];
        foreach ($data as $key => $value) {
            $l_data[$value['key']] = $value['value'];
        }
        return $l_data;
    }

    /**
     * 获取全部配置
     * @return [type] [description]
     */
    public static function getConfigList($configKey = '', $groupKey = '')
    {
        $gt = ConfigGroup::tableName();
        $ct = Config::tableName();
        $query = Config::find()
            ->join('LEFT JOIN', $gt, "{$gt}.id={$ct}.group_id")
            ->where([
                "{$ct}.status" => Config::STATUS_Y
            ]);
        if (!empty($configKey)) {
            $query->andWhere(["{$ct}.key" => $configKey]);
        }
        if (!empty($groupKey)) {
            $query->andWhere(["{$gt}.key" => $groupKey]);
        }
        $data = $query->select([
            "{$ct}.name",
            "{$ct}.key",
            "{$ct}.type",
            "{$ct}.value",
            "{$gt}.key group_key"
        ])
            ->orderBy("{$ct}.sort asc")
            ->asArray()
            ->all();
        foreach ($data as $key => &$value) {
            if (empty($value['group_key'])) {
                $value['group_key'] = 'common';
            }
            if ($value['type'] == Config::TYPE_ARRAY_JSON) {
                $value['value'] = json_decode($value['value'], true);
            }
            unset($value['type']);
        }

        return $data;
    }

    public static function updateConfigCache($data = [])
    {
        if (empty($data)) {
            $data = self::getConfigList();
        }
        foreach ($data as $k => $v) {
            if (empty($v['key'])) {
                continue;
            }
            get_cache()->hset(self::CONFIG_CACHE_KEY, $v['key'], $v);
        }
        get_cache()->expire(self::CONFIG_CACHE_KEY, 30 * 24 * 3600);
    }


    /**
     * [getConfigByKey description]
     * @param string $configKey [description]
     * @return [item]            [description]
     */
    public static function getConfigItemByKey(string $configKey)
    {
        if (empty($configKey)) {
            return '';
        }
        $data = get_cache()->hget(self::CONFIG_CACHE_KEY, $configKey);
        if (empty($data)) {
            $data = self::getConfigList($configKey);
            self::updateConfigCache($data);
        }
        return $data;
    }

    /**
     * @desc 会员购预售开关
     * @return int
     */
    public static function getShopYsStatus()
    {
        $status = \common\services\config\ConfigService::getConfigByKey($key = 'HTDF_YS_STATUS');

        return (int)$status;
    }

    public static function updateSwitchStatus($key = '', $status = 0)
    {
        $config = Config::findOne(['key' => trim($key)]);
        $now_time = time();
        if (empty($config)) {
            $config = new Config();
            $config->key = $key;
            $config->group_id = 9; # 通用配置
            $config->status = 2; # 启用状态
            $config->create_time = $now_time; # 时间
            $config->admin_id = UserService::getUserInfo($_COOKIE['token'])['id'];

        }
        $config->update_time = $now_time;
        $config->value = (int)$status ? 1 : 0;
        if (!$config->save()) {
            return ['code' => 10001, 'message' => Json::encode($config->getErrors())];
        }
        self::updateConfigCache($key);

        return ['code' => 0, 'message' => '操作成功'];
    }

    /**
     * @Notes:获取京东plus会员折扣配置
     * @Author:雇佣兵
     * @Date: 2023/2/21
     */
    public static function getJdPlusDiscountInfo($goodsInfo)
    {
        $platformId = Platform::getPlatformId();
        if ($platformId != Platform::ID_JD_MINI) {
            return [];
        }

        $isCommissionGoods = GoodsSkuService::isCommissionGoods($goodsInfo);
        if (!$isCommissionGoods) {
            return [];
        }
        $config = self::getConfigByKey('jd_plus_discount');

        if (empty($config)) $config = [];

        $config['text'] = self::getConfigByKey('jd_plus_discount_text');

        return $config;
    }

    /**
     * @param string $key 参数
     * @return array
     */
    public static function getOne($key)
    {
        $query = Config::find()
            ->where(['key' => $key])
            ->select([
                'name',
                'key',
                'type',
                'value'
            ]);

        $info = $query->asArray()->one();

        if (empty($info)) {
            return [];
        }

        if ($info['type'] == Config::TYPE_ARRAY_JSON) {
            $info['value'] = json_decode($info['value'], true);
        }

        return $info;
    }

}
