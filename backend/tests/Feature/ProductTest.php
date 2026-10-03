<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'sku' => 'tst-001',
        'name' => 'Test Hammer',
        'cost_price' => 10,
        'sell_price' => 20,
        'reorder_level' => 5,
    ];

    public function test_admin_can_create_update_and_delete_a_product(): void
    {
        Sanctum::actingAs(User::create(['name' => 'Admin', 'email' => 'ad@example.com', 'password' => 'x', 'role' => 'admin']));

        $id = $this->postJson('/api/products', $this->payload)
            ->assertCreated()
            ->assertJsonPath('sku', 'TST-001')
            ->json('id');

        $this->putJson("/api/products/{$id}", [...$this->payload, 'name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('name', 'Renamed');

        $this->getJson('/api/products?search=renamed')->assertOk()->assertJsonPath('total', 1);

        $this->deleteJson("/api/products/{$id}")->assertNoContent();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_validation_errors_are_returned(): void
    {
        Sanctum::actingAs(User::create(['name' => 'Admin', 'email' => 'ad@example.com', 'password' => 'x', 'role' => 'admin']));
        Product::create([...$this->payload, 'sku' => 'TST-001']);

        $this->postJson('/api/products', [...$this->payload, 'sell_price' => 5, 'name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'name', 'sell_price']);
    }

    public function test_staff_cannot_modify_catalogue(): void
    {
        Sanctum::actingAs(User::create(['name' => 'Staff', 'email' => 'st@example.com', 'password' => 'x', 'role' => 'staff']));

        $this->getJson('/api/products')->assertOk();
        $this->postJson('/api/products', $this->payload)->assertForbidden();
    }

    public function test_missing_product_returns_json_404(): void
    {
        Sanctum::actingAs(User::create(['name' => 'Staff', 'email' => 'st@example.com', 'password' => 'x']));

        $this->getJson('/api/products/999')->assertNotFound()->assertJsonPath('message', 'The requested record was not found.');
    }
}
