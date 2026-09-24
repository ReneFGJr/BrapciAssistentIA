<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddPersonPhoto extends Migration
{
    public function up()
    {
        $this->forge->addColumn('persons', [
            'photo' => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('persons', 'photo');
    }
}