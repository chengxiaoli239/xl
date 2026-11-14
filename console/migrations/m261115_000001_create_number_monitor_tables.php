<?php

use yii\db\Migration;

class m261115_000001_create_number_monitor_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%number_monitor_files}}', [
            'id' => $this->primaryKey()->unsigned(),
            'uid' => $this->integer()->notNull(),
            'lottery_type' => $this->smallInteger()->notNull(),
            'name' => $this->string(180)->notNull(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1)->comment('0暂停1监控'),
            'scheme_count' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="独立号码监控文件"');
        $this->createIndex('idx_number_monitor_files_owner', '{{%number_monitor_files}}', ['uid', 'lottery_type', 'status', 'id']);

        $this->createTable('{{%number_monitor_schemes}}', [
            'id' => $this->primaryKey()->unsigned(),
            'file_id' => $this->integer()->unsigned()->notNull(),
            'uid' => $this->integer()->notNull(),
            'lottery_type' => $this->smallInteger()->notNull(),
            'line_no' => $this->smallInteger()->notNull(),
            'title' => $this->string(80)->notNull(),
            'sample' => $this->string(160)->notNull()->defaultValue(''),
            'fixed_count' => $this->tinyInteger()->notNull(),
            'pattern_count' => $this->smallInteger()->notNull(),
            'started_draw_id' => $this->bigInteger()->notNull()->defaultValue(0),
            'last_draw_id' => $this->bigInteger()->notNull()->defaultValue(0),
            'last_qihao' => $this->string(24)->null(),
            'current_status' => $this->tinyInteger()->notNull()->defaultValue(0)->comment('0等待1中2错'),
            'streak_count' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="号码监控方案状态"');
        $this->createIndex('idx_number_monitor_schemes_file', '{{%number_monitor_schemes}}', ['file_id', 'id']);
        $this->createIndex('idx_number_monitor_schemes_owner', '{{%number_monitor_schemes}}', ['uid', 'lottery_type', 'file_id']);

        $this->createTable('{{%number_monitor_patterns}}', [
            'id' => $this->primaryKey()->unsigned(),
            'scheme_id' => $this->integer()->unsigned()->notNull(),
            'pattern' => $this->char(4)->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT="监控方案号码索引"');
        $this->createIndex('uq_number_monitor_pattern_scheme', '{{%number_monitor_patterns}}', ['scheme_id', 'pattern'], true);
        $this->createIndex('idx_number_monitor_pattern_match', '{{%number_monitor_patterns}}', ['pattern', 'scheme_id']);

        $this->createTable('{{%number_monitor_cursors}}', [
            'id' => $this->primaryKey()->unsigned(),
            'lottery_type' => $this->smallInteger()->notNull(),
            'last_draw_id' => $this->bigInteger()->notNull()->defaultValue(0),
            'updated_at' => $this->integer()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="号码监控开奖处理游标"');
        $this->createIndex('uq_number_monitor_cursor_lottery', '{{%number_monitor_cursors}}', ['lottery_type'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%number_monitor_patterns}}');
        $this->dropTable('{{%number_monitor_schemes}}');
        $this->dropTable('{{%number_monitor_files}}');
        $this->dropTable('{{%number_monitor_cursors}}');
    }
}
