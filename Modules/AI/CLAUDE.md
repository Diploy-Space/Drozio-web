@../../.claude/rules/translate.md

# AI Module

Provider-agnostic AI layer for the 6Valley backend: text/image generation behind a pluggable provider (OpenAI / Claude), plus usage limiting, response validation, and prompt templates consumed by other features (Auction, Blog, Shopping Assistant). Root [CLAUDE.md](../../CLAUDE.md) rules still apply.

## Comments

**Write no comments, and delete the ones you find.** No docblocks, line comments, section banners,
`TODO`s, Blade `{{-- --}}` or HTML `<!-- -->` — in `.php`, `.blade.php`, `.js`, `.css` and every
other file in this module. Logic that seems to need a sentence gets a name instead: extract a
private method or a named variable. Only machine-read annotations stay — attributes, a generic
`@var`/`@return` a static analyser needs, licence headers a package requires. Full rule:
[engineering-standards.md](../../.claude/rules/engineering-standards.md) §15.

## Provider selection (DB-driven, not code-driven)

`AIProviders/AIProviderManager.php` is the entry point. It never hardcodes a vendor:
- `getActiveAIProvider()` reads the active provider row from the DB (`app/Models/AISetting`, via `getActiveAIProviderConfig()`), throwing `AIProviderException` if none is active.
- `getAvailableProviderObject()` matches `AISetting->ai_name` against the registered provider objects and injects that row's `api_key` / `organization_id`.
- Registered providers live in `AIProviders/`: `OpenAIProvider.php`, `ClaudeProvider.php`. **Add a new vendor by adding a `*Provider` class here** (implementing the same `getName()`/`setApiKey()`/`setOrganization()` contract) and registering it — don't branch on vendor inside the manager.

`generate(prompt, imageUrl, options)` is the main call. It also enforces demo limits: when `APP_MODE=demo`, generation is capped at 10 calls per client IP (`Cache` key `demo_ip_usage_*`).

## Cross-cutting services

- `app/Services/AIUsageManagerService.php` — usage accounting / limits.
- `app/Services/AIResponseValidatorService.php` — validates provider output before returning.
- `app/Utils/CurrentAuthUser.php` — admin vs. vendor context (`isAdmin()`), which affects limits/behavior.
- Exceptions (`app/Exceptions/`): `AIProviderException`, `UsageLimitException`, `ValidationException`, `ImageValidationException` — throw these, don't return ad-hoc error strings.

## Prompt templates (consumed by other modules)

`app/PromptTemplates/{Auction,Blog}/` and `app/Services/Auction/` hold feature-specific prompt/response logic. This module is a **dependency of** those features — keep templates here; don't scatter AI prompt strings into Auction/Blog controllers.

## Routes

`app/Providers/RouteServiceProvider.php` loads only lowercase `routes/` via `module_path('AI', '/routes/...')`:

| File | Notes |
|---|---|
| `routes/web.php` | web |
| `routes/admin/routes.php` | admin panel |
| `routes/vendor/routes.php` | vendor panel |
| `routes/api.php` | API (see `app/Http/Controllers/API/V3`) |

Only `routes/` (lowercase) is git-tracked and loaded — ignore any capitalized `Routes/` that appears locally (case-insensitive-filesystem artifact); never add real files under it.

## Addon gating

`Addon/info.php` (`is_published`) is the licensing switch, read via `getCheckAddonPublishedStatus('AI')` (`app/Utils/module-helper.php`). Always gate through that helper; don't check addon state ad hoc.

## Config

- `config/config.php` — display `name` + `thumbnail` only.
- Provider credentials and the active-provider choice are **DB rows** (`AISetting`), not config keys or `.env`. Never hardcode API keys.
