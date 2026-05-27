<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('color', 10)->default('#5a8a3c');
            $table->timestamps();
        });

        DB::table('site_groups')->insert([
            ['name' => 'production', 'color' => '#5a8a3c', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'staging',    'color' => '#e6a817', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_groups');
    }
};
