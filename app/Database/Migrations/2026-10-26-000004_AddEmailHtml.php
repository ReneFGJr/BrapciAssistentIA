<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddEmailHtml extends Migration
{
    public function up()
    {
        $this->forge->addColumn('email_user_row', [
            'email_html' => ['type' => 'LONGTEXT', 'null' => true],
            'email_html_loaded' => ['type' => 'TINYINT', 'default' => 0],
        ]);
    }
    public function down()
    {
        $this->forge->dropColumn('email_user_row', ['email_html', 'email_html_loaded']);
    }
}
