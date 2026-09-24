<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInstitutions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ror_id' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'acronym' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'address' => ['type' => 'TEXT', 'null' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'state' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'country' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'country_code' => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            'latitude' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'established_year' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('ror_id');
        $this->forge->addKey('name');
        $this->forge->addKey('acronym');
        $this->forge->createTable('institutions');

        if ($this->db->DBDriver === 'SQLite3') {
            // SQLite supports adding nullable references directly; avoid rebuilding persons.
            $persons = $this->db->escapeIdentifiers($this->db->prefixTable('persons'));
            $institutions = $this->db->escapeIdentifiers($this->db->prefixTable('institutions'));
            $this->db->query("ALTER TABLE {$persons} ADD COLUMN institution_id INTEGER REFERENCES {$institutions}(id) ON UPDATE CASCADE ON DELETE SET NULL");
        } else {
            $this->forge->addColumn('persons', [
                'institution_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            ]);
            $this->forge->addForeignKey('institution_id', 'institutions', 'id', 'CASCADE', 'SET NULL', 'persons_institution_fk');
            $this->forge->processIndexes('persons');
        }
        // Preserve legacy names. No ROR identity is inferred from an unverified name.
        $rows = $this->db->table('persons')->select('id, institution')
            ->where('institution !=', null)->where('institution !=', '')->get()->getResultArray();
        foreach ($rows as $row) {
            $name = trim($row['institution']);
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
    }

    public function down()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $persons = $this->db->escapeIdentifiers($this->db->prefixTable('persons'));
            $this->db->query("ALTER TABLE {$persons} DROP COLUMN institution_id");
        } else {
            $this->forge->dropForeignKey('persons', 'persons_institution_fk');
            $this->forge->dropColumn('persons', 'institution_id');
        }
        $this->forge->dropTable('institutions');
    }
}