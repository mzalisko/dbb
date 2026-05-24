<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UiComponentTest extends TestCase
{
    public function test_button_variants(): void
    {
        foreach (['primary', 'secondary', 'danger', 'ghost', 'accent'] as $v) {
            $html = Blade::render('<x-ui.button variant="' . $v . '">Test</x-ui.button>');
            $this->assertStringContainsString("btn-$v", $html);
        }
    }

    public function test_button_sizes(): void
    {
        foreach (['sm', 'lg'] as $s) {
            $html = Blade::render('<x-ui.button variant="primary" size="' . $s . '">Test</x-ui.button>');
            $this->assertStringContainsString("btn-$s", $html);
        }
    }

    public function test_alert_variants(): void
    {
        foreach (['info', 'success', 'warning', 'danger'] as $v) {
            $html = Blade::render('<x-ui.alert variant="' . $v . '">Message</x-ui.alert>');
            $this->assertStringContainsString('alert', $html);
        }
    }

    public function test_avatar_initials_fallback(): void
    {
        $html = Blade::render('<x-ui.avatar initials="jd" />');
        $this->assertStringContainsString('JD', $html);
    }

    public function test_spinner_sizes(): void
    {
        $html = Blade::render('<x-ui.spinner size="sm" />');
        $this->assertStringContainsString('spinner-sm', $html);

        $html = Blade::render('<x-ui.spinner size="lg" />');
        $this->assertStringContainsString('spinner-lg', $html);

        $html = Blade::render('<x-ui.spinner />');
        $this->assertStringContainsString('spinner', $html);
    }

    public function test_modal_has_alpine_attributes(): void
    {
        $html = Blade::render('<x-ui.modal name="test-modal"><p>Content</p></x-ui.modal>');
        $this->assertStringContainsString('x-data', $html);
        $this->assertStringContainsString('open-modal.window', $html);
        $this->assertStringContainsString('close-modal.window', $html);
    }

    public function test_dropdown_has_alpine_attributes(): void
    {
        $html = Blade::render('<x-ui.dropdown><x-slot:trigger><button>Open</button></x-slot:trigger><a>Item</a></x-ui.dropdown>');
        $this->assertStringContainsString('x-data', $html);
        $this->assertStringContainsString('close-dropdown.window', $html);
    }

    public function test_empty_state_renders(): void
    {
        $html = Blade::render('<x-ui.empty-state title="No data" description="Nothing here" />');
        $this->assertStringContainsString('empty-state', $html);
        $this->assertStringContainsString('No data', $html);
    }

    public function test_table_renders(): void
    {
        $html = Blade::render('<x-ui.table><tr><td>Cell</td></tr></x-ui.table>');
        $this->assertStringContainsString('table-wrap', $html);
        $this->assertStringContainsString('<table>', $html);
    }
}