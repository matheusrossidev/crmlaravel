<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant;
    protected User $admin;
    protected Pipeline $pipeline;
    protected PipelineStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable strict mode for testing — views access many attributes
        // that may not be explicitly set in factories.
        Model::preventAccessingMissingAttributes(false);

        // Register MySQL FIELD() function for SQLite compatibility.
        // FIELD(val, a, b, c) returns the 1-based position of val in the list.
        if (config('database.default') === 'sqlite') {
            $pdo = \DB::connection()->getPdo();
            $pdo->sqliteCreateFunction('FIELD', function ($value, ...$list) {
                $pos = array_search($value, $list, true);
                return $pos === false ? 0 : $pos + 1;
            });
        }
    }

    /**
     * Cria tenant + pipeline padrão + admin autenticado.
     */
    protected function actingAsTenant(array $tenantAttrs = []): static
    {
        $this->tenant = Tenant::factory()->create($tenantAttrs);

        $this->pipeline = Pipeline::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'is_default' => true,
        ]);

        $this->stage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'name'        => 'Novo',
            'position'    => 0,
        ]);

        $this->admin = User::factory()->admin()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($this->admin);

        return $this;
    }

    /**
     * Cria tenant + user viewer autenticado.
     */
    protected function actingAsViewer(array $tenantAttrs = []): static
    {
        $this->actingAsTenant($tenantAttrs);

        $viewer = User::factory()->viewer()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($viewer);

        return $this;
    }

    /**
     * Cria tenant + super admin autenticado (sem tenant_id).
     */
    protected function actingAsSuperAdmin(): static
    {
        $this->admin = User::factory()->create([
            'is_super_admin' => true,
            'tenant_id'      => null,
        ]);

        $this->actingAs($this->admin);

        return $this;
    }
}
