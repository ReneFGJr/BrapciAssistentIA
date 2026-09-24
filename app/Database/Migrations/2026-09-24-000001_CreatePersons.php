<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePersons extends Migration
{
    public function up()
    {
        $fields = [
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nickname' => ['type' => 'VARCHAR', 'constraint' => 150],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 255],
        ];
        foreach (['cpf' => 14, 'phone_1' => 30, 'phone_2' => 30, 'email_1' => 254, 'email_2' => 254, 'institution' => 255] as $name => $length) {
            $fields[$name] = ['type' => 'VARCHAR', 'constraint' => $length, 'null' => true];
        }
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('nickname');
        $this->forge->createTable('persons');
    }

    public function down()
    {
        $this->forge->dropTable('persons');
    }
}