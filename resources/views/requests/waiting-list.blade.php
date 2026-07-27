<x-layouts.home.header>
    <flux:main class="py-12 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="rounded-2xl bg-white/90 p-6 shadow-lg shadow-emerald-950/5 ring-1 ring-emerald-900/10 backdrop-blur dark:bg-zinc-950/90 dark:ring-white/5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.24em] text-yellow-600 dark:text-yellow-300">Waiting list</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight text-emerald-950 dark:text-white">Your submitted requests</h1>
                        <p class="mt-2 text-sm leading-6 text-emerald-900/70 dark:text-zinc-300">Track your requests, update details, and monitor the progress of the linked event.</p>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/20 dark:text-amber-200">
                    {{ session('warning') }}
                </div>
            @endif

            @forelse ($requests as $request)
                @php
                    $status = $request->Status;
                    $isCancelled = $status === 'Cancelled';
                    $isRejected = $status === 'Rejected';
                    $isApproved = $status === 'Approved';
                    $isPending = $status === 'Pending';

                    // Step 1 "Submitted" is always complete once a request exists.
                    // Step 2 "Under review" is complete once a decision has been made.
                    $step2Done = $isApproved || $isRejected;
                    $step2Current = $isPending;

                    // Step 3 is the decision itself.
                    $step3Done = $isApproved;
                    $step3Failed = $isRejected;
                    $step3Label = $isRejected ? 'Rejected' : 'Decision';

                    $lineToStep3 = $step2Done ? ($step3Failed ? 'bg-rose-300' : 'bg-emerald-400') : 'bg-zinc-200 dark:bg-zinc-700';
                @endphp

                <div class="space-y-4">
                    {{-- Status tracker card --}}
                    <div class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-950">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-black uppercase tracking-[0.2em] text-yellow-600 dark:text-yellow-300">Tracking</p>
                                <h3 class="mt-1 text-base font-semibold text-emerald-950 dark:text-white">Request #{{ $request->RID }} progress</h3>
                            </div>
                            @if ($isCancelled)
                                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Cancelled</span>
                            @endif
                        </div>

                        @if ($isCancelled)
                            <div class="mt-4 flex items-center gap-3 rounded-2xl border border-emerald-900/10 bg-emerald-50 px-4 py-3 dark:border-white/10 dark:bg-zinc-900">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-400 text-white dark:bg-zinc-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-950 dark:text-zinc-100">This request was cancelled</p>
                                    <p class="text-xs text-emerald-900/70 dark:text-zinc-400">It will not move forward for review.</p>
                                </div>
                            </div>
                        @else
                            <div class="mt-6 flex items-start">
                                {{-- Step 1: Submitted (always complete) --}}
                                <div class="flex w-1/3 flex-col items-center text-center">
                                    <div class="flex w-full items-center">
                                        <span class="h-0.5 flex-1"></span>
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        </span>
                                        <span class="h-0.5 flex-1 bg-emerald-400"></span>
                                    </div>
                                    <p class="mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">Submitted</p>
                                </div>

                                {{-- Step 2: Under review --}}
                                <div class="flex w-1/3 flex-col items-center text-center">
                                    <div class="flex w-full items-center">
                                        <span class="h-0.5 flex-1 bg-emerald-400"></span>
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold {{ $step2Done ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-emerald-700 bg-white text-emerald-700 dark:bg-zinc-900' }}">
                                            @if ($step2Done)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                            @else
                                                2
                                            @endif
                                        </span>
                                        <span class="h-0.5 flex-1 {{ $lineToStep3 }}"></span>
                                    </div>
                                    <p class="mt-2 text-xs font-medium {{ $step2Current ? 'text-emerald-700' : ($step2Done ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400') }}">Under review</p>
                                </div>

                                {{-- Step 3: Decision --}}
                                <div class="flex w-1/3 flex-col items-center text-center">
                                    <div class="flex w-full items-center">
                                        <span class="h-0.5 flex-1 {{ $lineToStep3 }}"></span>
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold {{ $step3Done ? 'border-emerald-500 bg-emerald-500 text-white' : ($step3Failed ? 'border-rose-500 bg-rose-500 text-white' : 'border-emerald-900/10 bg-white text-zinc-400 dark:border-white/10 dark:bg-zinc-900') }}">
                                            @if ($step3Done)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                            @elseif ($step3Failed)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                                            @else
                                                3
                                            @endif
                                        </span>
                                        <span class="h-0.5 flex-1"></span>
                                    </div>
                                    <p class="mt-2 text-xs font-medium {{ $step3Done ? 'text-emerald-600 dark:text-emerald-400' : ($step3Failed ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-400') }}">{{ $step3Label }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Request details + edit form card --}}
                    <div class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-950">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-sm font-black uppercase tracking-[0.2em] text-yellow-600 dark:text-yellow-300">Request #{{ $request->RID }}</p>
                                <h2 class="mt-2 text-xl font-black text-emerald-950 dark:text-white">{{ $request->facility?->Facility_Name ?? 'Facility request' }}</h2>
                                <p class="mt-2 text-sm text-emerald-900/70 dark:text-zinc-300">Status: <span class="font-semibold">{{ $request->Status }}</span></p>
                            </div>
                            <div class="rounded-2xl border border-emerald-900/10 bg-emerald-50 px-4 py-3 text-sm text-emerald-900/70 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300">
                                <p><span class="font-semibold">Event:</span> {{ $request->event?->Event_Title ?? 'No linked event' }}</p>
                                <p><span class="font-semibold">Date:</span> {{ $request->Proposed_Date?->format('F j, Y') }}</p>
                                <p><span class="font-semibold">Time:</span> {{ $request->Proposed_Start_Time?->format('H:i') }} - {{ $request->Proposed_End_Time?->format('H:i') }}</p>
                                <p><span class="font-semibold">Expected attendees:</span> {{ $request->Capacity ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <form action="{{ route('waiting.list.update', $request) }}" method="POST" class="mt-6 space-y-4">
                            @csrf
                            <div class="grid gap-4 lg:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Event_Title_{{ $request->RID }}">Event title</label>
                                    <input id="Event_Title_{{ $request->RID }}" name="Event_Title" value="{{ old('Event_Title', $request->event?->Event_Title) }}" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900" />
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Type_Event_{{ $request->RID }}">Event type</label>
                                    <select id="Type_Event_{{ $request->RID }}" name="Type_Event" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900">
                                        <option value="">Select a type</option>
                                        @foreach (['Meeting', 'Seminar', 'Workshop', 'Conference', 'Other'] as $type)
                                            <option value="{{ $type }}" {{ old('Type_Event', $request->event?->Type_Event) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Description_{{ $request->RID }}">Description</label>
                                    <textarea id="Description_{{ $request->RID }}" name="Description" rows="4" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900">{{ old('Description', $request->event?->Description) }}</textarea>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Proposed_Date_{{ $request->RID }}">Proposed date</label>
                                    <input id="Proposed_Date_{{ $request->RID }}" name="Proposed_Date" type="date" value="{{ old('Proposed_Date', $request->Proposed_Date?->toDateString()) }}" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900" />
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Proposed_Start_Time_{{ $request->RID }}">Start time</label>
                                    <input id="Proposed_Start_Time_{{ $request->RID }}" name="Proposed_Start_Time" type="time" value="{{ old('Proposed_Start_Time', $request->Proposed_Start_Time?->format('H:i')) }}" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900" />
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Proposed_End_Time_{{ $request->RID }}">End time</label>
                                    <input id="Proposed_End_Time_{{ $request->RID }}" name="Proposed_End_Time" type="time" value="{{ old('Proposed_End_Time', $request->Proposed_End_Time?->format('H:i')) }}" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900" />
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Purpose_{{ $request->RID }}">Purpose</label>
                                    <input id="Purpose_{{ $request->RID }}" name="Purpose" value="{{ old('Purpose', $request->Purpose) }}" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900" />
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Capacity_{{ $request->RID }}">Expected attendees</label>
                                    <input id="Capacity_{{ $request->RID }}" name="Capacity" type="number" min="1" value="{{ old('Capacity', $request->Capacity) }}" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900" />
                                    @error('Capacity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300" for="Event_Status_{{ $request->RID }}">Event status</label>
                                    <select id="Event_Status_{{ $request->RID }}" name="Event_Status" class="w-full rounded-xl border border-emerald-900/10 bg-white px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-900">
                                        @foreach (['Upcoming', 'Ongoing', 'Completed', 'Cancelled'] as $eventStatus)
                                            <option value="{{ $eventStatus }}" {{ old('Event_Status', $request->event?->Status) == $eventStatus ? 'selected' : '' }}>{{ $eventStatus }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-8 text-center text-emerald-900 dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-300">
                    You do not have any submitted requests yet.
                </div>
            @endforelse
        </div>
    </flux:main>
</x-layouts.home.header>

