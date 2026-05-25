<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            // type: phone | messenger | price | social | address | custom
            $table->string('type', 20);
            // kind: telegram | whatsapp | viber | messenger | signal | skype (for messengers)
            $table->string('kind', 30)->nullable();
            $table->string('value', 500);
            $table->string('label', 255)->nullable();
            // role: primary | backup | hidden
            $table->string('role', 20)->default('primary');
            // geo_mode: all | only | except
            $table->string('geo_mode', 10)->default('all');
            // ["PL", "UA"] — ISO-2 country codes
            $table->json('countries')->nullable();
            $table->boolean('visible')->default(true);
            $table->smallInteger('order')->default(1);
            // backup entries reference their parent primary
            $table->foreignId('parent_id')->nullable()->constrained('contact_entries')->nullOnDelete();
            // price-specific fields
            $table->string('currency', 3)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->string('price_unit', 50)->nullable();
            $table->string('sku', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_entries');
    }
};
