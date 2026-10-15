<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailUser extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'email_user' => ['type' => 'VARCHAR', 'constraint' => 254],
            'pass_user' => ['type' => 'TEXT'],
            'mail_user' => ['type' => 'VARCHAR', 'constraint' => 253],
            'mail_port_user' => ['type' => 'INT', 'unsigned' => true],
            'protocol' => ['type' => 'VARCHAR', 'constraint' => 10],
            'security' => ['type' => 'VARCHAR', 'constraint' => 10],
            'lastupdated' => ['type' => 'DATETIME'],
            'created' => ['type' => 'DATETIME'],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'email_user']);
        $this->forge->createTable('email_user');
    }

    public function down()
    {
        $this->forge->dropTable('email_user');
    }
}
