<?php

namespace Tests\Unit;

use App\Models\Banner;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;

/**
 * The two things the base actually does: turn the controller's formatted filter array into where
 * clauses, and pick the default-locale value out of a parallel-input form.
 */
class BaseServiceTest extends TestCase
{
    public function test_it_translates_each_filter_form(): void
    {
        $sql = $this->service()->filter([
            'status' => 1,
            'id' => [1, 2, 3],
            'deleted_at' => null,
        ])->toSql();

        $this->assertStringContainsString('`status` = ?', $sql);
        $this->assertStringContainsString('`id` in (?, ?, ?)', $sql);
        $this->assertStringContainsString('`deleted_at` is null', $sql);
    }

    public function test_no_filters_adds_no_clause(): void
    {
        $this->assertSame([], $this->service()->filter([])->getBindings());
    }

    public function test_it_reads_the_english_value_whatever_position_it_was_posted_in(): void
    {
        $data = ['lang' => ['bn', 'en'], 'name' => ['পোশাক', 'Clothing']];

        $this->assertSame('Clothing', $this->service()->localeValue($data, 'name'));
    }

    public function test_it_falls_back_to_the_first_locale_when_english_is_absent(): void
    {
        $data = ['lang' => ['bn', 'ar'], 'name' => ['পোশাক', 'ملابس']];

        $this->assertSame('পোশাক', $this->service()->localeValue($data, 'name'));
    }

    public function test_a_missing_or_empty_field_is_null(): void
    {
        $this->assertNull($this->service()->localeValue([], 'name'));
        $this->assertNull($this->service()->localeValue(['lang' => ['en'], 'name' => ['']], 'name'));
    }

    private function service(): object
    {
        return new class(new Banner) extends BaseService
        {
            public function filter(array $filters): Builder
            {
                return $this->applyFilters($this->query(), $filters);
            }

            public function localeValue(array $data, string $field): ?string
            {
                return $this->defaultLocaleValue($data, $field);
            }
        };
    }
}
