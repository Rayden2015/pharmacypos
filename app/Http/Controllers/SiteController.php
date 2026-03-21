<?php

namespace App\Http\Controllers;

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

    public function index(): View
    {
        $sites = Site::query()->orderBy('name')->paginate(20);

        return view('sites.index', compact('sites'));
    }

    public function create(): View
    {
        return view('sites.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:32|unique:sites,code',
            'address' => 'nullable|string|max:2000',
            'is_default' => 'nullable|boolean',
        ]);

        if (! empty($data['is_default'])) {
            Site::query()->update(['is_default' => false]);
        }

        Site::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => true,
            'is_default' => ! empty($data['is_default']),
        ]);

        return redirect()->route('sites.index')->with('success', 'Site created.');
    }

    public function edit(Site $site): View
    {
        return view('sites.edit', compact('site'));
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:32|unique:sites,code,'.$site->id,
            'address' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        if (! empty($data['is_default'])) {
            Site::query()->where('id', '!=', $site->id)->update(['is_default' => false]);
        }

        $site->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_default' => ! empty($data['is_default']),
        ]);

        return redirect()->route('sites.index')->with('success', 'Site updated.');
    }

    public function destroy(Site $site): RedirectResponse
    {
        if ($site->is_default) {
            return redirect()->route('sites.index')->with('error', 'Cannot delete the default site.');
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
            if (! $request->user()->isSuperAdmin()) {
                throw new AccessDeniedHttpException('Only super admins can view dashboard metrics for all sites.');
            }

            session(['dashboard_all_sites' => true]);

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

        $request->validate([
            'site_id' => 'required|exists:sites,id',
        ]);

        $previous = session('current_site_id');
        session([
            'current_site_id' => (int) $request->site_id,
            'dashboard_all_sites' => false,
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
}
