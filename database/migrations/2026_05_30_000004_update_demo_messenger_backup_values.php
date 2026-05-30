<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            '+48 22 555 33 11' => ['@demo_pl_backup', 'PL Telegram backup'],
            'm.me/demoPL' => ['@demo_pl_support', 'PL Telegram backup'],
            '+38 099 11 22 33' => ['@demo_ua_backup', 'UA Telegram backup'],
            '+38 073 000 11 22' => ['@demo_ua_support', 'UA Telegram backup'],
        ];

        foreach ($replacements as $oldValue => [$newValue, $newLabel]) {
            DB::table('contact_entries')
                ->where('type', 'messenger')
                ->where('role', 'backup')
                ->where('kind', 'telegram')
                ->where('value', $oldValue)
                ->update([
                    'value' => $newValue,
                    'label' => $newLabel,
                ]);
        }
    }

    public function down(): void
    {
        // Demo cleanup only; do not reintroduce mixed-platform backup values.
    }
};
