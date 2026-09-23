                                <div x-show="step === 2" x-ref="scheduleDetails" class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-ui::input
                                            label="First event day"
                                            name="Proposed_Date"
                                            type="date"
                                            x-ref="startDate"
                                            x-on:change="syncDailySchedules()"
                                            min="{{ app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user()) }}"
                                            value="{{ old('Proposed_Date', app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user())) }}"
                                            x-bind:required="step === 2"
                                        />
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ app(\App\Services\BookingPolicy::class)->noticeMessage(auth()->user()) }}</p>
                                    </div>

                                    <div>
                                        <x-ui::input
                                            label="Last event day"
                                            name="Proposed_End_Date"
                                            type="date"
                                            x-ref="endDate"
                                            x-on:change="syncDailySchedules()"
                                            x-bind:min="$refs.startDate?.value || '{{ app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user()) }}'"
                                            min="{{ app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user()) }}"
                                            value="{{ old('Proposed_End_Date', old('Proposed_Date', app(\App\Services\BookingPolicy::class)->earliestDate(auth()->user()))) }}"
                                            x-bind:required="step === 2"
                                        />
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Use the same date for a one-day event.</p>
                                    </div>

                                    <div x-ref="eventTimeSection" class="space-y-3 sm:col-span-2">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <h3 class="text-sm font-bold text-emerald-950 dark:text-white">Event time</h3>
                                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400" x-text="dailySchedules.length <= 1 ? 'Choose the start and end time.' : customizeDailyTimes ? 'Set a time for each event day.' : `This time will apply to all ${dailySchedules.length} event days.`"></p>
                                            </div>
                                            <button
                                                x-show="dailySchedules.length > 1"
                                                type="button"
                                                x-on:click="customizeDailyTimes ? useOneTimeForAllDays() : customizeDailyTimes = true"
                                                class="inline-flex h-9 items-center justify-center rounded-lg border border-emerald-600 bg-white px-3 text-xs font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500 dark:bg-zinc-950 dark:text-emerald-300 dark:hover:bg-emerald-950/30"
                                                x-text="customizeDailyTimes ? 'Use one time for all days' : 'Customize each day'"
                                            ></button>
                                        </div>

                                        <div x-cloak x-show="scheduleValidationError" x-transition role="alert" class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-500/40 dark:bg-red-950/30 dark:text-red-200">
                                            <span x-text="scheduleValidationError"></span>
                                        </div>

                                        <div x-show="!customizeDailyTimes" class="grid gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20 sm:grid-cols-[1.2fr_1fr_1fr] sm:items-end">
                                            <div>
                                                <span class="block text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300" x-text="dailySchedules.length === 1 ? 'Booking day' : 'Booking period'"></span>
                                                <span class="mt-2 block font-semibold text-emerald-950 dark:text-white" x-text="dailySchedules.length === 1 ? new Date(`${dailySchedules[0]?.date}T12:00:00`).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }) : `${dailySchedules.length} consecutive days`"></span>
                                            </div>
                                            <div class="block">
                                                <span class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300">Start time</span>
                                                <x-ui::time-dropdown selection="sharedStartTime" label="Start time"><select aria-label="Start time" x-model="sharedStartTime" x-on:change="chooseSharedStart($event.target.value)" x-bind:required="!customizeDailyTimes" class="h-11 w-full rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                                                    <option value="">Select time</option>
                                                    <template x-for="slot in slots" :key="slot"><option style="height: 2rem; padding: 0.375rem 0.75rem;" :value="slot" :disabled="sharedStartDisabled(slot)" x-text="`${formatTime(slot)}${slotLabel(dailySchedules.map(schedule => slotStatus(schedule.date, slot, addMinutes(slot, 60))).find(status => ['approved', 'past', 'unavailable'].includes(status)) ?? 'available')}`"></option></template>
                                                </select></x-ui::time-dropdown>
                                            </div>
                                            <div class="block">
                                                <span class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300">End time <span class="font-normal text-zinc-500">(1 hour minimum)</span></span>
                                                <x-ui::time-dropdown selection="sharedEndTime" label="End time"><select aria-label="End time" x-model="sharedEndTime" x-on:change="applySharedTime()" x-bind:required="!customizeDailyTimes" class="h-11 w-full rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                                                    <option value="">Select time</option>
                                                    <template x-for="slot in endSlots.filter(slot => slot >= minimumEndTime(sharedStartTime))" :key="slot"><option style="height: 2rem; padding: 0.375rem 0.75rem;" :value="slot" :disabled="sharedEndDisabled(slot)" x-text="`${formatTime(slot)}${slotLabel(dailySchedules.map(schedule => slotStatus(schedule.date, sharedStartTime, slot)).find(status => ['approved', 'past', 'unavailable'].includes(status)) ?? 'available')}`"></option></template>
                                                </select></x-ui::time-dropdown>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" aria-live="polite">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-sm font-bold text-emerald-950 dark:text-white">Available booking hours: 5:00 AM–12:00 AM</p>
                                                <p class="text-xs text-zinc-500">30-minute preparation + 30-minute cleanup buffer</p>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs" aria-label="Availability color guide">
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"><i class="inline-block h-3 w-3 shrink-0 rounded-full" style="background:#10b981"></i> Available</span>
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"><i class="inline-block h-3 w-3 shrink-0 rounded-full" style="background:#f59e0b"></i> Pending request</span>
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"><i class="inline-block h-3 w-3 shrink-0 rounded-full" style="background:#ef4444"></i> Already booked</span>
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"><i class="inline-block h-3 w-3 shrink-0 rounded-full" style="background:#a1a1aa"></i> Elapsed / closed</span>
                                            </div>
                                            <p x-show="availabilityLoading" class="mt-3 text-sm text-zinc-500">Checking availability…</p>
                                            <p x-show="availabilityError" x-text="availabilityError" class="mt-3 text-sm font-medium text-red-600"></p>
                                            <div x-show="!availabilityLoading && !availabilityError" class="mt-4 space-y-3">
                                                <template x-for="schedule in dailySchedules" :key="`timeline-${schedule.date}`">
                                                    <div>
                                                    <div class="mb-1 flex justify-between text-xs"><span class="font-semibold" x-text="new Date(`${schedule.date}T12:00:00`).toLocaleDateString(undefined, {weekday:'short', month:'short', day:'numeric'})"></span><span class="capitalize" x-text="scheduleStatus(schedule) === 'incomplete' ? 'Choose a time' : scheduleStatus(schedule) === 'approved' ? 'Already Booked' : scheduleStatus(schedule) === 'pending' ? 'Pending Request' : scheduleStatus(schedule) === 'past' ? 'Time Elapsed' : scheduleStatus(schedule)"></span></div>
                                                        <div class="flex h-5 overflow-hidden rounded-full bg-zinc-200" role="img" :aria-label="`Daily availability for ${schedule.date}`">
                                                            <template x-for="slot in slots" :key="`${schedule.date}-${slot}`"><span class="flex-1 border-r border-white/40" :style="`background:${slotStatus(schedule.date, slot, addMinutes(slot, 30)) === 'available' ? '#10b981' : slotStatus(schedule.date, slot, addMinutes(slot, 30)) === 'pending' ? '#f59e0b' : slotStatus(schedule.date, slot, addMinutes(slot, 30)) === 'approved' ? '#ef4444' : '#a1a1aa'}`"></span></template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        @include('requests.partials.booking-summary')

                                        <template x-for="(schedule, index) in dailySchedules" :key="schedule.date">
                                            <div>
                                                <input type="hidden" x-bind:name="`Daily_Schedules[${index}][date]`" x-bind:value="schedule.date">
                                                <input type="hidden" x-bind:name="!customizeDailyTimes ? `Daily_Schedules[${index}][start]` : null" x-bind:value="schedule.start">
                                                <input type="hidden" x-bind:name="!customizeDailyTimes ? `Daily_Schedules[${index}][end]` : null" x-bind:value="schedule.end">

                                                <div x-show="customizeDailyTimes" class="grid gap-3 rounded-xl border border-emerald-900/10 bg-emerald-50/60 p-4 dark:border-white/10 dark:bg-zinc-900 sm:grid-cols-[1.2fr_1fr_1fr] sm:items-end">
                                                    <div>
                                                        <span class="block text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Booking day</span>
                                                        <span class="mt-2 block font-semibold text-emerald-950 dark:text-white" x-text="new Date(`${schedule.date}T12:00:00`).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })"></span>
                                                    </div>
                                                    <div class="block">
                                                        <span class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300">Start time</span>
                                                        <x-ui::time-dropdown selection="schedule.start" label="Start time"><select aria-label="Start time" x-bind:name="customizeDailyTimes ? `Daily_Schedules[${index}][start]` : null" x-model="schedule.start" x-on:change="chooseDayStart(schedule, $event.target.value)" x-bind:required="customizeDailyTimes" class="h-11 w-full rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm dark:border-white/10 dark:bg-zinc-950 dark:text-white"><option value="">Select time</option><template x-for="slot in slots" :key="slot"><option :value="slot" :disabled="['approved', 'unavailable', 'past'].includes(slotStatus(schedule.date, slot, addMinutes(slot, 60)))" x-text="`${formatTime(slot)}${slotLabel(slotStatus(schedule.date, slot, addMinutes(slot, 60)))}`"></option></template></select></x-ui::time-dropdown>
                                                    </div>
                                                    <div class="block">
                                                        <span class="mb-2 block text-sm font-medium text-emerald-900 dark:text-zinc-300">End time <span class="font-normal text-zinc-500">(1 hour minimum)</span></span>
                                                        <x-ui::time-dropdown selection="schedule.end" label="End time"><select aria-label="End time" x-bind:name="customizeDailyTimes ? `Daily_Schedules[${index}][end]` : null" x-model="schedule.end" x-on:change="loadAvailability()" x-bind:required="customizeDailyTimes" class="h-11 w-full rounded-xl border border-emerald-900/10 bg-white px-3 text-sm text-emerald-950 shadow-sm dark:border-white/10 dark:bg-zinc-950 dark:text-white"><option value="">Select time</option><template x-for="slot in endSlots.filter(slot => slot >= minimumEndTime(schedule.start))" :key="slot"><option :value="slot" :disabled="['approved', 'unavailable', 'past'].includes(slotStatus(schedule.date, schedule.start, slot))" x-text="`${formatTime(slot)}${slotLabel(slotStatus(schedule.date, schedule.start, slot))}`"></option></template></select></x-ui::time-dropdown>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        @error('Daily_Schedules') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        @error('Daily_Schedules.*.date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        @error('Daily_Schedules.*.start') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                        @error('Daily_Schedules.*.end') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                </div>
