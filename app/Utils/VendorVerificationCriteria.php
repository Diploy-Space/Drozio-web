<?php

namespace App\Utils;

use Carbon\CarbonInterface;

class VendorVerificationCriteria
{
    public const COMPLETED_ORDERS = 'completed_orders';

    public const ORDER_COMPLETION_RATE = 'order_completion_rate';

    public const ACCOUNT_AGE_YEARS = 'account_age_years';

    public const POSITIVE_FEEDBACK_TREND = 'positive_feedback_trend';

    public const AVERAGE_RATING = 'average_rating';

    public const TARGET_COMPLETED_ORDERS = 1000;

    public const TARGET_ORDER_COMPLETION_RATE = 90;

    public const TARGET_ACCOUNT_AGE_YEARS = 2;

    public const TARGET_FEEDBACK_TREND_WINDOW_DAYS = 60;

    public const TARGET_FEEDBACK_TREND_POSITIVE_RATE = 80;

    public const POSITIVE_REVIEW_RATING = 4;

    public const TARGET_AVERAGE_RATING = 4.5;

    public const RATING_SCALE = 5;

    public const SANDBOX_COMPLETED_ORDERS = 10;

    public const SANDBOX_ORDER_COMPLETION_RATE = 80;

    public const SANDBOX_ACCOUNT_AGE_DAYS = 2;

    public const DAYS_PER_YEAR = 365;

    public const SANDBOX_FEEDBACK_TREND_WINDOW_DAYS = 120;

    public const SANDBOX_FEEDBACK_TREND_POSITIVE_RATE = 50;

    public const SANDBOX_POSITIVE_REVIEW_RATING = 3;

    public const SANDBOX_AVERAGE_RATING = 3.5;

    private const SANDBOX_APP_MODE = 'dev';

    public static function usesSandboxTargets(): bool
    {
        return env('APP_DEBUG') === true && env('APP_MODE') === self::SANDBOX_APP_MODE;
    }

    public static function completedOrdersTarget(): int
    {
        return self::usesSandboxTargets() ? self::SANDBOX_COMPLETED_ORDERS : self::TARGET_COMPLETED_ORDERS;
    }

    public static function orderCompletionRateTarget(): int
    {
        return self::usesSandboxTargets() ? self::SANDBOX_ORDER_COMPLETION_RATE : self::TARGET_ORDER_COMPLETION_RATE;
    }

    public static function accountAgeYearsTarget(): float
    {
        return self::usesSandboxTargets()
            ? round(self::SANDBOX_ACCOUNT_AGE_DAYS / self::DAYS_PER_YEAR, 4)
            : (float) self::TARGET_ACCOUNT_AGE_YEARS;
    }

    public static function accountCreatedCutoff(): CarbonInterface
    {
        return self::usesSandboxTargets()
            ? now()->subDays(self::SANDBOX_ACCOUNT_AGE_DAYS)
            : now()->subYears(self::TARGET_ACCOUNT_AGE_YEARS);
    }

    public static function feedbackTrendWindowDays(): int
    {
        return self::usesSandboxTargets() ? self::SANDBOX_FEEDBACK_TREND_WINDOW_DAYS : self::TARGET_FEEDBACK_TREND_WINDOW_DAYS;
    }

    public static function feedbackTrendPositiveRateTarget(): int
    {
        return self::usesSandboxTargets() ? self::SANDBOX_FEEDBACK_TREND_POSITIVE_RATE : self::TARGET_FEEDBACK_TREND_POSITIVE_RATE;
    }

    public static function positiveReviewRating(): int
    {
        return self::usesSandboxTargets() ? self::SANDBOX_POSITIVE_REVIEW_RATING : self::POSITIVE_REVIEW_RATING;
    }

    public static function averageRatingTarget(): float
    {
        return self::usesSandboxTargets() ? (float) self::SANDBOX_AVERAGE_RATING : self::TARGET_AVERAGE_RATING;
    }

    /** @return array<string, float|int> */
    public static function thresholds(): array
    {
        return [
            self::COMPLETED_ORDERS => self::completedOrdersTarget(),
            self::ORDER_COMPLETION_RATE => self::orderCompletionRateTarget(),
            self::ACCOUNT_AGE_YEARS => self::accountAgeYearsTarget(),
            self::POSITIVE_FEEDBACK_TREND => self::feedbackTrendPositiveRateTarget(),
            self::AVERAGE_RATING => self::averageRatingTarget(),
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::thresholds());
    }

    /**
     * @param  array{completed_orders?: int, terminal_orders?: int, account_age_years?: float, recent_reviews?: int, recent_positive_reviews?: int, previous_reviews?: int, previous_positive_reviews?: int, average_rating?: float}  $metrics
     * @return array<int, array{key: string, target: float|int, actual: float|int, met: bool}>
     */
    public static function evaluate(array $metrics): array
    {
        $completedOrders = (int) ($metrics['completed_orders'] ?? 0);
        $completionRate = self::completionRate($metrics);
        $accountAgeYears = (float) ($metrics['account_age_years'] ?? 0);
        $recentPositiveRate = self::positiveRate(
            positive: (int) ($metrics['recent_positive_reviews'] ?? 0),
            total: (int) ($metrics['recent_reviews'] ?? 0),
        );
        $previousPositiveRate = self::positiveRate(
            positive: (int) ($metrics['previous_positive_reviews'] ?? 0),
            total: (int) ($metrics['previous_reviews'] ?? 0),
        );
        $averageRating = round((float) ($metrics['average_rating'] ?? 0), 2);
        $completedOrdersTarget = self::completedOrdersTarget();
        $completionRateTarget = self::orderCompletionRateTarget();
        $accountAgeTarget = self::accountAgeYearsTarget();
        $positiveTrendTarget = self::feedbackTrendPositiveRateTarget();
        $averageRatingTarget = self::averageRatingTarget();

        return [
            [
                'key' => self::COMPLETED_ORDERS,
                'target' => $completedOrdersTarget,
                'actual' => $completedOrders,
                'met' => $completedOrders >= $completedOrdersTarget,
            ],
            [
                'key' => self::ORDER_COMPLETION_RATE,
                'target' => $completionRateTarget,
                'actual' => $completionRate,
                'met' => $completionRate >= $completionRateTarget,
            ],
            [
                'key' => self::ACCOUNT_AGE_YEARS,
                'target' => $accountAgeTarget,
                'actual' => $accountAgeYears,
                'met' => $accountAgeYears >= $accountAgeTarget,
            ],
            [
                'key' => self::POSITIVE_FEEDBACK_TREND,
                'target' => $positiveTrendTarget,
                'actual' => $recentPositiveRate,
                'met' => (int) ($metrics['recent_reviews'] ?? 0) > 0
                    && $recentPositiveRate >= $positiveTrendTarget
                    && $recentPositiveRate >= $previousPositiveRate,
            ],
            [
                'key' => self::AVERAGE_RATING,
                'target' => $averageRatingTarget,
                'actual' => $averageRating,
                'met' => $averageRating >= $averageRatingTarget,
            ],
        ];
    }

    /** @param  array<int, array{met: bool}>  $evaluation */
    public static function isQualified(array $evaluation): bool
    {
        foreach ($evaluation as $criterion) {
            if (! $criterion['met']) {
                return false;
            }
        }

        return $evaluation !== [];
    }

    /** @return array{is_verified: bool, is_badge_enabled: bool, is_qualified: bool, show_prompt: bool, criteria: array<int, array<string, mixed>>} */
    public static function overview(array $metrics, bool $isVerified, bool $isBadgeEnabled): array
    {
        $evaluation = self::evaluate(metrics: $metrics);

        return [
            'is_verified' => $isVerified,
            'is_badge_enabled' => $isBadgeEnabled,
            'is_qualified' => self::isQualified(evaluation: $evaluation),
            'show_prompt' => ! $isVerified && $isBadgeEnabled,
            'criteria' => self::displayRows(evaluation: $evaluation),
        ];
    }

    /** @return array<int, array{key: string, label: string, leading: string, highlight: string, trailing: string, current: ?string, met: bool}> */
    public static function displayRows(array $evaluation = []): array
    {
        $thresholds = self::thresholds();
        $met = [];
        $current = [];

        foreach ($evaluation as $criterion) {
            $met[$criterion['key']] = (bool) $criterion['met'];
            $current[$criterion['key']] = self::formatCurrentValue(key: $criterion['key'], actual: (float) ($criterion['actual'] ?? 0));
        }

        $rows = [
            [
                'key' => self::COMPLETED_ORDERS,
                'leading' => '',
                'highlight' => number_format($thresholds[self::COMPLETED_ORDERS]),
                'trailing' => ' '.translate('Orders completed'),
            ],
            [
                'key' => self::ORDER_COMPLETION_RATE,
                'leading' => translate('Order completion rate above').' ',
                'highlight' => $thresholds[self::ORDER_COMPLETION_RATE].'%',
                'trailing' => '',
            ],
            self::accountAgeDisplayRow(),
            [
                'key' => self::POSITIVE_FEEDBACK_TREND,
                'leading' => '',
                'highlight' => translate('Positive'),
                'trailing' => ' '.translate('customer feedback trend'),
            ],
            [
                'key' => self::AVERAGE_RATING,
                'leading' => '',
                'highlight' => $thresholds[self::AVERAGE_RATING].'+',
                'trailing' => '/'.number_format(self::RATING_SCALE, 2).' '.translate('Rating'),
            ],
        ];

        return array_map(
            static fn (array $row): array => [
                ...$row,
                'label' => trim($row['leading'].$row['highlight'].$row['trailing']),
                'current' => $current[$row['key']] ?? null,
                'met' => $met[$row['key']] ?? true,
            ],
            $rows,
        );
    }

    private static function formatCurrentValue(string $key, float $actual): string
    {
        return match ($key) {
            self::COMPLETED_ORDERS => number_format($actual),
            self::ORDER_COMPLETION_RATE, self::POSITIVE_FEEDBACK_TREND => self::formatDecimal(value: $actual, decimals: 2).'%',
            self::ACCOUNT_AGE_YEARS => self::formatAccountAge(years: $actual),
            self::AVERAGE_RATING => number_format($actual, 2).'/'.number_format(self::RATING_SCALE, 2),
            default => self::formatDecimal(value: $actual, decimals: 2),
        };
    }

    private static function formatAccountAge(float $years): string
    {
        if (self::usesSandboxTargets()) {
            return (int) floor(round($years * self::DAYS_PER_YEAR, 2)).' '.translate('Days');
        }

        return self::formatDecimal(value: floor($years * 10) / 10, decimals: 1).' '.translate('Years');
    }

    private static function formatDecimal(float $value, int $decimals): string
    {
        $formatted = number_format($value, $decimals);

        return str_contains($formatted, '.') ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
    }

    /** @return array{key: string, leading: string, highlight: string, trailing: string} */
    private static function accountAgeDisplayRow(): array
    {
        if (self::usesSandboxTargets()) {
            return [
                'key' => self::ACCOUNT_AGE_YEARS,
                'leading' => '',
                'highlight' => self::SANDBOX_ACCOUNT_AGE_DAYS.'+',
                'trailing' => ' '.translate('days since account creation'),
            ];
        }

        return [
            'key' => self::ACCOUNT_AGE_YEARS,
            'leading' => '',
            'highlight' => self::TARGET_ACCOUNT_AGE_YEARS.'+',
            'trailing' => ' '.translate('years since account creation'),
        ];
    }

    public static function completionRate(array $metrics): float
    {
        $terminalOrders = (int) ($metrics['terminal_orders'] ?? 0);

        if ($terminalOrders === 0) {
            return 0.0;
        }

        return round(((int) ($metrics['completed_orders'] ?? 0) / $terminalOrders) * 100, 2);
    }

    public static function positiveRate(int $positive, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($positive / $total) * 100, 2);
    }
}
