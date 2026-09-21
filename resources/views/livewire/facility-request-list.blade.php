    <section id="requests" class="scroll-mt-20 border-t border-emerald-900/10 bg-emerald-50/50 py-20 dark:border-white/10 dark:bg-zinc-900">
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
                    @if ($requests->isEmpty())
                        {{ $requestStatus === '' ? '0' : '0 matching' }} of {{ $totalUserRequests }} submitted
                    @else
                        {{ $requests->firstItem() }}&ndash;{{ $requests->lastItem() }} of {{ $requests->total() }}{{ $requestStatus === '' ? ' submitted' : ' matching' }}
                        @if ($requestStatus !== '')
                            ({{ $totalUserRequests }} submitted)
                        @endif
                    @endif
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

                    </span>
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Status</span>
                    <span class="relative block">
                        <select wire:model.live="requestStatus" class="h-12 w-full appearance-none rounded-xl border border-emerald-900/10 bg-white px-4 pr-11 text-sm font-semibold text-emerald-950 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                            <option value="" @selected($requestStatus === '')>All statuses</option>
                            <option value="Pending" @selected($requestStatus === 'Pending')>Pending</option>
                            <option value="Needs Revision" @selected($requestStatus === 'Needs Revision')>Needs Revision</option>
                            <option value="Approved" @selected($requestStatus === 'Approved')>Approved</option>
                            <option value="Awaiting Payment" @selected($requestStatus === 'Awaiting Payment')>Awaiting Payment</option>
                            <option value="Rejected" @selected($requestStatus === 'Rejected')>Rejected</option>
                            <option value="Cancelled" @selected($requestStatus === 'Cancelled')>Cancelled</option>
                            <option value="Ended" @selected($requestStatus === 'Ended')>Event Ended</option>
                        </select>

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
                        $isAwaitingPayment = $status === 'Awaiting Payment';
                        $isRejected = $status === 'Rejected';
                        $isCancelled = $status === 'Cancelled';
                        $isEnded = $status === 'Ended';
                        $needsRevision = $status === 'Pending' && filled($request->Review_Requested_At);
                        $canCancel = in_array($status, ['Pending', 'Approved'], true);
                        $hasOldInput = (int) old('_request_id') === $request->RID;
                        $oldForRequest = fn (string $key, mixed $default = null): mixed => $hasOldInput ? old($key, $default) : $default;
                        $statusClass = match ($status) {
                            'Approved' => 'bg-emerald-600 text-white',
                            'Awaiting Payment' => 'bg-amber-500 text-amber-950',
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

                            <div class="lg:min-w-[520px]" style="padding-right: 3.5rem;">
                                <p class="mb-3 text-left text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300 lg:text-right">
                                    Requested {{ $request->Created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}
                                </p>
                                <div class="grid gap-2 text-sm font-semibold text-emerald-900/75 dark:text-zinc-300 sm:grid-cols-3 lg:text-right">
                                    <span>{{ $request->Proposed_Date?->format('M j, Y') ?? 'No date' }}@if($request->Proposed_End_Date && ! $request->Proposed_End_Date->isSameDay($request->Proposed_Date)) – {{ $request->Proposed_End_Date->format('M j, Y') }}@endif</span>
                                    <span>{{ $request->Proposed_Start_Time?->format('H:i') ?? '--:--' }} - {{ $request->Proposed_End_Time?->format('H:i') ?? '--:--' }}</span>
                                    <span>{{ $request->Capacity ?? 'N/A' }} attendees</span>
                                </div>
                            </div>
                            <span
                                class="pointer-events-none flex items-center justify-center rounded-full bg-emerald-50 text-emerald-700 transition-transform duration-200 group-open:rotate-180 dark:bg-emerald-500/10 dark:text-emerald-300"
                                style="position: absolute; right: 1.5rem; top: 50%; width: 2.25rem; height: 2.25rem; transform: translateY(-50%);"
                                aria-hidden="true"
                            >
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
                                    <p class="mt-2"><span class="font-black">Event classification:</span> {{ $request->event?->Event_Scope ? $request->event->Event_Scope.' event' : 'N/A' }}</p>
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
                                            href="#map"
                                            data-map-facility-link="{{ $request->Facility_ID }}"
                                            class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-black text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-600/20"
                                        >
                                            <x-ui::icon.map-pin class="size-4" />
                                            View on Map
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if ($isEnded || $isRejected || $isApproved || $isCancelled || $isAwaitingPayment)
                                <div class="mt-6 flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p>{{ $isAwaitingPayment ? 'Payment is required before this request can be approved.' : ($isApproved ? 'This request was approved.' : ($isRejected ? 'This request was rejected.' : ($isCancelled ? 'This request was cancelled.' : 'This event has ended.'))) }} Its submitted information is read-only and can no longer be changed.</p>
                                        @if ($isEnded)
                                            <p class="mt-1 text-xs font-normal">Sharing feedback is optional.</p>
                                        @endif
                                    </div>
                                    @if ($isEnded && $request->feedback)
                                        <span class="inline-flex shrink-0 items-center rounded-xl bg-emerald-100 px-4 py-2.5 text-xs font-black text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">
                                            Feedback submitted
                                        </span>
                                    @elseif ($isEnded && $request->Facility_ID)
                                        <a
                                            href="{{ route('requests.feedback.create', $request) }}"
                                            class="inline-flex shrink-0 items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-black text-white transition hover:bg-emerald-800"
                                        >
                                            Give optional feedback
                                        </a>
                                    @endif
                                </div>

                                @if ($isAwaitingPayment)
                                    <div class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                                        <p class="text-xs font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">Action needed</p>
                                        <h4 class="mt-1 text-lg font-black">Complete your payment</h4>
                                        <p class="mt-1 text-sm font-medium">Follow these steps so an administrator can review and approve your request.</p>

                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-xl bg-white/80 p-4 dark:bg-zinc-950/60">
                                                <p class="text-xs font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">Amount to pay</p>
                                                <p class="mt-1 text-2xl font-black">₱{{ number_format((float) $request->Payment_Amount, 2) }}</p>
                                            </div>
                                            <div class="rounded-xl bg-white/80 p-4 dark:bg-zinc-950/60">
                                                <p class="text-xs font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">Pay on or before</p>
                                                <p class="mt-1 text-base font-black">{{ $request->Payment_Deadline?->format('M j, Y · g:i A') ?? 'Contact the administrator' }}</p>
                                            </div>
                                        </div>

                                        <ol class="mt-5 space-y-3 text-sm">
                                            <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-600 font-black text-white">1</span><span><strong class="block">Pay at the Admin Cashier</strong>Bring the amount shown above and pay in cash before the deadline.</span></li>
                                            <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-600 font-black text-white">2</span><span><strong class="block">Keep your official receipt</strong>You will need a clear photo or PDF of it as proof of payment.</span></li>
                                            <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-600 font-black text-white">3</span><span><strong class="block">Upload your receipt below</strong>Your request remains awaiting payment until an administrator verifies the upload.</span></li>
                                        </ol>

                                        <form
                                            action="{{ route('requests.payment-proof.upload', $request) }}"
                                            method="POST"
                                            enctype="multipart/form-data"
                                            class="mt-5 rounded-xl bg-white p-4 dark:bg-zinc-950"
                                            x-data="{ fileName: '' }"
                                        >
                                            @csrf
                                            <input type="hidden" name="_request_id" value="{{ $request->RID }}">
                                            @if ($request->Payment_Proof_Path)
                                                <div class="mb-4 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="flex items-start gap-3">
                                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-lg font-black text-white" aria-hidden="true">✓</span>
                                                        <div>
                                                            <p class="font-black">Payment proof uploaded</p>
                                                            <p class="mt-0.5 text-xs">Received {{ $request->Payment_Proof_Uploaded_At?->diffForHumans() }} and ready for admin review.</p>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('requests.payment-proof.download', $request) }}" class="inline-flex shrink-0 items-center justify-center rounded-lg border border-emerald-700 px-3 py-2 text-xs font-black text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-300 dark:text-emerald-100 dark:hover:bg-emerald-500/20">
                                                        View uploaded proof
                                                    </a>
                                                </div>
                                            @endif

                                            <label for="payment-proof-{{ $request->RID }}" class="block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">
                                                {{ $request->Payment_Proof_Path ? 'Choose a replacement receipt' : 'Upload your official receipt' }}
                                            </label>
                                            <input
                                                id="payment-proof-{{ $request->RID }}"
                                                name="payment_proof"
                                                type="file"
                                                required
                                                accept=".pdf,.jpg,.jpeg,.png"
                                                x-on:change="fileName = $event.target.files[0]?.name || ''"
                                                class="mt-2 block w-full rounded-xl border border-slate-200 p-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-700 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-emerald-800 dark:border-slate-700 dark:bg-zinc-900 dark:text-slate-300"
                                            >
                                            <p class="mt-2 text-xs text-slate-500" x-text="fileName ? `Selected: ${fileName}` : 'Accepted files: PDF, JPG, or PNG (maximum 5 MB).' "></p>
                                            <button
                                                type="submit"
                                                x-bind:disabled="!fileName"
                                                class="mt-4 w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600 dark:disabled:bg-slate-700 dark:disabled:text-slate-300"
                                            >
                                                <span x-show="!fileName">Choose a file to continue</span>
                                                <span x-show="fileName">{{ $request->Payment_Proof_Path ? 'Replace uploaded proof' : 'Upload proof of payment' }}</span>
                                            </button>
                                            @if ($hasOldInput)
                                                @error('payment_proof') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                            @endif
                                        </form>
                                    </div>
                                @endif

                                <dl class="mt-6 grid gap-4 rounded-xl border border-emerald-900/10 bg-white p-5 text-sm dark:border-white/10 dark:bg-zinc-900 sm:grid-cols-2">
                                    @foreach ([
                                        'Event title' => $request->event?->Event_Title ?? 'N/A',
                                        'Event type' => $request->event?->Type_Event ?? 'N/A',
                                        'Event classification' => $request->event?->Event_Scope ? $request->event->Event_Scope.' event' : 'N/A',
                                        'First event day' => $request->Proposed_Date?->format('M j, Y') ?? 'N/A',
                                        'Last event day' => $request->Proposed_End_Date?->format('M j, Y') ?? $request->Proposed_Date?->format('M j, Y') ?? 'N/A',
                                        'Start time' => $request->Proposed_Start_Time?->format('g:i A') ?? 'N/A',
                                        'End time' => $request->Proposed_End_Time?->format('g:i A') ?? 'N/A',
                                        'Expected attendees' => $request->Capacity ?? 'N/A',
                                        'Purpose' => $request->Purpose ?? 'N/A',
                                    ] as $label => $value)
                                        <div>
                                            <dt class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ $label }}</dt>
                                            <dd class="mt-1 whitespace-pre-wrap font-semibold text-emerald-950 dark:text-white">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Description</dt>
                                        <dd class="mt-1 whitespace-pre-wrap font-semibold text-emerald-950 dark:text-white">{{ $request->event?->Description ?? 'N/A' }}</dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Request letter</dt>
                                        <dd class="mt-2">
                                            @if ($request->attachment_path)
                                                <a href="{{ route('requests.attachment.download', $request) }}" class="inline-flex rounded-lg bg-emerald-700 px-3 py-2 text-xs font-black text-white hover:bg-emerald-800">Download submitted PDF</a>
                                            @else
                                                <span class="font-semibold text-zinc-500 dark:text-zinc-400">No request letter uploaded.</span>
                                            @endif
                                        </dd>
                                    </div>
                                </dl>

                                @if ($isApproved)
                                    @php
                                        $eventStart = \Carbon\Carbon::parse(
                                            $request->Proposed_Date->toDateString().' '.$request->Proposed_Start_Time->format('H:i:s')
                                        );
                                        $canEndEvent = $eventStart->isPast();
                                    @endphp
                                    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 dark:border-slate-700 dark:bg-slate-900/70">
                                        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Manage booking</p>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Choose the action that best matches your event.</p>
                                        </div>

                                        <div class="grid gap-4 p-4 lg:grid-cols-2">
                                            <div class="flex flex-col rounded-xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-500/30 dark:bg-zinc-950">
                                                <div class="flex items-start gap-3">
                                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                                                    </span>
                                                    <div>
                                                        <h4 class="font-black text-emerald-950 dark:text-white">Event finished successfully</h4>
                                                        <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">Close the booking and continue to optional facility feedback.</p>
                                                    </div>
                                                </div>

                                                <div class="mt-auto pt-5">
                                                    @unless ($canEndEvent)
                                                        <p class="mb-3 flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                            Available {{ $eventStart->format('M j, Y \a\t g:i A') }}
                                                        </p>
                                                    @endunless
                                                    <form action="{{ route('requests.waiting.end', $request) }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="_request_id" value="{{ $request->RID }}">
                                                        <button
                                                            type="submit"
                                                            @disabled(! $canEndEvent)
                                                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-600/20 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500 disabled:shadow-none dark:disabled:bg-slate-800 dark:disabled:text-slate-400"
                                                            data-ui-confirm="Are you sure you want to end this event? The booking will become read-only and this action cannot be undone."
                                                            data-ui-confirm-title="End this event?"
                                                            data-ui-confirm-label="Yes, end event"
                                                            data-ui-confirm-variant="danger"
                                                            onclick="this.form?.addEventListener('submit', () => { this.disabled = true; }, { once: true })"
                                                        >
                                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                                                            {{ $canEndEvent ? 'End event' : 'Not available yet' }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <form action="{{ route('requests.waiting.cancel', $request) }}" method="POST" class="flex flex-col rounded-xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-500/30 dark:bg-zinc-950" x-data="{ cancellationReason: @js($hasOldInput ? old('Cancellation_Reason', '') : '') }">
                                                @csrf
                                                <input type="hidden" name="_request_id" value="{{ $request->RID }}">
                                                <div class="flex items-start gap-3">
                                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                    </span>
                                                    <div>
                                                        <h4 class="font-black text-rose-950 dark:text-white">Event will not continue</h4>
                                                        <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">Cancel the booking and release the reserved schedule.</p>
                                                    </div>
                                                </div>

                                                <div class="mt-auto pt-5">
                                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300" for="approved-cancellation-reason-{{ $request->RID }}">
                                                Cancellation reason
                                            </label>
                                            <select
                                                id="approved-cancellation-reason-{{ $request->RID }}"
                                                name="Cancellation_Reason"
                                                required
                                                x-model="cancellationReason"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-emerald-950 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 dark:border-slate-700 dark:bg-zinc-900 dark:text-white"
                                            >
                                                <option value="">Select a cancellation reason</option>
                                                @foreach (['Change of plans', 'Schedule conflict', 'Event postponed', 'Event cancelled', 'Facility no longer needed', 'Other'] as $reason)
                                                    <option value="{{ $reason }}">{{ $reason }}</option>
                                                @endforeach
                                            </select>
                                            @if ($hasOldInput)
                                                @error('Cancellation_Reason')
                                                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                                @enderror
                                            @endif

                                            <div x-cloak x-show="cancellationReason === 'Other'" x-transition class="mt-3">
                                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300" for="approved-other-cancellation-reason-{{ $request->RID }}">Tell us the reason</label>
                                                <textarea
                                                    id="approved-other-cancellation-reason-{{ $request->RID }}"
                                                    name="Other_Cancellation_Reason"
                                                    rows="3"
                                                    minlength="5"
                                                    maxlength="1000"
                                                    x-bind:required="cancellationReason === 'Other'"
                                                    placeholder="Briefly explain why the event will not continue."
                                                    class="w-full resize-y rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-emerald-950 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 dark:border-slate-700 dark:bg-zinc-900 dark:text-white"
                                                >{{ $hasOldInput ? old('Other_Cancellation_Reason') : '' }}</textarea>
                                                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Use at least 5 characters.</p>
                                                @if ($hasOldInput)
                                                    @error('Other_Cancellation_Reason')
                                                        <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                                    @enderror
                                                @endif
                                            </div>
                                            <button
                                                type="submit"
                                                class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-5 py-3 text-sm font-black text-rose-700 transition hover:border-rose-600 hover:bg-rose-600 hover:text-white focus:outline-none focus:ring-4 focus:ring-rose-500/15 disabled:opacity-60 dark:border-rose-500/40 dark:bg-transparent dark:text-rose-300 dark:hover:bg-rose-600 dark:hover:text-white"
                                                data-ui-confirm="Cancel this approved facility request? Its reserved schedule will be released."
                                                data-ui-confirm-title="Cancel approved request"
                                                data-ui-confirm-label="Cancel request"
                                                data-ui-confirm-variant="danger"
                                                onclick="this.form?.addEventListener('submit', () => { this.disabled = true; }, { once: true })"
                                            >
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                Cancel booking
                                            </button>
                                        </div>
                                    </form>
                                        </div>
                                    </section>
                                @endif
                            @else
                            <form action="{{ route('requests.waiting.update', $request) }}" method="POST" enctype="multipart/form-data" class="mt-6 grid gap-4 lg:grid-cols-2">
                                @csrf
                                <input type="hidden" name="_request_id" value="{{ $request->RID }}">
                                <div>
                                    <label for="event-title-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Event title</label>
                                    <input id="event-title-{{ $request->RID }}" name="Event_Title" value="{{ $oldForRequest('Event_Title', $request->event?->Event_Title) }}" placeholder="Enter event title" class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                </div>
                                <div>
                                    <label for="event-type-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Event type</label>
                                    <select id="event-type-{{ $request->RID }}" name="Type_Event" class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                        <option value="">Select event type</option>
                                        @foreach (['Meeting', 'Seminar', 'Workshop', 'Conference', 'Other'] as $type)
                                            <option value="{{ $type }}" {{ $oldForRequest('Type_Event', $request->event?->Type_Event) === $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lg:col-span-2">
                                    <label for="description-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Description</label>
                                    <textarea id="description-{{ $request->RID }}" name="Description" rows="3" placeholder="Describe the event" class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">{{ $oldForRequest('Description', $request->event?->Description) }}</textarea>
                                </div>
                                <div>
                                    <label for="start-date-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">First event day</label>
                                    <input id="start-date-{{ $request->RID }}" name="Proposed_Date" type="date" min="{{ app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user()) }}" value="{{ $oldForRequest('Proposed_Date', $request->Proposed_Date?->toDateString()) }}" class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                </div>
                                <div>
                                    <label for="end-date-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Last event day</label>
                                    <input id="end-date-{{ $request->RID }}" name="Proposed_End_Date" type="date" min="{{ app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user()) }}" value="{{ $oldForRequest('Proposed_End_Date', $request->Proposed_End_Date?->toDateString() ?? $request->Proposed_Date?->toDateString()) }}" class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                </div>
                                <div>
                                    <label for="capacity-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Expected attendees</label>
                                    <input id="capacity-{{ $request->RID }}" name="Capacity" type="number" min="1" value="{{ $oldForRequest('Capacity', $request->Capacity) }}" placeholder="Enter number of attendees" class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                </div>
                                <div>
                                    <label id="start-time-{{ $request->RID }}-label" for="start-time-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Start time</label>
                                    <x-ui::time-select id="start-time-{{ $request->RID }}" aria-labelledby="start-time-{{ $request->RID }}-label" name="Proposed_Start_Time" :value="$oldForRequest('Proposed_Start_Time', $request->Proposed_Start_Time?->format('H:i'))" :options="app(\App\Services\FacilityAvailabilityService::class)->slots()" />
                                </div>
                                <div>
                                    <label id="end-time-{{ $request->RID }}-label" for="end-time-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">End time</label>
                                    <x-ui::time-select id="end-time-{{ $request->RID }}" aria-labelledby="end-time-{{ $request->RID }}-label" name="Proposed_End_Time" :value="$oldForRequest('Proposed_End_Time', data_get($request->Daily_Schedules, '0.end', $request->Proposed_End_Time?->format('H:i')))" :options="app(\App\Services\FacilityAvailabilityService::class)->endSlots()" />
                                </div>
                                <div>
                                    <label for="purpose-{{ $request->RID }}" class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Purpose</label>
                                    <input id="purpose-{{ $request->RID }}" name="Purpose" value="{{ $oldForRequest('Purpose', $request->Purpose) }}" placeholder="Enter reservation purpose" class="w-full rounded-xl border border-emerald-900/10 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900 dark:text-white">
                                </div>
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
                                    @if ($hasOldInput)
                                        @error('attachment') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                    @endif
                                </div>
                                @if ($canCancel)
                                    <div class="lg:col-span-2" x-data="{ cancellationReason: @js($oldForRequest('Cancellation_Reason', '')) }">
                                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300" for="Cancellation_Reason_{{ $request->RID }}">
                                            Reason for cancellation
                                        </label>
                                        <select
                                            id="Cancellation_Reason_{{ $request->RID }}"
                                            name="Cancellation_Reason"
                                            form="cancel-request-{{ $request->RID }}"
                                            required
                                            x-model="cancellationReason"
                                            class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 dark:border-rose-500/30 dark:bg-zinc-900 dark:text-white"
                                        >
                                            <option value="">Select a cancellation reason</option>
                                            @foreach (['Change of plans', 'Schedule conflict', 'Event postponed', 'Event cancelled', 'Facility no longer needed', 'Other'] as $reason)
                                                <option value="{{ $reason }}">{{ $reason }}</option>
                                            @endforeach
                                        </select>
                                        @if ($hasOldInput)
                                            @error('Cancellation_Reason')
                                                <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                            @enderror
                                        @endif

                                        <div x-cloak x-show="cancellationReason === 'Other'" class="mt-3">
                                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300" for="Other_Cancellation_Reason_{{ $request->RID }}">Specific reason</label>
                                            <textarea
                                                id="Other_Cancellation_Reason_{{ $request->RID }}"
                                                name="Other_Cancellation_Reason"
                                                form="cancel-request-{{ $request->RID }}"
                                                rows="3"
                                                minlength="5"
                                                maxlength="1000"
                                                x-bind:required="cancellationReason === 'Other'"
                                                placeholder="Please provide a specific reason for cancelling this request."
                                                class="w-full rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm text-emerald-950 outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 dark:border-rose-500/30 dark:bg-zinc-900 dark:text-white"
                                            >{{ $oldForRequest('Other_Cancellation_Reason', '') }}</textarea>
                                            @if ($hasOldInput)
                                                @error('Other_Cancellation_Reason')
                                                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                                @enderror
                                            @endif
                                        </div>
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
                                            onclick="this.form?.addEventListener('submit', () => { this.disabled = true; }, { once: true })"
                                        >
                                            Cancel request
                                        </button>
                                    @endif

                                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-800 disabled:opacity-60" data-ui-confirm="Save these changes to this facility request?" data-ui-confirm-title="Confirm request update" data-ui-confirm-label="Save changes">
                                        Save changes
                                    </button>
                                </div>
                            </form>
                            @endif

                            @if ($canCancel && ! $isApproved)
                                <form id="cancel-request-{{ $request->RID }}" action="{{ route('requests.waiting.cancel', $request) }}" method="POST" class="hidden">
                                    @csrf
                                    <input type="hidden" name="_request_id" value="{{ $request->RID }}">
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
                        {{ $requests->links(data: ['scrollTo' => '#requests']) }}
                    </div>
                @endif
            </div>
        </div>
    </section>
