<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailUserRow extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'email_user_id' => ['type' => 'INT', 'unsigned' => true],
            'remote_key' => ['type' => 'VARCHAR', 'constraint' => 64],
            'message_id' => ['type' => 'TEXT', 'null' => true],
            'email_from' => ['type' => 'TEXT'],
            'email_to' => ['type' => 'TEXT'],
            'email_title' => ['type' => 'TEXT'],
            'email_context' => ['type' => 'LONGTEXT'],
            'email_data' => ['type' => 'DATETIME', 'null' => true],
            'email_status' => ['type' => 'TINYINT', 'default' => 0],
            'has_attachments' => ['type' => 'TINYINT', 'default' => 0],
            'size_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'folder' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'INBOX'],
            'created' => ['type' => 'DATETIME'],
            'lastupdated' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['email_user_id', 'remote_key']);
        $this->forge->addKey(['user_id', 'email_user_id', 'email_data']);
        $this->forge->addForeignKey('email_user_id', 'email_user', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('email_user_row');
    }

    public function down()
    {
        $this->forge->dropTable('email_user_row');
    }
}
