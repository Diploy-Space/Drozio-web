# CLAUDE.md

**16.6 is the Wholesale release.** Feature work happens in `Modules/Wholesale` — see its
[CLAUDE.md](Modules/Wholesale/CLAUDE.md) for module-specific rules.

**Call it Wholesale, not "B2B", and never repeat a module's name in a class inside it** —
`AttributeService`, not `B2BAttributeService`. Naming, folder placement and when to inherit Retail's
service rather than clone it: [engineering-standards.md](.claude/rules/engineering-standards.md),
"Naming and placement" (N1–N4).

**Write no comments, and delete the ones you find.** Every file you create or edit comes back with
zero comments — no docblocks, line comments, section banners, `TODO`s, Blade `{{-- --}}` or HTML
`<!-- -->` — in PHP, Blade, JS, CSS alike. Name things instead of explaining them. Only machine-read
annotations stay: attributes, a generic `@var`/`@return` a static analyser needs, required licence
headers. Full rule: [engineering-standards.md](.claude/rules/engineering-standards.md) §15.

## Project Rules
@.claude/rules/engineering-standards.md
@.claude/rules/coding-standards.md
@.claude/rules/translate.md
@.claude/rules/theme-ui.md
@.claude/rules/response-style.md
@.claude/rules/generalize-examples.md
@.claude/rules/api-playground.md

## Commands

### Artisan
- `php artisan serve` — dev server
- `php artisan migrate` — apply pending migrations (see Database note below)
- `php artisan optimize:clear` — clear all caches
- `php artisan generate:entity {name}` — scaffold model + repo interface + impl
- `php artisan file:permission` — fix storage permissions after deployment
- `php artisan lang:clean` — report junk translation keys (`--fix` to prune, `--merge-variants`, `--with-data`, `--locale=`); see `.claude/rules/translate.md`
- `php artisan lang:normalize-keys` — rewrite translation keys in `resources/lang` as English source text, no underscores (`--fix`, `--locale=`)
- `php artisan lang:normalize-call-sites` — same normalization applied to literal `translate('...')` arguments in source (`--fix`, `--path=`)
- `php artisan lang:consolidate` — fold `new-messages.php` into `messages.php`, dropping unreachable duplicates (`--fix`, `--locale=`)
- `php artisan lang:prune-orphans` — remove keys whose text appears nowhere (`--fix`, `--records`, `--keep-single-word`)
- `php artisan lang:merge-duplicates` — fold case-only duplicate keys onto one and repoint call sites (`--fix`, `--punctuation`, `--review`)

### Frontend (Vite)
- `npm run dev` — Vite dev server (writes `public/hot`)
- `npm run build` — production build to `public/build`
- Entry: `resources/js/app.jsx` (Inertia+React) + `resources/css/app.css`; `@` → `Modules/Builder/resources/js`

### Queue

`QUEUE_CONNECTION=database`, so every queued job is a row in `jobs` and **nothing happens until a
worker consumes it**. A deployment without a worker looks fine — jobs simply pile up unseen. This
codebase accumulated 390,951 of them over three weeks that way, so treat the worker as required
infrastructure, not an optimisation.

```
php -d memory_limit=1G artisan queue:work database \
    --queue=exports,high,default,auctions,auction_notifications \
    --tries=3 --timeout=900 --max-time=3600 --sleep=3
```

- Run it under supervisor/systemd with `autorestart=true`; `--max-time` makes the process exit
  hourly so the supervisor recycles it and long-running memory is reclaimed.
- **Queue order is priority order.** `exports` is listed first so a report an admin is waiting on is
  never stuck behind a notification burst — a single status reconcile can enqueue tens of thousands
  of `auction_notifications` jobs.
- `--timeout=900` exists for the export jobs; the default 60s kills them mid-write. The job class
  also sets its own `$timeout`, which wins over the flag.
- `memory_limit=1G` is required, not precautionary. The report exports render through `FromView`,
  which builds the whole HTML table as a DOM before a single cell exists, so peak memory scales with
  row count and no `excel.cache.driver` setting helps — `batch` measured identical to `memory`.
  Roughly 730 MB at 90k rows, which puts the practical ceiling near 100k rows per file; past that an
  export needs a chunked writer rather than more memory.
- Restart the worker on every deploy (`php artisan queue:restart`) or it keeps running the old code.
- Inspect and recover: `php artisan queue:monitor database:exports`, `queue:failed`,
  `queue:retry all`, and `queue:clear database --queue=<name>` to drop a stale backlog.

## Database
**Critical:** Never run `php artisan migrate` on an empty database. Import `installation/backup/database.sql` first, then migrate.

## Architecture

**This is a service-based codebase.** Every entry point — admin web, vendor web, storefront and
API — funnels through the same layer:

```
Request class  →  Controller method  →  Service (all business logic + model queries)  →  Controller response
                                                                                          ├─ web: Blade view / redirect
                                                                                          └─ API: API Resource
```

**No repository, no DTO, no Action classes** in new code. Services query Eloquent directly; the
controller hands them the validated array and the resolved identity. (`app/Repositories/` still
serves legacy web/admin/vendor paths — don't delete them, don't extend them, don't call them from
new code.)

**The service is the only layer that touches a model.** A controller, a request class, a Blade view,
a job or a command never calls `SomeModel::query()`/`::find()`/`::create()` — it asks that model's
service. A validation rule needing stored data injects the owning service into the request's
constructor and calls it (see
`Modules/Wholesale/app/Http/Requests/Vendor/Campaign/JoinRequest.php`);
if the service lacks a method for it, add one.

Two rules carry most of the weight, and both are about **not forking by perspective**:

1. **One service per feature operation, reused by admin, vendor, web and API.** A
   `ProductService::create()` is called by all four controllers; only the controller differs, and
   only in how it renders the result. No `AdminXService`/`VendorXService` pair, no separate API
   service. Services take typed scalars/arrays and the **already-resolved** identity — never
   `Illuminate\Http\Request`, never `auth()`/`session()`/`view()`/`->file()` inside a service.
2. **One request class per operation, serving the web form and the API endpoint.** Every class —
   core and module alike — extends `App\Http\Requests\BaseRequest`, which answers JSON to
   API/AJAX callers and redirects back for web forms. A genuine web/API difference is a branch on `$this->isApiRequest()`, never a second
   class. Every rule carries a translated, user-facing `messages()` entry.

**`BaseService`** — there is **one**, `app/Services/BaseService.php`, and every service extends it,
core and module alike. The per-module copies were removed: both were strict subsets of core's,
nothing kept them in step, and a shared mechanic drifting between three files is a bug rather than
independence.

**The base holds only what every service reuses unchanged**: the injected model and
`query()`, `applyFilters()` (the controller's formatted `column => value` array → where / whereIn /
whereNull), `listQueryString()`, `storageDisk()`, the polymorphic translation handling
(`defaultLocaleValue()`, `syncTranslations()`, `translatedIds()`, `deleteTranslations()`) and the
polymorphic SEO meta handling (`syncSeoMetaInfo()`, `deleteSeoMetaInfo()`). **No actions, no configuration
properties, no override hooks.** A wrapper that only calls another method, or an extension point with no extension, does not
belong there.

**And no service-level defaults.** `getList()`'s `$relations` and `$withCount` default to empty — the
**controller passes what its own screen renders**, because the list, the export and a load-more
partial each need something different. No `DEFAULT_RELATIONS`/`DEFAULT_SORT`/`DEFAULT_WITH_COUNT`
constants. Sorting is the exception, because it is a vocabulary rather than a default: the controller
passes a key the service declared (`CategoryService::SORT_NEWEST_ID`) and the service maps it, so a
raw column name off a request never reaches `orderBy`; a list with no sort control just calls
`->latest()`. A single row is different again — `find()` (by primary key) and `findBy()` (by any
other column, taking the same `column => value` filters) keep the entity's natural relations as a
**parameter default**, since an edit form loads the same row the same way everywhere.

**[`app/Services/DemoService.php`](app/Services/DemoService.php) is the reference every service is
written to — read it before writing a new one.** Reference only: nothing extends it, nothing resolves
it. Copy the actions your entity has — `getList`, `find`, `findBy`, `create`, `update`, `updateBy`,
`updateStatus`, `getStatusCounts`, `delete` — keeping their names and parameter lists,
and delete the rest. A parameter your feature never uses stays in the signature with its default. An
operation outside that vocabulary gets its own camelCase verb phrase (`getParticipationBoard()`,
`approveWithRevisedItems()`) — never a bent signature. No model uses `SoftDeletes`, so
`trash`/`restore`/`forceDelete`/`bulkAction`/`statusStatistics` are not part of the vocabulary, and
no current-generation service defines any of them. `updateShift` is the vocabulary's name for
moving rows from one parent onto another — `App\Services\New\ProductService::updateShift()` shifts
products off a deleted brand or category and onto its replacement.

**Services return plain values** — a model, a paginator, a collection, a bool — and `null`/`false`
when the row isn't there. There is no `['data','status']` envelope: status codes, messages and
redirects are chosen in the controller. `filters` reach `getList()` as formatted column ⇒ value pairs
(`['status' => 1]`, `['id' => [1,2]]`, `['parent_id' => null]`) — no operator language, no closures.

Full spec: [`Modules/Wholesale/docs/SERVICE-ARCHITECTURE.md`](Modules/Wholesale/docs/SERVICE-ARCHITECTURE.md).

**New core services go in `app/Services/New/`** (namespace `App\Services\New`) — anything still
directly in `app/Services/` is an earlier generation. A module's services stay in that module's own
`Modules/{Module}/app/Services/`.

**A feature's single service sits directly in that folder; a sub-folder appears only once a second
service joins it. Where Retail already implements the same logic and only the business module
differs, the module service extends Retail's and overrides `businessModule()` alone** —
`App\Services\New\BrandService` (extended by Wholesale and Auction) and `App\Services\New\AttributeService`
(extended by Wholesale). Never a shared trait for feature logic. See N3/N4.

**Golden reference:** [`Modules/Wholesale/docs/SERVICE-ARCHITECTURE.md`](Modules/Wholesale/docs/SERVICE-ARCHITECTURE.md)
— the complete worked example (request → controller → service → response), built from the live
`Campaign` vertical, with a checklist. Read it before writing a new controller, service or request.

**Request classes: role first, then feature — core and every module alike.**
`Modules/{Module}/app/Http/Requests/{Role}/{Feature}/{Operation}Request.php`. The role is the one the
operation's controller lives in (`Admin/`, `Vendor/`, `Customer/`, `Deliveryman/`, `Web/`), so the
request tree mirrors the controller tree; a module declares only the roles it serves. `Shared/` is
the role folder for an operation more than one role performs — the role folder is a location, never a
licence to fork the same operation into two classes. **The folder names the entity, so the class
names only the action** — `Attribute/AddRequest`, `Attribute/UpdateRequest`, `Campaign/JoinRequest`,
never `Attribute/AttributeAddRequest`. `app/Http/Requests_old/` is read-only reference for the frozen
APIs.

**Modules own their whole stack** — requests, controllers, resources, services, models, enums,
routes, utils, views, migrations and assets all live inside the module, never in the global `app/` or
`routes/` tree. Extending core base classes (`BaseService`, `BaseRequest`, `Controller`,
`App\Traits\*`) and querying core models for shared tables is shared infrastructure, not module code
in core.

**API versions:** v1 (customer app), v2 (legacy seller + delivery-man app), v3 (seller app) are
**frozen** — request, response, status, error body, guard and URI never change. `app/Services/V4/`
holds the parked 16.5 consolidation; keep its shape if you touch it, and don't treat it as a live
rule-set for 16.6.

**v5 is live** (193 routes, `routes/rest_api/v5/`, `app/Services/V5/`) and follows its **own**
convention: services extend `App\Services\V5\BaseService` — a deliberately minimal base with no
inherited CRUD, its own `pagingArgs(['per_page','page'])` shape and its own audience bases. That is
correct inside v5; don't port it into `New/` or a module, and don't push the common base into v5.

**Routes** (non-standard, loaded by `app/Providers/RouteServiceProvider.php`):
- Admin: `routes/admin/routes.php`
- Vendor: `routes/vendor/routes.php`
- Web: `routes/web/v6/routes.php`
- API v6: `routes/rest_api/v6/api.php`
- Payment gateways: `routes/payment/routes.php`
- Each `Modules/` dir has its own RouteServiceProvider.

**Modules** (`nwidart/laravel-modules`): `Modules/AI`, `Modules/Auction`, `Modules/Wholesale`,
`Modules/Blog`, `Modules/Builder`, `Modules/Courier`, `Modules/TaxModule` — each is a self-contained
mini-app with its own `admin.php`/`vendor.php`/`web.php`/`api.php` routes and RouteServiceProvider.
Cross-module needs go through the **core** layer, never another module's internals.

**Wholesale module — `Modules/Wholesale` (the 16.6 focus):** wholesale products, orders, campaigns, shipping and a
"Wholesale" workspace in the admin business-module switcher. Add-on metadata in
`Modules/Wholesale/Addon/info.php`; sidebar trees in `Addon/{admin,vendor}_routes.php`. Storefront
views resolve through `b2bThemeView()` and ship in both themes (`default`, `theme_aster`).

**Bootstrap:** `AppServiceProvider` loads themes, settings, payment configs and add-on states from
the DB at boot. Many behaviors are DB-driven, not code-driven.

**AI Module:** `Modules/AI/AIProviders/AIProviderManager.php` selects the active provider
(OpenAI/Claude) from DB config.

**Frontend:** Legacy admin/vendor/web views are server-rendered Blade + Bootstrap 4 with pre-built
static assets under `public/assets/` (no bundler). The `Modules/Builder` storefront is Inertia +
React, bundled by the root Vite config.

## Docs

`docs/` holds only what is current: `menu-refactor/` (admin sidebar/business-module structure) and
`postman-collection/` (regenerated, never hand-edited).

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/ai (AI) - v0
- laravel/framework (LARAVEL) - v12
- laravel/passport (PASSPORT) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- tightenco/ziggy (ZIGGY) - v2
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/react (INERTIA_REACT) - v3
- react (REACT) - v19

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Write no comments and remove existing ones (`.claude/rules/engineering-standards.md` §15); the only
  PHPDoc that stays is a type a static analyser genuinely needs.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
