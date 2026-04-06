<?php

namespace App\Console\Commands;

use App\Support\TenantDataConformance;
use Illuminate\Console\Command;

/**
 * Read-only tenant / branch integrity check. For auto-fixable drift, see {@see TenantRepairDataCommand}.
 */
class TenantCheckDataIntegrityCommand extends Command
{
    protected $signature = 'tenant:check-data-integrity
                            {--company= : Limit user / transaction / order-line checks to this company id (structural counts are always global)}
                            {--json : Output violations as JSON}';

    protected $description = 'Report multi-tenant data integrity issues (no writes). Repair: php artisan tenant:repair-data';

    public function handle(): int
    {
        $companyOpt = $this->option('company');
        $onlyCompanyId = $companyOpt !== null && $companyOpt !== '' ? (int) $companyOpt : null;

        $violations = TenantDataConformance::violations($onlyCompanyId);

        if ($this->option('json')) {
            $this->line(json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $violations === [] ? 0 : 1;
        }

        if ($violations === []) {
            $this->info('Tenant data integrity OK.');

            return 0;
        }

        $this->error(count($violations).' integrity issue group(s) found.');
        foreach ($violations as $v) {
            $this->warn(($v['type'] ?? 'unknown').': '.($v['message'] ?? ''));
            if (! empty($v['meta'])) {
                $this->line(json_encode($v['meta'], JSON_UNESCAPED_SLASHES));
            }
        }

        return 1;
    }
}
