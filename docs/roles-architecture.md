# Roles & permissions architecture

This document describes the **default hierarchy** every tenant receives, how **dynamic roles** work, and how **future** platform/tenant scopes (divisional / regional) can fit the same system.

## Default levels (reference model)

| Level | Role | Scope | Notes |
|------:|------|--------|--------|
| **L1** | **Super Admin** | Platform | Operates outside tenant teams (`company_id` null). Not a Spatie role inside a tenant. |
| **L2** | **Tenant Admin** | Whole organization (tenant) | Full Spatie role per company; syncs all catalog permissions in bootstrap. |
| **L3** | **Branch Manager** | Branch-first, often broad ops | Default permission set (POS, inventory, users, sites, etc.). |
| **L4** | **Supervisor** | Branch / floor | Narrower POS + reporting + prescriptions subset. |
| **L5** | **Cashier** | POS/checkout | Minimal set (POS, view products, customers, transactions). |

Legacy `users.is_admin` is still mapped in `TenantRolesBootstrapSeeder` when `tenant_role` is empty (e.g. `1` → Supervisor, `2` → Cashier, `3` → Branch Manager).

New tenants should prefer **explicit `tenant_role`** (`tenant_admin`, `branch_manager`, `supervisor`, `cashier`) on `users` plus Spatie assignment via the same seeder.

## Dynamic roles (per tenant)

- **Permission catalog** is global (`PermissionCatalogSeeder`): names like `pos.access`, `tenant.users.manage`, etc.
- **Roles are tenant-scoped** (`teams` = `company_id` on Spatie `roles`).
- **Built-in names are reserved**: `Tenant Admin`, `Branch Manager`, `Supervisor`, `Cashier` — created by `TenantRolesBootstrapSeeder`, not editable as names.
- **Custom roles**: Tenant Admin (or anyone with `tenant.roles.manage`) can create **additional** roles via `Tenant\RoleController` and assign any subset of catalog permissions.

So: L2–L5 are the **defaults**; a tenant can add e.g. “Dispensary lead” or “Auditor” as extra roles.

## Future: Divisional admin (platform, multi-tenant)

**Intent:** Super Admin delegates **multiple tenants** to a **divisional** operator (sales region, franchise group, etc.).

**Direction (not implemented yet):**

- Keep L1 Super Admin as today.
- Introduce a platform-scoped identity (e.g. `divisional_admin` flag or `role` on `users` with `company_id` null) **and** a pivot e.g. `divisional_admin_companies` (`user_id`, `company_id`) listing which tenants they manage.
- Middleware: `SetPermissionTeamFromAuth` already sets Spatie team from `company_id`; divisional admins would need **explicit tenant switch** or **query scoping** to companies in the pivot (similar in spirit to site switching).

## Future: Regional admin (tenant, multi-site)

**Intent:** Tenant Admin delegates **multiple sites/branches** to a **regional** manager (area manager).

**Direction (not implemented yet):**

- Today `users.site_id` is a **single** home branch.
- Regional scope would need either:
  - `user_sites` pivot (`user_id`, `site_id`) with allowed sites, **or**
  - a dedicated “region” entity grouping sites.
- Authorization and dashboards would filter by `site_id IN (allowed)` instead of a single `site_id`.

## Where this lives in code

| Concern | Location |
|--------|----------|
| Permission names | `PermissionCatalogSeeder` |
| Default L2–L5 roles per company | `TenantRolesBootstrapSeeder` |
| `tenant_role` → Spatie role | Same seeder + `User::$tenant_role` |
| Custom tenant roles UI | `App\Http\Controllers\Tenant\RoleController` |
| Team id for permission checks | `SetPermissionTeamFromAuth` (`company_id`) |
| Route authorization (`can:…`) | `routes/web.php` — reports, dashboard CSV export, POS (`OrderController`) |
| Super Admin bypass | `AuthServiceProvider` `Gate::before` (all abilities allowed for platform users) |
| Report / export audit trail | `App\Support\ReportAuditLogger` → `audit` log channel + `audit_logs` for exports & prints (optional HTML views via `AUDIT_LOG_REPORT_VIEWS`) |
| Batch / lot listing & CSV | `inventory.batches` + `inventory.batches.export` require `inventory.view`; tenant users only see receipts for sites in their company |
| Permission denials (403) | `Handler::report` logs `auth.policy.denied` to the `audit` channel |

---

*When divisional or regional behavior is implemented, update this doc and add migrations + policies so access is enforced in one place.*
