<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateNotes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'meeting_date' => ['type' => 'DATE'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'scheduled'],
            'person_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['meeting_date', 'id']);
        $this->forge->addKey('person_id');
        $this->forge->addForeignKey('person_id', 'persons', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('notes');
    }
    public function down()
    {
        $this->forge->dropTable('notes');
    }
}
