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
@endphp

<section class="sm:col-span-2 space-y-5 rounded-xl border border-emerald-900/10 bg-emerald-50/50 p-4 dark:border-white/10 dark:bg-zinc-900/60">
    <div>
        <h3 class="font-bold text-emerald-950 dark:text-white">Purpose of reserving this facility</h3>
        <p class="mt-1 text-xs text-emerald-900/60 dark:text-zinc-400">Select every category that applies.</p>
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
</section>
