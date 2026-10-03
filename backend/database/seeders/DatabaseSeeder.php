<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

/**
 * Demo data for "Harbourside Hardware", a three-store hardware retailer.
 */
class DatabaseSeeder extends Seeder
{
    public function run(StockService $stock): void
    {
        $branches = collect([
            ['name' => 'Sydney CBD', 'code' => 'SYD', 'address' => '120 George St, Sydney NSW 2000', 'phone' => '02 9000 1100'],
            ['name' => 'Parramatta', 'code' => 'PAR', 'address' => '45 Church St, Parramatta NSW 2150', 'phone' => '02 9000 2200'],
            ['name' => 'Newcastle', 'code' => 'NEW', 'address' => '8 Hunter St, Newcastle NSW 2300', 'phone' => '02 4900 3300'],
        ])->map(fn ($b) => Branch::create($b));

        $admin = User::create([
            'name' => 'Alex Morgan',
            'email' => 'admin@stockwise.test',
            'password' => 'Password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $staff = User::create([
            'name' => 'Sam Lee',
            'email' => 'staff@stockwise.test',
            'password' => 'Password123',
            'role' => User::ROLE_STAFF,
            'branch_id' => $branches[0]->id,
        ]);

        User::create([
            'name' => 'Priya Nair',
            'email' => 'priya@stockwise.test',
            'password' => 'Password123',
            'role' => User::ROLE_STAFF,
            'branch_id' => $branches[1]->id,
        ]);

        $categories = collect(['Power Tools', 'Hand Tools', 'Fasteners', 'Paint & Supplies', 'Garden', 'Plumbing', 'Electrical'])
            ->mapWithKeys(fn ($name) => [$name => Category::create(['name' => $name])]);

        $suppliers = collect([
            ['name' => 'ToolPro Distributors', 'contact_name' => 'Mark Chen', 'email' => 'orders@toolpro.example', 'phone' => '1300 111 222'],
            ['name' => 'FixRight Supplies', 'contact_name' => 'Hannah Wills', 'email' => 'sales@fixright.example', 'phone' => '1300 333 444'],
            ['name' => 'ColourCo Paints', 'contact_name' => 'Diego Ruiz', 'email' => 'trade@colourco.example', 'phone' => '1300 555 666'],
            ['name' => 'GreenGrow Wholesale', 'contact_name' => 'Aisha Khan', 'email' => 'hello@greengrow.example', 'phone' => '1300 777 888'],
        ])->mapWithKeys(fn ($s) => [$s['name'] => Supplier::create($s)]);

        // [sku, name, category, supplier, cost, sell, reorder level, [SYD, PAR, NEW] opening stock]
        $catalogue = [
            ['PT-DRL-18V', '18V Cordless Drill Kit', 'Power Tools', 'ToolPro Distributors', 89.00, 149.00, 5, [12, 8, 3]],
            ['PT-SAW-185', '185mm Circular Saw', 'Power Tools', 'ToolPro Distributors', 95.00, 169.00, 4, [6, 2, 5]],
            ['PT-GRD-115', '115mm Angle Grinder', 'Power Tools', 'ToolPro Distributors', 42.00, 79.00, 5, [10, 9, 7]],
            ['PT-SND-ORB', 'Random Orbital Sander', 'Power Tools', 'ToolPro Distributors', 55.00, 99.00, 4, [3, 6, 4]],
            ['HT-HAM-16', '16oz Claw Hammer', 'Hand Tools', 'FixRight Supplies', 12.50, 24.95, 10, [30, 22, 18]],
            ['HT-SDR-SET', '12pc Screwdriver Set', 'Hand Tools', 'FixRight Supplies', 14.00, 29.95, 10, [25, 7, 15]],
            ['HT-TAPE-8M', '8m Tape Measure', 'Hand Tools', 'FixRight Supplies', 6.80, 14.95, 15, [40, 35, 12]],
            ['HT-LVL-600', '600mm Spirit Level', 'Hand Tools', 'FixRight Supplies', 11.00, 22.50, 8, [14, 10, 9]],
            ['FA-SCR-8G', '8g x 40mm Timber Screws (200pk)', 'Fasteners', 'FixRight Supplies', 7.20, 15.90, 25, [80, 60, 20]],
            ['FA-NAIL-75', '75mm Bullet Head Nails 1kg', 'Fasteners', 'FixRight Supplies', 5.50, 11.90, 20, [55, 18, 30]],
            ['FA-ANC-10', '10mm Masonry Anchors (50pk)', 'Fasteners', 'FixRight Supplies', 9.00, 19.50, 15, [22, 25, 14]],
            ['PA-INT-4L', 'Interior Low Sheen White 4L', 'Paint & Supplies', 'ColourCo Paints', 38.00, 69.00, 10, [24, 30, 8]],
            ['PA-EXT-10L', 'Exterior Acrylic 10L', 'Paint & Supplies', 'ColourCo Paints', 85.00, 149.00, 5, [9, 12, 6]],
            ['PA-RLR-230', '230mm Roller Kit', 'Paint & Supplies', 'ColourCo Paints', 9.50, 19.95, 12, [28, 20, 11]],
            ['GA-HOSE-30', '30m Garden Hose', 'Garden', 'GreenGrow Wholesale', 24.00, 49.00, 6, [10, 14, 4]],
            ['GA-SOIL-25', 'Premium Potting Mix 25L', 'Garden', 'GreenGrow Wholesale', 6.00, 12.95, 30, [60, 75, 25]],
            ['GA-PRN-SEC', 'Bypass Pruning Secateurs', 'Garden', 'GreenGrow Wholesale', 11.00, 24.95, 8, [12, 5, 9]],
            ['PL-TAP-MIX', 'Chrome Sink Mixer Tap', 'Plumbing', 'FixRight Supplies', 48.00, 99.00, 4, [7, 5, 2]],
            ['PL-PVC-50', '50mm PVC Pipe 3m', 'Plumbing', 'FixRight Supplies', 8.00, 16.50, 15, [35, 28, 22]],
            ['EL-LED-10W', '10W LED Downlight', 'Electrical', 'ToolPro Distributors', 7.00, 15.95, 20, [65, 40, 16]],
            ['EL-EXT-5M', '5m Extension Lead', 'Electrical', 'ToolPro Distributors', 9.00, 19.95, 10, [18, 9, 13]],
        ];

        foreach ($catalogue as [$sku, $name, $category, $supplier, $cost, $sell, $reorder, $opening]) {
            $product = Product::create([
                'sku' => $sku,
                'name' => $name,
                'category_id' => $categories[$category]->id,
                'supplier_id' => $suppliers[$supplier]->id,
                'cost_price' => $cost,
                'sell_price' => $sell,
                'reorder_level' => $reorder,
            ]);

            foreach ($branches as $i => $branch) {
                // Open with extra stock, then record some sales so the movement log has history.
                $sold = intdiv($opening[$i], 3);
                $stock->adjust($product->id, $branch->id, $opening[$i] + $sold, 'in', $admin, 'PO-OPENING', 'Opening stock');
                if ($sold > 0) {
                    $stock->adjust($product->id, $branch->id, -$sold, 'out', $staff, 'POS-'.random_int(10000, 99999), 'Counter sales');
                }
            }
        }

        $stock->transfer(
            Product::where('sku', 'PT-DRL-18V')->value('id'),
            $branches[0]->id,
            $branches[2]->id,
            2,
            $admin,
            'Rebalance for weekend promotion',
        );

        // Spread the history over the past fortnight so the log looks realistic.
        StockMovement::orderBy('id')->get()->each(function (StockMovement $movement, int $i) {
            $movement->timestamps = false;
            $movement->created_at = $movement->updated_at = now()->subDays(14)->addMinutes($i * 150);
            $movement->save();
        });
    }
}
