<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateKanbanItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'todo'],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'normal'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->createTable('kanban_items');
    }

    public function down()
    {
        $this->forge->dropTable('kanban_items');
    }
}