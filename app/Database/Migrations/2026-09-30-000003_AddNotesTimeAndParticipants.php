<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddNotesTimeAndParticipants extends Migration
{
    public function up()
    {
        $this->forge->addColumn('notes', [
            'meeting_time' => ['type' => 'TIME', 'null' => true],
        ]);
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'note_id' => ['type' => 'INT', 'unsigned' => true],
            'person_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['note_id', 'person_id']);
        $this->forge->addForeignKey('note_id', 'notes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('person_id', 'persons', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('note_participants');
    }
    public function down()
    {
        $this->forge->dropTable('note_participants');
        $this->forge->dropColumn('notes', 'meeting_time');
    }
}
