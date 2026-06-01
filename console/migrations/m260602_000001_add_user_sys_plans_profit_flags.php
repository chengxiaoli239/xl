<?php

use yii\db\Migration;

/**
 * Adds profit-stat flags used by account profit reset/statistics.
 */
class m260602_000001_add_user_sys_plans_profit_flags extends Migration
{
    public function safeUp()
    {
        $table = '{{%user_sys_plans}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema->getColumn('is_profits_record') === null) {
            $this->addColumn($table, 'is_profits_record', $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('是否参与账号盈利统计0是1否')->after('is_batch_simulate'));
        }

        $schema = $this->db->getTableSchema($table, true);
        if ($schema->getColumn('is_area_profits') === null) {
            $this->addColumn($table, 'is_area_profits', $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('是否区间盈利记录0否1是')->after('is_profits_record'));
        }

        if (!$this->indexExists($table, 'idx_user_profits')) {
            $this->createIndex('idx_user_profits', $table, ['uid', 'is_profits_record', 'is_area_profits', 'status', 'is_test']);
        }
    }

    public function safeDown()
    {
        $table = '{{%user_sys_plans}}';

        if ($this->indexExists($table, 'idx_user_profits')) {
            $this->dropIndex('idx_user_profits', $table);
        }

        $schema = $this->db->getTableSchema($table, true);
        if ($schema->getColumn('is_area_profits') !== null) {
            $this->dropColumn($table, 'is_area_profits');
        }

        $schema = $this->db->getTableSchema($table, true);
        if ($schema->getColumn('is_profits_record') !== null) {
            $this->dropColumn($table, 'is_profits_record');
        }
    }

    private function indexExists($table, $name)
    {
        $rawTable = $this->db->schema->getRawTableName($table);
        $indexes = $this->db->createCommand('SHOW INDEX FROM ' . $this->db->quoteTableName($rawTable) . ' WHERE Key_name = :name', [
            ':name' => $name,
        ])->queryAll();

        return !empty($indexes);
    }
}
