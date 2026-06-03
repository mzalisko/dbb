<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use App\Services\AuditFeed;
use App\Support\AuditEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task N-A: the change log must read like plain language for an admin — never raw
 * JSON, arrays, technical keys or unchanged fields. Permissions show leaf-level
 * toggles, access lists show names (not ids), access level shows a human label.
 */
class AuditHumanDiffTest extends TestCase
{
    use RefreshDatabase;

    private function viewerMatrix(): array
    {
        $clean = [];
        foreach (User::RESOURCES as $res => $_) {
            foreach (User::ACTIONS as $act) {
                $clean[$res][$act] = (bool) (User::ROLE_PERMISSIONS['viewer'][$res][$act] ?? false);
            }
        }

        return $clean;
    }

    private function changedUserEntry(User $user): AuditEntry
    {
        $entry = AuditFeed::collect(['domain' => 'user'])
            ->first(fn (AuditEntry $e) => $e->subjectId === $user->id && $e->old !== []);

        $this->assertNotNull($entry, 'expected an update audit for the user');

        return $entry;
    }

    public function test_permission_change_reads_as_human_toggle_lines(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));

        $user = User::factory()->create(['role' => 'viewer', 'permissions' => $this->viewerMatrix()]);

        $matrix = $this->viewerMatrix();
        $matrix['sites']['create'] = true; // flip exactly one leaf
        $user->update(['permissions' => $matrix]);

        $rows = $this->changedUserEntry($user)->humanChanges();
        $group = collect($rows)->firstWhere('kind', 'group');

        $this->assertNotNull($group, 'permissions must render as a group of toggle lines');
        $this->assertSame('Дозволи', $group['field']);

        $line = collect($group['lines'])->firstWhere('label', 'Сайти: створення');
        $this->assertNotNull($line);
        $this->assertSame('вимкнено', $line['old']);
        $this->assertSame('увімкнено', $line['new']);

        // Only the changed leaf shows — viewer has many true reads, none must leak.
        $this->assertCount(1, $group['lines'], 'unchanged toggles must not appear');
    }

    public function test_site_access_change_shows_names_not_ids(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));

        $site = Site::factory()->create(['name' => 'Main Site']);
        $user = User::factory()->create(['role' => 'viewer', 'access_scope' => 'all']);

        $user->update([
            'access_scope' => 'limited',
            'site_access'  => [$site->id],
        ]);

        $rows = $this->changedUserEntry($user)->humanChanges();

        // Access level → human label, not "all → limited".
        $scope = collect($rows)->firstWhere('field', 'Рівень доступу');
        $this->assertNotNull($scope);
        $this->assertSame('Повний доступ', $scope['old']);
        $this->assertSame('Обмежений доступ', $scope['new']);

        // Site access → the site name, never the raw id.
        $access = collect($rows)->firstWhere('field', 'Доступ до сайтів');
        $this->assertNotNull($access);
        $this->assertContains('Main Site', $access['added']);
        $this->assertNotContains((string) $site->id, $access['added']);
    }

    public function test_group_access_change_shows_group_names(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));

        $user = User::factory()->create(['role' => 'viewer', 'access_scope' => 'all']);
        $user->update(['access_scope' => 'limited', 'group_access' => ['Poland']]);

        $rows = $this->changedUserEntry($user)->humanChanges();
        $access = collect($rows)->firstWhere('field', 'Доступ до груп');

        $this->assertNotNull($access);
        $this->assertContains('Poland', $access['added']);
    }

    public function test_human_changes_never_contain_json_or_raw_arrays(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));

        $site = Site::factory()->create(['name' => 'Main Site']);
        $user = User::factory()->create(['role' => 'viewer', 'permissions' => $this->viewerMatrix()]);

        $matrix = $this->viewerMatrix();
        $matrix['data_phones']['delete'] = true;
        $user->update([
            'permissions'  => $matrix,
            'access_scope' => 'limited',
            'site_access'  => [$site->id],
        ]);

        // Collect only the strings actually shown to the admin (labels + values).
        $shown = [];
        foreach ($this->changedUserEntry($user)->humanChanges() as $row) {
            $shown[] = $row['field'];
            if ($row['kind'] === 'group') {
                foreach ($row['lines'] as $l) {
                    $shown[] = $l['label'].' '.$l['old'].' '.$l['new'];
                }
            } elseif ($row['kind'] === 'delta') {
                $shown[] = implode(' ', array_merge($row['added'], $row['removed']));
            } else {
                $shown[] = $row['old'].' '.$row['new'];
            }
        }
        $text = implode(' | ', $shown);

        // Plain language only — no JSON, raw keys, brackets or boolean literals.
        foreach (['{', '}', '[', ']', '"', '=>', 'true', 'false', 'sites', 'limited', 'all'] as $needle) {
            $this->assertStringNotContainsString($needle, $text, "leaked technical token: {$needle}");
        }
    }
}
