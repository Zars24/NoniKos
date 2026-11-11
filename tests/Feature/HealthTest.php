<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_probe_reports_ok_without_authentication(): void
    {
        Storage::fake('private');

        $this->getJson(route('health'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.storage', true);
    }

    public function test_health_probe_does_not_leak_details(): void
    {
        Storage::fake('private');

        $body = $this->getJson(route('health'))->getContent();

        $this->assertStringNotContainsString('sqlite', $body);
        $this->assertStringNotContainsString('nonikos', $body);
    }
}
