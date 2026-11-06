<?php
namespace Tests\Feature\Contract\Web;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Contract\ContractTestCase;
class HeaderComposerContractTest extends ContractTestCase {
    #[Test]
    public function header_composer_provides_categories_and_currencies(): void {
        $factory = app('view');
        $view = $factory->make('layouts.front-end.partials._header');
        $factory->callComposer($view);   // runs registered View::composer callbacks
        $data = $view->getData();
        $this->assertArrayHasKey('headerCategories', $data, 'composer did not share headerCategories');
        $this->assertArrayHasKey('headerCurrencies', $data, 'composer did not share headerCurrencies');
        $this->assertInstanceOf(Collection::class, $data['headerCurrencies']);
        // active currencies from the seeded dump
        $this->assertGreaterThan(0, $data['headerCurrencies']->count());
        fwrite(STDERR, "\n[HEADER-COMPOSER] currencies=".$data['headerCurrencies']->count().", categories=".(is_countable($data['headerCategories'])?count($data['headerCategories']):'n/a')."\n");
    }
}
