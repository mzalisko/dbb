<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class IconComponentTest extends TestCase
{
    public function test_all_icon_components_render(): void
    {
        $iconDir = resource_path('views/components/icon');
        $icons = glob("$iconDir/*.blade.php");
        $this->assertGreaterThanOrEqual(45, count($icons), 'Need 45+ icon files');

        foreach ($icons as $iconFile) {
            $name = basename($iconFile, '.blade.php');
            $html = Blade::render('<x-icon.' . $name . ' />');
            $this->assertThat(
                $html,
                $this->logicalOr(
                    $this->stringContains('<svg'),
                    $this->stringContains('spinner')
                ),
                "Icon $name did not render SVG or spinner"
            );
        }
    }
}