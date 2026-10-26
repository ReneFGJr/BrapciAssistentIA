<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailMeetings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'email_user_id' => ['type' => 'INT', 'unsigned' => true],
            'message_id' => ['type' => 'INT', 'unsigned' => true],
            'service_id' => ['type' => 'INT', 'unsigned' => true],
            'request_key' => ['type' => 'VARCHAR', 'constraint' => 64],
            'google_event_id' => ['type' => 'VARCHAR', 'constraint' => 64],
            'payload' => ['type' => 'LONGTEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'event_url' => ['type' => 'TEXT', 'null' => true],
            'meet_url' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'request_key']);
        $this->forge->addKey(['user_id', 'message_id']);
        $this->forge->createTable('email_meetings');
    }

    public function down()
    {
        $this->forge->dropTable('email_meetings');
    }
}
