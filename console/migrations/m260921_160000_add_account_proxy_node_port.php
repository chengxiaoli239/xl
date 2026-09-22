<?php
use yii\db\Migration;

class m260921_160000_add_account_proxy_node_port extends Migration
{
    public function safeUp()
    {
        $table = '{{%tz_systems_users}}';
        if ($this->db->getTableSchema($table, true)->getColumn('proxy_node_port') === null) {
            $this->addColumn($table, 'proxy_node_port', $this->integer()->notNull()->defaultValue(0)
                ->comment('独立Mihomo节点端口，0未配置'));
        }
    }

    public function safeDown()
    {
        // Reverting while any account uses this provider would break live routing.
        if ((new \yii\db\Query())->from('{{%tz_systems_users}}')->where(['proxy_type'=>4])->exists($this->db)) {
            return false;
        }
        $this->dropColumn('{{%tz_systems_users}}', 'proxy_node_port');
    }
}
