<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UseInstitutionReference extends Migration
{
    public function up()
    {
        // Preserve any names written since the previous migration.
        $rows = $this->db->table('persons')->select('id, institution')
            ->where('institution_id', null)->get()->getResultArray();
        foreach ($rows as $row) {
            $name = trim((string) $row['institution']);
            if ($name === '') {
                continue;
            }
            $institution = $this->db->table('institutions')->where('name', $name)->get()->getRowArray();
            if ($institution === null) {
                $this->db->table('institutions')->insert([
                    'name' => $name, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s'),
                ]);
                $institution = ['id' => $this->db->insertID()];
            }
            $this->db->table('persons')->where('id', $row['id'])->update(['institution_id' => $institution['id']]);
        }
        if ($this->db->DBDriver === 'SQLite3') {
            $table = $this->db->escapeIdentifiers($this->db->prefixTable('persons'));
            $this->db->query("ALTER TABLE {$table} DROP COLUMN institution");
        } else {
            $this->forge->dropColumn('persons', 'institution');
        }
    }

    public function down()
    {
        $this->forge->addColumn('persons', ['institution' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]]);
        $rows = $this->db->table('persons')->select('persons.id, institutions.name')
            ->join('institutions', 'institutions.id = persons.institution_id')->get()->getResultArray();
        foreach ($rows as $row) {
            $this->db->table('persons')->where('id', $row['id'])->update(['institution' => $row['name']]);
        }
    }
}