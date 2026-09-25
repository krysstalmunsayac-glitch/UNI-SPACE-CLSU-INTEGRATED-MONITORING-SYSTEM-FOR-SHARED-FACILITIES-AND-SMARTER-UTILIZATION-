    {{-- ===== EDIT MODAL ===== --}}
    <x-ui::modal wire:model.self="showModal" class="md:w-[28rem]">
        <div class="space-y-6">
            <div>
                <x-ui::heading size="lg">
                    {{ $scheduleReadOnly ? 'Schedule Details' : 'Edit Schedule' }}
                </x-ui::heading>

                <x-ui::subheading>
                    {{ $scheduleReadOnly ? $scheduleReadOnlyReason : "Update this booking's details." }}
                </x-ui::subheading>
            </div>

            <div>
                <p class="mb-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">Request</p>
                @php
                    $originalRequest = $this->requestsList->firstWhere('RID', $form->Request_ID);
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <p class="font-semibold">
                        #{{ $form->Request_ID }}
                        — {{ $originalRequest?->facility?->Facility_Name ?? '—' }}
                        ({{ $originalRequest?->Purpose ?? 'No purpose' }})
                    </p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Requested by: <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $originalRequest?->requesterName() ?? 'Unknown requester' }}</span>
                    </p>
                </div>
            </div>

            @if ($scheduleReadOnly)
                @php
                    $displayDate = filled($form->Date)
                        ? \Illuminate\Support\Carbon::parse($form->Date)->format('F j, Y')
                        : '—';
                    $displayStartTime = filled($form->Start_Time)
                        ? \Illuminate\Support\Carbon::parse($form->Start_Time)->format('g:i A')
                        : '—';
                    $displayEndTime = match ($form->End_Time) {
                        null, '' => '—',
                        '24:00' => '12:00 AM (next day)',
                        default => \Illuminate\Support\Carbon::parse($form->End_Time)->format('g:i A'),
                    };
                @endphp

                <dl class="grid grid-cols-2 gap-3">
                    <div class="col-span-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</dt>
                        <dd class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">{{ $displayDate }}</dd>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Start time</dt>
                        <dd class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">{{ $displayStartTime }}</dd>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">End time</dt>
                        <dd class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">{{ $displayEndTime }}</dd>
                    </div>

                    <div class="col-span-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</dt>
                        <dd class="mt-1 font-semibold text-zinc-800 dark:text-zinc-100">{{ $form->Status ?: '—' }}</dd>
                    </div>
                </dl>
            @else
                <div>
                    <x-ui::input
                        wire:model="form.Date"
                        type="date"
                        label="Date"
                        min="{{ app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user()) }}"
                    />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ app(\App\Services\BookingPolicy::class)->noticeMessage(auth()->user()) }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-ui::time-select wire:model.live="form.Start_Time" label="Start time" :options="$this->startTimeSlots" />
                    </div>

                    <div>
                        <x-ui::time-select wire:model="form.End_Time" label="End time (1 hour minimum)" :options="$this->endTimeSlots" />
                    </div>
                </div>

                <div>
                    <x-ui::select wire:model="form.Status" label="Status">
                        <x-ui::select.option value="Booked">
                            Booked
                        </x-ui::select.option>

                        <x-ui::select.option value="Blocked">
                            Blocked
                        </x-ui::select.option>
                    </x-ui::select>
                </div>
            @endif

            <div class="flex gap-2">
                @unless ($scheduleReadOnly)
                    <x-ui::button
                        wire:click="save"
                        variant="primary"
                        class="flex-1"
                    >
                        Update
                    </x-ui::button>

                @endunless

                <x-ui::button
                    wire:click="$set('showModal', false)"
                    variant="ghost"
                    class="flex-1"
                >
                    {{ $scheduleReadOnly ? 'Close' : 'Cancel' }}
                </x-ui::button>
            </div>
        </div>
    </x-ui::modal>


