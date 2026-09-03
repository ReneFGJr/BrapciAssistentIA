<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppsAndUserAppAccess extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 100],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500],
            'access_level' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('access_level');
        $this->forge->createTable('apps', true);

        $this->forge->addField([
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'app_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'granted_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey(['user_id', 'app_id']);
        $this->forge->addKey('app_id');
        $this->forge->addForeignKey('app_id', 'apps', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_app_access', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_app_access', true);
        $this->forge->dropTable('apps', true);
    }
}
