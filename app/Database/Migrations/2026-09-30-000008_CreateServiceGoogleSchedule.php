<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateServiceGoogleSchedule extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 191],
            'service_id' => ['type' => 'INT', 'unsigned' => true],
            'subject_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            'google_event_id' => ['type' => 'VARCHAR', 'constraint' => 255],
            'calendar_id' => ['type' => 'VARCHAR', 'constraint' => 254],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT'],
            'location' => ['type' => 'TEXT'],
            'starts_at' => ['type' => 'DATETIME'],
            'ends_at' => ['type' => 'DATETIME'],
            'all_day' => ['type' => 'BOOLEAN', 'default' => false],
            'timezone' => ['type' => 'VARCHAR', 'constraint' => 100],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'synced_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['service_id', 'google_event_id']);
        $this->forge->addKey(['user_id', 'starts_at']);
        $this->forge->addForeignKey('service_id', 'user_services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('service_google_schedule');
    }
    public function down()
    {
        $this->forge->dropTable('service_google_schedule');
    }
}
