<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddNotepadMeeting extends Migration
{
    public function up()
    {
        $this->forge->addColumn('user_notes', [
            'person_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'meeting_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
    }
    public function down()
    {
        $this->forge->dropColumn('user_notes', ['person_id', 'meeting_at']);
    }
}
