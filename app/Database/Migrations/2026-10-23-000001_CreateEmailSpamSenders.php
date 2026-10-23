<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateEmailSpamSenders extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'sender' => ['type' => 'VARCHAR', 'constraint' => 254],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'sender']);
        $this->forge->createTable('email_spam_senders');
    }
    public function down() { $this->forge->dropTable('email_spam_senders'); }
}
