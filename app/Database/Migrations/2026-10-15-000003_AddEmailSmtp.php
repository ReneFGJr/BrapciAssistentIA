<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailSmtp extends Migration
{
    public function up()
    {
        $this->forge->addColumn('email_user', [
            'smtp_host' => ['type' => 'VARCHAR', 'constraint' => 253, 'default' => ''],
            'smtp_port' => ['type' => 'INT', 'unsigned' => true, 'default' => 587],
            'smtp_security' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'tls'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('email_user', ['smtp_host', 'smtp_port', 'smtp_security']);
    }
}
