<section class="relative hidden min-h-[640px] overflow-hidden bg-emerald-700 px-10 py-10 text-center text-white dark:bg-emerald-900 lg:flex">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(250,204,21,0.22),transparent_28%),linear-gradient(135deg,#14532d,#047857)]"></div>
    <div class="relative z-10 flex min-h-full w-full flex-col items-center justify-between">
        <a href="{{ route('home') }}" wire:navigate class="flex flex-col items-center gap-3" aria-label="UNI Space home">
            <img src="{{ asset('images/Logo_white.png') }}" alt="CLSU" class="h-20 w-20 object-contain">
            <span class="text-lg font-black tracking-tight">UNI Space</span>
        </a>

        <div class="max-w-sm">
            <h2 class="text-3xl font-black tracking-tight">{{ $panelTitle }}</h2>
            <p class="mx-auto mt-5 max-w-xs text-sm font-semibold leading-6 text-emerald-50/85">{{ $panelText }}</p>
        </div>

        <span class="text-xs font-bold text-emerald-50/65">Central Luzon State University</span>
    </div>
</section>
