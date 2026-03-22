<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('company_email');
            $table->boolean('is_active')->default(true)->after('slug');
        });

        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('billing_cycle', 16); // monthly, yearly
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('billing_days')->default(30);
            $table->unsignedInteger('subscriber_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('subscription_package_id')->constrained('subscription_packages')->restrictOnDelete();
            $table->string('status', 24)->default('active'); // active, expired, cancelled, pending
            $table->string('payment_method', 48)->nullable();
            $table->decimal('amount', 12, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_reference', 64)->nullable()->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('tenant_subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 48)->nullable();
            $table->string('status', 16)->default('paid'); // paid, unpaid, refunded
            $table->timestamp('paid_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->string('tenant_role', 32)->nullable()->after('is_super_admin');
        });

        $this->backfillTenancy();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'tenant_role']);
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('subscription_packages');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_active']);
        });
    }

    private function backfillTenancy(): void
    {
        $companyId = (int) DB::table('companies')->orderBy('id')->value('id');
        if ($companyId < 1) {
            $now = now();
            $companyId = (int) DB::table('companies')->insertGetId([
                'company_name' => 'Default Pharmacy',
                'company_address' => '',
                'company_mobile' => '',
                'company_email' => 'tenant@example.test',
                'slug' => 'default-pharmacy',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $row = DB::table('companies')->where('id', $companyId)->first();
            if ($row && empty($row->slug)) {
                DB::table('companies')->where('id', $companyId)->update([
                    'slug' => 'tenant-'.$companyId,
                    'is_active' => true,
                ]);
            }
        }

        foreach (DB::table('companies')->whereNull('slug')->get() as $c) {
            DB::table('companies')->where('id', $c->id)->update(['slug' => 'tenant-'.$c->id]);
        }

        DB::table('sites')->whereNull('company_id')->update(['company_id' => $companyId]);

        DB::table('users')->whereNull('company_id')->where('is_super_admin', 0)->update(['company_id' => $companyId]);
    }
};
