<?php

use yii\db\Migration;

/**
 * Uses automatic TLS negotiation for newly-created betting accounts.
 */
class m260718_231500_set_tz_systems_users_ssl_mode_default_auto extends Migration
{
    public function safeUp()
    {
        $table = '{{%tz_systems_users}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema->getColumn('ssl_mode') !== null) {
            $this->alterColumn(
                $table,
                'ssl_mode',
                $this->tinyInteger(1)
                    ->notNull()
                    ->defaultValue(1)
                    ->comment('TLS模式:0继承全局1自动2 TLS1.2 3兼容TLS')
            );
        }
    }

    public function safeDown()
    {
        $table = '{{%tz_systems_users}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema->getColumn('ssl_mode') !== null) {
            $this->alterColumn(
                $table,
                'ssl_mode',
                $this->tinyInteger(1)
                    ->notNull()
                    ->defaultValue(0)
                    ->comment('TLS模式:0继承全局1自动2 TLS1.2 3兼容TLS')
            );
        }
    }
}
