<x-layouts.home.header>
    <section class="border-b border-emerald-900/10 bg-white py-16 dark:border-white/10 dark:bg-zinc-950">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">SIEL SPACE</p>
            <h1 class="mt-4 text-5xl font-black tracking-tight text-emerald-950 dark:text-white">Terms and Conditions</h1>
            <p class="mt-5 text-lg leading-8 text-emerald-900/70 dark:text-zinc-300">
                Please read these terms before creating an account or submitting a facility reservation request.
            </p>
        </div>
    </section>

    <section class="bg-emerald-50/60 py-16 dark:bg-zinc-900">
        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
            @foreach ([
                [
                    'title' => 'Account Information',
                    'text' => 'You agree to provide accurate personal and contact information when registering. You are responsible for keeping your account secure and for activity submitted through your account.',
                ],
                [
                    'title' => 'Facility Requests',
                    'text' => 'Reservation details such as purpose, date, time, capacity, and event information must be complete and truthful. Submitting a request does not guarantee approval until the request status is approved by an authorized administrator.',
                ],
                [
                    'title' => 'Schedule Changes and Cancellations',
                    'text' => 'Approved schedules may be adjusted, cancelled, or reviewed when facility availability, campus operations, or administrative requirements change. Users should update or cancel requests as early as possible when plans change.',
                ],
                [
                    'title' => 'Appropriate Use',
                    'text' => 'SIEL SPACE must be used only for legitimate campus-related facility reservations. Misuse, false information, duplicate submissions, or attempts to bypass approval workflows may result in request rejection or account review.',
                ],
                [
                    'title' => 'Privacy and Records',
                    'text' => 'Information submitted in SIEL SPACE is used to process reservations, notify users, manage facility schedules, and support administrative reporting. Reservation records may be retained for operational and audit purposes.',
                ],
            ] as $section)
                <article class="rounded-xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-950">
                    <h2 class="text-xl font-black text-emerald-950 dark:text-white">{{ $section['title'] }}</h2>
                    <p class="mt-3 leading-7 text-emerald-900/70 dark:text-zinc-300">{{ $section['text'] }}</p>
                </article>
            @endforeach

            <div class="rounded-xl border border-yellow-300 bg-yellow-50 p-6 text-emerald-950 dark:border-yellow-400/40 dark:bg-yellow-400/10 dark:text-yellow-100">
                <h2 class="text-xl font-black">Agreement</h2>
                <p class="mt-3 leading-7">
                    By creating an account, you confirm that you understand and agree to follow these terms while using SIEL SPACE.
                </p>
            </div>
        </div>
    </section>
</x-layouts.home.header>
