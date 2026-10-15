<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailPreferred extends Migration
{
    public function up()
    {
        $this->forge->addColumn('email_user', ['is_preferred' => ['type' => 'TINYINT', 'default' => 0]]);
    }

    public function down()
    {
        $this->forge->dropColumn('email_user', 'is_preferred');
    }
}
