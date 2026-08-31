{{-- View Modal (read-only) --}}
<x-ui::modal
    wire:model.self="showViewModal"
    class="!flex !max-h-[94vh] w-[96vw] max-w-[calc(210mm+3rem)] !flex-col !overflow-hidden !rounded-2xl !bg-zinc-100 !p-0 dark:!bg-zinc-900"
>
    <div class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-6">
        <article class="mx-auto min-h-[297mm] w-full max-w-[210mm] bg-white px-5 py-6 text-zinc-900 shadow-xl ring-1 ring-black/5 dark:bg-zinc-950 dark:text-white sm:px-[14mm] sm:py-[12mm]">
            <header class="flex flex-col gap-4 border-b-2 border-emerald-700 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.28em] text-emerald-700 dark:text-emerald-300">Siel Space</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-zinc-950 dark:text-white">Facility Reservation Request</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Complete request record · Read-only</p>
                </div>
                <div class="flex items-center gap-3 sm:flex-col sm:items-end sm:gap-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-400">Request ID</span>
                    <span class="text-xl font-black text-emerald-800 dark:text-emerald-300">#{{ $viewingId }}</span>
                </div>
            </header>

            <section class="mt-6 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                <div class="border-b border-emerald-200 px-5 py-2.5 dark:border-emerald-900/50">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-800 dark:text-emerald-300">Requester information</h3>
                </div>
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-lg font-black text-white shadow-sm">
                        {{ \Illuminate\Support\Str::of($Requester_Name ?: 'Unknown')->substr(0, 1)->upper() }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-base font-black text-emerald-950 dark:text-white">{{ $Requester_Name ?? 'Unknown user' }}</p>
                        <p class="break-all text-sm text-emerald-800/70 dark:text-emerald-100/70">{{ $Requester_Email ?? 'No email available' }}</p>
                    </div>
                    <dl class="grid min-w-0 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Contact</dt>
                            <dd class="font-medium">{{ $Requester_Contact ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Office</dt>
                            <dd class="font-medium">{{ $Requester_Office ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">User ID</dt>
                            <dd class="font-medium">{{ $User_ID ? '#'.$User_ID : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="mt-6">
                <div class="mb-3 flex items-center justify-between gap-4">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Reservation details</h3>
                    <x-ui::badge
                        size="sm"
                        :color="match($Status) {
                            'Approved'  => 'green',
                            'Rejected'  => 'red',
                            'Cancelled' => 'amber',
                            'Ended'     => 'zinc',
                            default     => 'blue',
                        }"
                    >
                        {{ $Status === 'Ended' ? 'Event Ended' : $Status }}
                    </x-ui::badge>
                </div>

                <dl class="grid overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50/60 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-900/50">
                    @foreach ([
                        'Proposed Date' => $Proposed_Date ? \Carbon\Carbon::parse($Proposed_Date)->format('M d, Y') : '—',
                        'Time' => ($Proposed_Start_Time ?: '—').' – '.($Proposed_End_Time ?: '—'),
                        'Facility' => $Facility_Name ?? '—',
                        'Event' => $Event_Title ?? '—',
                        'Expected Attendees' => $Capacity ?? '—',
                    ] as $label => $value)
                        <div class="border-b border-zinc-200 px-4 py-3 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 sm:[&:nth-child(odd)]:border-r dark:border-zinc-800">
                            <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="mt-1 text-sm font-bold text-zinc-900 dark:text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="px-4 py-3">
                        <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Attachment</dt>
                        <dd class="mt-1 text-sm font-bold">
                            @if ($attachmentPath)
                                <a href="{{ route('requests.attachment.download', $viewingId) }}" class="inline-flex items-center gap-1.5 text-emerald-700 hover:underline dark:text-emerald-300">
                                    <span aria-hidden="true">↓</span> Download attachment
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Purpose</h3>
                    <p class="mt-2 whitespace-pre-wrap text-sm font-medium">{{ $Purpose ?: '—' }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Purpose categories</h3>
                    <p class="mt-2 text-sm font-medium">
                        {{ $Purpose_Categories ? implode(', ', $Purpose_Categories) : '—' }}
                        @if ($Other_Purpose) — {{ $Other_Purpose }} @endif
                    </p>
                </div>
            </section>

            <section class="mt-6">
                <h3 class="mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Reservation questionnaire</h3>
                <dl class="grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'Reservation frequency' => $Reservation_Frequency,
                        'Facility importance' => $Facility_Importance,
                        'Requirements fit' => $Requirements_Fit,
                        'Reserve again' => $Reserve_Again_Intent,
                    ] as $label => $answer)
                        <div class="rounded-lg bg-zinc-50 px-4 py-3 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                            <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="mt-1 text-sm font-bold">{{ $answer ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="mt-6 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Facility amenities</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if (! empty($Requested_Amenities))
                        @foreach ($Requested_Amenities as $amenityName)
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-200 dark:ring-emerald-900">{{ $amenityName }}</span>
                        @endforeach
                    @else
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">—</span>
                    @endif
                </div>
            </section>
        </article>
    </div>

    <footer class="flex shrink-0 flex-wrap gap-2 border-t border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-950 sm:px-6">
        @if (! $viewingArchived && $Status === 'Pending' && $viewingId)
            <x-ui::button wire:click="approve({{ $viewingId }})" data-ui-confirm="Approve this request?" variant="primary" icon="check" class="flex-1">
                Approve
            </x-ui::button>
        @endif
        @if (! $viewingArchived && $Status === 'Approved' && $viewingId)
            <x-ui::button
                wire:click="cancel({{ $viewingId }})"
                data-ui-confirm="Cancel this approved request? Its facility schedule will be removed and the requester will be notified."
                data-ui-confirm-title="Confirm cancellation"
                data-ui-confirm-label="Cancel request"
                data-ui-confirm-variant="danger"
                variant="danger"
                icon="x-mark"
                class="flex-1"
            >
                Cancel
            </x-ui::button>
        @elseif (! $viewingArchived && $Status === 'Pending' && $viewingId)
            <x-ui::button wire:click="openRejectModal({{ $viewingId }}); $set('showViewModal', false)" variant="danger" icon="x-mark" class="flex-1">
                Reject
            </x-ui::button>
        @endif
        <x-ui::button wire:click="$set('showViewModal', false)" variant="ghost" class="flex-1">Close</x-ui::button>
    </footer>
</x-ui::modal>
