<?php

namespace App\Support;

class UiManager
{
    public function toast(
        ?string $text = null,
        ?string $heading = null,
        int $duration = 5000,
        ?string $variant = null,
        ?string $position = null,
    ): void {
        app('livewire')->current()?->dispatch('swal',
            title: $heading ?? match ($variant) {
                'danger' => 'Error',
                'warning' => 'Warning',
                default => 'Success',
            },
            text: $text ?? '',
            icon: match ($variant) {
                'danger' => 'error',
                'warning' => 'warning',
                default => 'success',
            },
            timer: $duration,
            position: $position ?? 'center',
        );
    }

    public function modal(string $name): object
    {
        return new class($name) {
            public function __construct(private string $name) {}

            public function show(): void
            {
                app('livewire')->current()?->dispatch('ui-modal-show', name: $this->name);
            }

            public function close(): void
            {
                app('livewire')->current()?->dispatch('ui-modal-close', name: $this->name);
            }
        };
    }
}
