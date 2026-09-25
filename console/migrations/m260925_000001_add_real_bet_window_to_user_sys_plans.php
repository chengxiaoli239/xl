<?php

use yii\db\Migration;

class m260925_000001_add_real_bet_window_to_user_sys_plans extends Migration
{
    public function safeUp()
    {
        $table = '{{%user_sys_plans}}';
        $this->addColumn($table, 'real_bet_start_time', $this->string(5)->null()->comment('真实投注开始时间 HH:MM，空为不限制'));
        $this->addColumn($table, 'real_bet_end_time', $this->string(5)->null()->comment('真实投注结束时间 HH:MM，空为不限制'));
    }

    public function safeDown()
    {
        $table = '{{%user_sys_plans}}';
        $this->dropColumn($table, 'real_bet_end_time');
        $this->dropColumn($table, 'real_bet_start_time');
    }
}
