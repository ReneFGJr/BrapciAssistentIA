<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddScheduleLinks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('service_google_schedule', [
            'meeting_url' => ['type' => 'TEXT', 'null' => true],
            'google_url' => ['type' => 'TEXT', 'null' => true],
        ]);
    }
    public function down()
    {
        $this->forge->dropColumn('service_google_schedule', ['meeting_url', 'google_url']);
    }
}
