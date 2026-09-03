<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserNotes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
            ],
            'title_encrypted' => [
                'type' => 'TEXT',
            ],
            'content_encrypted' => [
                'type' => 'MEDIUMTEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey(['user_id', 'updated_at']);
        $this->forge->createTable('user_notes', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_notes', true);
    }
}
