<?php

use yii\db\Migration;

/**
 * 计划每期盈利记录表（每期开奖后一条）
 */
class m240206_100000_create_plan_period_profits_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('{{%plan_period_profits}}', [
            'id' => $this->primaryKey(),
            'plan_id' => $this->integer()->notNull()->comment('计划id'),
            'qihao' => $this->string(64)->notNull()->comment('期号'),
            'profit_before' => $this->decimal(12, 2)->notNull()->defaultValue(0)->comment('开奖前累计盈利金额'),
            'profit_change' => $this->decimal(12, 2)->notNull()->defaultValue(0)->comment('本期盈亏金额（正=盈利，负=亏损）'),
            'profit_after' => $this->decimal(12, 2)->notNull()->defaultValue(0)->comment('开奖后累计盈利金额'),
            'period_bet_amount' => $this->decimal(12, 2)->notNull()->defaultValue(0)->comment('本期投注金额'),
            'period_group_count' => $this->integer()->notNull()->defaultValue(0)->comment('本期组数(号码个数)'),
            'period_multiple' => $this->decimal(12, 2)->notNull()->defaultValue(0)->comment('本期倍数'),
            'uid' => $this->integer()->notNull()->defaultValue(0)->comment('用户id'),
            'lottery_type' => $this->integer()->notNull()->defaultValue(8)->comment('彩种'),
            'created_at' => $this->integer()->notNull()->defaultValue(0)->comment('创建时间'),
        ], $tableOptions);
        $this->createIndex('uk_plan_qihao', '{{%plan_period_profits}}', ['plan_id', 'qihao'], true);
        $this->createIndex('idx_plan_id', '{{%plan_period_profits}}', 'plan_id');
        $this->createIndex('idx_qihao', '{{%plan_period_profits}}', 'qihao');
        $this->createIndex('idx_uid', '{{%plan_period_profits}}', 'uid');
    }

    public function safeDown()
    {
        $this->dropTable('{{%plan_period_profits}}');
    }
}
