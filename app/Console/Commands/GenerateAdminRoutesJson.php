<?php

namespace App\Console\Commands;

use App\Enums\BusinessModule;
use App\Services\New\AdvancedSearchService;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Throwable;

class GenerateAdminRoutesJson extends Command
{
    protected $signature = 'generate:admin-routes-json {--panel=admin : The panel to index (admin or vendor)}';

    protected $description = 'Index every admin or vendor page with its title, business module and searchable keywords for the advanced search';

    private const PANELS = [
        AdvancedSearchService::PANEL_ADMIN => [
            'uri_prefix' => 'admin',
            'excluded_uri_prefixes' => ['admin/component', 'admin/ajax'],
            'excluded_uris' => [
                'admin/settings/content/blog/edit',
                'admin/settings/vat-tax',
                'admin/report/retail/get-tax-details',
                'admin/report/wholesale/get-tax-details',
            ],
            'page_index_path' => 'json/admin/admin_formatted_routes.json',
            'page_keywords_directory' => 'json/admin/lang',
            'view_directories' => ['resources/views/admin-views', 'Modules'],
            'route_name_ignored_segments' => ['admin', 'index', 'list', 'view'],
            'tab_helper_definitions' => [
                'businessSetupTabs' => 'business_modules.business_setup_tabs.definitions',
            ],
        ],
        AdvancedSearchService::PANEL_VENDOR => [
            'uri_prefix' => 'vendor/',
            'excluded_uri_prefixes' => ['vendor/component', 'vendor/ajax', 'vendor/auth'],
            'excluded_uris' => [],
            'page_index_path' => 'json/vendor/vendor_formatted_routes.json',
            'page_keywords_directory' => 'json/vendor/lang',
            'view_directories' => ['resources/views/vendor-views', 'Modules'],
            'route_name_ignored_segments' => ['vendor', 'index', 'list', 'view'],
            'tab_helper_definitions' => [],
        ],
    ];

    private const LOCALE_SOURCE_DIRECTORY = 'json/admin/lang';

    private const EXCLUDED_ACTION_FRAGMENT = 'Ajax';

    private const EXCLUDED_MIDDLEWARE = 'api';

    private const SOURCE_LOCALE = 'en';

    private const TRANSLATION_FILES = ['messages.php', 'new-messages.php'];

    private const PAGE_METHOD = 'GET';

    private const PAGE_PRIORITY = 2;

    private const PAGE_TYPE = 'page';

    private const MAX_DELEGATION_DEPTH = 2;

    private const TRANSLATE_PATTERN = '/\btranslate\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\$]|\\\\.)*)")\s*[,)]/';

    private const LABEL_TRANSLATE_PATTERN = '/=>\s*translate\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\$]|\\\\.)*)")\s*[,)]/';

    private const STRING_LITERAL_PATTERN = '/\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\$]|\\\\.)*)"/';

    private const INCLUDE_DIRECTIVE_PATTERN = '/@(?:include|includeIf|includeWhen|includeUnless|includeFirst|each)\s*\(/';

    private const TITLE_SECTION_PATTERN = '/@section\s*\(/';

    private const TITLE_SECTION_NAME = 'title';

    private const PAGE_HEADER_TITLE_PATTERN = '/<span[^>]*class=["\'][^"\']*page-header-title[^"\']*["\'][^>]*>(.*?)<\/span>/s';

    private const EXTENDS_PATTERN = '/@extends\s*\(/';

    private const ANCHOR_PATTERN = '/<a\b([^>]*)>(.*?)<\/a>/s';

    private const ANCHOR_ROUTE_PATTERN = '/\broute\(\s*[\'"]([^\'"]+)[\'"]\s*[,)]/';

    private const ANCHOR_ACTIVE_ROUTE_PATTERN = '/routeIs\(\s*[\'"]([^\'"]+)[\'"]/';

    private const ANCHOR_ACTIVE_URI_PATTERN = '/(?:Request::|request\(\)->)is\(\s*[\'"]([^\'"]+)[\'"]/';

    private const RETURNED_VIEW_PATTERN = '/return\s+(?:\\\\?view|View::make)\s*\(/';

    private const DELEGATED_RETURN_PATTERN = '/return\s+\$this->(\w+)\s*\(/';

    private const DELEGATED_CALL_PATTERN = '/\$this->(\w+)\s*\(/';

    private const RECORD_ID_INPUT_PATTERN = '/\$request(?:\[\s*[\'"]id[\'"]\s*\]|->(?:input|get|query|route)\(\s*[\'"]id[\'"]|->id\b)/';

    private const CLASS_CONSTANT_PATTERN = '/\b(?:self|static)::([A-Z_][A-Z0-9_]*)\b/';

    private const HELPER_CALL_PATTERN = '/(?<![\w$>:\\\\])([a-zA-Z_]\w*)\s*\(/';

    private const RENDERED_VIEW_PATTERN = '/^\s*->\s*render\s*\(/';

    private const TERNARY_PATTERN = '/\?(?![?>-])/';

    private const NULL_COALESCE_OPERATOR = '??';

    private const TRANSLATE_FUNCTION = 'translate';

    private const CONSTANT_LOOP_PATTERN = '/@foreach\s*\(\s*\\\\?([A-Z][\w\\\\]*)::([A-Z_][A-Z0-9_]*)\s+as\b/';

    private const IMPORTED_CLASS_PATTERN = '/\buse\s+([\w\\\\]+)\\\\%s\s*;/';

    private const DEFAULT_ENUM_NAMESPACE = 'App\\Enums\\';

    private const LABEL_SEPARATORS = ['-', '_'];

    private const ARRAY_SUBSCRIPT_PATTERN = '/\[[^\]]*\]/';

    private const CONSTANT_LOOP_BLOCK_PATTERN = '/@foreach\s*\(\s*\\\\?([A-Z][\w\\\\]*)::([A-Z_][A-Z0-9_]*)\s+as\s+(\$\w+)(?:\s*=>\s*(\$\w+))?\s*\)(.*?)@endforeach/s';

    private const LOOP_ROUTE_PATTERN = '/route\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[([^\]]*)\]\s*\)/';

    private const STRING_LITERAL_ARGUMENT_PATTERN = '/^[\'"]([^\'"]*)[\'"]$/';

    private const TAB_TITLE_SEPARATOR = ' › ';

    private const KEYED_LABEL_PATTERN = '/[\'"]([\w-]+)[\'"]\s*=>\s*translate\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';

    private array $viewFiles = [];

    private array $helperLabels = [];

    private array $translations = [];

    private array $loopTabTitles = [];

    private string $panel = AdvancedSearchService::PANEL_ADMIN;

    public function handle(AdvancedSearchService $advancedSearchService): int
    {
        $panel = (string) $this->option('panel');

        if (! array_key_exists($panel, self::PANELS)) {
            $this->error('Unknown panel: '.$panel);

            return self::INVALID;
        }

        $this->panel = $panel;
        $menuTitles = $this->getMenuTitles(advancedSearchService: $advancedSearchService->forPanel(panel: $panel));
        $this->loopTabTitles = $this->getLoopTabTitles();
        $pages = $this->attachHelperTabLabels(pages: $this->attachTabLabels(pages: $this->buildPages(menuTitles: $menuTitles)));

        $this->writeJson(path: public_path($this->panelSetting(key: 'page_index_path')), data: $this->buildIndexEntries(pages: $pages));

        foreach ($this->getKeywordLocales() as $locale) {
            $this->writeJson(path: $this->keywordFilePath(locale: $locale), data: $this->buildKeywordEntries(pages: $pages, locale: $locale));
        }

        $this->info('Indexed '.count($pages).' '.$panel.' pages for '.implode(', ', $this->getKeywordLocales()).'.');

        return self::SUCCESS;
    }

    private function getMenuTitles(AdvancedSearchService $advancedSearchService): array
    {
        $menuTitles = [];

        foreach (BusinessModule::values() as $businessModule) {
            foreach ($advancedSearchService->getMenus(businessModule: $businessModule) as $menu) {
                $uri = (string) ($menu['uri'] ?? '');

                if ($uri !== '' && ! str_contains($uri, '?') && ! isset($menuTitles[$uri])) {
                    $menuTitles[$uri] = (string) ($menu['title'] ?? '');
                }
            }
        }

        return $menuTitles;
    }

    private function buildPages(array $menuTitles): array
    {
        $pages = [];

        foreach ($this->getAdminRoutes() as $route) {
            foreach ($this->getPageUris(route: $route, menuTitles: $menuTitles) as $uri) {
                if (isset($pages[$uri])) {
                    continue;
                }

                $page = $this->buildPage(route: $route, uri: $uri, menuTitle: $menuTitles[$uri] ?? null);

                if ($page !== null) {
                    $pages[$uri] = $page;
                }
            }
        }

        ksort($pages);

        return $pages;
    }

    private function panelSetting(string $key): mixed
    {
        return self::PANELS[$this->panel][$key];
    }

    private function getAdminRoutes(): Collection
    {
        return collect(Route::getRoutes()->getRoutesByMethod()[self::PAGE_METHOD] ?? [])
            ->filter(fn (RegisteredRoute $route): bool => Str::startsWith($route->uri(), $this->panelSetting(key: 'uri_prefix'))
                && ! Str::startsWith($route->uri(), $this->panelSetting(key: 'excluded_uri_prefixes'))
                && ! in_array($route->uri(), $this->panelSetting(key: 'excluded_uris'), true)
                && ! Str::contains($route->getActionName(), self::EXCLUDED_ACTION_FRAGMENT)
                && ! in_array(self::EXCLUDED_MIDDLEWARE, $route->gatherMiddleware(), true))
            ->values();
    }

    private function getPageUris(RegisteredRoute $route, array $menuTitles): array
    {
        if (! str_contains($route->uri(), '{')) {
            return [$route->uri()];
        }

        $isLastSegmentParameter = Str::startsWith(Str::afterLast($route->uri(), '/'), '{');
        $uris = [];

        foreach (array_keys($menuTitles) as $menuUri) {
            if (! $route->matches(Request::create('/'.$menuUri))) {
                continue;
            }

            $groupKey = $isLastSegmentParameter ? Str::beforeLast($menuUri, '/') : $menuUri;
            $uris[$groupKey] ??= $menuUri;
        }

        foreach (array_keys($this->loopTabTitles) as $tabUri) {
            if ($route->matches(Request::create('/'.$tabUri))) {
                $uris[$tabUri] ??= $tabUri;
            }
        }

        return array_values(array_unique($uris));
    }

    private function buildPage(RegisteredRoute $route, string $uri, ?string $menuTitle): ?array
    {
        $source = $this->resolvePageSource(route: $route);

        if ($source === null) {
            return null;
        }

        $content = File::get($source['file']);

        if (preg_match(self::EXTENDS_PATTERN, $content) !== 1) {
            return null;
        }

        $visited = [];
        $scan = $this->scanView(file: $source['file'], currentUri: $uri, visited: $visited);
        $titleParts = $this->extractTitleParts(content: $content);

        if ($titleParts === [] && $menuTitle !== null && $menuTitle !== '') {
            $titleParts = [$menuTitle];
        }

        if ($titleParts === []) {
            $titleParts = [$this->getRouteTitle(route: $route)];
        }

        return [
            'uri' => $uri,
            'view' => $source['view'],
            'title_parts' => array_values(array_filter($titleParts, fn (string $part): bool => $part !== '')),
            'tab_title' => $this->loopTabTitles[$uri] ?? null,
            'business_module' => $this->resolveBusinessModule(uri: $uri, route: $route),
            'keywords' => array_values(array_unique(array_merge(
                $scan['keywords'],
                $this->getHelperLabels(helperNames: $scan['helpers']),
                $this->getFunctionLabels(functions: $source['functions']),
            ))),
            'tab_links' => $scan['tab_links'],
        ];
    }

    private function resolvePageSource(RegisteredRoute $route): ?array
    {
        $function = $this->getActionReflection(route: $route);

        if ($function === null || preg_match(self::RECORD_ID_INPUT_PATTERN, $this->getFunctionSource(function: $function)) === 1) {
            return null;
        }

        $functions = [];
        $view = $this->findReturnedView(function: $function, depth: 0, functions: $functions);
        $file = $view === null ? null : $this->findViewFile(view: $view);

        return $file === null ? null : ['view' => $view, 'file' => $file, 'functions' => $functions];
    }

    private function getActionReflection(RegisteredRoute $route): ?ReflectionFunctionAbstract
    {
        $action = $route->getAction();

        if (($action['uses'] ?? null) instanceof Closure) {
            return new ReflectionFunction($action['uses']);
        }

        if (! is_string($action['controller'] ?? null)) {
            return null;
        }

        [$controllerClass, $method] = Str::parseCallback($action['controller'], '__invoke');

        return class_exists($controllerClass) && method_exists($controllerClass, $method)
            ? new ReflectionMethod($controllerClass, $method)
            : null;
    }

    private function findReturnedView(ReflectionFunctionAbstract $function, int $depth, array &$functions): ?string
    {
        $functions[] = $function;
        $body = $this->getFunctionSource(function: $function);

        preg_match_all(self::RETURNED_VIEW_PATTERN, $body, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$match, $offset]) {
            [$arguments, $endOffset] = $this->readArguments(source: $body, openOffset: $offset + strlen($match));

            if (preg_match(self::RENDERED_VIEW_PATTERN, substr($body, $endOffset)) === 1) {
                continue;
            }

            $view = $this->resolveViewExpression(expression: $this->splitArguments(arguments: $arguments)[0] ?? '', function: $function);

            if ($view !== null) {
                return $view;
            }
        }

        if ($depth >= self::MAX_DELEGATION_DEPTH || ! $function instanceof ReflectionMethod) {
            return null;
        }

        preg_match_all(self::DELEGATED_RETURN_PATTERN, $body, $delegations);

        foreach (array_unique($delegations[1]) as $methodName) {
            $class = $function->getDeclaringClass();

            if ($methodName !== $function->getName() && $class->hasMethod($methodName)) {
                $view = $this->findReturnedView(function: $class->getMethod($methodName), depth: $depth + 1, functions: $functions);

                if ($view !== null) {
                    return $view;
                }
            }
        }

        return null;
    }

    private function resolveViewExpression(string $expression, ReflectionFunctionAbstract $function): ?string
    {
        foreach ($this->extractStringLiterals(source: $expression) as $literal) {
            if ($this->findViewFile(view: $literal) !== null) {
                return $literal;
            }
        }

        if ($function instanceof ReflectionMethod && preg_match(self::CLASS_CONSTANT_PATTERN, $expression, $constant) === 1) {
            $value = $function->getDeclaringClass()->getConstant($constant[1]);

            return is_string($value) && $this->findViewFile(view: $value) !== null ? $value : null;
        }

        return null;
    }

    private function findViewFile(string $view): ?string
    {
        if (array_key_exists($view, $this->viewFiles)) {
            return $this->viewFiles[$view];
        }

        try {
            $file = View::getFinder()->find($view);
        } catch (InvalidArgumentException) {
            $file = null;
        }

        return $this->viewFiles[$view] = $file;
    }

    private function scanView(string $file, string $currentUri, array &$visited): array
    {
        $result = ['keywords' => [], 'helpers' => [], 'tab_links' => []];

        if (isset($visited[$file])) {
            return $result;
        }

        $visited[$file] = true;
        $content = File::get($file);
        $tabLinks = [];
        $ownContent = $this->removeForeignTabs(content: $content, currentUri: $currentUri, tabLinks: $tabLinks);
        $loopTabs = $this->extractConstantLoopTabs(content: $content);
        $ownLoopTabLabels = [];

        foreach ($loopTabs as $label => $uri) {
            if ($uri === $currentUri) {
                $ownLoopTabLabels[] = $label;
            } else {
                $tabLinks[$label] = array_values(array_unique([...($tabLinks[$label] ?? []), $uri]));
            }
        }

        $result['keywords'] = array_merge(
            $this->extractTranslationKeys(source: $ownContent, pattern: self::TRANSLATE_PATTERN),
            array_diff($this->extractConstantLoopLabels(content: $content), array_keys($loopTabs)),
            $ownLoopTabLabels,
        );
        $result['helpers'] = $this->extractHelperNames(content: $content);
        $result['tab_links'] = $tabLinks;

        foreach ($this->extractIncludedViewFiles(content: $content) as $includedFile) {
            $included = $this->scanView(file: $includedFile, currentUri: $currentUri, visited: $visited);
            $result['keywords'] = array_merge($result['keywords'], $included['keywords']);
            $result['helpers'] = array_merge($result['helpers'], $included['helpers']);
            $result['tab_links'] = array_merge_recursive($result['tab_links'], $included['tab_links']);
        }

        $result['keywords'] = array_values(array_unique($result['keywords']));
        $result['helpers'] = array_values(array_unique($result['helpers']));

        return $result;
    }

    private function removeForeignTabs(string $content, string $currentUri, array &$tabLinks): string
    {
        return (string) preg_replace_callback(self::ANCHOR_PATTERN, function (array $anchor) use ($currentUri, &$tabLinks): string {
            $labels = $this->extractTranslationKeys(source: $anchor[2], pattern: self::TRANSLATE_PATTERN);
            $targets = $this->getAnchorTargets(attributes: $anchor[1]);

            if (count($labels) !== 1 || $targets === [] || Str::is($targets, $currentUri)) {
                return $anchor[0];
            }

            $tabLinks[$labels[0]] = array_values(array_unique(array_merge($tabLinks[$labels[0]] ?? [], $targets)));

            return '';
        }, $content);
    }

    private function getAnchorTargets(string $attributes): array
    {
        $routeUris = [];

        if (preg_match_all(self::ANCHOR_ROUTE_PATTERN, $attributes, $routeNames) > 0) {
            $routeUris = $this->getStaticRouteUris(routeNames: $routeNames[1]);
        }

        if ($routeUris !== []) {
            return $routeUris;
        }

        preg_match_all(self::ANCHOR_ACTIVE_ROUTE_PATTERN, $attributes, $activeRouteNames);
        preg_match_all(self::ANCHOR_ACTIVE_URI_PATTERN, $attributes, $activeUris);

        return array_values(array_unique(array_merge($this->getStaticRouteUris(routeNames: $activeRouteNames[1]), $activeUris[1])));
    }

    private function getStaticRouteUris(array $routeNames): array
    {
        $uris = [];

        foreach ($routeNames as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            if ($route !== null && ! str_contains($route->uri(), '{')) {
                $uris[] = $route->uri();
            }
        }

        return $uris;
    }

    private function extractConstantLoopLabels(string $content): array
    {
        preg_match_all(self::CONSTANT_LOOP_PATTERN, $content, $loops, PREG_SET_ORDER);
        $labels = [];

        foreach ($loops as [, $className, $constantName]) {
            $class = $this->resolveBladeClass(className: $className, content: $content);
            $values = $class !== null && defined($class.'::'.$constantName) ? constant($class.'::'.$constantName) : null;

            foreach (is_array($values) ? $values : [] as $value) {
                if (is_string($value) && $value !== '') {
                    $labels[] = trim(str_replace(self::LABEL_SEPARATORS, ' ', $value));
                }
            }
        }

        return $labels;
    }

    private function getLoopTabTitles(): array
    {
        $titles = [];

        foreach ($this->panelSetting(key: 'view_directories') as $directory) {
            foreach (File::allFiles(base_path($directory)) as $file) {
                if (! Str::endsWith($file->getFilename(), '.blade.php')) {
                    continue;
                }

                foreach ($this->extractConstantLoopTabs(content: $file->getContents()) as $label => $uri) {
                    $titles[$uri] ??= $label;
                }
            }
        }

        return $titles;
    }

    private function extractConstantLoopTabs(string $content): array
    {
        preg_match_all(self::CONSTANT_LOOP_BLOCK_PATTERN, $content, $loops, PREG_SET_ORDER);
        $tabs = [];

        foreach ($loops as $loop) {
            [, $className, $constantName, $firstVariable] = $loop;
            $secondVariable = $loop[4] ?? '';

            if (preg_match(self::LOOP_ROUTE_PATTERN, $loop[5], $routeCall) !== 1 || ! Route::has($routeCall[1])) {
                continue;
            }

            $class = $this->resolveBladeClass(className: $className, content: $content);
            $values = $class !== null && defined($class.'::'.$constantName) ? constant($class.'::'.$constantName) : null;

            foreach (is_array($values) ? $values : [] as $key => $value) {
                $parameters = array_map(
                    fn (string $argument): ?string => $this->resolveLoopArgument(
                        argument: trim($argument),
                        variables: $secondVariable === '' ? [$firstVariable => $value] : [$firstVariable => $key, $secondVariable => $value],
                    ),
                    explode(',', $routeCall[2]),
                );

                if (! is_string($value) || in_array(null, $parameters, true)) {
                    continue;
                }

                try {
                    $tabs[trim(str_replace(self::LABEL_SEPARATORS, ' ', $value))] = ltrim(route($routeCall[1], $parameters, false), '/');
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return $tabs;
    }

    private function resolveLoopArgument(string $argument, array $variables): ?string
    {
        if (preg_match(self::STRING_LITERAL_ARGUMENT_PATTERN, $argument, $literal) === 1) {
            return $literal[1];
        }

        return isset($variables[$argument]) ? (string) $variables[$argument] : null;
    }

    private function resolveBladeClass(string $className, string $content): ?string
    {
        if (str_contains($className, '\\')) {
            return class_exists($className) ? ltrim($className, '\\') : null;
        }

        $candidates = [self::DEFAULT_ENUM_NAMESPACE.$className];

        if (preg_match(sprintf(self::IMPORTED_CLASS_PATTERN, preg_quote($className, '/')), $content, $import) === 1) {
            array_unshift($candidates, $import[1].'\\'.$className);
        }

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractIncludedViewFiles(string $content): array
    {
        $files = [];

        preg_match_all(self::INCLUDE_DIRECTIVE_PATTERN, $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$match, $offset]) {
            [$arguments] = $this->readArguments(source: $content, openOffset: $offset + strlen($match));

            foreach ($this->extractStringLiterals(source: $arguments) as $literal) {
                $file = $this->findViewFile(view: $literal);

                if ($file !== null) {
                    $files[] = $file;
                }
            }

            $files = array_merge($files, $this->findDynamicViewFiles(expression: $this->splitArguments(arguments: $arguments)[0] ?? ''));
        }

        return array_values(array_unique($files));
    }

    private function findDynamicViewFiles(string $expression): array
    {
        if (! str_contains($expression, '$')) {
            return [];
        }

        $concatenation = trim((string) preg_replace(self::ARRAY_SUBSCRIPT_PATTERN, '', $expression));
        $literals = $this->extractStringLiterals(source: $concatenation);

        if ($literals === [] || ! Str::startsWith($concatenation, ['\'', '"']) || ! str_contains($literals[0], '.')) {
            return [];
        }

        $pattern = implode('*', $literals).(Str::endsWith($concatenation, ['\'', '"']) ? '' : '*');
        [$namespace, $viewPattern] = str_contains($pattern, '::') ? explode('::', $pattern, 2) : [null, $pattern];
        $finder = View::getFinder();
        $directories = $namespace === null ? $finder->getPaths() : ($finder->getHints()[$namespace] ?? []);
        $files = [];

        foreach ($directories as $directory) {
            $files = array_merge($files, File::glob($directory.'/'.str_replace('.', '/', $viewPattern).'.blade.php'));
        }

        return $files;
    }

    private function extractHelperNames(string $content): array
    {
        preg_match_all(self::HELPER_CALL_PATTERN, $content, $matches);

        return array_values(array_filter(
            array_unique($matches[1]),
            fn (string $name): bool => $name !== self::TRANSLATE_FUNCTION && $this->isApplicationHelper(name: $name),
        ));
    }

    private function isApplicationHelper(string $name): bool
    {
        if (! function_exists($name)) {
            return false;
        }

        $function = new ReflectionFunction($name);

        return $function->isUserDefined() && Str::startsWith((string) $function->getFileName(), app_path());
    }

    private function getHelperLabels(array $helperNames): array
    {
        $labels = [];

        foreach (array_diff($helperNames, array_keys($this->panelSetting(key: 'tab_helper_definitions'))) as $helperName) {
            $this->helperLabels[$helperName] ??= $this->extractTranslationKeys(
                source: $this->getFunctionSource(function: new ReflectionFunction($helperName)),
                pattern: self::LABEL_TRANSLATE_PATTERN,
            );
            $labels = array_merge($labels, $this->helperLabels[$helperName]);
        }

        return $labels;
    }

    private function getFunctionLabels(array $functions): array
    {
        $labels = [];
        $pending = array_map(fn (ReflectionFunctionAbstract $function): array => [$function, 0], $functions);
        $visited = [];

        while ($pending !== []) {
            [$function, $depth] = array_shift($pending);
            $identity = ($function instanceof ReflectionMethod ? $function->getDeclaringClass()->getName().'::' : '').$function->getName();

            if (isset($visited[$identity])) {
                continue;
            }

            $visited[$identity] = true;
            $body = $this->getFunctionSource(function: $function);
            $labels = array_merge($labels, $this->extractTranslationKeys(source: $body, pattern: self::LABEL_TRANSLATE_PATTERN));

            if ($depth >= self::MAX_DELEGATION_DEPTH || ! $function instanceof ReflectionMethod) {
                continue;
            }

            preg_match_all(self::DELEGATED_CALL_PATTERN, $body, $calls);

            foreach (array_unique($calls[1]) as $methodName) {
                $class = $function->getDeclaringClass();

                if ($class->hasMethod($methodName)) {
                    $pending[] = [$class->getMethod($methodName), $depth + 1];
                }
            }
        }

        return array_values(array_unique($labels));
    }

    private function extractTitleParts(string $content): array
    {
        preg_match_all(self::TITLE_SECTION_PATTERN, $content, $sections, PREG_OFFSET_CAPTURE);

        foreach ($sections[0] as [$match, $offset]) {
            [$arguments] = $this->readArguments(source: $content, openOffset: $offset + strlen($match));
            $sectionArguments = $this->splitArguments(arguments: $arguments);

            if (count($sectionArguments) < 2 || $this->extractStringLiterals(source: $sectionArguments[0]) !== [self::TITLE_SECTION_NAME]) {
                continue;
            }

            $titleParts = $this->getTitlePartsFromExpression(expression: $sectionArguments[1]);

            if ($titleParts !== []) {
                return $titleParts;
            }
        }

        if (preg_match_all(self::PAGE_HEADER_TITLE_PATTERN, $content, $headers) > 0) {
            return array_values(array_unique(array_map(
                fn (string $key): string => normalizeTranslationKey($key),
                $this->extractTranslationKeys(source: implode(' ', $headers[1]), pattern: self::TRANSLATE_PATTERN),
            )));
        }

        return [];
    }

    private function getTitlePartsFromExpression(string $expression): array
    {
        if (str_contains($expression, self::NULL_COALESCE_OPERATOR)) {
            return [];
        }

        $keys = $this->extractTranslationKeys(source: $expression, pattern: self::TRANSLATE_PATTERN);

        if ($keys === []) {
            $literals = $this->extractStringLiterals(source: $expression);
            $isSingleLiteral = count($literals) === 1 && preg_match(self::STRING_LITERAL_PATTERN, trim($expression), $match) === 1 && $match[0] === trim($expression);

            return $isSingleLiteral ? [normalizeTranslationKey($literals[0])] : [];
        }

        if (preg_match(self::TERNARY_PATTERN, $expression) === 1) {
            $keys = [end($keys)];
        }

        return array_values(array_unique(array_map(fn (string $key): string => normalizeTranslationKey($key), $keys)));
    }

    private function getRouteTitle(RegisteredRoute $route): string
    {
        $segments = array_values(array_filter(
            explode('.', (string) ($route->getName() ?? str_replace('/', '.', $route->uri()))),
            fn (string $segment): bool => ! in_array($segment, $this->panelSetting(key: 'route_name_ignored_segments'), true)
                && ! in_array($segment, BusinessModule::values(), true),
        ));

        $words = str_replace(['-', '_'], ' ', implode(' ', array_slice($segments, -2)));

        return ucfirst(strtolower(trim((string) preg_replace('/\s+/', ' ', $words))));
    }

    private function resolveBusinessModule(string $uri, RegisteredRoute $route): string
    {
        foreach (array_slice(explode('/', $uri), 1) as $segment) {
            foreach (BusinessModule::values() as $businessModule) {
                if ($segment === $businessModule || Str::startsWith($segment, $businessModule.'-')) {
                    return $businessModule;
                }
            }
        }

        $moduleName = Str::lower((string) Str::of($route->getActionName())->after('Modules\\')->before('\\'));

        return Str::startsWith($route->getActionName(), 'Modules\\') && in_array($moduleName, BusinessModule::values(), true)
            ? $moduleName
            : BusinessModule::COMMON;
    }

    private function attachTabLabels(array $pages): array
    {
        $uris = array_keys($pages);

        foreach ($pages as $page) {
            foreach ($page['tab_links'] as $label => $targets) {
                foreach ($uris as $uri) {
                    if (Str::is($targets, $uri) && ! in_array($label, $pages[$uri]['keywords'], true)) {
                        $pages[$uri]['keywords'][] = $label;
                    }
                }
            }
        }

        return $pages;
    }

    private function attachHelperTabLabels(array $pages): array
    {
        foreach ($this->panelSetting(key: 'tab_helper_definitions') as $helperName => $definitionsConfig) {
            if (! function_exists($helperName)) {
                continue;
            }

            $definitions = (array) config($definitionsConfig, []);

            foreach ($this->extractKeyedLabels(source: $this->getFunctionSource(function: new ReflectionFunction($helperName))) as $tabKey => $label) {
                $patterns = (array) ($definitions[$tabKey]['patterns'] ?? []);

                foreach (array_keys($pages) as $uri) {
                    if ($patterns !== [] && Str::is($patterns, $uri) && ! in_array($label, $pages[$uri]['keywords'], true)) {
                        $pages[$uri]['keywords'][] = $label;
                    }
                }
            }
        }

        return $pages;
    }

    private function extractKeyedLabels(string $source): array
    {
        preg_match_all(self::KEYED_LABEL_PATTERN, $source, $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn (array $match): array => [$match[1] => $match[2]])->all();
    }

    private function buildIndexEntries(array $pages): array
    {
        return array_values(array_map(fn (array $page): array => [
            'page_title' => $this->getPageTitle(page: $page, locale: self::SOURCE_LOCALE),
            'page_title_value' => $this->getPageTitle(page: $page, locale: self::SOURCE_LOCALE),
            'key' => base64_encode($page['uri']),
            'uri' => $page['uri'],
            'uri_count' => count(explode('/', $page['uri'])),
            'method' => self::PAGE_METHOD,
            'view' => $page['view'],
            'priority' => self::PAGE_PRIORITY,
            'type' => self::PAGE_TYPE,
            'business_module' => $page['business_module'],
        ], $pages));
    }

    private function buildKeywordEntries(array $pages, string $locale): array
    {
        return array_values(array_map(function (array $page) use ($locale): array {
            $keywords = [];

            foreach ($page['keywords'] as $keyword) {
                $keywords[$keyword] = $this->translateText(text: $keyword, locale: $locale);
            }

            return [
                'key' => base64_encode($page['uri']),
                'page_title' => $this->getPageTitle(page: $page, locale: self::SOURCE_LOCALE),
                'page_title_value' => $this->getPageTitle(page: $page, locale: $locale),
                'keywords' => (object) $keywords,
            ];
        }, $pages));
    }

    private function getPageTitle(array $page, string $locale): string
    {
        $title = implode(' ', array_map(fn (string $part): string => $this->translateText(text: $part, locale: $locale), $page['title_parts']));

        return $page['tab_title'] === null ? $title : $title.self::TAB_TITLE_SEPARATOR.$this->translateText(text: ucfirst($page['tab_title']), locale: $locale);
    }

    private function translateText(string $text, string $locale): string
    {
        $key = normalizeTranslationKey($text);
        $translations = $this->translations[$locale] ??= $this->loadTranslations(locale: $locale);
        $value = $translations[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : $key;
    }

    private function loadTranslations(string $locale): array
    {
        $translations = [];

        foreach (array_reverse(self::TRANSLATION_FILES) as $fileName) {
            $path = lang_path($locale.'/'.$fileName);
            $messages = File::exists($path) ? include $path : [];
            $translations = array_merge($translations, is_array($messages) ? $messages : []);
        }

        return $translations;
    }

    private function getKeywordLocales(): array
    {
        $locales = array_map(
            fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            File::glob(public_path(self::LOCALE_SOURCE_DIRECTORY.'/*.json')),
        );

        return array_values(array_unique(array_merge([self::SOURCE_LOCALE], $locales)));
    }

    private function keywordFilePath(string $locale): string
    {
        return public_path($this->panelSetting(key: 'page_keywords_directory').'/'.$locale.'.json');
    }

    private function writeJson(string $path, array $data): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function extractTranslationKeys(string $source, string $pattern): array
    {
        preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);
        $keys = [];

        foreach ($matches as $match) {
            $key = trim(stripslashes(($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? '')));

            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    private function extractStringLiterals(string $source): array
    {
        preg_match_all(self::STRING_LITERAL_PATTERN, $source, $matches, PREG_SET_ORDER);

        return array_map(fn (array $match): string => stripslashes(($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? '')), $matches);
    }

    private function getFunctionSource(ReflectionFunctionAbstract $function): string
    {
        $file = $function->getFileName();

        if ($file === false || ! is_file($file)) {
            return '';
        }

        $lines = file($file);

        return implode('', array_slice($lines, $function->getStartLine() - 1, $function->getEndLine() - $function->getStartLine() + 1));
    }

    private function readArguments(string $source, int $openOffset): array
    {
        $depth = 1;
        $quote = null;
        $length = strlen($source);

        for ($position = $openOffset; $position < $length; $position++) {
            $character = $source[$position];

            if ($quote !== null) {
                if ($character === '\\') {
                    $position++;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;
            } elseif ($character === '(' || $character === '[') {
                $depth++;
            } elseif ($character === ')' || $character === ']') {
                $depth--;

                if ($depth === 0) {
                    return [substr($source, $openOffset, $position - $openOffset), $position + 1];
                }
            }
        }

        return [substr($source, $openOffset), $length];
    }

    private function splitArguments(string $arguments): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $quote = null;
        $length = strlen($arguments);

        for ($position = 0; $position < $length; $position++) {
            $character = $arguments[$position];
            $current .= $character;

            if ($quote !== null) {
                if ($character === '\\' && $position + 1 < $length) {
                    $current .= $arguments[++$position];
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '\'' || $character === '"') {
                $quote = $character;
            } elseif (in_array($character, ['(', '[', '{'], true)) {
                $depth++;
            } elseif (in_array($character, [')', ']', '}'], true)) {
                $depth--;
            } elseif ($character === ',' && $depth === 0) {
                $parts[] = trim(substr($current, 0, -1));
                $current = '';
            }
        }

        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }
}
