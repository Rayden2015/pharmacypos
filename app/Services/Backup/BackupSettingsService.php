<?php

namespace App\Services\Backup;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\SubscriptionPayment;
use App\Models\SupplierInvoice;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\Announcement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupSettingsService
{
    private const DISK = 'backups';

    public function isPlatformScope(?\App\Models\User $user): bool
    {
        return $user && $user->isSuperAdmin();
    }

    /**
     * @return list<array{name: string, path: string, created: \Carbon\Carbon}>
     */
    public function listSystemFiles(?\App\Models\User $user): array
    {
        $prefix = $this->systemPrefix($user);

        return $this->listFilesIn("{$prefix}/system");
    }

    /**
     * @return list<array{name: string, path: string, created: \Carbon\Carbon}>
     */
    public function listDatabaseFiles(?\App\Models\User $user): array
    {
        $prefix = $this->systemPrefix($user);

        return $this->listFilesIn("{$prefix}/database");
    }

    private function systemPrefix(?\App\Models\User $user): string
    {
        if ($this->isPlatformScope($user)) {
            return 'platform';
        }
        $cid = (int) ($user->company_id ?? 0);
        if ($cid < 1) {
            throw new \InvalidArgumentException('Tenant admin must have a company.');
        }

        return 'tenants/'.$cid;
    }

    /**
     * @return list<array{name: string, path: string, created: \Carbon\Carbon}>
     */
    private function listFilesIn(string $directory): array
    {
        $disk = Storage::disk(self::DISK);
        if (! $disk->exists($directory)) {
            return [];
        }
        $files = $disk->files($directory);
        $rows = [];
        foreach ($files as $path) {
            $name = basename($path);
            if ($name === '.gitignore' || str_starts_with($name, '.')) {
                continue;
            }
            $rows[] = [
                'name' => $name,
                'path' => $path,
                'created' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($path)),
            ];
        }
        usort($rows, fn ($a, $b) => $b['created']->timestamp <=> $a['created']->timestamp);

        return $rows;
    }

    public function generateSystemBackup(?\App\Models\User $user): string
    {
        if ($this->isPlatformScope($user)) {
            return $this->writePlatformSystemBackup();
        }

        return $this->writeTenantSystemBackup((int) $user->company_id);
    }

    public function generateDatabaseBackup(?\App\Models\User $user): string
    {
        if ($this->isPlatformScope($user)) {
            return $this->writePlatformDatabaseBackup();
        }

        return $this->writeTenantDatabaseExport((int) $user->company_id);
    }

    private function writePlatformSystemBackup(): string
    {
        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory('platform/system');

        $name = 'system_manifest_'.now()->format('Y-m-d_His').'.txt';
        $path = 'platform/system/'.$name;

        $lines = [
            'Pharmacy POS — platform system backup (summary)',
            'Generated: '.now()->toIso8601String(),
            'Laravel: '.app()->version(),
            'PHP: '.PHP_VERSION,
            'Environment: '.config('app.env'),
            '',
            'Table row counts (approximate):',
        ];

        $tables = $this->allTableNames();
        sort($tables);
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $lines[] = sprintf('  %s: %s', $table, number_format($count));
            } catch (\Throwable $e) {
                $lines[] = sprintf('  %s: (unreadable)', $table);
            }
        }

        $disk->put($path, implode("\n", $lines));

        return $path;
    }

    private function writeTenantSystemBackup(int $companyId): string
    {
        $disk = Storage::disk(self::DISK);
        $base = 'tenants/'.$companyId.'/system';
        $disk->makeDirectory($base);

        $name = 'business_data_backup_'.now()->format('Ymd_His').'.txt';
        $path = $base.'/'.$name;

        $company = Company::query()->find($companyId);
        $siteIds = Site::query()->where('company_id', $companyId)->pluck('id')->all();

        $lines = [
            'Pharmacy POS — tenant business summary backup',
            'Generated: '.now()->toIso8601String(),
            'Company ID: '.$companyId,
            'Company: '.($company->company_name ?? '(unknown)'),
            '',
            'Counts:',
            '  Sites: '.Site::query()->where('company_id', $companyId)->count(),
            '  Users: '.User::query()->where('company_id', $companyId)->count(),
            '  Products: '.Product::query()->where('company_id', $companyId)->count(),
            '  Customers: '.Customer::query()->whereIn('site_id', $siteIds ?: [0])->count(),
            '  Orders: '.($siteIds ? Order::query()->whereIn('site_id', $siteIds)->count() : 0),
            '  Prescriptions: '.($siteIds ? Prescription::query()->whereIn('site_id', $siteIds)->count() : 0),
            '  Doctors: '.($siteIds ? Doctor::query()->whereIn('site_id', $siteIds)->count() : 0),
            '  Supplier invoices: '.SupplierInvoice::query()->where('company_id', $companyId)->count(),
            '  Stock receipts (catalog): '.StockReceipt::query()->whereHas('product', fn ($q) => $q->where('company_id', $companyId))->count(),
        ];

        $disk->put($path, implode("\n", $lines));

        return $path;
    }

    private function writePlatformDatabaseBackup(): string
    {
        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory('platform/database');

        $connection = config('database.default');
        $name = 'full_db_backup_'.now()->format('Ymd_His');

        if ($connection === 'sqlite') {
            $src = config('database.connections.sqlite.database');
            if ($src === ':memory:' || ! is_string($src) || ! is_file($src)) {
                throw new \RuntimeException(
                    'SQLite is not using an on-disk database file (e.g. :memory: in tests). Use a file path in DB_DATABASE or MySQL for full platform dumps.'
                );
            }
            $destName = $name.'.sqlite';
            $path = 'platform/database/'.$destName;
            $full = $disk->path($path);
            if (! @copy($src, $full)) {
                throw new \RuntimeException('Could not copy SQLite database file.');
            }

            return $path;
        }

        if ($connection === 'mysql') {
            $cfg = config('database.connections.mysql');
            $dumpPath = 'platform/database/'.$name.'.sql';
            $full = $disk->path($dumpPath);

            $process = new Process([
                'mysqldump',
                '--single-transaction',
                '--quick',
                '-h', (string) ($cfg['host'] ?? '127.0.0.1'),
                '-P', (string) ($cfg['port'] ?? '3306'),
                '-u', (string) ($cfg['username'] ?? 'root'),
                '-p'.(string) ($cfg['password'] ?? ''),
                (string) ($cfg['database'] ?? ''),
            ]);
            $process->setTimeout(600);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::channel('audit')->warning('backup.mysql_dump_failed', [
                    'error' => $process->getErrorOutput(),
                    'exit' => $process->getExitCode(),
                ]);
                throw new \RuntimeException(
                    'mysqldump failed. Ensure mysqldump is installed and DB credentials are correct. '.$process->getErrorOutput()
                );
            }

            file_put_contents($full, $process->getOutput());

            return $dumpPath;
        }

        throw new \RuntimeException('Database driver "'.$connection.'" is not supported for automated full backup. Use SQLite or MySQL.');
    }

    private function writeTenantDatabaseExport(int $companyId): string
    {
        $disk = Storage::disk(self::DISK);
        $base = 'tenants/'.$companyId.'/database';
        $disk->makeDirectory($base);

        $name = 'tenant_data_backup_'.now()->format('Ymd_His').'.json';
        $path = $base.'/'.$name;

        $siteIds = Site::query()->where('company_id', $companyId)->pluck('id')->all();

        $payload = [
            'meta' => [
                'exported_at' => now()->toIso8601String(),
                'company_id' => $companyId,
                'format' => 'tenant_json_v1',
            ],
            'company' => Company::query()->find($companyId),
            'sites' => Site::query()->where('company_id', $companyId)->get(),
            'users' => User::query()->where('company_id', $companyId)->get()->makeHidden(['password', 'confirm_password', 'remember_token']),
            'products' => Product::query()->where('company_id', $companyId)->get(),
            'customers' => $siteIds
                ? Customer::query()->whereIn('site_id', $siteIds)->get()
                : [],
            'orders' => $siteIds
                ? Order::query()->whereIn('site_id', $siteIds)->with(['orderdetail', 'transaction'])->get()
                : [],
            'prescriptions' => $siteIds
                ? Prescription::query()->whereIn('site_id', $siteIds)->get()
                : [],
            'doctors' => $siteIds
                ? Doctor::query()->whereIn('site_id', $siteIds)->get()
                : [],
            'supplier_invoices' => SupplierInvoice::query()->where('company_id', $companyId)->get(),
            'tenant_subscriptions' => TenantSubscription::query()->where('company_id', $companyId)->get(),
            'subscription_payments' => SubscriptionPayment::query()->where('company_id', $companyId)->get(),
        ];

        if (Schema::hasTable('announcements')) {
            $payload['announcements'] = Announcement::query()->where('company_id', $companyId)->get();
        }

        if (Schema::hasTable('stock_receipts')) {
            $payload['stock_receipts'] = StockReceipt::query()
                ->whereHas('product', fn ($q) => $q->where('company_id', $companyId))
                ->get();
        }

        if (Schema::hasTable('inventory_movements')) {
            $payload['inventory_movements'] = InventoryMovement::query()
                ->whereHas('product', fn ($q) => $q->where('company_id', $companyId))
                ->limit(50000)
                ->get();
        }

        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($payload, $flags);
        if ($json === false) {
            throw new \RuntimeException('Could not encode tenant export JSON.');
        }

        $disk->put($path, $json);

        return $path;
    }

    public function assertDownloadAllowed(?\App\Models\User $user, string $relativePath): void
    {
        $relativePath = ltrim($relativePath, '/');
        $prefix = $this->systemPrefix($user).'/';
        if (! str_starts_with($relativePath, $prefix)) {
            abort(403);
        }
        if (! Storage::disk(self::DISK)->exists($relativePath)) {
            abort(404);
        }
    }

    public function safeBasename(string $filename): string
    {
        $name = basename($filename);
        if ($name === '' || $name === '.' || $name === '..') {
            abort(404);
        }
        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $name)) {
            abort(404);
        }

        return $name;
    }

    /**
     * @return list<string>
     */
    private function allTableNames(): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
                ->pluck('name')
                ->filter()
                ->values()
                ->all();
        }

        $dbName = DB::connection()->getDatabaseName();
        $rows = DB::select('SHOW TABLES');
        $key = 'Tables_in_'.$dbName;
        $out = [];
        foreach ($rows as $row) {
            if (isset($row->$key)) {
                $out[] = $row->$key;
            }
        }

        return $out;
    }
}

