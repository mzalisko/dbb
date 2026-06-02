<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The entry form defaulted currency to 'EUR', so phones/messengers saved before
     * the fix carry stale price columns. That makes a plain edit look like a price
     * change in the audit feed (currency EUR → —). Null those columns for every
     * non-price entry. Raw query — a one-off normalization, no audit needed.
     */
    public function up(): void
    {
        DB::table('contact_entries')
            ->where('type', '!=', 'price')
            ->update([
                'currency'   => null,
                'price'      => null,
                'old_price'  => null,
                'price_unit' => null,
                'sku'        => null,
            ]);
    }

    public function down(): void
    {
        // Irreversible data normalization — nothing to restore.
    }
};
