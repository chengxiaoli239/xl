<?php

use yii\db\Migration;

/**
 * 计划利润统计分组：分组表 + 分组内计划（每组最多 20 个计划，每个计划仅能属于一个分组）
 */
class m260323_120000_create_plan_profit_stat_groups extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%plan_profit_stat_groups}}', [
            'id' => $this->primaryKey(),
            'uid' => $this->integer()->notNull()->comment('所属用户'),
            'lottery_type' => $this->integer()->notNull()->comment('彩种类型，与计划列表一致'),
            'name' => $this->string(64)->notNull()->comment('分组名称'),
            'created_at' => $this->integer()->notNull()->defaultValue(0),
            'updated_at' => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);
        $this->createIndex('idx_ppsg_uid_lottery', '{{%plan_profit_stat_groups}}', ['uid', 'lottery_type']);

        $this->createTable('{{%plan_profit_stat_group_plans}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer()->notNull()->comment('分组id'),
            'plan_id' => $this->integer()->notNull()->comment('计划id'),
            'created_at' => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);
        $this->createIndex('idx_ppsgp_group', '{{%plan_profit_stat_group_plans}}', 'group_id');
        $this->createIndex('uk_ppsgp_plan', '{{%plan_profit_stat_group_plans}}', 'plan_id', true);
        $this->createIndex('uk_ppsgp_group_plan', '{{%plan_profit_stat_group_plans}}', ['group_id', 'plan_id'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%plan_profit_stat_group_plans}}');
        $this->dropTable('{{%plan_profit_stat_groups}}');
    }
}
