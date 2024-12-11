<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLeadsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'source' => [
                'type' => 'ENUM',
                'constraint' => ['form', 'linkedin', 'facebook', 'twitter'],
                'default' => 'form',
            ],
            'source_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'consent_given' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'consent_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'tags' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('email');
        $this->forge->createTable('leads');
    }

    public function down()
    {
        $this->forge->dropTable('leads');
    }
}
