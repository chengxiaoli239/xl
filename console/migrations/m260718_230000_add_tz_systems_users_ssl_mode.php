<?php

use yii\db\Migration;

/**
 * Adds an account-level TLS mode without changing existing request behavior.
 */
class m260718_230000_add_tz_systems_users_ssl_mode extends Migration
{
    public function safeUp()
    {
        $table = '{{%tz_systems_users}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema->getColumn('ssl_mode') === null) {
            $this->addColumn(
                $table,
                'ssl_mode',
                $this->tinyInteger(1)
                    ->notNull()
                    ->defaultValue(0)
                    ->comment('TLS模式:0继承全局1自动2 TLS1.2 3兼容TLS')
                    ->after('proxy_type')
            );
        }
    }

    public function safeDown()
    {
        $table = '{{%tz_systems_users}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema->getColumn('ssl_mode') !== null) {
            $this->dropColumn($table, 'ssl_mode');
        }
    }
}
