<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePersonsUser extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'person_id' => ['type' => 'INT', 'unsigned' => true],
            'user_own' => ['type' => 'VARCHAR', 'constraint' => 191],
            'user' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 254, 'null' => true],
            'access_level' => ['type' => 'VARCHAR', 'constraint' => 10],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['person_id', 'user']);
        $this->forge->addUniqueKey(['person_id', 'email']);
        $this->forge->addKey('user');
        $this->forge->addKey('email');
        $this->forge->addForeignKey('person_id', 'persons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('persons_user');
    }

    public function down()
    {
        $this->forge->dropTable('persons_user');
    }
}