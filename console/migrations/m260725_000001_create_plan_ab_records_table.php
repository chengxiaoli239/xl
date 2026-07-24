<?php

use yii\db\Migration;

/**
 * Stores per-period A-signal state separately from real B betting records.
 */
class m260725_000001_create_plan_ab_records_table extends Migration
{
    public function safeUp()
    {
        $table = '{{%plan_ab_records}}';
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($table, [
            'id' => $this->primaryKey(),
            'plan_id' => $this->integer()->notNull()->comment('计划id'),
            'uid' => $this->integer()->notNull()->defaultValue(0)->comment('用户id'),
            'lottery_type' => $this->integer()->notNull()->defaultValue(8)->comment('彩种'),
            'qihao' => $this->string(64)->notNull()->comment('开奖期号'),
            'kj_codes' => $this->string(64)->null()->comment('开奖号码'),
            'a_hit' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('A是否命中'),
            'b_hit' => $this->tinyInteger(1)->null()->defaultValue(null)->comment('B是否命中，仅用于展示'),
            'is_bet' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('本期是否真实产生B投注'),
            'bet_record_id' => $this->integer()->notNull()->defaultValue(0)->comment('真实投注记录id'),
            'bet_codes' => $this->text()->null()->comment('实际投注B号码'),
            'bet_status' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('B投注状态0未下注1待开奖2中奖3未中奖'),
            'strategy_status_before' => $this->tinyInteger(1)->notNull()->defaultValue(1)->comment('处理前状态1等待2下注'),
            'strategy_status_after' => $this->tinyInteger(1)->notNull()->defaultValue(1)->comment('处理后状态1等待2下注'),
            'strategy_action' => $this->string(64)->notNull()->defaultValue('')->comment('策略动作'),
            'single' => $this->decimal(12, 2)->notNull()->defaultValue(0)->comment('本期倍数'),
            'singles_key' => $this->integer()->notNull()->defaultValue(0)->comment('本期倍数档位'),
            'created_at' => $this->integer()->notNull()->defaultValue(0),
            'updated_at' => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->createIndex('uk_plan_ab_qihao', $table, ['plan_id', 'qihao'], true);
        $this->createIndex('idx_plan_ab_uid', $table, ['uid', 'id']);
        $this->createIndex('idx_plan_ab_lottery', $table, ['lottery_type', 'id']);
        $this->createIndex('idx_plan_ab_bet_record', $table, 'bet_record_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%plan_ab_records}}');
    }
}
