<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_plugins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->unique()->constrained()->cascadeOnDelete();

            // Унікальна ідентичність білда (анти-фінгерпринтинг)
            $table->string('slug', 40)->unique();
            $table->string('display_name', 80);
            $table->string('prefix', 24);
            $table->string('text_domain', 40);

            // Dead-drop шлях
            $table->string('feed_token', 64)->unique();
            $table->string('feed_filename', 80);

            // Per-site симетричний ключ, шифрується APP_KEY (cast encrypted)
            $table->text('sym_key');

            $table->string('cron_hook', 48);
            $table->unsignedInteger('cron_interval');

            $table->string('status', 12)->default('draft'); // draft | enabled | paused
            $table->unsignedInteger('payload_version')->default(1);
            $table->json('build_meta')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('keys_rotated_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_plugins');
    }
};
