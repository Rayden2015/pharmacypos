<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('super-admin.dashboard') }}" class="btn btn-sm {{ request()->routeIs('super-admin.dashboard') ? 'btn-primary' : 'btn-outline-primary' }}">Dashboard</a>
    <a href="{{ route('super-admin.companies.index') }}" class="btn btn-sm {{ request()->routeIs('super-admin.companies.*') ? 'btn-primary' : 'btn-outline-primary' }}">Tenants (companies)</a>
    <a href="{{ route('super-admin.subscriptions.index') }}" class="btn btn-sm {{ request()->routeIs('super-admin.subscriptions.*') ? 'btn-primary' : 'btn-outline-primary' }}">Subscriptions</a>
    <a href="{{ route('super-admin.packages.index') }}" class="btn btn-sm {{ request()->routeIs('super-admin.packages.*') ? 'btn-primary' : 'btn-outline-primary' }}">Packages</a>
    <a href="{{ route('super-admin.domain') }}" class="btn btn-sm {{ request()->routeIs('super-admin.domain') ? 'btn-primary' : 'btn-outline-primary' }}">Domain</a>
    <a href="{{ route('super-admin.payments.index') }}" class="btn btn-sm {{ request()->routeIs('super-admin.payments.*') ? 'btn-primary' : 'btn-outline-primary' }}">Purchase transactions</a>
</div>
