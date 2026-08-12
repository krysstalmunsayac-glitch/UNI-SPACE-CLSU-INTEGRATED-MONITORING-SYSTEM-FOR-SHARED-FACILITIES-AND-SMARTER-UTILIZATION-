<div class="settings-workspace">
    <div
        data-settings-navigation
        wire:ignore
        data-active="{{ request()->routeIs('settings.password') ? 'password' : 'profile' }}"
        data-profile-url="{{ request()->routeIs('profile.external') ? route('profile.external') : route('settings.profile') }}"
        data-password-url="{{ route('settings.password') }}"
    >
        <nav class="settings-nav-fallback" aria-label="Settings navigation">
            <a href="{{ request()->routeIs('profile.external') ? route('profile.external') : route('settings.profile') }}">Profile</a>
            <a href="{{ route('settings.password') }}">Password</a>
        </nav>
    </div>

    <article class="settings-panel">
        <div class="settings-panel-heading">
            <span class="settings-eyebrow">Account settings</span>
            <x-ui::heading>{{ $heading ?? '' }}</x-ui::heading>
            <x-ui::subheading>{{ $subheading ?? '' }}</x-ui::subheading>
        </div>

        <div class="settings-form-area">
            {{ $slot }}
        </div>
    </article>
</div>
