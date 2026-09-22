<?php

use yii\db\Migration;

class m260918_000001_add_bet_execution_times_to_bet_error_plans_task extends Migration
{
    public function safeUp()
    {
        $table = '{{%bet_error_plans_task}}';
        $this->addColumn($table, 'bet_started_at', $this->integer()->null()->comment('下注执行开始时间Unix时间戳'));
        $this->addColumn($table, 'bet_finished_at', $this->integer()->null()->comment('下注执行结束时间Unix时间戳'));
    }

    public function safeDown()
    {
        $table = '{{%bet_error_plans_task}}';
        $this->dropColumn($table, 'bet_finished_at');
        $this->dropColumn($table, 'bet_started_at');
    }
}
