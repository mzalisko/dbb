<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sites')
            ->where('name', 'demo-site.example')
            ->update(['messenger_kinds' => json_encode(['telegram', 'skype'])]);
    }

    public function down(): void
    {
        // Demo cleanup only; leave current site preferences intact.
    }
};
