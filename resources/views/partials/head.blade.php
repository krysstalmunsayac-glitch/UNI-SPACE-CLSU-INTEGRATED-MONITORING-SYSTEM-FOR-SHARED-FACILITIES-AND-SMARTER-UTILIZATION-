<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? 'Uni Space' }}</title>

<link rel="icon" type="image/png" href="{{ asset('images/Logo_Green.png') }}" />

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    try {
        if (!window.localStorage.getItem('flux.appearance')) {
            window.localStorage.setItem('flux.appearance', 'light');
        }
    } catch (error) {
        // Light remains the document default when storage is unavailable.
    }
</script>

<style>
    /* Header */
    [data-flux-header] {
        background-color: #009639 !important;
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif;
    }

    /* Navbar Items */
    [data-flux-navbar-item] {
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif !important;
        font-weight: 800 !important;
        font-size: 15px !important;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    [data-flux-navbar-item]:hover {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.08);
    }

    [data-flux-navbar-item][aria-current="page"],
    [data-flux-navbar-item][data-current],
    [data-flux-navbar-item].current {
        color: #007a2f !important;
        background: #facc15 !important;
        box-shadow: inset 0 -3px 0 #007a2f;
    }

    /* Mobile Sidebar */
    [data-flux-sidebar] {
        background-color: #009639 !important;
        border-right-color: rgba(255, 255, 255, 0.12) !important;
    }

    /* Sidebar Items */
    [data-flux-sidebar] [data-flux-sidebar-item],
    [data-flux-sidebar] [data-flux-navlist-item] {
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif !important;
        font-weight: 800 !important;
        font-size: 16px !important;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.9) !important;
        min-height: 44px;
    }

    [data-flux-sidebar] [data-flux-navlist-group] > div:first-child,
    [data-flux-sidebar] [data-flux-navlist-group] > div:first-child *,
    [data-flux-sidebar] [data-flux-navlist-group] button,
    [data-flux-sidebar] [data-flux-navlist-group] button * {
        color: #ffffff !important;
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif !important;
        font-weight: 800 !important;
        font-size: 13px !important;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    [data-flux-sidebar] [data-flux-sidebar-item]:hover,
    [data-flux-sidebar] [data-flux-navlist-item]:hover {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.12) !important;
    }

    [data-flux-sidebar] [data-flux-sidebar-item][aria-current="page"],
    [data-flux-sidebar] [data-flux-sidebar-item][data-current],
    [data-flux-sidebar] [data-flux-sidebar-item].current,
    [data-flux-sidebar] [data-flux-navlist-item][aria-current="page"],
    [data-flux-sidebar] [data-flux-navlist-item][data-current],
    [data-flux-sidebar] [data-flux-navlist-item].current {
        color: #007a2f !important;
        background: #facc15 !important;
        border-color: #facc15 !important;
        box-shadow: 0 10px 20px rgba(20, 83, 45, 0.18);
    }

    /* Force all text inside Flux navigation to use the heavier weight */
    [data-flux-navbar-item] *,
    [data-flux-sidebar] [data-flux-sidebar-item] *,
    [data-flux-sidebar] [data-flux-navlist-item] * {
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif !important;
        font-weight: 800 !important;
    }

    [data-flux-sidebar] [data-flux-sidebar-item][aria-current="page"] *,
    [data-flux-sidebar] [data-flux-sidebar-item][data-current] *,
    [data-flux-sidebar] [data-flux-sidebar-item].current *,
    [data-flux-sidebar] [data-flux-navlist-item][aria-current="page"] *,
    [data-flux-sidebar] [data-flux-navlist-item][data-current] *,
    [data-flux-sidebar] [data-flux-navlist-item].current *,
    [data-flux-navbar-item][aria-current="page"] *,
    [data-flux-navbar-item][data-current] *,
    [data-flux-navbar-item].current * {
        color: #007a2f !important;
    }

    #campus-map.leaflet-container,
    #user-campus-map.leaflet-container {
        z-index: 0;
    }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')
@fluxAppearance
