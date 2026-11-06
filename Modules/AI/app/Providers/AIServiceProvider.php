<?php

namespace Modules\AI\app\Providers;

use App\Services\New\ProductRetailService;
use App\Services\New\ProductService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\AI\AIProviders\AIProviderManager;
use Modules\AI\AIProviders\ClaudeProvider;
use Modules\AI\AIProviders\OpenAIProvider;
use Modules\AI\app\Console\PruneChatSessions;
use Modules\AI\app\Contracts\AuctionAIContract;
use Modules\AI\app\Events\ShoppingAssistantTurnRequestedEvent;
use Modules\AI\app\Listeners\ShoppingAssistantTurnListener;
use Modules\AI\app\Services\New\AuctionAIContentService;
use Modules\AI\app\Services\ShoppingAssistant\CartActionCollector;
use Modules\AI\app\Services\ShoppingAssistant\CartSelectionContext;
use Modules\AI\app\Services\ShoppingAssistant\ProductCollector;
use Modules\AI\app\Services\ShoppingAssistant\ShownProductStore;
use Modules\AI\app\Tools\AddToCartTool;
use Modules\AI\app\Tools\GetSimilarProductsTool;
use Modules\AI\app\Tools\SearchProductsTool;
use Modules\AI\app\Tools\UpdateCartQuantityTool;
use Modules\AI\app\View\Composers\ShoppingAssistantComposer;

class AIServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'AI';

    protected string $moduleNameLower = 'ai';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        Event::listen(ShoppingAssistantTurnRequestedEvent::class, ShoppingAssistantTurnListener::class);

        View::composer('ai::partials.shopping-assistant._sidebar', ShoppingAssistantComposer::class);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->bind(AuctionAIContract::class, AuctionAIContentService::class);

        $this->app->when(SearchProductsTool::class)->needs(ProductService::class)->give(ProductRetailService::class);
        $this->app->when(GetSimilarProductsTool::class)->needs(ProductService::class)->give(ProductRetailService::class);
        $this->app->when(AddToCartTool::class)->needs(ProductService::class)->give(ProductRetailService::class);
        $this->app->when(UpdateCartQuantityTool::class)->needs(ProductService::class)->give(ProductRetailService::class);

        $this->app->singleton(AIProviderManager::class, fn () => new AIProviderManager([
            new OpenAIProvider,
            new ClaudeProvider,
        ]));

        $this->app->scoped(ProductCollector::class);
        $this->app->scoped(CartActionCollector::class);
        $this->app->scoped(ShownProductStore::class);
        $this->app->scoped(CartSelectionContext::class);

        $this->app->singleton(ShoppingAssistantComposer::class);
    }

    protected function registerCommands(): void
    {
        $this->commands([PruneChatSessions::class]);
    }

    protected function registerCommandSchedules(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('ai:prune-chat-sessions')->daily();
        });
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower.'-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);

        $componentNamespace = str_replace('/', '\\', config('modules.namespace').'\\'.$this->moduleName.'\\'.config('modules.paths.generator.component-class.path'));
        Blade::componentNamespace($componentNamespace, $this->moduleNameLower);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
