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
                    'text' => 'You agree to provide accurate personal and contact information when registering. You are responsible for keeping your account secure and for all activity conducted through your account.',
                ],
                [
                    'title' => 'Facility Requests',
                    'text' => 'Reservation details, such as the purpose, date, time, capacity, and event information, must be complete and accurate. Submitting a request does not guarantee approval. A request is approved only when authorized by an administrator.',
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

            <article id="privacy-notice" class="scroll-mt-24 rounded-xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-950">
                <h2 class="text-xl font-black text-emerald-950 dark:text-white">Privacy Notice</h2>
                <p class="mt-1 text-sm font-semibold text-emerald-800 dark:text-emerald-300">Version 2026-09-11 · Effective September 11, 2026</p>
                <div class="mt-3 space-y-3 leading-7 text-emerald-900/70 dark:text-zinc-300">
                    <p>
                        This Privacy Notice explains how we collect, use, store, and protect your personal information in accordance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> and its Implementing Rules and Regulations.
                    </p>
                    <p>
                        When you register, we collect your full name, email address, CLSU ID when applicable, contact number, address, account credentials, account classification, and consent record. When you use the service, we also process facility-request and reservation information. This data is used to create and secure accounts, verify identity, process requests, communicate decisions, manage schedules, prevent misuse, and maintain operational and audit records.
                    </p>
                    <p>
                        All collected information is accessible only to authorized CLSU personnel from the Strategic Communication Office and is used solely for official purposes. We do not share or disclose your information to external parties unless required by law, necessary for university operations, or authorized by your explicit consent. All personal information is stored securely on CLSU-authorized servers and retained only for as long as necessary to fulfill the purposes stated in this notice. When the information is no longer needed, the records are securely deleted or anonymized.
                    </p>
                    <p>
                        CLSU implements strict organizational, physical, and technical measures to protect your personal information against unauthorized access, loss, or misuse. These include password-protected systems, encrypted data storage, and restricted access to authorized personnel.
                    </p>
                    <p>
                        As a data subject, you have rights under the <strong>Data Privacy Act</strong>, including the rights to be informed; to access, correct, or request the deletion of your personal information; and to object to data processing under certain conditions. You also have the right to file a complaint with the <strong>CLSU Data Privacy Office or the National Privacy Commission (NPC)</strong> if you believe your data privacy rights have been violated.
                    </p>
                    <p>
                        For questions, concerns, or requests regarding your personal information, you may contact the <strong>Data Protection Officer (DPO)</strong> at Central Luzon State University, Science City of Muñoz, Nueva Ecija, or by email at <a href="mailto:dpo@clsu.edu.ph" class="rounded font-semibold underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">dpo@clsu.edu.ph</a>. You may also visit the National Privacy Commission website at <a href="https://privacy.gov.ph" target="_blank" rel="noopener noreferrer" class="rounded font-semibold underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">https://privacy.gov.ph<span class="sr-only"> (opens in a new tab)</span></a> for more information.
                    </p>
                    <p>
                        This Privacy Notice may be updated from time to time to reflect changes in policy, technology, or university procedures. Updates will be posted on this website with the revised effective date.
                    </p>
                </div>
            </article>

            <div class="rounded-xl border border-yellow-300 bg-yellow-50 p-6 text-emerald-950 dark:border-yellow-400/40 dark:bg-yellow-400/10 dark:text-yellow-100">
                <h2 class="text-xl font-black">Agreement</h2>
                <p class="mt-3 leading-7">
                    By creating an account, you confirm that you understand and agree to follow these terms while using SIEL SPACE.
                </p>
            </div>
        </div>
    </section>
</x-layouts.home.header>
