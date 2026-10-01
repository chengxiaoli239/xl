<?php

use yii\db\Migration;
use yii\db\Query;

class m261001_100000_complete_ssc_2nums_values extends Migration
{
    public function safeUp()
    {
        $table = '{{%ssc_2nums_val}}';
        $existing = (new Query())->select('val')->from($table)->column($this->db);
        $existing = array_fill_keys(array_map('strval', $existing), true);
        $now = time();
        $rows = [];

        for ($first = 0; $first <= 9; $first++) {
            for ($second = $first; $second <= 9; $second++) {
                $val = (string)$first.$second;
                if (!isset($existing[$val])) {
                    $rows[] = [$val, $now, $now];
                }
            }
        }

        if ($rows) {
            $this->batchInsert($table, ['val', 'created_at', 'updated_at'], $rows);
        }
    }

    public function safeDown()
    {
        // The rows may predate this migration; never remove live omission definitions.
        return true;
    }
}
