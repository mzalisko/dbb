<?php

namespace Tests\Unit;

use App\Support\AuditAction;
use PHPUnit\Framework\TestCase;

class AuditActionTest extends TestCase
{
    public function test_every_catalog_code_has_label_severity_and_icon(): void
    {
        foreach (AuditAction::codes() as $code) {
            $this->assertNotSame('', AuditAction::label($code), "label for {$code}");
            $this->assertContains(AuditAction::severity($code), [0, 1, 2], "severity for {$code}");
            $this->assertNotSame('', AuditAction::icon($code), "icon for {$code}");
        }
    }

    public function test_unknown_code_falls_back_without_error(): void
    {
        $this->assertSame('weird.unknown.code', AuditAction::label('weird.unknown.code'));
        $this->assertSame(AuditAction::INFO, AuditAction::severity('weird.unknown.code'));
        $this->assertSame('edit', AuditAction::icon('weird.unknown.code'));
        $this->assertFalse(AuditAction::exists('weird.unknown.code'));
    }

    public function test_domain_extracts_the_prefix(): void
    {
        $this->assertSame('entry', AuditAction::domain('entry.bulk.deleted'));
        $this->assertSame('auth', AuditAction::domain('auth.login_failed'));
        $this->assertSame('site', AuditAction::domain('site.geo.updated'));
    }
}
