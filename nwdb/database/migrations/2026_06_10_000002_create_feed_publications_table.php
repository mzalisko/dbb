<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_plugin_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('version');
            // sha256 канонічного payload (без generated_at) — для дедупу;
            // конверт містить рандомний nonce, тому його хеш недетермінований
            $table->string('payload_hash', 64);
            $table->unsignedInteger('byte_size')->default(0);
            $table->unsignedSmallInteger('entry_count')->default(0);
            $table->string('disk', 20);
            $table->string('path', 160);
            $table->string('status', 12)->default('pending'); // pending | published | failed
            $table->text('error')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['site_plugin_id', 'version']);
            $table->index('payload_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_publications');
    }
};
