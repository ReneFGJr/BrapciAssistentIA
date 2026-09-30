<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AllowNotesWithoutPerson extends Migration
{
    public function up()
    {
        foreach ($this->db->getFieldData('notes') as $field) {
            if ($field->name === 'person_id' && !$field->nullable) {
                $this->forge->modifyColumn('notes', ['person_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true]]);
            }
        }
        $this->forge->addColumn('notes', ['user_id' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true]]);
    }
    public function down()
    {
        if ($this->db->table('notes')->where('person_id', null)->countAllResults() > 0) {
            throw new \RuntimeException('Existem notas sem Person. A reversão removeria seu controle de acesso.');
        }
        $this->forge->modifyColumn('notes', ['person_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false]]);
        $this->forge->dropColumn('notes', 'user_id');
    }
}
