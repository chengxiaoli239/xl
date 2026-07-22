<?php

use yii\db\Migration;

/**
 * Adds account-level proxy switches for login and non-login requests.
 */
class m260722_011000_add_tz_systems_users_proxy_scene_switches extends Migration
{
    public function safeUp()
    {
        $table = '{{%tz_systems_users}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema->getColumn('is_proxy_login') === null) {
            $this->addColumn(
                $table,
                'is_proxy_login',
                $this->tinyInteger(1)
                    ->notNull()
                    ->defaultValue(1)
                    ->comment('登录接口使用代理:0否1是')
                    ->after('is_use_proxy')
            );
        }

        if ($schema->getColumn('is_proxy_bet') === null) {
            $this->addColumn(
                $table,
                'is_proxy_bet',
                $this->tinyInteger(1)
                    ->notNull()
                    ->defaultValue(1)
                    ->comment('非登录/下注接口使用代理:0否1是')
                    ->after('is_proxy_login')
            );
        }
    }

    public function safeDown()
    {
        $table = '{{%tz_systems_users}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema->getColumn('is_proxy_bet') !== null) {
            $this->dropColumn($table, 'is_proxy_bet');
        }

        if ($schema->getColumn('is_proxy_login') !== null) {
            $this->dropColumn($table, 'is_proxy_login');
        }
    }
}
