<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private Branch $north;
    private Branch $south;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->north = Branch::create(['name' => 'North', 'code' => 'N']);
        $this->south = Branch::create(['name' => 'South', 'code' => 'S']);
        $this->product = Product::create(['sku' => 'P-1', 'name' => 'Widget', 'cost_price' => 1, 'sell_price' => 2, 'reorder_level' => 3]);
    }

    private function quantityAt(Branch $branch): int
    {
        return (int) StockLevel::where('product_id', $this->product->id)->where('branch_id', $branch->id)->value('quantity');
    }

    public function test_stock_in_and_out_update_level_and_log_movements(): void
    {
        Sanctum::actingAs(User::create(['name' => 'S', 'email' => 's@example.com', 'password' => 'x', 'branch_id' => $this->north->id]));

        $this->postJson('/api/stock-movements', ['product_id' => $this->product->id, 'branch_id' => $this->north->id, 'type' => 'in', 'quantity' => 10])
            ->assertCreated()
            ->assertJsonPath('balance_after', 10);

        $this->postJson('/api/stock-movements', ['product_id' => $this->product->id, 'branch_id' => $this->north->id, 'type' => 'out', 'quantity' => 4])
            ->assertCreated()
            ->assertJsonPath('quantity', -4);

        $this->assertSame(6, $this->quantityAt($this->north));
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_stock_cannot_go_negative(): void
    {
        Sanctum::actingAs(User::create(['name' => 'S', 'email' => 's@example.com', 'password' => 'x', 'branch_id' => $this->north->id]));

        $this->postJson('/api/stock-movements', ['product_id' => $this->product->id, 'branch_id' => $this->north->id, 'type' => 'out', 'quantity' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_staff_cannot_record_movements_for_another_branch(): void
    {
        Sanctum::actingAs(User::create(['name' => 'S', 'email' => 's@example.com', 'password' => 'x', 'branch_id' => $this->north->id]));

        $this->postJson('/api/stock-movements', ['product_id' => $this->product->id, 'branch_id' => $this->south->id, 'type' => 'in', 'quantity' => 5])
            ->assertForbidden();
    }

    public function test_transfer_moves_stock_atomically(): void
    {
        Sanctum::actingAs(User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x', 'role' => 'admin']));
        StockLevel::create(['product_id' => $this->product->id, 'branch_id' => $this->north->id, 'quantity' => 5]);

        $this->postJson('/api/stock-transfers', [
            'product_id' => $this->product->id, 'from_branch_id' => $this->north->id, 'to_branch_id' => $this->south->id, 'quantity' => 8,
        ])->assertUnprocessable();

        $this->assertSame(5, $this->quantityAt($this->north));
        $this->assertSame(0, $this->quantityAt($this->south));

        $this->postJson('/api/stock-transfers', [
            'product_id' => $this->product->id, 'from_branch_id' => $this->north->id, 'to_branch_id' => $this->south->id, 'quantity' => 3,
        ])->assertCreated();

        $this->assertSame(2, $this->quantityAt($this->north));
        $this->assertSame(3, $this->quantityAt($this->south));
    }

    public function test_low_stock_filter_and_dashboard(): void
    {
        Sanctum::actingAs(User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x', 'role' => 'admin']));
        StockLevel::create(['product_id' => $this->product->id, 'branch_id' => $this->north->id, 'quantity' => 2]);
        StockLevel::create(['product_id' => $this->product->id, 'branch_id' => $this->south->id, 'quantity' => 50]);

        $this->getJson('/api/stock-levels?low_only=1')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('totals.low_stock_items', 1)
            ->assertJsonPath('totals.units_on_hand', 52);
    }
}
