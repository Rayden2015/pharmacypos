<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Site;
use App\Models\SubscriptionPackage;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo tenants: companies, branches, SaaS subscriptions, and billing payments.
 * Idempotent: safe to re-run (cleans prior DEMO-* subscription rows for known demo slugs).
 */
class TenantDemoSeeder extends Seeder
{
    /** @var list<string> */
    private const DEMO_COMPANY_SLUGS = [
        'default-pharmacy',
        'demo-kumasi-pharmacy',
        'demo-tema-pharmacy',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $this->resetDemoBillingRows();

            $primary = Company::query()->orderBy('id')->first();
            if (! $primary) {
                $this->command->warn('No company row found; skipping tenant demo.');

                return;
            }

            $this->seedPrimaryCompany($primary);
            $kumasi = $this->seedKumasiCompany();
            $tema = $this->seedTemaCompany();

            $pkgBasic = SubscriptionPackage::query()
                ->where('name', 'Basic')
                ->where('billing_cycle', 'monthly')
                ->firstOrFail();
            $pkgAdvanced = SubscriptionPackage::query()
                ->where('name', 'Advanced')
                ->where('billing_cycle', 'monthly')
                ->firstOrFail();
            $pkgPremium = SubscriptionPackage::query()
                ->where('name', 'Premium')
                ->where('billing_cycle', 'monthly')
                ->firstOrFail();
            $pkgEnterpriseYearly = SubscriptionPackage::query()
                ->where('name', 'Enterprise')
                ->where('billing_cycle', 'yearly')
                ->firstOrFail();

            $now = Carbon::now();

            $subPrimary = $this->createSubscription(
                $primary,
                $pkgAdvanced,
                'active',
                $now->copy()->subMonths(4),
                $now->copy()->addMonth(),
                'Bank transfer',
                'HQ plan — multi-branch'
            );

            $subKumasi = $this->createSubscription(
                $kumasi,
                $pkgBasic,
                'active',
                $now->copy()->subMonths(2),
                $now->copy()->addMonth(),
                'Mobile Money',
                'Single-branch starter'
            );

            $subTema = $this->createSubscription(
                $tema,
                $pkgPremium,
                'expired',
                $now->copy()->subYear(),
                $now->copy()->subMonth(),
                'Card',
                'Trial ended — renewal pending'
            );

            $this->seedSubscriptionPayments($primary, $subPrimary, $pkgAdvanced->price);
            $this->seedSubscriptionPayments($kumasi, $subKumasi, $pkgBasic->price);
            $this->seedSubscriptionPayments($tema, $subTema, $pkgPremium->price, includeUnpaid: true);

            // Standalone invoice (e.g. setup fee) for Kumasi
            SubscriptionPayment::query()->updateOrCreate(
                ['invoice_reference' => 'DEMO-INV-SETUP-KUMASI'],
                [
                    'company_id' => $kumasi->id,
                    'tenant_subscription_id' => null,
                    'amount' => 150,
                    'payment_method' => 'Bank transfer',
                    'status' => 'paid',
                    'paid_at' => $now->copy()->subMonths(3),
                    'description' => 'One-time onboarding & training (demo)',
                ]
            );

            // Annual enterprise upgrade path (demo): separate company not required — attach to primary as historical
            $subEnterprise = $this->createSubscription(
                $primary,
                $pkgEnterpriseYearly,
                'cancelled',
                $now->copy()->subYears(2),
                $now->copy()->subYear(),
                'Bank transfer',
                'Previous yearly contract (cancelled at renewal)'
            );
            SubscriptionPayment::query()->updateOrCreate(
                ['invoice_reference' => 'DEMO-INV-ENT-YR-1'],
                [
                    'company_id' => $primary->id,
                    'tenant_subscription_id' => $subEnterprise->id,
                    'amount' => $pkgEnterpriseYearly->price,
                    'payment_method' => 'Bank transfer',
                    'status' => 'paid',
                    'paid_at' => $now->copy()->subYears(2)->addDay(),
                    'description' => 'Enterprise annual — first period',
                ]
            );

            $this->syncPackageSubscriberCounts();
            $this->seedDemoUsers($primary);
        });

        $this->command->info('Tenant demo: companies, sites, subscriptions, and payments seeded.');
    }

    private function resetDemoBillingRows(): void
    {
        $ids = Company::query()->whereIn('slug', self::DEMO_COMPANY_SLUGS)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        SubscriptionPayment::query()
            ->whereIn('company_id', $ids)
            ->where('invoice_reference', 'like', 'DEMO-%')
            ->delete();

        TenantSubscription::query()->whereIn('company_id', $ids)->delete();
    }

    private function seedPrimaryCompany(Company $primary): void
    {
        $primary->update([
            'company_name' => 'Sunrise Pharmacy Group',
            'company_email' => 'hq@sunrise-pharm.demo',
            'company_mobile' => '+233 30 123 4567',
            'company_address' => 'Oxford Street, Osu, Accra',
            'slug' => 'default-pharmacy',
            'is_active' => true,
        ]);

        $mainSiteId = (int) Site::query()
            ->where('company_id', $primary->id)
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id');

        if ($mainSiteId < 1) {
            $mainSiteId = Site::defaultId();
        }

        Site::query()->updateOrCreate(
            ['company_id' => $primary->id, 'code' => 'ACC-OSU'],
            [
                'name' => 'Osu satellite',
                'address' => '14 Osu Baden Powell Road, Accra',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        Site::query()->where('id', $mainSiteId)->update([
            'name' => 'Accra — Ring Road main',
            'code' => 'ACC-MAIN',
            'address' => 'Ring Road Central, Accra',
        ]);
    }

    private function seedKumasiCompany(): Company
    {
        $company = Company::query()->updateOrCreate(
            ['slug' => 'demo-kumasi-pharmacy'],
            [
                'company_name' => 'Kumasi Family Care Pharmacy',
                'company_email' => 'branch@kumasi-family.demo',
                'company_mobile' => '+233 32 445 8899',
                'company_address' => 'Adum, Kumasi',
                'is_active' => true,
            ]
        );

        Site::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'KUM-MAIN'],
            [
                'name' => 'Kumasi — Adum',
                'address' => 'Prempeh II Street, Adum',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        return $company->fresh();
    }

    private function seedTemaCompany(): Company
    {
        $company = Company::query()->updateOrCreate(
            ['slug' => 'demo-tema-pharmacy'],
            [
                'company_name' => 'Tema Community Meds',
                'company_email' => 'info@tema-community.demo',
                'company_mobile' => '+233 24 998 1122',
                'company_address' => 'Community 4, Tema',
                'is_active' => true,
            ]
        );

        Site::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'TEM-01'],
            [
                'name' => 'Tema — Community 4',
                'address' => 'Hospital Road, Tema',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        return $company->fresh();
    }

    private function createSubscription(
        Company $company,
        SubscriptionPackage $package,
        string $status,
        Carbon $startsAt,
        Carbon $endsAt,
        ?string $paymentMethod,
        ?string $notes
    ): TenantSubscription {
        return TenantSubscription::query()->create([
            'company_id' => $company->id,
            'subscription_package_id' => $package->id,
            'status' => $status,
            'payment_method' => $paymentMethod,
            'amount' => $package->price,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $notes,
        ]);
    }

    private function seedSubscriptionPayments(
        Company $company,
        TenantSubscription $subscription,
        $amountPerPeriod,
        bool $includeUnpaid = false
    ): void {
        $base = Carbon::now()->startOfMonth();

        for ($i = 0; $i < 3; $i++) {
            $paidAt = $base->copy()->subMonths(2 - $i)->addDays(5);
            SubscriptionPayment::query()->updateOrCreate(
                ['invoice_reference' => 'DEMO-INV-'.$company->id.'-'.($i + 1)],
                [
                    'company_id' => $company->id,
                    'tenant_subscription_id' => $subscription->id,
                    'amount' => $amountPerPeriod,
                    'payment_method' => $subscription->payment_method ?? 'Bank transfer',
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'description' => 'Subscription period '.($i + 1).' (demo)',
                ]
            );
        }

        if ($includeUnpaid) {
            SubscriptionPayment::query()->updateOrCreate(
                ['invoice_reference' => 'DEMO-INV-'.$company->id.'-UNPAID'],
                [
                    'company_id' => $company->id,
                    'tenant_subscription_id' => $subscription->id,
                    'amount' => $amountPerPeriod,
                    'payment_method' => null,
                    'status' => 'unpaid',
                    'paid_at' => null,
                    'description' => 'Renewal invoice — outstanding (demo)',
                ]
            );
        }
    }

    private function syncPackageSubscriberCounts(): void
    {
        SubscriptionPackage::query()->each(function (SubscriptionPackage $pkg) {
            $count = TenantSubscription::query()
                ->where('subscription_package_id', $pkg->id)
                ->where('status', 'active')
                ->count();
            $pkg->update(['subscriber_count' => $count]);
        });
    }

    private function seedDemoUsers(Company $primary): void
    {
        $siteId = (int) Site::query()
            ->where('company_id', $primary->id)
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id');

        if ($siteId < 1) {
            $siteId = Site::defaultId();
        }

        User::query()->updateOrCreate(
            ['email' => 'cashier@demo.test'],
            [
                'name' => 'Demo Cashier',
                'password' => bcrypt('secret'),
                'confirm_password' => bcrypt('secret'),
                'is_admin' => '1',
                'is_super_admin' => false,
                'company_id' => $primary->id,
                'site_id' => $siteId,
                'tenant_role' => 'cashier',
                'mobile' => '0244999001',
                'status' => '1',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'manager@demo.test'],
            [
                'name' => 'Demo Branch Manager',
                'password' => bcrypt('secret'),
                'confirm_password' => bcrypt('secret'),
                'is_admin' => '1',
                'is_super_admin' => false,
                'company_id' => $primary->id,
                'site_id' => $siteId,
                'tenant_role' => 'branch_manager',
                'mobile' => '0244999002',
                'status' => '1',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'tenant.admin@demo.test'],
            [
                'name' => 'Demo Tenant Admin',
                'password' => bcrypt('secret'),
                'confirm_password' => bcrypt('secret'),
                'is_admin' => '1',
                'is_super_admin' => false,
                'company_id' => $primary->id,
                'site_id' => $siteId,
                'tenant_role' => 'tenant_admin',
                'mobile' => '0244999003',
                'status' => '1',
            ]
        );
    }
}
