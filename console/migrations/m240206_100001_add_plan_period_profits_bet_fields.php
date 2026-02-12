<?php

use yii\db\Migration;

/**
 * 为已存在的 plan_period_profits 表增加：本期投注金额、组数、倍数
 */
class m240206_100001_add_plan_period_profits_bet_fields extends Migration
{
    public function safeUp()
    {
        $table = '{{%plan_period_profits}}';
        $this->addColumn($table, 'period_bet_amount', $this->decimal(12, 2)->notNull()->defaultValue(0)->comment('本期投注金额'));
        $this->addColumn($table, 'period_group_count', $this->integer()->notNull()->defaultValue(0)->comment('本期组数(号码个数)'));
        $this->addColumn($table, 'period_multiple', $this->decimal(12, 2)->notNull()->defaultValue(0)->comment('本期倍数'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%plan_period_profits}}', 'period_bet_amount');
        $this->dropColumn('{{%plan_period_profits}}', 'period_group_count');
        $this->dropColumn('{{%plan_period_profits}}', 'period_multiple');
    }
}
