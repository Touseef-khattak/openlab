# OpenLab v2 — LIMS Rebuild: Roadmap & Progress Tracker

> **This is the canonical living plan.** Any agent or developer working on this project must:
> 1. Read this file top to bottom before starting work.
> 2. After each work session, tick completed checkboxes and append a dated entry to the **Progress Log** at the bottom.
> 3. Keep the status snapshot in Claude memory (`openlab-progress.md` in the memory directory for `D:\Projects\Lab\OpenLab`) in sync with the **Current Status** section.

---

## Vision

Rebuild the legacy OpenLab lab-management app (vanilla PHP, at `D:\Projects\Lab\OpenLab`) into a sellable, multi-deployment LIMS for small/mid-size medical laboratories, initially targeting the Pakistani / South Asian market (CNIC identifiers, Asia/Karachi timezone, doctor-referral commissions, WhatsApp report delivery).

**Business model:** monthly subscription per lab, tiered by users/branches/message credits. Two deployment models from ONE codebase:
- **Cloud SaaS** — multi-tenant, database-per-tenant.
- **On-premise** — same app running single-tenant, shipped as Docker Compose with a license key (offline grace period).

## Locked architecture decisions (do not re-litigate without the user)

| Decision | Choice |
|---|---|
| Framework | Laravel 12 (scaffolded from `laravel/react-starter-kit` v1.0.1) |
| Frontend | Inertia v2 + React 19, Tailwind, shadcn/ui; TanStack Table for grids |
| Database | MySQL (SQLite only as temporary dev default from the starter kit) |
| Multi-tenancy | `stancl/tenancy`, **database-per-tenant**; on-prem = single tenant, tenancy layer inactive |
| Admin UI | NO Filament for the product. Optionally Filament later for the internal SaaS super-admin panel only |
| Roles/permissions | `spatie/laravel-permission` (named roles, not magic numbers) |
| Legacy app | Reference only — do NOT modify it. Phase 0 (patching its security holes) was **explicitly skipped by the user** |

## Key paths & environment

- New app: `D:\Projects\Lab\OpenLab-v2` (this repo)
- Legacy app (reference for features/schema): `D:\Projects\Lab\OpenLab` — schema dump at `php/laboratory.sql` (9 tables: users, user_role, doctors, patients, patient_test, patient_test_record, tests, related_tests, units)
- Dev machine: Windows 11, PHP 8.2.0, Composer 2.5.8, Node 22, MySQL via localhost (root, no password — legacy `laboratory` DB has real data)
- ⚠️ PHP 8.2.0 is the unpatched 2022 release — upgrade to 8.3+ before any production deployment.

## Legacy feature inventory (parity checklist for Phase 1)

Patient registration (CNIC, name, age, gender, phone) → invoice with tests, referring doctor, specimen, amount/discount/net bill → result entry per test (incl. related/child tests) → result verification (role 3 = pathologist went straight to results screen) → printed report + invoice. Roles: 1 admin, 2 front desk, 3 results. Backup download page.

---

## Phase 1 — Core product at feature parity (CURRENT PHASE)

### 1.1 Scaffold & dev environment
- [x] Scaffold Laravel 12 + React starter kit into `OpenLab-v2` (2026-07-13)
- [x] `npm install` + `npm run build` succeed (2026-07-13)
- [x] Switch `.env` to MySQL (`openlab_v2` database), migrate (2026-07-13 — XAMPP MariaDB 10.4.25 at `C:\xampp-8`; ⚠️ below Laravel's officially supported MariaDB floor, worked fine so far; plan a DB upgrade alongside PHP 8.3)
- [x] Dev server boots; home + login pages return HTTP 200 on MySQL (2026-07-13)
- [x] `git init` done (2026-07-13) — [ ] initial commit pending user go-ahead

### 1.2 Domain schema & models — ✅ DONE 2026-07-13
Design for tenancy from day one (no cross-schema FKs, tenant-agnostic code).
- [x] `patients` — mrn, cnic (nullable, indexed), name, dob + legacy age_years, gender, phone (indexed)
- [x] `doctors` — name, specialty, phone, commission_percent, is_active
- [x] `test_categories`, `tests` — code (unique), name, category, price, specimen type, is_panel
- [x] `test_parameters` (replaces legacy `related_tests`) — every test has ≥1 parameter; result_type numeric/text/select, unit, decimals, options JSON, display order
- [x] `reference_ranges` — per parameter, gender- and age-banded (months), low/high, critical low/high, normal_text
- [x] `visits` (replaces `patient_test`) — visit_number, patient, referring doctor, visited_at, amounts, payment_status, created_by
- [x] `samples` — visit, specimen type, sample_number (for Phase 3 barcodes), status enum `registered → collected → in_progress → resulted → verified → reported` (transitions encoded in `App\Enums\SampleStatus`)
- [x] `visit_tests` — visit↔test with price copied at order time; links to sample
- [x] `results` — visit_test + parameter (unique pair), value + value_numeric mirror, flag, entered_by/at, verified_by/at
- [x] Audit: `spatie/laravel-activitylog` 4.12 installed + `activity_log` table migrated (LogsActivity traits get wired onto models when write flows land in 1.4–1.6); `spatie/laravel-permission` 6.25 installed + tables migrated for 1.3
- [x] Factories (Patient, Doctor) + `TestCatalogSeeder` (4 categories, 11 tests, 37 parameters, 38 gender-specific ranges: CBC, ESR, glucose, HbA1c, LFT, RFT, lipid profile, HBsAg, Anti-HCV, urine R/E)
- [x] Verified via tinker: counts correct; `TestParameter::rangeFor()` resolves gender-specific bands; `ReferenceRange::flagFor()` returns low/normal/critical_low correctly

### 1.3 Auth & roles — ✅ DONE 2026-07-13
- [x] `spatie/laravel-permission`; roles: **admin**, **receptionist**, **technician**, **pathologist** with 14 module permissions (`RolePermissionSeeder`); admin bypasses all checks via `Gate::before` in `AppServiceProvider`
- [x] Public self-registration removed (routes + `RegisteredUserController` + register page deleted; login/welcome links cleaned; verified `/register` → 404, `/login` → 200). Dev users seeded, all password `password`: admin@ / reception@ / tech@ / path@ `openlab.test`
- [ ] Per-model policies land with each module in 1.4–1.6 (checks currently via `can:` permission middleware/gates)

### 1.4 Front desk: patients, doctors, invoicing — ✅ DONE 2026-07-13
- [x] Patient search-or-create by phone/CNIC/MRN/name with debounced autocomplete + history (`PatientController::search`, min 3 chars); MRN auto-assigned (`P000001`)
- [x] Doctor CRUD (dialog form, commission %, active toggle) at `/doctors`
- [x] New visit flow at `/visits/create`: grouped searchable test picker, price snapshot, discount/net/paid with payment status derivation, doctor referral select. `CreateVisit` action (transaction): resolves patient → visit (day-sequenced `V-yymmdd-###` via `Visit::nextNumber`) → one sample per specimen type (`{visit}-S1…`) → visit_tests → pre-created empty result rows for the bench worklist
- [x] Visits list with search, payment badge, verified-samples progress; visit detail page doubles as printable receipt (print CSS visibility trick)
- [x] Patients list page with search
- [x] Sidebar nav (New Visit/Visits/Patients/Doctors) filtered by shared `auth.permissions` (`use-can` hook); flash messages shared via Inertia middleware
- [x] Pest installed (v3.8, PHP 8.2-capped); 6 front-desk feature tests + starter suite = **30 passed**. Obsolete RegistrationTest removed
- [ ] Thermal-format receipt (80mm) — deferred; A4 print works via visit page

### 1.5 Lab bench: worklist & result entry — ✅ DONE 2026-07-14
- [x] Worklist at `/worklist`: status tabs with live counts (awaiting collection → reported), oldest-first queue, Collect action (guarded by `SampleStatus::allowedTransitions`), permission `results.enter` (receptionists 403)
- [x] Keyboard-first entry grid at `/samples/{id}/entry`: Enter/↑/↓ moves between inputs, instant client-side H/L/CRIT flags (server recomputes authoritatively via `rangeFor`/`flagFor` on save), previous-result column with date for delta checks, select-type parameters (HBsAg etc.) as dropdowns, filled/total counter
- [x] Panel entry: all parameters of a test on one grid, grouped per test card
- [x] Flow rules: draft save moves collected → in_progress; submit requires every parameter filled and moves to resulted; results editable until verification; foreign-sample result IDs rejected; edits blocked once verified/reported (409)
- [x] 7 feature tests (suite now **37 passed / 121 assertions**)

### 1.6 Verification & reporting — ✅ DONE 2026-07-18
- [x] Verification queue at `/verification` (`can:results.verify`): resulted samples with abnormal/critical flag counts; review screen reuses the entry payload (`BuildSampleResultsPayload`, extracted from ResultEntryController) read-only with critical rows highlighted; **Verify & sign off** stamps verified_by/verified_at on all results and moves sample → verified; **Return to bench** sends it back to in_progress for correction
- [x] PDF lab report via `barryvdh/laravel-dompdf` at `/visits/{id}/report` (`can:reports.view`): header placeholder for Phase 2 branding, patient/visit meta, per-specimen sections, parameter rows with value/flag/unit/patient-specific range, verified-by footer + signature line; streaming the report marks verified samples → reported (+ new `reported_at` column)
- [x] No report until verified: 409 before verification; unverified samples never appear in the PDF
- [x] 7 feature tests (suite now **44 passed / 141 assertions**)

### 1.7 Migration & hardening
- [ ] ~~Artisan command to import legacy `laboratory` MySQL data~~ **DEFERRED by user 2026-07-20** — tracked as session task #1 with full legacy→v2 schema mapping; legacy dump reference: `D:\Projects\Lab\OpenLab\php\laboratory.sql`
- [x] Feature tests for the critical flows (registration → entry → verify → report) — covered across FrontDesk/LabBench/Verification/DashboardStats suites, **46 passed / 161 assertions**
- [x] Dashboard with real stats (`DashboardController`): visits/revenue today, unpaid balance, patients, pipeline counts (pending collection / on bench / awaiting verification / reported today) + recent visits; pipeline cards deep-link into worklist & verification
- [x] Wired `LogsActivity` onto clinical models via `RecordsActivity` trait (Patient, Doctor, Visit, Sample, Result) — deferred from 1.2, now done
- [x] Self-review pass done (skill `/code-review` is user-triggered only). One follow-up noted, non-blocking: pre-created empty Result rows each emit a "created" audit entry → audit noise on big panels; revisit when the Phase 2 audit-log viewer lands (consider logging Result on update-only)

**Phase 1 done when:** a lab can run its full daily workflow end-to-end on v2 without touching the legacy app.

### 1.8 Test catalog management (added 2026-07-20, user request) — ✅ DONE
Legacy had an Add Test / Add Value / Edit / Delete UI (`OpenLab\pages\tests.php`); v2 shipped seed-only until now.
- [x] Tests CRUD at `/tests` (`catalog.view` to list, `catalog.manage` — admin — to mutate): list w/ search + active filter, create, edit, **activate/deactivate toggle** (user-requested), delete
- [x] Single vs Multiple test types (legacy parity): Single = one parameter mirroring the test name + one reference range; Multiple = panel with N named parameters, each with its own ranges. Radio switch on the form
- [x] Richer than legacy where it matters: proper `unit` field (legacy stuffed units into the max string), result_type numeric/text/select (select = options list), per-parameter reference ranges with **optional gender + age band** (defaults to "All" = legacy-simple min/max), critical low/high, normal_text
- [x] `SaveTest` action: safe param sync — match by id, create new, delete dropped **only when unused** (results `restrictOnDelete`); ranges cascade-rewritten per save; auto code-gen from name when blank (unique). Delete blocked if test was ever ordered (deactivate instead)
- [x] Sidebar "Tests" nav (catalog.view). 7 feature tests; suite **53 passed / 199 assertions**

### 1.9 Reports / MIS (added 2026-07-20, user request — before multitenancy) — ✅ v1 DONE
Business/analytics reports for the owner (distinct from the clinical patient PDF). Gated by new `reports.business` permission (**admin-only**; not granted to other roles). Data-model research produced a full menu (see progress log); user picked 3 for v1, CSV export, no payment-mode field yet.
- [x] **Daily collection** (`/reports/daily-collection?date=`): day closing — count, gross, discount, net, collected, outstanding + visit list + by-payment-status
- [x] **Revenue over period** (`/reports/revenue?from=&to=`): totals, daily net-revenue bar chart (inline SVG, single-series, theme-aware per dataviz skill), revenue-by-category table
- [x] **Volume & TAT** (`/reports/volume-tat?from=&to=`): test order counts + revenue (bar-in-cell), avg turnaround per stage (registration→report, collection→report, entry→verification — computed in PHP for MySQL/SQLite portability), current pending-backlog snapshot
- [x] CSV export on each (`&format=csv`, streamed). Reports sidebar nav (reports.business). 8 feature tests; suite **60 passed / 287 assertions**
- [x] **Doctor commission** (`/reports/doctor-commission?from=&to=`, added 2026-07-20) — per-doctor referred visits, referred net, commission % and commission payable (net × percent); totals + CSV. Pulled forward from Phase 2. 3 feature tests; suite **63 passed**
- **Deferred (noted for later):** payment-mode field (cash/card/online) → needs a `visits.payment_mode` column before a cash-vs-card split is possible; PDF export of MIS reports; critical-results & positive-infectious-disease registers; audit-log viewer
- **Not possible without new modules:** inventory/reagent, QC/control trends, login/session reports

## Phase 2 — Make it a sellable product
- [ ] `stancl/tenancy` DB-per-tenant; central `landlord` DB (tenants, domains, plans, subscriptions)
- [ ] `DEPLOYMENT_MODE=saas|onprem` config switch; on-prem runs single-tenant with tenancy inactive
- [ ] Lab branding: name, logo, report header/footer, receipt format — per tenant settings UI
- [ ] Patient report portal: public verified-report page via signed URL + **QR code printed on report**
- [ ] WhatsApp / SMS report delivery (queued; provider abstraction — e.g. Twilio/local gateways); message-credit accounting
- [x] Doctor commission statements (period reports per referring doctor) — **DONE 2026-07-20, pulled forward into Reports §1.9**
- [ ] Audit log viewer (admin)
- [ ] Automated encrypted backups (per-tenant DB dump, off-site target)
- [ ] Subscription billing + plan gating (users, branches, credits)
- [ ] Internal super-admin panel (tenant management — Filament acceptable here)

## Phase 3 — Differentiators
- [ ] Barcode/QR sample labels + scanner-driven worklist
- [ ] Analyzer interfacing (ASTM E1381/1394, HL7 v2) as premium tier
- [ ] Multi-branch / collection-center support (sample routing, branch reports)
- [ ] Owner analytics dashboard (revenue, test volumes, doctor referrals, TAT)

## Phase 4 — Packaging, distribution & ops
- [ ] Dockerfile + docker-compose (app, MySQL, queue worker, scheduler, backup)
- [ ] License key system for on-prem (signed, offline grace period)
- [ ] CI (GitHub Actions): tests, lint, build
- [ ] PHP 8.3+ upgrade; production hardening checklist
- [ ] Update/release channel for on-prem installs

---

## Current Status

**Phase:** 1 · **COMPLETE except deferred legacy importer** (46 tests green; initial git commit still awaiting user go-ahead). **Phase 1 exit criterion MET:** a lab can run its full daily workflow end-to-end on v2 (register → collect → result → verify → PDF report), verified by the test suite + app smoke test.

**Only open Phase 1 item:** legacy MySQL importer — **DEFERRED by user** (2026-07-20), tracked as session task #1 with full schema mapping. Not required for the exit criterion (fresh labs need no import).

**► Next session — pick one:**
- Build the legacy importer (session task #1) if/when the user wants historical data carried over, **or**
- Begin **Phase 2** (see below) — first step: `stancl/tenancy` DB-per-tenant + `DEPLOYMENT_MODE` switch.
- Consider an initial **git commit** first — project has been uncommitted since scaffolding.

## Progress Log (append-only; newest last)

- **2026-07-13** — Assessed legacy app (security holes documented; Phase 0 patching explicitly skipped by user). Stack decided: Laravel 12 + Inertia/React; Filament rejected for product UI; DB-per-tenant for SaaS+on-prem. Scaffolded `laravel/react-starter-kit` v1.0.1 into `D:\Projects\Lab\OpenLab-v2` (composer create-project succeeded, app key set, starter migrations ran on temporary SQLite). Created this roadmap. `.gitignore` + `config.example.php` added to legacy repo folder.
- **2026-07-13 (later)** — Phase 1.1 completed: `npm install` + Vite build OK (2005 modules, 13.6s). Created `openlab_v2` DB on XAMPP MariaDB 10.4.25 (`C:\xampp-8\mysql\bin\mysql.exe`; root, no password; legacy `laboratory` DB lives on the same server — convenient for the Phase 1.7 import). Switched `.env` to MySQL, `APP_NAME=OpenLab`, migrations ran. Smoke test: `php artisan serve` → home and `/login` both HTTP 200. `git init` done; no commit yet (user hasn't asked). Next: 1.2 domain schema.
- **2026-07-13 (later still)** — Phase 1.2 completed: installed spatie permission 6.25 + activitylog 4.12 (versions capped by PHP 8.2 — another reason for the 8.3 upgrade). Created 5 enums (`app/Enums`), 10 domain migrations, 10 models with relations/casts (`TestParameter::rangeFor()` picks most-specific gender/age band; `ReferenceRange::flagFor()` computes H/L/critical flags; `SampleStatus::allowedTransitions()` encodes the workflow), Patient/Doctor factories, `TestCatalogSeeder` (11 tests, 37 params, 38 ranges). `migrate:fresh --seed` green (first attempt hit MariaDB "no more room in record file" — user freed disk space, retry passed). Flag logic verified in tinker. Dev login: admin@openlab.test / password. Next: 1.3.
- **2026-07-13 (night)** — Phase 1.4 completed: backend (`CreateVisit` action, `Visit::nextNumber`, Patient/Doctor/Visit controllers, 3 FormRequests, permission-gated routes — note `whereNumber` on `visits/{visit}` so `/visits/create` isn't swallowed); frontend (5 pages: visits create/index/show, patients index, doctors index; `use-can` hook; `lib/format.ts` money/date/age helpers; sidebar nav with permission filtering). Inertia middleware now shares `auth.roles/permissions/is_admin` + `flash`. Pest 3.8 installed (starter kit had only PHPUnit classes; `tests/Pest.php` was dead scaffolding until now). Suite: 30 passed incl. 6 front-desk tests (visit creation with samples/results/pricing, patient reuse, search endpoint, day sequencing, role gating). DatabaseSeeder also seeds 6 doctors. Deferred: 80mm thermal receipt format. Next: 1.5 worklist + result entry.
- **2026-07-14** — Phase 1.5 completed: Worklist/Sample/ResultEntry controllers under `can:results.enter`; entry payload resolves patient-specific range + previous result per parameter server-side; save recomputes value_numeric/flag/entered_by authoritatively, submit validates completeness then → resulted. Frontend: `worklist/index.tsx` (status tabs + counts + Collect), `worklist/entry.tsx` (keyboard nav via input refs, client-side flag mirror of `flagFor`, previous column, per-test cards), Worklist nav item. Tests: 7 new (transition guards, flag computation 35→'high' for female ESR, completeness gate, foreign-result rejection, Inertia prop assertions via AssertableInertia). Suite 37 passed. Gotcha hit: running `php artisan test` in parallel with `npm run build` fails Inertia page renders (Vite manifest rewritten mid-test) — run them sequentially. Next: 1.6 verification + PDF report.
- **2026-07-20 (later still)** — Added **§1.9 Reports/MIS v1** (user wants a reports section before multitenancy). Researched full lab-MIS report menu against our schema (financial: daily collection/revenue/receivables/discounts/by-test/by-doctor; operational: volume/TAT/backlog/workload; clinical: critical log/abnormal/positive-register/amendments; register: visit & demographics; audit). User chose 3 for v1 (daily collection, revenue-with-chart, volume+TAT), CSV export only, no payment-mode field. Built `ReportController` (renamed clinical `show`→`labReport`; new dailyCollection/revenue/volumeTat + PHP-computed TAT for SQLite portability + streamed CSV), `reports.business` permission (admin-only), 4 Inertia pages + shared date/range controls + inline-SVG revenue chart (consulted dataviz skill: single-series, no legend, theme-aware), Reports nav. Hit + fixed a mutable-Carbon bug (`$request->date()` returns mutable Carbon; `->endOfDay()` mutated the range start — forced CarbonImmutable). Suite **60 passed / 287 assertions**; app boots. Biggest known gap: no payment-mode → can't split cash/card (would need a column + retrofit).
- **2026-07-20 (later)** — Added **§1.8 Test catalog CRUD** (user request: legacy had test management, v2 was seed-only). Backend: `TestController` (index/create/store/edit/update/toggle/destroy), `StoreTestRequest` (nested params+ranges validation), `SaveTest` action (id-matched param sync respecting results `restrictOnDelete`, auto code-gen, cascade-rewrite ranges); added `TestParameter::results()` + `Test::visitTests()` relations. Frontend: `tests/index.tsx` (search/active-filter/toggle/delete), shared `tests/test-form.tsx` (Single/Multiple radio, repeatable params, repeatable gender/age ranges) used by create+edit pages, Tests sidebar nav. Single-vs-Multiple mirrors legacy `pages/tests.php`; added active toggle + proper unit/result_type/critical/gender-band beyond legacy. 7 tests; suite **53 passed / 199 assertions**; app boots. Note: managing tests is admin-only (only role with catalog.manage; others have catalog.view).
- **2026-07-20** — Phase 1.7 started, paused mid-task, then resumed same session. Legacy MySQL importer deferred to session task #1 with full schema mapping (legacy dump `OpenLab\php\laboratory.sql`: patients/doctors/tests/related_tests/patient_test/patient_test_record). Built: DashboardController + real dashboard.tsx (live stats + recent visits, replaces placeholder), `dashboard` route now controller-based; `RecordsActivity` audit trait on Patient/Doctor/Visit/Sample/Result; DashboardStatsTest (2 tests). On resume: `npm run build` + `php artisan test` → **46 passed / 161 assertions**; dev DB reseeded; app boots (login 200). Self-review (skill `/code-review` is user-invoke-only) flagged one non-blocking follow-up: empty pre-created Result rows emit per-row "created" audit entries (noise on big panels) — revisit with Phase 2 audit viewer. **Phase 1 COMPLETE except deferred importer; exit criterion met.** Still zero git commits.
- **2026-07-18** — Phase 1.6 completed: dompdf 3.x installed; `BuildSampleResultsPayload` action extracted (shared by entry + verification screens); VerificationController (queue/show/verify/reject), ReportController (PDF stream, gates on ≥1 verified sample, marks reported + reported_at via new migration); `reports/lab-report.blade.php` (DejaVu Sans for dompdf, branding placeholder for Phase 2); report button on visit page when verified results exist. Verify also ran into the stale-Vite-manifest trap — new Inertia pages need `npm run build` BEFORE `php artisan test`. Suite 44 passed. Next: 1.7. `HasRoles` on User; `RolePermissionSeeder` (4 roles, 14 permissions: patients/doctors/catalog/visits view+manage, results.enter, results.verify, reports.view/print, users.manage, settings.manage); `Gate::before` admin bypass; public registration fully removed (routes, controller, React page, links); one dev user per role seeded. Verified: permission matrix via tinker (receptionist ✔ visits.manage ✘ results.verify; technician ✔ enter ✘ verify; pathologist ✔ verify; admin ✔ everything), `/register` 404, `/login` 200, Vite build green. Tinker-on-Windows note for future agents: `php artisan tinker <file>` works but hangs in REPL after — run in background, read output file, TaskStop; piping into tinker via PowerShell corrupts `$vars`/`use` statements. Next: 1.4.
