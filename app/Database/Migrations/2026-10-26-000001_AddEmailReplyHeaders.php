<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailReplyHeaders extends Migration
{
    public function up()
    {
        $this->forge->addColumn('email_user_row', [
            'email_cc' => ['type' => 'TEXT', 'null' => true],
            'email_reply_to' => ['type' => 'TEXT', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('email_user_row', ['email_cc', 'email_reply_to']);
    }
}
