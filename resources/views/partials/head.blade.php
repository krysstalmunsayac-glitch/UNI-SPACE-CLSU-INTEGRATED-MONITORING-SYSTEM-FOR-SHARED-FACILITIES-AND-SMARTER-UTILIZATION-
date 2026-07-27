<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? 'Uni Space' }}</title>

<link rel="icon" type="image/png" href="{{ asset('images/Logo_Green.png') }}" />

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Header */
    [data-flux-header] {
        background-color: #14532d !important;
        font-family: 'Barlow', sans-serif;
    }

    /* Navbar Items */
    [data-flux-navbar-item] {
        font-family: 'Barlow Condensed', sans-serif !important;
        font-weight: 800 !important;
        font-size: 17px !important;
        letter-spacing: 0.8px;
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
        color: #14532d !important;
        background: #facc15 !important;
        box-shadow: inset 0 -3px 0 #14532d;
    }

    /* Mobile Sidebar */
    [data-flux-sidebar] {
        background-color: #14532d !important;
        border-right-color: rgba(255, 255, 255, 0.12) !important;
    }

    /* Sidebar Items */
    [data-flux-sidebar-item],
    [data-flux-navlist-item] {
        font-family: 'Barlow Condensed', sans-serif !important;
        font-weight: 800 !important;
        font-size: 17px !important;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    [data-flux-navlist-group] > div:first-child,
    [data-flux-navlist-group] > div:first-child *,
    [data-flux-navlist-group] button,
    [data-flux-navlist-group] button * {
        color: #ffffff !important;
        font-family: 'Barlow Condensed', sans-serif !important;
        font-weight: 800 !important;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    [data-flux-sidebar-item]:hover,
    [data-flux-navlist-item]:hover {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.12) !important;
    }

    [data-flux-sidebar-item][aria-current="page"],
    [data-flux-sidebar-item][data-current],
    [data-flux-sidebar-item].current,
    [data-flux-navlist-item][aria-current="page"],
    [data-flux-navlist-item][data-current],
    [data-flux-navlist-item].current {
        color: #14532d !important;
        background: #facc15 !important;
        border-color: #facc15 !important;
        box-shadow: 0 10px 20px rgba(20, 83, 45, 0.18);
    }

    /* Force all text inside Flux navigation to use the heavier weight */
    [data-flux-navbar-item] *,
    [data-flux-sidebar-item] *,
    [data-flux-navlist-item] * {
        font-family: 'Barlow Condensed', sans-serif !important;
        font-weight: 800 !important;
    }

    [data-flux-sidebar-item][aria-current="page"] *,
    [data-flux-sidebar-item][data-current] *,
    [data-flux-sidebar-item].current *,
    [data-flux-navlist-item][aria-current="page"] *,
    [data-flux-navlist-item][data-current] *,
    [data-flux-navlist-item].current *,
    [data-flux-navbar-item][aria-current="page"] *,
    [data-flux-navbar-item][data-current] *,
    [data-flux-navbar-item].current * {
        color: #14532d !important;
    }

    #campus-map.leaflet-container,
    #user-campus-map.leaflet-container {
        z-index: 0;
    }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')
@fluxAppearance
