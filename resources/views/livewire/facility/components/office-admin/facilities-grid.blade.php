<x-ui::card>
    <div class="mb-4">
        <x-ui::heading size="lg">Assigned Facilities</x-ui::heading>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->facilities as $facility)
            <x-ui::card wire:key="assigned-facility-{{ $facility->FID }}" class="flex flex-col gap-0 overflow-hidden p-0">
                <div class="relative h-36 w-full bg-slate-100 dark:bg-slate-800">
                    @if ($facility->images->isNotEmpty())
                        <img
                            src="{{ asset('storage/'.$facility->images->first()->image_path) }}"
                            class="h-full w-full object-cover"
                            alt="{{ $facility->Facility_Name }}"
                            loading="lazy"
                            decoding="async"
                        />
                    @else
                        <div class="flex h-full w-full items-center justify-center">
                            <x-ui::icon.building-office class="size-10 text-slate-300 dark:text-slate-600" />
                        </div>
                    @endif

                    <button
                        type="button"
                        wire:click="requestToggleStatus({{ $facility->FID }})"
                        wire:loading.attr="disabled"
                        wire:target="requestToggleStatus({{ $facility->FID }})"
                        class="group absolute right-3 top-3 rounded-full transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-slate-800"
                        aria-label="Change {{ $facility->Facility_Name }} status from {{ $facility->Status }} to {{ $facility->Status === 'Unavailable' ? 'Available' : 'Unavailable' }}"
                        title="Click to mark as {{ $facility->Status === 'Unavailable' ? 'available' : 'unavailable' }}"
                    >
                        <span
                            @class([
                                'inline-flex min-w-24 items-center justify-center whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold leading-none text-white shadow-sm transition-colors',
                                'bg-emerald-700 group-hover:bg-red-600' => $facility->Status === 'Available',
                                'bg-red-600 group-hover:bg-emerald-700' => $facility->Status === 'Unavailable',
                            ])
                        >
                            {{ $facility->Status }}
                        </span>
                    </button>
                </div>

                <div class="flex flex-col gap-3 px-5 py-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <x-ui::heading size="lg">{{ $facility->Facility_Name }}</x-ui::heading>
                            <x-ui::text size="sm" variant="subtle">#{{ $facility->FID }}</x-ui::text>
                        </div>

                    </div>

                    <x-ui::separator />

                    <div class="grid grid-cols-1 gap-2 text-sm">
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <x-ui::icon.tag class="size-4 text-slate-400" />
                            <span>{{ $facility->facility_type ? ucfirst($facility->facility_type) : 'Type not specified' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <x-ui::icon.users class="size-4 text-slate-400" />
                            <span>{{ $facility->Capacity !== null ? $facility->Capacity.' capacity' : '—' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <x-ui::icon.map-pin class="size-4 text-slate-400" />
                            <span>{{ $facility->Location ?? '—' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <x-ui::icon.briefcase class="size-4 text-slate-400" />
                            <span>{{ $facility->Office ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <x-ui::button
                            size="sm"
                            variant="primary"
                            wire:click="edit({{ $facility->FID }})"
                            class="w-full"
                        >
                            Edit facility
                        </x-ui::button>
                        <x-ui::button
                            size="sm"
                            :variant="$facility->Status === 'Unavailable' ? 'primary' : 'danger'"
                            wire:click="requestToggleStatus({{ $facility->FID }})"
                            class="w-full"
                        >
                            {{ $facility->Status === 'Unavailable' ? 'Activate Facility' : 'Deactivate Facility' }}
                        </x-ui::button>
                    </div>
                </div>
            </x-ui::card>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                No facilities assigned to your account.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->facilities->links() }}
    </div>
</x-ui::card>
