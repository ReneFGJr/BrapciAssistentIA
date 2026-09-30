<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateUserServices extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'service' => ['type' => 'VARCHAR', 'constraint' => 20],
            'value' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'service']);
        $this->forge->createTable('user_services');
    }
    public function down()
    {
        $this->forge->dropTable('user_services');
    }
}
