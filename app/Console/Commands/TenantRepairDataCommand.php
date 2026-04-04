<?php

namespace App\Console\Commands;

use App\Support\TenantDataConformance;
use Illuminate\Console\Command;

class TenantRepairDataCommand extends Command
{
    protected $signature = 'tenant:repair-data
                            {--company= : Limit user/transaction repairs to this company id (suppliers only repaired when company is not set)}
                            {--dry-run : List violations without changing data}';

    protected $description = 'Check multi-tenant / multi-branch data alignment and repair common drift (users, payment rows, orphaned suppliers)';

    public function handle(): int
    {
        $companyOpt = $this->option('company');
        $onlyCompanyId = $companyOpt !== null && $companyOpt !== '' ? (int) $companyOpt : null;

        $violations = TenantDataConformance::violations($onlyCompanyId);

        if ($this->option('dry-run')) {
            if ($violations === []) {
                $this->info('No conformance violations found.');

                return 0;
            }
            foreach ($violations as $v) {
                $this->warn(($v['type'] ?? 'unknown').': '.($v['message'] ?? ''));
                if (! empty($v['meta'])) {
                    $this->line(json_encode($v['meta']));
                }
            }

            return 1;
        }

        if ($violations !== []) {
            $this->warn('Repairing '.count($violations).' violation type(s)…');
        }

        $stats = TenantDataConformance::repair($onlyCompanyId);

        foreach ($stats as $k => $n) {
            $this->line($k.': '.$n);
        }

        $remaining = TenantDataConformance::violations($onlyCompanyId);
        foreach ($remaining as $v) {
            if (($v['type'] ?? '') === 'order_line_product_company_mismatch') {
                $this->error($v['message'].' — fix manually (lines point at SKUs outside the order branch).');
            } else {
                $this->warn(($v['type'] ?? 'unknown').': '.($v['message'] ?? ''));
            }
        }

        if ($remaining !== []) {
            return 1;
        }

        $this->info('Conformance OK.');

        return 0;
    }
}
