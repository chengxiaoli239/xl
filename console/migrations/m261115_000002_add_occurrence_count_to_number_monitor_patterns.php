<?php

use yii\db\Migration;

class m261115_000002_add_occurrence_count_to_number_monitor_patterns extends Migration
{
    public function safeUp()
    {
        $tableName = $this->db->tablePrefix . 'number_monitor_patterns';
        $schema = $this->db->schema->getTableSchema($tableName, true);
        if ($schema && !isset($schema->columns['occurrence_count'])) {
            $this->addColumn('{{%number_monitor_patterns}}', 'occurrence_count',
                $this->integer()->unsigned()->notNull()->defaultValue(1));
        }
    }

    public function safeDown()
    {
        $tableName = $this->db->tablePrefix . 'number_monitor_patterns';
        $schema = $this->db->schema->getTableSchema($tableName, true);
        if ($schema && isset($schema->columns['occurrence_count'])) {
            $this->dropColumn('{{%number_monitor_patterns}}', 'occurrence_count');
        }
    }
}
