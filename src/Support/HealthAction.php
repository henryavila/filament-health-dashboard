<?php

declare(strict_types=1);

namespace HenryAvila\FilamentHealthDashboard\Support;

use Closure;

/**
 * A remediation action shown for a check (in the card footer and the modal),
 * matching the design's two kinds: `rerun` (re-run, no confirm) and `fix`
 * (tinted by status, with an inline confirm step). The handler runs server-side
 * via the dashboard's Livewire `runAction()`.
 */
final class HealthAction
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $kind = 'rerun',
        public readonly bool $confirm = false,
        public readonly ?string $confirmMessage = null,
        public readonly bool $authorized = true,
        private readonly ?Closure $handler = null,
    ) {}

    public static function rerun(string $label, Closure $handler, string $key = 'rerun'): self
    {
        return new self($key, $label, 'rerun', false, null, true, $handler);
    }

    public static function fix(
        string $label,
        Closure $handler,
        ?string $confirmMessage = null,
        bool $authorized = true,
        string $key = 'fix',
    ): self {
        return new self($key, $label, 'fix', true, $confirmMessage, $authorized, $handler);
    }

    public function run(): void
    {
        if ($this->handler !== null) {
            ($this->handler)();
        }
    }

    /**
     * View-model (no closure) for the blade.
     *
     * @return array{key: string, label: string, kind: string, confirm: bool, confirmMessage: string|null}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'kind' => $this->kind,
            'confirm' => $this->confirm,
            'confirmMessage' => $this->confirmMessage,
        ];
    }
}
