<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $viewer = $request->user();
        $q = $request->input('q');

        $query = Site::query()
            ->forUserTenant($viewer)
            ->orderBy('name');

        if ($viewer->isSuperAdmin()) {
            $query->with(['company:id,company_name']);
        }

        if (is_string($q) && trim($q) !== '') {
            $term = '%'.addcslashes(trim($q), '%_\\').'%';
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('manager_name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        $sites = $query->paginate(20)->withQueryString();

        return view('sites.index', compact('sites', 'q'));
    }

    public function create(): View
    {
        $companies = auth()->user()->isSuperAdmin()
            ? Company::query()->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        return view('sites.create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $viewer = $request->user();

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:32|unique:sites,code',
            'address' => 'nullable|string|max:2000',
            'manager_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'is_default' => 'nullable|boolean',
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }
        $data = $request->validate($rules);

        $companyId = $viewer->isSuperAdmin()
            ? (int) $data['company_id']
            : (int) ($viewer->company_id ?? Company::defaultId());

        if (! empty($data['is_default'])) {
            Site::query()->where('company_id', $companyId)->update(['is_default' => false]);
        }

        Site::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'address' => $data['address'] ?? null,
            'manager_name' => $data['manager_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'is_active' => true,
            'is_default' => ! empty($data['is_default']),
        ]);

        return redirect()->route('sites.index')->with('success', 'Site created.');
    }

    public function edit(Site $site): View
    {
        $this->authorizeSiteAccess($site);

        $companies = auth()->user()->isSuperAdmin()
            ? Company::query()->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        return view('sites.edit', compact('site', 'companies'));
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $this->authorizeSiteAccess($site);

        $viewer = $request->user();
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:32|unique:sites,code,'.$site->id,
            'address' => 'nullable|string|max:2000',
            'manager_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }
        $data = $request->validate($rules);

        $companyId = $viewer->isSuperAdmin()
            ? (int) $data['company_id']
            : (int) $site->company_id;

        if (! empty($data['is_default'])) {
            Site::query()->where('company_id', $companyId)->where('id', '!=', $site->id)->update(['is_default' => false]);
        }

        $isDefault = $site->is_default || ! empty($data['is_default']);

        $site->update([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'address' => $data['address'] ?? null,
            'manager_name' => $data['manager_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_default' => $isDefault,
        ]);

        return redirect()->route('sites.index')->with('success', 'Site updated.');
    }

    public function destroy(Site $site): RedirectResponse
    {
        $this->authorizeSiteAccess($site);

        if ($site->is_default) {
            return redirect()->route('sites.index')->with('error', 'Cannot delete the head office (default branch). Edit it instead.');
        }

        if (ProductSiteStock::query()->where('site_id', $site->id)->where('quantity', '>', 0)->exists()) {
            return redirect()->route('sites.index')->with('error', 'Cannot delete: this site still has stock on hand.');
        }

        if (ProductSiteStock::query()->where('site_id', $site->id)->exists()) {
            ProductSiteStock::query()->where('site_id', $site->id)->delete();
        }

        $site->delete();

        return redirect()->route('sites.index')->with('success', 'Site deleted.');
    }

    public function switch(Request $request): RedirectResponse
    {
        $raw = $request->input('site_id');

        if ($raw === 'all') {
            if ($request->user()->isSuperAdmin()) {
                session([
                    'dashboard_all_sites' => true,
                    'dashboard_all_branches' => false,
                ]);

                Audit::record(
                    'site.dashboard_all_scope',
                    ['dashboard_all_sites' => false],
                    ['dashboard_all_sites' => true],
                    null,
                    null,
                    auth()->id(),
                    ['audit_channel' => 'controller']
                );

                return redirect()->back()->with('success', 'Dashboard view: all sites.');
            }

            if (! $request->user()->isTenantAdmin()) {
                throw new AccessDeniedHttpException('Only tenant administrators can view dashboard metrics for all branches.');
            }

            $branches = Site::forSessionSwitcher($request->user());
            if ($branches->count() < 2) {
                throw new AccessDeniedHttpException('All branches dashboard view requires more than one branch.');
            }

            session([
                'dashboard_all_branches' => true,
                'dashboard_all_sites' => false,
            ]);

            Audit::record(
                'site.dashboard_all_branches',
                ['dashboard_all_branches' => false],
                ['dashboard_all_branches' => true],
                null,
                null,
                auth()->id(),
                ['audit_channel' => 'controller']
            );

            return redirect()->back()->with('success', 'Dashboard view: all branches in your organization.');
        }

        $request->validate([
            'site_id' => 'required|exists:sites,id',
        ]);

        $site = Site::query()->findOrFail((int) $request->site_id);
        if (! $request->user()->isSuperAdmin()) {
            if ((int) $site->company_id !== (int) ($request->user()->company_id ?? 0)) {
                throw new AccessDeniedHttpException('That branch does not belong to your organization.');
            }
            $allowed = Site::forSessionSwitcher($request->user())->pluck('id')->map(fn ($id) => (int) $id);
            if (! $allowed->contains((int) $request->site_id)) {
                throw new AccessDeniedHttpException('You cannot switch to that branch.');
            }
        }

        $previous = session('current_site_id');
        session([
            'current_site_id' => (int) $request->site_id,
            'dashboard_all_sites' => false,
            'dashboard_all_branches' => false,
        ]);

        Audit::record(
            'site.switch',
            $previous !== null ? ['current_site_id' => (int) $previous] : null,
            ['current_site_id' => (int) $request->site_id],
            null,
            null,
            auth()->id(),
            ['audit_channel' => 'controller']
        );

        return redirect()->back()->with('success', 'Active site updated.');
    }

    private function authorizeSiteAccess(Site $site): void
    {
        $viewer = auth()->user();
        if ($viewer->isSuperAdmin()) {
            return;
        }
        if ((int) $site->company_id !== (int) ($viewer->company_id ?? 0)) {
            abort(403);
        }
    }
}
