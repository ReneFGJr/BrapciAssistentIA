<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserLogins extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'user_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('user_id');
        $this->forge->createTable('user_logins', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_logins', true);
    }
}
