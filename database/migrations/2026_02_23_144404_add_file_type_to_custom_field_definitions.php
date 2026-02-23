<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE custom_field_definitions MODIFY COLUMN field_type ENUM('text','textarea','number','currency','date','select','multiselect','checkbox','url','phone','email','file') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE custom_field_definitions MODIFY COLUMN field_type ENUM('text','textarea','number','currency','date','select','multiselect','checkbox','url','phone','email') NOT NULL");
    }
};
