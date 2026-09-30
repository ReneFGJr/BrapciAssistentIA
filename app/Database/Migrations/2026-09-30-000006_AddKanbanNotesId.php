<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddKanbanNotesId extends Migration
{
    public function up()
    {
        $this->forge->addColumn('kanban_items', [
            'notes_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('notes_id');
        $this->forge->processIndexes('kanban_items');
    }
    public function down()
    {
        $this->forge->dropColumn('kanban_items', 'notes_id');
    }
}
