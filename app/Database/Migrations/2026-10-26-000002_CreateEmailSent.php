<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailSent extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'email_user_id' => ['type' => 'INT', 'unsigned' => true],
            'original_id' => ['type' => 'INT', 'unsigned' => true],
            'original_message_id' => ['type' => 'TEXT', 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 20],
            'sender' => ['type' => 'TEXT'],
            'recipients' => ['type' => 'TEXT'],
            'cc' => ['type' => 'TEXT'],
            'subject' => ['type' => 'TEXT'],
            'body' => ['type' => 'LONGTEXT'],
            'reply_text' => ['type' => 'LONGTEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'sent_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'status', 'original_id']);
        $this->forge->createTable('email_sent');
    }

    public function down()
    {
        $this->forge->dropTable('email_sent');
    }
}
