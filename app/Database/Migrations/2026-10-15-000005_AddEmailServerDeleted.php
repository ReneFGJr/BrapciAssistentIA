<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailServerDeleted extends Migration
{
    public function up()
    {
        $this->forge->addColumn('email_user_row', ['server_deleted_at' => ['type' => 'DATETIME', 'null' => true]]);
    }

    public function down()
    {
        $this->forge->dropColumn('email_user_row', 'server_deleted_at');
    }
}
