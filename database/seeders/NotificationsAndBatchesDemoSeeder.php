<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Product;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dummy org announcements and inbound batch lines for demos (batch management + notifications inbox).
 * Idempotent: removes prior rows tagged with [DEMO] titles / DEMO-BATCH-* refs, then re-seeds.
 */
class NotificationsAndBatchesDemoSeeder extends Seeder
{
    private const TITLE_PREFIX = '[DEMO] ';

    public function run(): void
    {
        $author = User::query()
            ->where('email', 'tenant.admin@demo.test')
            ->first()
            ?? User::query()
                ->where('email', 'manager@demo.test')
                ->first()
            ?? User::query()
                ->where('is_super_admin', false)
                ->whereNotNull('company_id')
                ->orderBy('id')
                ->first();

        if (! $author || ! $author->company_id) {
            $this->command->warn('NotificationsAndBatchesDemoSeeder: no tenant user with company; skip.');

            return;
        }

        $companyId = (int) $author->company_id;
        $siteId = (int) ($author->site_id ?? Site::defaultId());

        DB::transaction(function () use ($author, $companyId, $siteId) {
            $this->clearPreviousDemoRows($companyId);

            $branchSiteId = (int) Site::query()
                ->where('company_id', $companyId)
                ->where('is_default', false)
                ->orderBy('id')
                ->value('id');

            Announcement::query()->create([
                'company_id' => $companyId,
                'site_id' => null,
                'author_id' => $author->id,
                'title' => self::TITLE_PREFIX.'Org-wide: cold-chain reminder',
                'body' => 'Please verify fridge temps twice daily this week. Demo announcement for the notifications inbox.',
            ]);

            if ($branchSiteId > 0) {
                Announcement::query()->create([
                    'company_id' => $companyId,
                    'site_id' => $branchSiteId,
                    'author_id' => $author->id,
                    'title' => self::TITLE_PREFIX.'Branch: stock-take window',
                    'body' => 'Mini stock-take scheduled for Saturday morning. Demo branch-scoped post.',
                ]);
            }

            $products = Product::query()
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->limit(3)
                ->get();

            if ($products->isEmpty()) {
                $this->command->warn('NotificationsAndBatchesDemoSeeder: no products for company; batch lines skipped.');

                return;
            }

            $supplier = Supplier::query()->firstOrCreate(
                [
                    'supplier_name' => 'Demo Wholesale Ltd',
                    'company_id' => $companyId,
                ],
                [
                    'supplier_name' => 'Demo Wholesale Ltd',
                    'company_id' => $companyId,
                    'address' => 'Industrial Area, Accra (demo)',
                    'mobile' => '0244000999',
                    'email' => 'demo-wholesale@example.test',
                ]
            );

            $today = Carbon::today();
            $rows = [
                [
                    'ref' => 'DEMO-BATCH-EXPIRED',
                    'qty' => 6,
                    'lot' => 'LOT-EXP-PAST',
                    'expiry' => $today->copy()->subDays(14)->toDateString(),
                    'received' => $today->copy()->subMonths(2)->toDateString(),
                ],
                [
                    'ref' => 'DEMO-BATCH-EXPIRING',
                    'qty' => 24,
                    'lot' => 'LOT-EXP-SOON',
                    'expiry' => $today->copy()->addDays(45)->toDateString(),
                    'received' => $today->copy()->subWeek()->toDateString(),
                ],
                [
                    'ref' => 'DEMO-BATCH-OK',
                    'qty' => 48,
                    'lot' => 'LOT-OK-2030',
                    'expiry' => $today->copy()->addYear()->toDateString(),
                    'received' => $today->copy()->subDays(3)->toDateString(),
                ],
                [
                    'ref' => 'DEMO-BATCH-NOEXP',
                    'qty' => 12,
                    'lot' => 'NON-LOT',
                    'expiry' => null,
                    'received' => $today->copy()->subDays(1)->toDateString(),
                ],
            ];

            foreach ($rows as $i => $row) {
                $product = $products[$i % $products->count()];

                StockReceipt::query()->create([
                    'product_id' => $product->id,
                    'user_id' => $author->id,
                    'site_id' => $siteId,
                    'quantity' => $row['qty'],
                    'batch_number' => $row['lot'],
                    'expiry_date' => $row['expiry'],
                    'supplier_id' => $supplier->id,
                    'document_reference' => $row['ref'],
                    'received_at' => $row['received'],
                    'notes' => 'Seeded for batch management demo.',
                ]);
            }
        });

        $this->command->info('Demo announcements + batch receipt lines seeded ([DEMO] + DEMO-BATCH-*).');
    }

    private function clearPreviousDemoRows(int $companyId): void
    {
        Announcement::query()
            ->where('company_id', $companyId)
            ->where('title', 'like', self::TITLE_PREFIX.'%')
            ->delete();

        StockReceipt::query()
            ->where('document_reference', 'like', 'DEMO-BATCH-%')
            ->delete();
    }
}
