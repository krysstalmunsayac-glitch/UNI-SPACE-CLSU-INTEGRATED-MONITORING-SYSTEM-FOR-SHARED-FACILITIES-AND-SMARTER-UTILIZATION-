<flux:card>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="lg">Assigned Facilities</flux:heading>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <a href="{{ route('exports.facilities.csv') }}" download class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-800 transition hover:border-emerald-600 hover:bg-emerald-50 hover:text-emerald-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-400 dark:hover:bg-emerald-500/15">
                Download CSV
            </a>
            <a href="{{ route('exports.facilities.pdf') }}" download class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-800 transition hover:border-emerald-600 hover:bg-emerald-50 hover:text-emerald-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-emerald-400 dark:hover:bg-emerald-500/15">
                Download PDF
            </a>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->facilities as $facility)
            <flux:card class="flex flex-col gap-0 overflow-hidden p-0">
                <div class="relative h-36 w-full bg-slate-100 dark:bg-slate-800">
                    @if ($facility->images->isNotEmpty())
                        <img
                            src="{{ asset('storage/'.$facility->images->first()->image_path) }}"
                            class="h-full w-full object-cover"
                            alt="{{ $facility->Facility_Name }}"
                        />
                    @else
                        <div class="flex h-full w-full items-center justify-center">
                            <flux:icon.building-office class="size-10 text-slate-300 dark:text-slate-600" />
                        </div>
                    @endif

                    <button
                        type="button"
                        wire:click="toggleStatus({{ $facility->FID }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleStatus({{ $facility->FID }})"
                        class="group absolute right-3 top-3 rounded-full transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-slate-800"
                        aria-label="Change {{ $facility->Facility_Name }} status from {{ $facility->Status }} to {{ $facility->Status === 'Unavailable' ? 'Available' : 'Unavailable' }}"
                        title="Click to mark as {{ $facility->Status === 'Unavailable' ? 'available' : 'unavailable' }}"
                    >
                        <span
                            @class([
                                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold text-white shadow-sm transition-colors',
                                'bg-emerald-700 group-hover:bg-red-600' => $facility->Status === 'Available',
                                'bg-amber-600 group-hover:bg-red-600' => $facility->Status === 'Under Maintenance',
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
                            <flux:heading size="lg">{{ $facility->Facility_Name }}</flux:heading>
                            <flux:text size="sm" variant="subtle">#{{ $facility->FID }}</flux:text>
                        </div>

                        <flux:text class="whitespace-nowrap font-semibold">
                            ₱{{ number_format($facility->Price, 2) }}
                        </flux:text>
                    </div>

                    <flux:separator />

                    <div class="grid grid-cols-1 gap-2 text-sm">
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <flux:icon.tag class="size-4 text-slate-400" />
                            <span>{{ $facility->facility_type ? ucfirst($facility->facility_type) : 'Type not specified' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <flux:icon.users class="size-4 text-slate-400" />
                            <span>{{ $facility->Capacity ?? 'N/A' }} capacity</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <flux:icon.map-pin class="size-4 text-slate-400" />
                            <span>{{ $facility->Location ?? 'N/A' }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <flux:icon.briefcase class="size-4 text-slate-400" />
                            <span>{{ $facility->Office ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <flux:button
                            size="sm"
                            variant="primary"
                            wire:click="edit({{ $facility->FID }})"
                            class="w-full"
                        >
                            Edit facility
                        </flux:button>
                        <flux:button
                            size="sm"
                            :variant="$facility->Status === 'Unavailable' ? 'primary' : 'danger'"
                            wire:click="toggleStatus({{ $facility->FID }})"
                            class="w-full"
                        >
                            {{ $facility->Status === 'Unavailable' ? 'Activate Facility' : 'Deactivate Facility' }}
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                No facilities assigned to your account.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->facilities->links() }}
    </div>
</flux:card>
