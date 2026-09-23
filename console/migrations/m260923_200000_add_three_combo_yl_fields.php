<?php

use yii\db\Migration;

class m260923_200000_add_three_combo_yl_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%ssc_static_yl}}', 'codes', $this->text()->null()->comment('统计号码集合'));
        $this->addColumn('{{%ssc_static_yl}}', 'stat_last_index_id', $this->integer()->notNull()->defaultValue(0)->comment('统计到的开奖顺序ID'));
        $this->createIndex('idx_ssc_static_yl_lottery_type_status', '{{%ssc_static_yl}}', ['lottery_type', 'type', 'status']);
        $this->createIndex('idx_ssc_kj_data_lottery_index', '{{%ssc_kj_data}}', ['lottery_type', 'index_id']);
        $this->createIndex('idx_ssc_kj_data_lottery_date_hz', '{{%ssc_kj_data}}', ['lottery_type', 'date', 'codes_4nums_hz']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_ssc_kj_data_lottery_date_hz', '{{%ssc_kj_data}}');
        $this->dropIndex('idx_ssc_kj_data_lottery_index', '{{%ssc_kj_data}}');
        $this->dropIndex('idx_ssc_static_yl_lottery_type_status', '{{%ssc_static_yl}}');
        $this->dropColumn('{{%ssc_static_yl}}', 'stat_last_index_id');
        $this->dropColumn('{{%ssc_static_yl}}', 'codes');
    }
}
