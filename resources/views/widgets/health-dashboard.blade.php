{{-- Reusable health dashboard body (widget = Livewire component).
     Renders the spatie/laravel-health results: status grid + per-check meta
     drill-down. Per-check CheckIntegration infolist/actions land in the UI pass. --}}
@php
    /** @var \Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults|null $results */
    $results = $this->getLatestResults();
    $pollingInterval = $this->getPollingInterval();
    $checks = $results?->storedCheckResults ?? collect();

    $statusMeta = [
        'ok' => ['color' => 'success', 'icon' => 'heroicon-m-check-circle'],
        'warning' => ['color' => 'warning', 'icon' => 'heroicon-m-exclamation-triangle'],
        'failed' => ['color' => 'danger', 'icon' => 'heroicon-m-x-circle'],
        'crashed' => ['color' => 'danger', 'icon' => 'heroicon-m-x-circle'],
        'skipped' => ['color' => 'gray', 'icon' => 'heroicon-m-minus-circle'],
    ];
    $resolve = fn (string $status) => $statusMeta[$status] ?? ['color' => 'gray', 'icon' => 'heroicon-m-question-mark-circle'];

    $formatMeta = fn (mixed $value): string => match (gettype($value)) {
        'array', 'object' => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'boolean' => $value ? 'true' : 'false',
        'NULL' => '—',
        default => (string) $value,
    };

    $countBy = fn (array $statuses) => $checks->whereIn('status', $statuses)->count();
    $summary = [
        ['label' => 'Total', 'value' => $checks->count(), 'color' => 'gray'],
        ['label' => 'OK', 'value' => $countBy(['ok']), 'color' => 'success'],
        ['label' => 'Atenção', 'value' => $countBy(['warning']), 'color' => 'warning'],
        ['label' => 'Falhando', 'value' => $countBy(['failed', 'crashed']), 'color' => 'danger'],
    ];

    $lastRanAt = $results?->finishedAt;
    $isStale = $lastRanAt && $lastRanAt->getTimestamp() < now()->subMinutes(5)->getTimestamp();
@endphp

<div @if ($pollingInterval) wire:poll.{{ $pollingInterval }} @endif class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        @if ($lastRanAt)
            <p @class([
                'text-sm',
                'text-danger-500' => $isStale,
                'text-gray-500 dark:text-gray-400' => ! $isStale,
            ])>
                Última verificação: {{ $lastRanAt->format('d/m/Y H:i:s') }}@if ($isStale) — desatualizado @endif
            </p>
        @else
            <span></span>
        @endif

        <x-filament::button
            wire:click="runChecks"
            wire:loading.attr="disabled"
            wire:target="runChecks"
            icon="heroicon-o-arrow-path"
            size="sm"
            color="gray"
        >
            Verificar agora
        </x-filament::button>
    </div>

    @if ($checks->isEmpty())
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Nenhum resultado de health check disponível ainda. Rode <code>php artisan health:check</code>
                ou clique em <strong>Verificar agora</strong>.
            </div>
        </x-filament::section>
    @else
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($summary as $stat)
                <x-filament::section class="text-center">
                    <div @class([
                        'text-3xl font-bold',
                        'text-success-600 dark:text-success-400' => $stat['color'] === 'success',
                        'text-warning-600 dark:text-warning-400' => $stat['color'] === 'warning',
                        'text-danger-600 dark:text-danger-400' => $stat['color'] === 'danger',
                        'text-gray-700 dark:text-gray-300' => $stat['color'] === 'gray',
                    ])>{{ $stat['value'] }}</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($checks as $check)
                @php $meta = $resolve($check->status); @endphp
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon
                                :icon="$meta['icon']"
                                @class([
                                    'h-5 w-5',
                                    'text-success-500' => $meta['color'] === 'success',
                                    'text-warning-500' => $meta['color'] === 'warning',
                                    'text-danger-500' => $meta['color'] === 'danger',
                                    'text-gray-400' => $meta['color'] === 'gray',
                                ])
                            />
                            <span>{{ $check->label ?: $check->name }}</span>
                        </div>
                    </x-slot>

                    @if ($check->notificationMessage || $check->shortSummary)
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $check->notificationMessage ?: $check->shortSummary }}
                        </p>
                    @endif

                    @if (! empty($check->meta))
                        <details class="mt-3 text-sm">
                            <summary class="cursor-pointer text-gray-500 dark:text-gray-400">Detalhes</summary>
                            <div class="mt-2 overflow-x-auto rounded-lg bg-gray-50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                <table class="w-full text-left">
                                    @foreach ($check->meta as $key => $value)
                                        <tr class="align-top">
                                            <th class="pr-3 font-medium text-gray-500 dark:text-gray-400">{{ $key }}</th>
                                            <td class="font-mono text-gray-700 dark:text-gray-200">{{ $formatMeta($value) }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </details>
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    @endif
</div>
