{{-- Remediation action button with the design's phase flow.
     Props: $a (action vm), $check (vm), $Icon (class), $color (status color), $size --}}
@php
    $pad = ($size ?? 'xs') === 'sm' ? '7px 13px' : '5px 10px';
    $fs = ($size ?? 'xs') === 'sm' ? 13 : 12.5;
    $isz = ($size ?? 'xs') === 'sm' ? 15 : 14;
    $name = $check['name'];
    $key = $a['key'];
@endphp

@if ($a['kind'] === 'rerun')
    <button type="button" x-data="{ phase: 'idle' }" class="h-btn h-btn--secondary"
        style="padding: {{ $pad }}; font-size: {{ $fs }}px"
        :disabled="phase === 'running'" @click.stop="phase = 'running'; $wire.runAction(@js($name), @js($key)).then(() => phase = 'idle')">
        <span :class="phase === 'running' && 'h-spin'" style="display: inline-flex">{!! $Icon::outline('sync', $isz, 2) !!}</span>
        <span x-text="phase === 'running' ? 'Rodando…' : @js($a['label'])"></span>
    </button>
@else
    <span x-data="{ phase: 'idle' }" @click.stop style="display: inline-flex; gap: 6px">
        <button type="button" x-show="phase === 'idle'" class="h-btn h-btn--fix"
            style="--accent: {{ $color }}; padding: {{ $pad }}; font-size: {{ $fs }}px" @click="phase = 'confirm'">
            {!! $Icon::outline('wrench', $isz, 2) !!}<span>{{ $a['label'] }}</span>
        </button>

        <template x-if="phase === 'confirm'">
            <span style="display: inline-flex; gap: 6px">
                <button type="button" class="h-btn h-btn--fix" style="--accent: {{ $color }}; padding: {{ $pad }}; font-size: {{ $fs }}px"
                    @click="phase = 'running'; $wire.runAction(@js($name), @js($key)).then(() => phase = 'done')">
                    {!! $Icon::solid('ok', $isz) !!}<span>Confirmar</span>
                </button>
                <button type="button" class="h-btn h-btn--ghost" style="padding: {{ $pad }}; font-size: {{ $fs }}px" @click="phase = 'idle'">Cancelar</button>
            </span>
        </template>

        <button type="button" x-show="phase === 'running'" class="h-btn h-btn--fix" disabled
            style="--accent: {{ $color }}; padding: {{ $pad }}; font-size: {{ $fs }}px">
            <span class="h-spin" style="display: inline-flex">{!! $Icon::outline('sync', $isz, 2) !!}</span><span>Aplicando…</span>
        </button>

        <button type="button" x-show="phase === 'done'" class="h-btn h-btn--secondary" disabled
            style="padding: {{ $pad }}; font-size: {{ $fs }}px; color: var(--success); border-color: color-mix(in srgb, var(--success) 35%, transparent)">
            {!! $Icon::solid('ok', $isz) !!}<span>Aplicado</span>
        </button>
    </span>
@endif
