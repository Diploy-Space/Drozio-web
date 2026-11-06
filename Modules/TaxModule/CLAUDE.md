@../../.claude/rules/translate.md

# TaxModule

Tax setup, calculation, and tax reporting add-on for the 6Valley backend. Root [CLAUDE.md](../../CLAUDE.md) rules still apply; this file notes what's module-specific.

## Comments

**Write no comments, and delete the ones you find.** No docblocks, line comments, section banners,
`TODO`s, Blade `{{-- --}}` or HTML `<!-- -->` — in `.php`, `.blade.php`, `.js`, `.css` and every
other file in this module. Logic that seems to need a sentence gets a name instead: extract a
private method or a named variable. Only machine-read annotations stay — attributes, a generic
`@var`/`@return` a static analyser needs, licence headers a package requires. Full rule:
[engineering-standards.md](../../.claude/rules/engineering-standards.md) §15.

## Perspectives & APIs
- Controllers split by perspective: `app/Http/Controllers/{Admin,Vendor,Api}/`, with reporting under `Admin/Reports` and `Vendor/Reports`. Keep each perspective's controllers/views separate.
- Versioned API controllers live in `app/Http/Controllers/Api/{V1,v3}/` — keep new API code inside the matching version folder; don't mix versions.

## Routes / menu trees
- Runtime routes load from lowercase `routes/` via the module's `RouteServiceProvider` (only `routes/` is tracked; ignore any capitalized `Routes/`).
- `Addon/tax_routes.php`, `Addon/tax_report_routes.php`, `Addon/vendor_tax_report_routes.php` are declarative sidebar/menu trees consumed by the core admin/vendor layout partials (same pattern as Auction/Wholesale `Addon/*_routes.php`) — add menu entries here, not runtime routes.

## Addon gating
`Addon/info.php` (`is_published`) is the licensing switch, read via `getCheckAddonPublishedStatus('TaxModule')` (`app/Utils/module-helper.php`). Always gate through that helper.

## Config & translations
- `config/config.php` carries real settings (`project`, `version`, `pagination`) — not display-only.
- This module ships its own `lang/` — follow root [translate.md](../../.claude/rules/translate.md): wrap new hardcoded Blade strings in `translate('...')`, leave existing `translate()`/`__()`/`trans()` alone.
- Has a `tests/` folder — keep it populated when changing tax logic.
