<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait ReportPeriodTrait
{
    public const DATE_TYPE_THIS_YEAR = 'this_year';

    public const DATE_TYPE_THIS_MONTH = 'this_month';

    public const DATE_TYPE_THIS_WEEK = 'this_week';

    public const DATE_TYPE_TODAY = 'today';

    public const DATE_TYPE_CUSTOM = 'custom_date';

    public const DATE_TYPES = [
        self::DATE_TYPE_THIS_YEAR,
        self::DATE_TYPE_THIS_MONTH,
        self::DATE_TYPE_THIS_WEEK,
        self::DATE_TYPE_TODAY,
        self::DATE_TYPE_CUSTOM,
    ];

    private const GRANULARITY_WEEKDAY = 'weekday';

    private const GRANULARITY_DAY = 'day';

    private const GRANULARITY_MONTH = 'month';

    private const GRANULARITY_YEAR = 'year';

    private const BUCKET_EXPRESSIONS = [
        self::GRANULARITY_WEEKDAY => "DATE_FORMAT(created_at, '%W')",
        self::GRANULARITY_DAY => 'DAY(created_at)',
        self::GRANULARITY_MONTH => 'MONTH(created_at)',
        self::GRANULARITY_YEAR => 'YEAR(created_at)',
    ];

    /** @return array{start: Carbon, end: Carbon, granularity: string, buckets: array<int|string, int|string>} */
    protected function resolvePeriod(string $dateType, ?string $from, ?string $to): array
    {
        $now = Carbon::now();

        return match ($dateType) {
            self::DATE_TYPE_TODAY => $this->weekdayPeriod(start: $now->copy()->startOfDay(), end: $now->copy()->endOfDay()),
            self::DATE_TYPE_THIS_WEEK => $this->weekdayPeriod(start: $now->copy()->startOfWeek(), end: $now->copy()->endOfWeek()),
            self::DATE_TYPE_THIS_MONTH => $this->dayPeriod(start: $now->copy()->startOfMonth(), end: $now->copy()->endOfMonth()),
            self::DATE_TYPE_CUSTOM => $this->customPeriod(start: Carbon::parse($from)->startOfDay(), end: Carbon::parse($to)->endOfDay()),
            default => $this->monthPeriod(start: $now->copy()->startOfYear(), end: $now->copy()->endOfYear()),
        };
    }

    private function customPeriod(Carbon $start, Carbon $end): array
    {
        return match (true) {
            $start->year !== $end->year => $this->yearPeriod(start: $start, end: $end),
            $start->month !== $end->month => $this->monthPeriod(start: $start, end: $end),
            default => $this->dayPeriod(start: $start, end: $end),
        };
    }

    private function weekdayPeriod(Carbon $start, Carbon $end): array
    {
        $buckets = [];
        for ($day = $start->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
            $buckets[$day->format('l')] = $day->format('l');
        }

        return $this->period(start: $start, end: $end, granularity: self::GRANULARITY_WEEKDAY, buckets: $buckets);
    }

    private function dayPeriod(Carbon $start, Carbon $end): array
    {
        $days = range($start->day, $end->day);

        return $this->period(start: $start, end: $end, granularity: self::GRANULARITY_DAY, buckets: array_combine($days, $days));
    }

    private function monthPeriod(Carbon $start, Carbon $end): array
    {
        $buckets = [];
        foreach (range($start->month, $end->month) as $month) {
            $buckets[$month] = Carbon::create(year: $start->year, month: $month)->format('M');
        }

        return $this->period(start: $start, end: $end, granularity: self::GRANULARITY_MONTH, buckets: $buckets);
    }

    private function yearPeriod(Carbon $start, Carbon $end): array
    {
        $years = range($start->year, $end->year);

        return $this->period(start: $start, end: $end, granularity: self::GRANULARITY_YEAR, buckets: array_combine($years, $years));
    }

    private function period(Carbon $start, Carbon $end, string $granularity, array $buckets): array
    {
        return ['start' => $start, 'end' => $end, 'granularity' => $granularity, 'buckets' => $buckets];
    }

    protected function bucketExpression(array $period): string
    {
        return self::BUCKET_EXPRESSIONS[$period['granularity']];
    }
}
