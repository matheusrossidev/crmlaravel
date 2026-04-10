<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use App\Models\Product;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    public function test_can_create_product(): void
    {
        $response = $this->postJson('/configuracoes/produtos', [
            'name'  => 'Plano Premium',
            'price' => 299.90,
            'sku'   => 'PREM-001',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Plano Premium',
            'sku'       => 'PREM-001',
        ]);
    }

    public function test_cannot_create_product_without_name(): void
    {
        $response = $this->postJson('/configuracoes/produtos', [
            'price' => 99.90,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_cannot_create_product_without_price(): void
    {
        $response = $this->postJson('/configuracoes/produtos', [
            'name' => 'Sem Preço',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('price');
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->putJson("/configuracoes/produtos/{$product->id}", [
            'name'  => 'Produto Atualizado',
            'price' => 499.90,
        ]);

        $response->assertSuccessful();

        $product->refresh();
        $this->assertEquals('Produto Atualizado', $product->name);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/configuracoes/produtos/{$product->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_can_deactivate_product(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->putJson("/configuracoes/produtos/{$product->id}", [
            'is_active' => false,
        ]);

        $response->assertSuccessful();

        $product->refresh();
        $this->assertFalse($product->is_active);
    }

    public function test_list_products_page_loads(): void
    {
        Product::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->get('/configuracoes/produtos');

        $response->assertSuccessful();
    }
}
