@php
    use HenryAvila\FilamentHealthDashboard\Support\HealthIcons as Icon;
    use HenryAvila\FilamentHealthDashboard\Support\HealthStatus as St;

    $Icon = Icon::class;
    $St = St::class;

    $checks = $this->checks();
    $counts = $this->counts();
    $spark = $this->sparklines();
    $lastRan = $this->lastRanLabel();
    $stale = $this->isStale();
    $poll = $this->getPollingInterval();
    $empty = $this->isEmpty();

    // Sparkline → inline SVG (port of the design's Sparkline component).
    $sparkSvg = function (array $data, string $color, int $w = 108, int $h = 30): string {
        $data = array_values($data);
        if (count($data) < 2) {
            return '';
        }
        $max = max($data);
        $min = min($data);
        $span = ($max - $min) ?: 1;
        $stepX = $w / (count($data) - 1);
        $y = fn ($v) => $h - 3 - (($v - $min) / $span) * ($h - 6);
        $pts = [];
        foreach ($data as $i => $v) {
            $pts[] = [$i * $stepX, $y($v)];
        }
        $line = '';
        foreach ($pts as $i => $p) {
            $line .= ($i === 0 ? 'M' : 'L') . round($p[0], 1) . ' ' . round($p[1], 1) . ' ';
        }
        $area = $line . 'L' . $w . ' ' . $h . ' L0 ' . $h . ' Z';
        $last = end($pts);
        $gid = 'spk' . substr(md5($color . $w . implode(',', $data)), 0, 6);

        return <<<SVG
            <svg width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}" style="display:block;overflow:visible" aria-hidden="true">
              <defs><linearGradient id="{$gid}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="{$color}" stop-opacity="0.18"/><stop offset="1" stop-color="{$color}" stop-opacity="0"/>
              </linearGradient></defs>
              <path d="{$area}" fill="url(#{$gid})"/>
              <path d="{$line}" fill="none" stroke="{$color}" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/>
              <circle cx="{$last[0]}" cy="{$last[1]}" r="2.4" fill="{$color}"/>
            </svg>
            SVG;
    };

    $stats = [
        ['label' => 'Total', 'value' => $counts['total'], 'accent' => 'var(--fg-2)', 'spark' => $spark['total'], 'sub' => $counts['skip'] > 0 ? $counts['skip'] . ' ignorado(s)' : 'verificações ativas', 'dot' => false],
        ['label' => 'OK', 'value' => $counts['ok'], 'accent' => 'var(--success)', 'spark' => $spark['ok'], 'sub' => 'saudáveis', 'dot' => true],
        ['label' => 'Atenção', 'value' => $counts['warn'], 'accent' => 'var(--warning)', 'spark' => $spark['warn'], 'sub' => 'requer revisão', 'dot' => true],
        ['label' => 'Falhando', 'value' => $counts['fail'], 'accent' => 'var(--danger)', 'spark' => $spark['fail'], 'sub' => 'ação imediata', 'dot' => true],
    ];
@endphp

<div class="hd-root" x-data="{ selected: null }" @keydown.escape.window="selected = null"
    @if ($poll) wire:poll.{{ $poll }} @endif
    style="display: flex; flex-direction: column; gap: 24px">

    {{-- Header --}}
    <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap">
        <div>
            <h1 style="margin: 0; font-family: var(--font-sans); font-weight: 800; font-size: 30px; letter-spacing: -0.03em; color: var(--fg-1)">{{ $this->title() }}</h1>
            <p style="margin: 6px 0 0; font-family: var(--font-sans); font-size: 14.5px; color: var(--fg-2); max-width: 560px; line-height: 1.5">{{ $this->subtitle() }}</p>
            @if ($lastRan)
                <div style="display: flex; align-items: center; gap: 9px; margin-top: 12px">
                    <span style="color: {{ $stale ? 'var(--danger)' : 'var(--fg-3)' }}; display: inline-flex">{!! $Icon::outline('clock', 15, 1.8) !!}</span>
                    <span style="font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: {{ $stale ? 'var(--danger)' : 'var(--fg-2)' }}">Última verificação: {{ $lastRan }}</span>
                    @if ($stale)
                        <span style="display: inline-flex; align-items: center; padding: 2px 9px; border-radius: var(--r-full); background: var(--danger-bg); color: var(--danger); font-family: var(--font-sans); font-size: 11.5px; font-weight: 700">desatualizado</span>
                    @endif
                </div>
            @endif
        </div>
        <button type="button" class="h-btn h-btn--primary" style="padding: 11px 20px; font-size: 14.5px"
            wire:loading.attr="disabled" wire:target="runChecks" wire:click="runChecks">
            <span wire:loading.class="h-spin" wire:target="runChecks" style="display: inline-flex">{!! $Icon::outline('sync', 17, 2) !!}</span>
            <span wire:loading.remove wire:target="runChecks">Verificar agora</span>
            <span wire:loading wire:target="runChecks">Verificando…</span>
        </button>
    </div>

    @if ($stale)
        <div role="status" style="display: flex; align-items: center; gap: 13px; padding: 13px 16px; border-radius: var(--r-lg); background: var(--st-warn-bg); border: 1px solid color-mix(in srgb, var(--st-warn) 28%, transparent)">
            <span style="color: var(--st-warn); display: inline-flex; flex: none">{!! $Icon::solid('warn', 20) !!}</span>
            <div style="flex: 1; min-width: 0">
                <div style="font-family: var(--font-sans); font-weight: 700; font-size: 13.5px; color: var(--st-warn)">Resultados desatualizados</div>
                <div style="font-family: var(--font-sans); font-size: 13px; color: var(--fg-2); margin-top: 1px">A última verificação foi {{ $lastRan }} e o cache expirou (janela: 5 min). Os dados abaixo podem não refletir o estado atual.</div>
            </div>
        </div>
    @endif

    @if ($empty)
        {{-- Empty state --}}
        <div class="h-card" style="padding: 64px 32px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px">
            <span class="h-chip" style="width: 64px; height: 64px; background: var(--bg-subtle); color: var(--fg-3); margin-bottom: 8px">{!! $Icon::outline('pulse', 30, 1.8) !!}</span>
            <h3 style="margin: 0; font-family: var(--font-sans); font-weight: 700; font-size: 19px; color: var(--fg-1)">Nenhuma verificação registrada</h3>
            <p style="margin: 2px 0 0; font-family: var(--font-sans); font-size: 14px; color: var(--fg-2); max-width: 420px; line-height: 1.55">Registre o primeiro health check para começar a monitorar a aplicação. As verificações aparecem aqui assim que forem definidas.</p>
        </div>
    @else
        {{-- Stat row --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px">
            @foreach ($stats as $stat)
                <div class="h-card" style="padding: 16px 18px 14px; display: flex; flex-direction: column; gap: 2px; position: relative; overflow: hidden">
                    <div style="display: flex; align-items: center; gap: 7px">
                        @if ($stat['dot'])<span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $stat['accent'] }}; flex: none"></span>@endif
                        <span class="arch-overline">{{ $stat['label'] }}</span>
                    </div>
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-top: 4px">
                        <span class="tnum" style="font-family: var(--font-sans); font-weight: 900; font-size: 40px; line-height: 1; letter-spacing: -0.03em; color: var(--fg-1)">{{ $stat['value'] }}</span>
                        <span style="flex: none; padding-bottom: 3px">{!! $sparkSvg($stat['spark'], $stat['accent']) !!}</span>
                    </div>
                    <span style="margin-top: 8px; font-family: var(--font-sans); font-size: 12px; font-weight: 500; color: var(--fg-3)">{{ $stat['sub'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Checks grid (treatment A) --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 16px; align-items: stretch">
            @foreach ($checks as $i => $check)
                @php $s = $St::of($check['status']); @endphp
                <div class="h-card h-card--int" tabindex="0" role="button" wire:key="hc-{{ $check['name'] }}"
                    @click="selected = {{ $i }}" @keydown.enter="selected = {{ $i }}" @keydown.space.prevent="selected = {{ $i }}"
                    style="position: relative; overflow: hidden; display: flex; flex-direction: column">
                    <span class="h-accent" style="--accent: {{ $s['color'] }}"></span>
                    <div style="padding: var(--hd-card-pad); padding-left: calc(var(--hd-card-pad) + 4px); display: flex; flex-direction: column; gap: 12px; flex: 1">
                        <div style="display: flex; align-items: flex-start; gap: 13px">
                            <span class="h-chip" style="width: 42px; height: 42px; background: {{ $s['bg'] }}; color: {{ $s['color'] }}">{!! $Icon::outline($check['icon'], 22, 1.8) !!}</span>
                            <div style="flex: 1; min-width: 0">
                                <div style="font-family: var(--font-sans); font-weight: 700; font-size: 16px; color: var(--fg-1); letter-spacing: -0.01em">{{ $check['label'] }}</div>
                                <div style="margin-top: 3px; display: inline-flex; align-items: center; gap: 5px; color: var(--fg-3); font-size: 12px; font-weight: 500; font-family: var(--font-sans)">{!! $Icon::outline('clock', 13, 1.8) !!} Verificado {{ $check['lastRan'] }}</div>
                            </div>
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px 4px 8px; border-radius: var(--r-full); background: {{ $s['bg'] }}; color: {{ $s['color'] }}; font-family: var(--font-sans); font-weight: 700; font-size: 12px; line-height: 1; white-space: nowrap">{!! $Icon::solid($s['icon'], 15) !!}{{ $s['label'] }}</span>
                        </div>
                        <p style="margin: 0; font-family: var(--font-sans); font-size: 14px; line-height: 1.5; color: var(--fg-2); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 42px">{{ $check['summary'] }}</p>
                    </div>
                    <div style="border-top: 1px solid var(--border); padding: 11px 16px 11px calc(var(--hd-card-pad) + 4px); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; row-gap: 8px">
                        <span style="display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--fg-2)">Detalhes {!! $Icon::outline('chevronR', 14, 2.2) !!}</span>
                        <span style="display: inline-flex; flex-wrap: wrap; justify-content: flex-end; gap: 6px" @click.stop>
                            @foreach ($check['actions'] as $a)
                                @include('filament-health-dashboard::widgets._action', ['a' => $a, 'check' => $check, 'Icon' => $Icon, 'color' => $s['color'], 'size' => 'xs'])
                            @endforeach
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- History heatmap --}}
        <div class="h-card" style="padding: 0; overflow: hidden" x-data="{ open: true }">
            <button type="button" @click="open = !open" class="h-nav" style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 15px 20px; border: none; background: transparent; cursor: pointer; text-align: left">
                <span style="color: var(--fg-3); flex: none; display: inline-flex" x-html="open ? @js($Icon::outline('chevronD', 18, 2)) : @js($Icon::outline('chevronR', 18, 2))"></span>
                <span style="color: var(--fg-2); flex: none; display: inline-flex">{!! $Icon::outline('history', 18, 1.8) !!}</span>
                <span style="font-family: var(--font-sans); font-weight: 700; font-size: 15px; color: var(--fg-1)">Histórico — últimos 30 dias</span>
                <span style="font-family: var(--font-sans); font-size: 12.5px; color: var(--fg-3)">· detectar flapping</span>
                <span style="flex: 1"></span>
                <span style="display: flex; align-items: center; gap: 12px">
                    @foreach ([['OK', 'var(--success)'], ['Atenção', 'var(--warning)'], ['Falha', 'var(--danger)'], ['Ignorado', 'var(--gray-300)']] as $leg)
                        <span style="display: inline-flex; align-items: center; gap: 5px; font-family: var(--font-sans); font-size: 11.5px; font-weight: 500; color: var(--fg-3)"><span style="width: 9px; height: 9px; border-radius: 2px; background: {{ $leg[1] }}"></span>{{ $leg[0] }}</span>
                    @endforeach
                </span>
            </button>
            <div x-show="open" x-collapse style="padding: 4px 20px 18px">
                <div style="display: grid; grid-template-columns: 180px 1fr; gap: 14px; padding: 0 0 8px">
                    <span></span>
                    <div style="display: flex; justify-content: space-between; font-family: var(--font-mono); font-size: 10.5px; color: var(--fg-3)"><span>30 dias atrás</span><span>hoje</span></div>
                </div>
                @foreach ($checks as $check)
                    <div style="display: grid; grid-template-columns: 180px 1fr; align-items: center; gap: 14px; padding: 5px 0">
                        <div style="display: flex; align-items: center; gap: 8px; min-width: 0">
                            <span style="color: var(--fg-3); flex: none; display: inline-flex">{!! $Icon::outline($check['icon'], 15, 1.7) !!}</span>
                            <span style="font-family: var(--font-sans); font-size: 12.5px; font-weight: 600; color: var(--fg-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ $check['label'] }}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(30, 1fr); gap: 3px">
                            @foreach ($check['history'] as $di => $d)
                                <div class="h-cell" title="{{ $di === 29 ? 'hoje' : 'há ' . (29 - $di) . ' dia(s)' }} · {{ $d ? $St::of($d)['label'] : 'sem dados' }}"
                                    style="height: 15px; background: {{ $d ? $St::heatColor($d) : 'var(--gray-200)' }}; opacity: {{ $d && $St::key($d) === 'ok' ? '0.85' : '1' }}"></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Drill-down modal --}}
    <template x-if="selected !== null">
        <div class="h-scrim" @click="selected = null">
            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; padding: 24px">
                @foreach ($checks as $i => $check)
                    @php $s = $St::of($check['status']); @endphp
                    <div x-show="selected === {{ $i }}" class="h-modal" role="dialog" aria-modal="true" @click.stop
                        style="width: min(720px, 100%); max-height: 88vh; display: flex; flex-direction: column; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--r-xl); box-shadow: var(--shadow-xl); overflow: hidden">
                        {{-- header --}}
                        <div style="display: flex; align-items: flex-start; gap: 14px; padding: 20px 22px; border-bottom: 1px solid var(--border)">
                            <span class="h-chip" style="width: 46px; height: 46px; background: {{ $s['bg'] }}; color: {{ $s['color'] }}; flex: none">{!! $Icon::outline($check['icon'], 24, 1.8) !!}</span>
                            <div style="flex: 1; min-width: 0">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap">
                                    <span style="font-family: var(--font-sans); font-weight: 800; font-size: 19px; color: var(--fg-1); letter-spacing: -0.02em">{{ $check['label'] }}</span>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px 4px 8px; border-radius: var(--r-full); background: {{ $s['bg'] }}; color: {{ $s['color'] }}; font-family: var(--font-sans); font-weight: 700; font-size: 12px; line-height: 1; white-space: nowrap">{!! $Icon::solid($s['icon'], 15) !!}{{ $s['label'] }}</span>
                                </div>
                                <div style="margin-top: 4px; display: inline-flex; align-items: center; gap: 5px; color: var(--fg-3); font-size: 12.5px; font-weight: 500">{!! $Icon::outline('clock', 13, 1.8) !!} Verificado {{ $check['lastRan'] }}<span style="margin: 0 4px; color: var(--border-strong)">·</span><span style="font-family: var(--font-mono); font-size: 11.5px">{{ $check['name'] }}</span></div>
                            </div>
                            <button type="button" class="h-iconbtn" @click="selected = null" aria-label="Fechar" style="width: 34px; height: 34px; flex: none; color: var(--fg-3)">{!! $Icon::outline('x', 20, 2) !!}</button>
                        </div>
                        {{-- body --}}
                        <div class="h-scroll" style="padding: 20px 22px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px">
                            @if ($check['message'])
                                <div style="display: flex; gap: 11px; padding: 13px 15px; border-radius: var(--r-lg); background: {{ $s['bg'] }}; border: 1px solid color-mix(in srgb, {{ $s['color'] }} 22%, transparent)">
                                    <span style="color: {{ $s['color'] }}; flex: none; margin-top: 1px; display: inline-flex">{!! $Icon::solid($s['icon'], 19) !!}</span>
                                    <p style="margin: 0; font-family: var(--font-sans); font-size: 13.5px; line-height: 1.55; color: var(--fg-1)">{{ $check['message'] }}</p>
                                </div>
                            @endif

                            @if (! empty($check['meta']))
                                <div>
                                    <div class="arch-overline" style="margin-bottom: 9px">Metadados</div>
                                    <div style="border: 1px solid var(--border); border-radius: var(--r-lg); overflow: hidden">
                                        @foreach ($check['meta'] as $k => $v)
                                            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 12px; padding: 10px 14px; {{ ! $loop->last ? 'border-bottom: 1px solid var(--border);' : '' }} {{ $loop->index % 2 ? 'background: var(--bg-subtle);' : '' }}">
                                                <span style="font-family: var(--font-sans); font-size: 13px; font-weight: 600; color: var(--fg-2)">{{ $k }}</span>
                                                <span class="tnum" style="font-family: var(--font-mono); font-size: 12.5px; color: var(--fg-1); overflow-wrap: anywhere">{{ $v }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($check['dataTable'])
                                <div>
                                    <div style="display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 9px">
                                        <span class="arch-overline">{{ $check['dataTable']['title'] ?? 'Detalhes' }}</span>
                                        <span style="font-family: var(--font-sans); font-size: 12px; color: var(--fg-3)">{{ count($check['dataTable']['rows']) }} linhas</span>
                                    </div>
                                    <div class="h-scroll" style="border: 1px solid var(--border); border-radius: var(--r-lg); overflow: auto">
                                        <table style="width: 100%; border-collapse: collapse; font-family: var(--font-sans); font-size: 12.5px; min-width: 560px">
                                            <thead>
                                                <tr style="background: var(--bg-subtle)">
                                                    @foreach ($check['dataTable']['columns'] as $col)
                                                        <th style="text-align: left; padding: 9px 12px; font-weight: 700; font-size: 11px; letter-spacing: 0.04em; text-transform: uppercase; color: var(--fg-3); border-bottom: 1px solid var(--border); white-space: nowrap">{{ $col }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($check['dataTable']['rows'] as $row)
                                                    @php $rs = $St::of($row['status']); @endphp
                                                    <tr class="h-row-hover" style="{{ ! $loop->last ? 'border-bottom: 1px solid var(--border);' : '' }}">
                                                        @foreach ($row['cells'] as $ci => $cell)
                                                            @if ($ci === count($row['cells']) - 1)
                                                                <td style="padding: 9px 12px; color: {{ $row['fixable'] ? 'var(--fg-1)' : 'var(--fg-3)' }}; font-family: {{ $row['fixable'] ? 'var(--font-mono)' : 'var(--font-sans)' }}; font-size: {{ $row['fixable'] ? '11.5px' : '12.5px' }}; font-style: {{ $row['fixable'] ? 'normal' : 'italic' }}; white-space: nowrap">{{ $cell }}</td>
                                                            @elseif ($ci === count($row['cells']) - 2)
                                                                <td style="padding: 9px 12px"><span style="display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: var(--r-full); background: {{ $rs['bg'] }}; color: {{ $rs['color'] }}; font-family: var(--font-sans); font-size: 11.5px; font-weight: 700; white-space: nowrap"><span style="width: 6px; height: 6px; border-radius: 50%; background: {{ $rs['color'] }}"></span>{{ $cell }}</span></td>
                                                            @else
                                                                <td style="padding: 9px 12px; font-family: var(--font-mono); font-size: 11.5px; color: {{ $ci === 2 ? 'var(--fg-1)' : 'var(--fg-2)' }}; white-space: nowrap">{{ $cell }}</td>
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                        {{-- footer --}}
                        @if (! empty($check['actions']))
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding: 14px 22px; border-top: 1px solid var(--border); background: var(--bg-subtle)">
                                @foreach ($check['actions'] as $a)
                                    @include('filament-health-dashboard::widgets._action', ['a' => $a, 'check' => $check, 'Icon' => $Icon, 'color' => $s['color'], 'size' => 'sm'])
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </template>
</div>
