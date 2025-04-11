<?php
namespace yiitron\novakit\audit;
use yiitron\novakit\Migration;

class AuditTableMigration extends Migration
{
    protected function beginCommand($description)
    {
        return true;
    }
    protected function endCommand($time)
    {
        return true;
    }
    public function safeUp()
    {
        $this->createTable('{{%audit_trail}}', [
            'id' => $this->bigPrimaryKey(),
            'audit_time' => $this->integer()->notNull(),
            'model_name' => $this->string(100)->notNull(),
            'operation' => $this->string(32)->notNull(),
            'request_method' => $this->string(16)->notNull(),
            'field_name' => $this->string(32)->notNull(),
            'old_value' => $this->text(),
            'new_value' => $this->text(),
            'user_id' => $this->string(20)->notNull(),
            'duration' => $this->double()->notNull(),
            'memory_max' => $this->integer()->notNull(),
            'request_route' => $this->string()->notNull(),
            'headers' => $this->text(),
            'query_params' => $this->text(),
            'body_params' => $this->text(),
            'raw_body' => $this->text(),
            'url' => $this->text()->notNull(),
            'ip_address' => $this->string(20)->notNull(),
            'user_agent' => $this->string(),
            'is_deleted' => $this->integer(2)->notNull()->defaultValue(0),
            'status' => $this->integer(3)->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('audit_trail');
    }
}
