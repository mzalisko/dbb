<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_design_system_page_returns_200(): void
    {
        $response = $this->get('/_design');
        $response->assertStatus(200);
    }

    public function test_design_system_contains_all_ui_components(): void
    {
        $response = $this->get('/_design');
        $components = [
            'btn btn-primary', 'btn btn-secondary', 'btn btn-danger', 'btn btn-ghost',
            'input', 'textarea', 'select', 'checkbox', 'radio',
            'card', 'tabs', 'pill', 'avatar',
            'modal', 'dropdown', 'alert',
            'spinner', 'empty-state',
        ];
        foreach ($components as $c) {
            $response->assertSee($c, false);
        }
    }

    public function test_design_system_contains_svg_icons(): void
    {
        $response = $this->get('/_design');
        $content = $response->getContent();
        // Minimum 20 SVG elements on the page
        $svgCount = substr_count($content, '<svg');
        $this->assertGreaterThanOrEqual(20, $svgCount, "Expected 20+ SVG icons, found $svgCount");
    }
}