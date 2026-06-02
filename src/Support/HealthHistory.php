<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Support;

use Illuminate\Support\Facades\Date;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

/**
 * Builds 30-day history views from spatie's stored history items
 * ({@see HealthCheckResultHistoryItem}). Index 0 = 29 days ago, index 29 = today
 * (matching the design). Each day holds the worst status observed that day.
 */
final class HealthHistory
{
    public const int DAYS = 30;

    /**
     * Per-check 30-day status arrays. Days with no run are `null`.
     *
     * @return array<string, list<string|null>>
     */
    public static function heatmaps(): array
    {
        $today = Date::today();
        $since = $today->copy()->subDays(self::DAYS - 1)->startOfDay();

        /** @var array<string, array<int, string>> $byCheck */
        $byCheck = [];

        self::query($since)->each(function (HealthCheckResultHistoryItem $item) use (&$byCheck, $today): void {
            $name = (string) $item->check_name;
            $endedAt = $item->ended_at ?? $item->created_at;

            if ($endedAt === null) {
                return;
            }

            $offset = self::DAYS - 1 - (int) $endedAt->copy()->startOfDay()->diffInDays($today);

            if ($offset < 0 || $offset > self::DAYS - 1) {
                return;
            }

            $status = (string) $item->status;
            $current = $byCheck[$name][$offset] ?? null;

            if ($current === null || HealthStatus::rank($status) > HealthStatus::rank($current)) {
                $byCheck[$name][$offset] = $status;
            }
        });

        $result = [];

        foreach ($byCheck as $name => $days) {
            $row = [];
            for ($i = 0; $i < self::DAYS; $i++) {
                $row[] = $days[$i] ?? null;
            }
            $result[$name] = $row;
        }

        return $result;
    }

    private static function query(\Carbon\CarbonInterface $since): \Illuminate\Support\LazyCollection
    {
        return HealthCheckResultHistoryItem::query()
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->cursor();
    }
}
