@../../.claude/rules/translate.md

# Blog Module

Content/blog add-on for the 6Valley backend. Root [CLAUDE.md](../../CLAUDE.md) rules still apply; this file notes what's module-specific.

## Comments

**Write no comments, and delete the ones you find.** No docblocks, line comments, section banners,
`TODO`s, Blade `{{-- --}}` or HTML `<!-- -->` — in `.php`, `.blade.php`, `.js`, `.css` and every
other file in this module. Logic that seems to need a sentence gets a name instead: extract a
private method or a named variable. Only machine-read annotations stay — attributes, a generic
`@var`/`@return` a static analyser needs, licence headers a package requires. Full rule:
[engineering-standards.md](../../.claude/rules/engineering-standards.md) §15.

## Layout
- Perspectives split by controller: `app/Http/Controllers/{Admin,Web}/` — Admin authors/manages posts, Web renders the storefront blog. Don't share controllers/views across the two perspectives.
- Routes load from lowercase `routes/` via the module's `RouteServiceProvider` (ignore any capitalized `Routes/` that appears locally — case-insensitive-filesystem artifact; only `routes/` is tracked).
- `config/config.php` is display-only (`name`, `thumbnail`). No `Addon/` — Blog is not addon-license-gated.

## AI prompts
AI-generated blog content uses templates in `Modules/AI/app/PromptTemplates/Blog/` — keep prompt logic there, not inline in Blog controllers (see [Modules/AI/CLAUDE.md](../AI/CLAUDE.md)).

## Storefront theming
Storefront views are theme-switchable (`default` / `theme_aster`). Same theme-separation rule as [theme-ui.md](../../.claude/rules/theme-ui.md): reuse logic/conditions only — never copy HTML/classes/layout between themes.
