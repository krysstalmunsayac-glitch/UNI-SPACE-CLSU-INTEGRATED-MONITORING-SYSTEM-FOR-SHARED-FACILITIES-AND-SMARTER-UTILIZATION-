@php
    $purposeOptions = [
        'Meeting or Conference',
        'Seminar or Workshop',
        'Training Session',
        'Class or Educational Activity',
        'Student Organization Event',
        'Club Meeting',
        'Sports or Recreational Activity',
        'Cultural or Arts Program',
        'Religious Activity',
        'Community Outreach Program',
        'Birthday Celebration',
        'Wedding Reception or Ceremony',
        'Family Gathering or Reunion',
        'Corporate Event',
        'Product Launch or Promotion',
        'Exhibition or Fair',
        'Concert or Performance',
        'Graduation or Recognition Ceremony',
        'Health or Medical Mission',
        'Government or Public Service Activity',
        'Photo or Video Shoot',
        'Other',
    ];

    $questions = [
        'Reservation_Frequency' => [
            'label' => 'How often do you reserve this facility for this purpose?',
            'options' => [
                'First time' => 'First time',
                'Occasionally' => 'Occasionally (1–3 times per year)',
                'Regularly' => 'Regularly (monthly)',
                'Frequently' => 'Frequently (weekly)',
            ],
        ],
        'Facility_Importance' => [
            'label' => 'How important is the facility in helping you achieve your event purpose?',
            'options' => array_combine(
                ['Very Important', 'Important', 'Neutral', 'Slightly Important', 'Not Important'],
                ['Very Important', 'Important', 'Neutral', 'Slightly Important', 'Not Important'],
            ),
        ],
        'Requirements_Fit' => [
            'label' => 'Based on the available information, does this facility meet your requirements?',
            'options' => array_combine(
                ['Yes, completely', 'Mostly', 'Partially', 'No'],
                ['Yes, completely', 'Mostly', 'Partially', 'No'],
            ),
        ],
        'Reserve_Again_Intent' => [
            'label' => 'Would you reserve this facility again for the same purpose?',
            'options' => array_combine(
                ['Definitely Yes', 'Probably Yes', 'Not Sure', 'Probably No', 'Definitely No'],
                ['Definitely Yes', 'Probably Yes', 'Not Sure', 'Probably No', 'Definitely No'],
            ),
        ],
    ];
@endphp

<section class="sm:col-span-2 space-y-5 rounded-xl border border-emerald-900/10 bg-emerald-50/50 p-4 dark:border-white/10 dark:bg-zinc-900/60">
    <div>
        <h3 class="font-bold text-emerald-950 dark:text-white">Purpose of reserving this facility</h3>
        <p class="mt-1 text-xs text-emerald-900/60 dark:text-zinc-400">Select every category that applies, then answer the short planning questions.</p>
    </div>

    <fieldset>
        <legend class="text-sm font-semibold text-emerald-950 dark:text-white">Purpose checklist</legend>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            @foreach ($purposeOptions as $option)
                <x-ui::checkbox
                    name="Purpose_Categories[]"
                    value="{{ $option }}"
                    label="{{ $option }}"
                    :checked="in_array($option, old('Purpose_Categories', []), true)"
                />
            @endforeach
        </div>
        @error('Purpose_Categories') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('Purpose_Categories.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </fieldset>

    <x-ui::input
        name="Other_Purpose"
        label="Other purpose"
        value="{{ old('Other_Purpose') }}"
        placeholder="Complete this only when Other is selected"
        maxlength="150"
    />
    @error('Other_Purpose') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

    @foreach ($questions as $name => $question)
        <fieldset>
            <legend class="text-sm font-semibold text-emerald-950 dark:text-white">{{ $question['label'] }}</legend>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach ($question['options'] as $value => $label)
                    <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-emerald-900/10 bg-white px-3 py-2 text-sm text-emerald-950 transition hover:border-emerald-500 dark:border-white/10 dark:bg-zinc-950 dark:text-white">
                        <input
                            type="radio"
                            name="{{ $name }}"
                            value="{{ $value }}"
                            @checked(old($name) === $value)
                            x-bind:required="step === 2"
                            class="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-600"
                        >
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error($name) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </fieldset>
    @endforeach
</section>
