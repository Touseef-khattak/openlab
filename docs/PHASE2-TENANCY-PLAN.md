# Plan: Multi-tenancy foundation (DB-per-tenant) for OpenLab-v2

## Context
OpenLab-v2 is a complete single-lab LIMS (Laravel 12.63 + Inertia/React, MySQL `openlab_v2`). Phase 2's goal is to make it a sellable product served two ways from one codebase: **SaaS** (many labs, each isolated) and **on-prem** (one lab, data in-house). This milestone builds the multi-tenancy *foundation* only — the engine that gives each lab its own database and auto-provisions it — so every later Phase-2 feature (branding, reports, billing) is automatically per-lab. It does **not** include a super-admin UI, self-signup, or billing.

Today the app is ideal for this: all 19 migrations, all seeders, and all authenticated routes are already per-lab, and there is **zero** connection-switching code anywhere (`stancl/tenancy` swaps the default connection at runtime, so models need no changes). No landlord concepts exist yet.

## Confirmed decisions
- **Tenant identity:** subdomain per lab — `acme.openlab.app` → tenant `acme`; central domain `openlab.app`. Local dev via `*.lvh.me`.
- **Users:** per-tenant — each lab's users + spatie roles/permissions live in its own DB (matches today). Central super-admin is a later milestone.
- **Scope:** plumbing only — tenancy engine, central DB (tenants/domains), auto create+migrate+seed+admin per tenant, `DEPLOYMENT_MODE=saas|onprem` switch (on-prem behaves exactly as today), data-isolation tests. No super-admin UI / signup / billing.

## Two decisions that shape everything
- **On-prem is the default, tenancy-inactive path.** `DEPLOYMENT_MODE` defaults to `onprem`: the package is installed and its provider registered, but no identification middleware runs, app routes load on the default connection, and `php artisan migrate` builds the whole schema on `openlab_v2` — byte-for-byte today's behavior. SaaS mode is purely additive (turns on domain identification + provisioning).
- **Database sessions stay on the CENTRAL connection.** If `sessions` lived in the tenant DB, Laravel's `StartSession` runs *before* `InitializeTenancyByDomain` switches the connection — a known stancl foot-gun that writes sessions to the wrong DB. Pinning `SESSION_CONNECTION=mysql` (central) sidesteps init-order entirely. Login still validates against the **tenant** `users` table (tenancy is initialized by route middleware before the `auth` guard resolves the user), and host-only cookies (`SESSION_DOMAIN=null`) keep subdomains isolated with no session bleed.

## Implementation (ordered)

**1. Install** — `composer require stancl/tenancy:^3.9`; `php artisan tenancy:install` (publishes `config/tenancy.php`, `routes/tenant.php`, `TenancyServiceProvider`, tenants+domains migrations; auto-registers provider in `bootstrap/providers.php`).

**2. Config**
- `config/openlab.php` (new): `['deployment_mode' => env('DEPLOYMENT_MODE', 'onprem')]`.
- `.env.example`: add `DEPLOYMENT_MODE=onprem`, `CENTRAL_DOMAIN=localhost`, `SESSION_CONNECTION=mysql`.
- `config/queue.php`: pin `connections.database.connection` to central so the jobs table stays central.

**3. `config/tenancy.php`**
- `tenant_model = App\Models\Tenant::class`.
- `central_domains = [env('CENTRAL_DOMAIN','localhost'), 'localhost', '127.0.0.1']` (+ `lvh.me` for dev).
- `database`: `prefix = 'openlab_tenant_'`, MySQL database manager.
- `bootstrappers`: Database (mandatory), Cache (isolates spatie permission cache), Filesystem (future per-tenant storage), Queue. **No** Redis bootstrapper.
- `migration_parameters['--path'] = [database_path('migrations/tenant')]`.
- `seeder_parameters['--class'] = Database\Seeders\TenantDatabaseSeeder::class`.

**4. Migration split** — create `database/migrations/tenant/`.
- **Central** (`database/migrations/`): stancl `tenants` (extend with `name`, `plan` columns) + `domains`; a **new sessions-only** migration (extracted from the users migration); keep `jobs`/`job_batches`/`failed_jobs` central.
- **Tenant** (`database/migrations/tenant/`): a **new users+password_reset_tokens** migration (rest of the split); `cache`; spatie `permission_tables`; `activity_log` (+2 alters); all clinical tables (patients…results, +reported_at alter); `lab_settings`.
- Only real edit: split `0001_01_01_000000_create_users_table.php` into (tenant) users+password-reset and (central) sessions. Other 17 move verbatim. No cross-schema FKs exist, so no FK changes.

**5. Models** — `app/Models/Tenant.php` (extends stancl `Tenant`, implements `TenantWithDatabase`, uses `HasDatabase, HasDomains`; `getCustomColumns()` = `['id','name','plan']`); `app/Models/Domain.php` (thin subclass of stancl `Domain`).

**6. `TenancyServiceProvider`** (the hub)
- `events()`: `TenantCreated` → `JobPipeline[CreateDatabase, MigrateDatabase, SeedDatabase]`, `shouldBeQueued(false)` (synchronous provisioning for the foundation).
- `boot()`: register tenant routes with a **mode-computed** middleware stack:
  - saas → `['web', InitializeTenancyByDomain, PreventAccessFromCentralDomains]`
  - onprem → `['web']` (no tenancy middleware)
  - `Route::middleware($stack)->group(base_path('routes/tenant.php'))`.
- `boot()`: on-prem only → `$this->loadMigrationsFrom(database_path('migrations/tenant'))` so plain `migrate` builds tenant tables on the default DB.

**7. Route split**
- `routes/web.php` → central only (keep `/` welcome).
- `routes/tenant.php` → the entire authenticated app block (dashboard, patients, tests, doctors, visits, worklist, verification, reports, audit-log) + `require settings.php; require auth.php;`. `settings.php`/`auth.php` unchanged.

**8. Provisioning**
- `database/seeders/TenantDatabaseSeeder.php` (new): calls `RolePermissionSeeder` + `TestCatalogSeeder` (no dev users). Existing `DatabaseSeeder` left untouched (serves on-prem `db:seed` + tests).
- `app/Console/Commands/CreateTenant.php` (new): `tenant:create {name} {domain} {--admin-email=} {--admin-password=}` → `Tenant::create` (fires the pipeline) → add domain → `$tenant->run()` creates the first admin user + `assignRole('admin')`.

**9. `HandleInertiaRequests::share()`** — add `'tenant' => tenancy()->initialized ? ['id'=>tenant('id'),'name'=>tenant('name')] : null` (null on-prem / central). No React change required for the foundation.

## Verification
- **On-prem (must be unchanged):** unset `DEPLOYMENT_MODE`; `php artisan migrate:fresh --seed`; log in at `localhost` — behaves as today; **run full suite, expect 70 green** (testing runs the on-prem path, `RefreshDatabase` builds central+tenant tables into `:memory:`; no test files change).
- **SaaS:** `DEPLOYMENT_MODE=saas`, `CENTRAL_DOMAIN=lvh.me`; `php artisan migrate` (landlord only); `php artisan tenant:create Acme acme.lvh.me --admin-email=admin@acme.test --admin-password=secret`; visit `http://acme.lvh.me:8000`, log in; confirm `openlab_tenant_*` DB exists, seeded, admin works.
- **Isolation test** (`tests/Feature/Tenancy/TenantIsolationTest.php`): `tenancy()->initialize($a)` → create patient → count 1; `initialize($b)` → count 0. **Runs against real MySQL** in a dedicated `Tenancy` test group (DB-per-tenant can't use shared `:memory:` sqlite); explicit tenant teardown. Excluded from the fast default suite.

## Top risks / gotchas
1. Session/auth init-order — mitigated by central sessions (decision above); if ever moved to tenant DB, must also set middleware priority.
2. `SESSION_DOMAIN` must stay `null` (host-only) — a shared parent domain would leak sessions across subdomains.
3. Spatie permission cache — Cache bootstrapper + tenant-local cache table prevent cross-lab leakage; `RolePermissionSeeder` already calls `forgetCachedPermissions()`.
4. `RefreshDatabase` refreshes only the central connection — tenancy tests need their own provisioning + teardown, kept out of the `:memory:` suite.
5. Queue jobs need tenant context — provisioning is synchronous in the foundation to avoid this; future tenant jobs must dispatch while tenancy is initialized.
6. SaaS `migrate:fresh --seed` rebuilds only the landlord DB — tenant schemas use `tenants:migrate-fresh --seed`. On-prem unchanged. Document to avoid confusion.
7. MySQL user needs `CREATE/DROP DATABASE`; watch the 64-char DB-name limit vs `openlab_tenant_` + UUID.
8. On-prem carries unused tenants/domains/central-session tables — harmless.

## Critical files
- `app/Providers/TenancyServiceProvider.php` — the hub (events pipeline, mode-based route + migration-path registration)
- `config/tenancy.php`, `config/openlab.php` (new)
- `bootstrap/app.php` (central web routing) + route split `routes/web.php` / new `routes/tenant.php`
- `database/migrations/0001_01_01_000000_create_users_table.php` (split anchors the migration reorg) + new `database/migrations/tenant/`
- `app/Models/Tenant.php`, `app/Models/Domain.php` (new)
- `database/seeders/TenantDatabaseSeeder.php`, `app/Console/Commands/CreateTenant.php` (new)
- `app/Http/Middleware/HandleInertiaRequests.php` (tenant prop)

## Suggested commit staging (if implemented)
1. install stancl/tenancy + register provider
2. config (tenancy, openlab, session/queue env)
3. Tenant/Domain models + extend tenants migration
4. migration split (users/sessions) + move to tenant/
5. TenancyServiceProvider (events + mode-based routes/migrations)
6. route split (web central / tenant.php)
7. TenantDatabaseSeeder + tenant:create command
8. HandleInertiaRequests tenant prop
9. isolation test suite (MySQL group)
10. docs (ROADMAP) + dev-setup notes
