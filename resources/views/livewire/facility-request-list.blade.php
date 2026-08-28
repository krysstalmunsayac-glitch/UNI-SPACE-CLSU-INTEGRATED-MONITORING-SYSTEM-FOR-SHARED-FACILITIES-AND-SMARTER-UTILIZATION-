<?php

use App\Models\Requests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(as: 'request_sort', except: 'latest')]
    public string $requestSort = 'latest';

    #[Url(as: 'request_status', except: '')]
    public string $requestStatus = '';

    public function mount(): void
    {
        Requests::markPastRequestsAsEnded();
        $this->normalizeFilters();
    }

    public function updatedRequestSort(): void
    {
        $this->normalizeFilters();
        $this->resetPage('requests_page');
    }

    public function updatedRequestStatus(): void
    {
        $this->normalizeFilters();
        $this->resetPage('requests_page');
    }

    private function normalizeFilters(): void
    {
        if (! in_array($this->requestSort, ['latest', 'oldest'], true)) {
            $this->requestSort = 'latest';
        }

        if (! in_array($this->requestStatus, ['', 'Pending', 'Approved', 'Ended'], true)) {
            $this->requestStatus = '';
        }
    }

    public function with(): array
    {
        $requests = Requests::withTrashed()
            ->where('User_ID', Auth::id())
            ->where(function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->orWhere('Status', 'Ended');
            })
            ->with(['facility', 'event', 'feedback'])
            ->when($this->requestStatus, fn (Builder $query) => $query->where('Status', $this->requestStatus))
            ->orderBy('Created_at', $this->requestSort === 'oldest' ? 'asc' : 'desc')
            ->orderBy('RID', $this->requestSort === 'oldest' ? 'asc' : 'desc')
            ->paginate(5, ['*'], 'requests_page');

        return [
            'requests' => $requests,
            'totalUserRequests' => Requests::withTrashed()
                ->where('User_ID', Auth::id())
                ->where(function (Builder $query) {
                    $query->whereNull('deleted_at')
                        ->orWhere('Status', 'Ended');
                })
                ->count(),
        ];
    }
};
?>

    <section id="requests" class="border-t border-emerald-900/10 bg-emerald-50/50 py-20 dark:border-white/10 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.24em] text-yellow-600 dark:text-yellow-300">Dashboard</p>
                    <h2 class="mt-3 text-5xl font-black tracking-tight text-emerald-950 dark:text-white">Your facility requests</h2>
                    <p class="mt-5 max-w-3xl text-xl text-emerald-900/70 dark:text-zinc-300">
                        View the status of your submitted requests and update details from the same dashboard.
                    </p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-emerald-800 px-4 py-2 text-sm font-black text-white">
                    {{ $requests->count() }} of {{ $totalUserRequests }} submitted
                </span>
            </div>

            @if (session('success'))
                <div class="mt-8 rounded-2xl border border-emerald-200 bg-white p-4 font-semibold text-emerald-900 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-950/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="mt-8 rounded-2xl border border-yellow-300 bg-yellow-50 p-4 font-semibold text-emerald-950 shadow-sm dark:border-yellow-400/40 dark:bg-yellow-400/10 dark:text-yellow-100">
                    {{ session('warning') }}
                </div>
            @endif

            <div class="mt-8 grid gap-4 rounded-2xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-zinc-950 sm:grid-cols-2 lg:max-w-2xl">
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Sort by</span>
                    <span class="relative block">
                        <select wire:model.live="requestSort" class="h-12 w-full appearance-none rounded-xl border border-emerald-900/10 bg-white px-4 pr-11 text-sm font-semibold text-emerald-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                            <option value="latest" @selected($requestSort === 'latest')>Latest first</option>
                            <option value="oldest" @selected($requestSort === 'oldest')>Oldest first</option>
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-lg leading-none text-emerald-700 dark:text-emerald-300">⌄</span>
                    </span>
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Status</span>
                    <span class="relative block">
                        <select wire:model.live="requestStatus" class="h-12 w-full appearance-none rounded-xl border border-emerald-900/10 bg-white px-4 pr-11 text-sm font-semibold text-emerald-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                            <option value="" @selected($requestStatus === '')>All statuses</option>
                            <option value="Pending" @selected($requestStatus === 'Pending')>Pending</option>
                            <option value="Approved" @selected($requestStatus === 'Approved')>Approved</option>
                            <option value="Ended" @selected($requestStatus === 'Ended')>Event Ended</option>
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-lg leading-none text-emerald-700 dark:text-emerald-300">⌄</span>
                    </span>
                </label>

                <div class="min-h-5 sm:col-span-2">
                    <p wire:loading.flex wire:target="requestSort,requestStatus" class="items-center gap-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                        <span class="size-3 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-700 dark:border-emerald-900 dark:border-t-emerald-300"></span>
                        Updating requests…
                    </p>
                </div>
            </div>

            <div wire:loading.class="opacity-60" wire:target="requestSort,requestStatus" class="mt-12 space-y-5 transition-opacity">
                @forelse ($requests as $request)
                    @php
                        $status = $request->Status;
                        $isApproved = $status === 'Approved';
                        $isRejected = $status === 'Rejected';
                        $isCancelled = $status === 'Cancelled';
                        $isEnded = $status === 'Ended';
                        $needsRevision = $status === 'Pending' && filled($request->Review_Requested_At);
                        $canCancel = in_array($status, ['Pending', 'Approved'], true);
                        $statusClass = match ($status) {
                            'Approved' => 'bg-emerald-600 text-white',
                            'Rejected' => 'bg-rose-600 text-white',
                            'Cancelled' => 'bg-zinc-600 text-white',
                            'Ended' => 'bg-slate-700 text-white',
                            default => 'bg-yellow-400 text-emerald-950',
                        };
                        $statusLabel = $needsRevision ? 'Needs Revision' : ($isEnded ? 'Event Ended' : $status);
                    @endphp

                    <details
                        id="request-{{ $request->RID }}"
                        @if (request()->integer('request') === $request->RID) open @endif
                        class="group scroll-mt-24 overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-950"
                    >
                        <summary class="relative flex cursor-pointer list-none flex-col gap-5 p-6 pr-16 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-wide {{ $statusClass }}">{{ $statusLabel }}</span>
                                    <span class="text-sm font-bold text-emerald-800 dark:text-emerald-300">Request #{{ $request->RID }}</span>
                                </div>
                                <h3 class="mt-3 text-2xl font-black text-emerald-950 dark:text-white">{{ $request->facility?->Facility_Name ?? 'Facility request' }}</h3>
                                <p class="mt-2 text-sm leading-6 text-emerald-900/70 dark:text-zinc-300">
                                    {{ $request->event?->Event_Title ?? $request->Purpose ?? 'Submitted facility request' }}
                                </p>
                            </div>

                            <div class="lg:min-w-[520px]">
                                <p class="mb-3 text-left text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300 lg:text-right">
                                    Requested {{ $request->Created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}
                                </p>
                                <div class="grid gap-2 text-sm font-semibold text-emerald-900/75 dark:text-zinc-300 sm:grid-cols-3 lg:text-right">
                                    <span>{{ $request->Proposed_Date?->format('M j, Y') ?? 'No date' }}@if($request->Proposed_End_Date && ! $request->Proposed_End_Date->isSameDay($request->Proposed_Date)) – {{ $request->Proposed_End_Date->format('M j, Y') }}@endif</span>
                                    <span>{{ $request->Proposed_Start_Time?->format('H:i') ?? '--:--' }} - {{ $request->Proposed_End_Time?->format('H:i') ?? '--:--' }}</span>
                                    <span>{{ $request->Capacity ?? 'N/A' }} attendees</span>
                                </div>
                            </div>
                            <span class="pointer-events-none absolute right-6 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 transition-transform duration-200 group-open:rotate-180 dark:bg-emerald-500/10 dark:text-emerald-300" aria-hidden="true">
                                <x-ui::icon.chevron-down class="size-5" />
                            </span>
                        </summary>

                        <div class="border-t border-emerald-900/10 p-6 dark:border-white/10">
                            <div class="grid gap-4 lg:grid-cols-3">
                                <div class="rounded-xl bg-emerald-50 p-4 dark:bg-zinc-900">
                                    <p class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Progress</p>
                                    <div class="mt-4 flex items-center gap-2 text-sm font-bold">
                                        <span class="rounded-full bg-emerald-600 px-3 py-1 text-white">Submitted</span>
                                        <span class="h-0.5 flex-1 bg-emerald-300"></span>
                                        <span class="rounded-full {{ $isApproved || $isRejected || $isEnded ? 'bg-emerald-600 text-white' : 'bg-yellow-400 text-emerald-950' }} px-3 py-1">Review</span>
                                        <span class="h-0.5 flex-1 {{ $isApproved || $isEnded ? 'bg-emerald-300' : ($isRejected ? 'bg-rose-300' : 'bg-zinc-200') }}"></span>
                                        <span class="rounded-full {{ $isApproved ? 'bg-emerald-600 text-white' : ($isEnded ? 'bg-slate-700 text-white' : ($isRejected ? 'bg-rose-600 text-white' : ($isCancelled ? 'bg-zinc-600 text-white' : 'bg-zinc-200 text-zinc-600'))) }} px-3 py-1">
                                            {{ $isEnded ? 'Event Ended' : ($isRejected ? 'Rejected' : ($isCancelled ? 'Cancelled' : 'Decision')) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900/80 dark:bg-zinc-900 dark:text-zinc-300 lg:col-span-2">
                                    <p><span class="font-black">Location:</span> {{ $request->facility?->Location ?? 'N/A' }}</p>
                                    <p class="mt-2"><span class="font-black">Purpose:</span> {{ $request->Purpose ?? 'N/A' }}</p>
                                    <p class="mt-2"><span class="font-black">Event type:</span> {{ $request->event?->Type_Event ?? 'N/A' }}</p>
                                    @if ($isCancelled && $request->Cancellation_Reason)
                                        <p class="mt-2"><span class="font-black">Cancellation reason:</span> {{ $request->Cancellation_Reason }}</p>
                                    @endif
                                    @if ($isRejected && $request->Rejection_Reason)
                                        <p class="mt-2"><span class="font-black">Reason for rejection:</span> {{ $request->Rejection_Reason }}</p>
                                    @endif
                                    @if ($needsRevision && $request->Review_Notes)
                                        <div class="mt-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-100">
                                            <p class="text-xs font-black uppercase tracking-wide">Information requested by the reviewer</p>
                                            <p class="mt-2 whitespace-pre-wrap font-semibold">{{ $request->Review_Notes }}</p>
                                            <p class="mt-2 text-xs">Update the fields below and save this same request. You do not need to create a new one.</p>
                                        </div>
                                    @endif
                                    @if ($request->facility)
                                        <a
                                            href="{{ route('dashboard', ['map_facility' => $request->Facility_ID]) }}#map"
                                            class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-black text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-600/20"
                                        >
                                            <x-ui::icon.map-pin class="size-4" />
                                            View on Map
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if ($isEnded)
                                <div class="mt-6 flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p>This event has ended. Its request details are now read-only and can no longer be changed.</p>
                                        <p class="mt-1 text-xs font-normal">Sharing feedback is optional.</p>
                                    </div>
                                    @if ($request->feedback)
                                        <span class="inline-flex shrink-0 items-center rounded-xl bg-emerald-100 px-4 py-2.5 text-xs font-black text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">
                                            Feedback submitted
                                        </span>
                                    @elseif ($request->Facility_ID)
                                        <a
                                            href="{{ route('facility-feedback.create', $request) }}"
                                            class="inline-flex shrink-0 items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-black text-white transition hover:bg-emerald-800"
                                        >
                                            Give optional feedback
                                        </a>
                                    @endif
                                </div>
                            @else
                            <form action="{{ route('waiting.list.update', $request) }}" method="POST" enctype="multipart/form-data" class="mt-6 grid gap-4 lg:grid-cols-2">
                                @csrf
                                <input name="Event_Title" value="{{ old('Event_Title', $request->event?->Event_Title) }}" placeholder="Event title" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <select name="Type_Event" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Select event type</option>
                                    @foreach (['Meeting', 'Seminar', 'Workshop', 'Conference', 'Other'] as $type)
                                        <option value="{{ $type }}" {{ old('Type_Event', $request->event?->Type_Event) === $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                <textarea name="Description" rows="3" placeholder="Description" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white lg:col-span-2">{{ old('Description', $request->event?->Description) }}</textarea>
                                <input name="Proposed_Date" type="date" min="{{ now()->addDays(3)->toDateString() }}" value="{{ old('Proposed_Date', $request->Proposed_Date?->toDateString()) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Proposed_End_Date" type="date" min="{{ now()->addDays(3)->toDateString() }}" value="{{ old('Proposed_End_Date', $request->Proposed_End_Date?->toDateString() ?? $request->Proposed_Date?->toDateString()) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Capacity" type="number" min="1" value="{{ old('Capacity', $request->Capacity) }}" placeholder="Expected attendees" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Proposed_Start_Time" type="time" value="{{ old('Proposed_Start_Time', $request->Proposed_Start_Time?->format('H:i')) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Proposed_End_Time" type="time" value="{{ old('Proposed_End_Time', $request->Proposed_End_Time?->format('H:i')) }}" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <input name="Purpose" value="{{ old('Purpose', $request->Purpose) }}" placeholder="Purpose" class="rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                <div class="lg:col-span-2">
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300" for="attachment-{{ $request->RID }}">
                                        Request letter
                                    </label>
                                    <input
                                        id="attachment-{{ $request->RID }}"
                                        name="attachment"
                                        type="file"
                                        accept=".pdf,application/pdf"
                                        class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-3 file:py-2 file:font-semibold file:text-emerald-800 dark:border-white/10 dark:bg-zinc-900 dark:text-white dark:file:bg-emerald-500/20 dark:file:text-emerald-200"
                                    >
                                    <p class="mt-1 text-xs text-emerald-900/60 dark:text-zinc-400">
                                        {{ $request->attachment_path ? 'Upload a PDF only if you need to replace the current request letter.' : 'PDF — max 5 MB.' }}
                                    </p>
                                    @if ($request->attachment_path)
                                        <a
                                            href="{{ route('requests.attachment.download', $request) }}"
                                            class="mt-2 inline-flex items-center text-xs font-black text-emerald-700 underline underline-offset-2 hover:text-emerald-900 dark:text-emerald-300"
                                        >
                                            Download current request letter
                                        </a>
                                    @endif
                                    @error('attachment') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                </div>
                                @if ($canCancel)
                                    <div class="lg:col-span-2">
                                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300" for="Cancellation_Reason_{{ $request->RID }}">
                                            Reason for cancellation
                                        </label>
                                        <textarea
                                            id="Cancellation_Reason_{{ $request->RID }}"
                                            name="Cancellation_Reason"
                                            form="cancel-request-{{ $request->RID }}"
                                            rows="3"
                                            required
                                            minlength="5"
                                            maxlength="1000"
                                            placeholder="Please explain why you are cancelling this {{ strtolower($request->Status) }} request."
                                            class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 dark:border-rose-500/30 dark:bg-zinc-900 dark:text-white"
                                        >{{ old('Cancellation_Reason') }}</textarea>
                                        @error('Cancellation_Reason')
                                            <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                                <div class="flex flex-wrap justify-end gap-3 lg:col-span-2">
                                    @if ($canCancel)
                                        <button
                                            type="submit"
                                            form="cancel-request-{{ $request->RID }}"
                                            class="rounded-xl border border-rose-200 bg-white px-5 py-3 text-sm font-black text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/30 dark:bg-zinc-950 dark:text-rose-300 dark:hover:bg-rose-500/10"
                                            data-ui-confirm="Cancel this facility request? The request will be moved to your archived records."
                                            data-ui-confirm-title="Confirm cancellation"
                                            data-ui-confirm-label="Cancel request"
                                            data-ui-confirm-variant="danger"
                                        >
                                            Cancel request
                                        </button>
                                    @endif

                                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-800">
                                        Save changes
                                    </button>
                                </div>
                            </form>
                            @endif

                            @if ($canCancel)
                                <form id="cancel-request-{{ $request->RID }}" action="{{ route('waiting.list.cancel', $request) }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                            @endif
                        </div>
                    </details>
                @empty
                    <div class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-10 text-center text-emerald-900 dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-300">
                        {{ $totalUserRequests > 0 ? 'No requests match the selected filters.' : 'You do not have any submitted requests yet.' }}
                    </div>
                @endforelse

                @if ($requests->hasPages())
                    <div class="pt-3">
                        {{ $requests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
