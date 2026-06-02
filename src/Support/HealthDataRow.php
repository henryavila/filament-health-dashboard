<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Support;

final class HealthDataRow
{
    /**
     * @param  list<string>  $cells
     */
    public function __construct(
        public readonly array $cells,
        public readonly string $status = 'ok',
        public readonly bool $fixable = false,
    ) {}

    /**
     * @return array{cells: list<string>, status: string, fixable: bool}
     */
    public function toArray(): array
    {
        return [
            'cells' => $this->cells,
            'status' => $this->status,
            'fixable' => $this->fixable,
        ];
    }
}
