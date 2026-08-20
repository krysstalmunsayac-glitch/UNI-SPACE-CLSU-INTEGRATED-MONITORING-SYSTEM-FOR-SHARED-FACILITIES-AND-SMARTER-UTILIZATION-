<section class="relative hidden min-h-[640px] overflow-hidden bg-emerald-700 px-10 py-10 text-center text-white dark:bg-emerald-900 lg:flex">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,215,0,0.22),transparent_28%),linear-gradient(135deg,#009639,#1e6031)]"></div>
    <div class="relative z-10 flex min-h-full w-full flex-col items-center justify-between">
        <a href="{{ route('home') }}" wire:navigate class="flex flex-col items-center gap-3" aria-label="SIEL SPACE home">
            <img src="{{ asset('images/silesyu-space-logo.png') }}" alt="SIEL SPACE" class="h-20 max-w-xs object-contain">
        </a>

        <div class="max-w-sm">
            <h2 class="text-3xl font-black tracking-tight">{{ $panelTitle }}</h2>
            <p class="mx-auto mt-5 max-w-xs text-sm font-semibold leading-6 text-emerald-50/85">{{ $panelText }}</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <span class="text-sm font-semibold text-emerald-50/90">
                    {{ $panelButton === 'Sign Up' ? "Don't have an account?" : 'Already have an account?' }}
                </span>
                <a
                    href="{{ $panelRoute }}"
                    data-auth-switch="{{ $panelButton === 'Sign Up' ? 'register' : 'login' }}"
                    class="inline-flex min-w-36 items-center justify-center rounded-full border-2 border-white/85 px-8 py-3 text-xs font-black uppercase tracking-widest text-white transition duration-300 hover:scale-105 hover:bg-white hover:text-emerald-800 focus:outline-none focus:ring-4 focus:ring-white/30"
                >
                    {{ $panelButton }}
                </a>
            </div>
        </div>

        <span class="text-xs font-bold text-emerald-50/65">Central Luzon State University</span>
    </div>
</section>
