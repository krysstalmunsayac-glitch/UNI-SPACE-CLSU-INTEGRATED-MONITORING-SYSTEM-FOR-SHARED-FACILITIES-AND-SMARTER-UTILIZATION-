<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? 'SIEL SPACE' }}</title>

<link rel="icon" type="image/png" href="{{ asset('images/Logo_Green.png') }}" />

@if (request()->routeIs('home', 'dashboard', 'Facility*'))
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js" defer></script>
@endif
<style>
    /* Header */
    [data-ui-header] {
        background-color: #009639 !important;
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif;
    }

    /* Navbar Items */
    [data-ui-navbar-item] {
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif !important;
        font-weight: 800 !important;
        font-size: 15px !important;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    [data-ui-navbar-item]:hover {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.08);
    }

    [data-ui-navbar-item][aria-current="page"],
    [data-ui-navbar-item][data-current],
    [data-ui-navbar-item].current {
        color: #007a2f !important;
        background: #facc15 !important;
        box-shadow: inset 0 -3px 0 #007a2f;
    }

    /* Mobile Sidebar */
    [data-ui-sidebar] {
        background-color: #009639 !important;
        border-right-color: rgba(255, 255, 255, 0.12) !important;
    }

    /* Sidebar Items */
    [data-ui-sidebar] [data-ui-sidebar-item],
    [data-ui-sidebar] [data-ui-navlist-item] {
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif !important;
        font-weight: 800 !important;
        font-size: 16px !important;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.9) !important;
        min-height: 44px;
    }

    [data-ui-sidebar] [data-ui-navlist-group] > div:first-child,
    [data-ui-sidebar] [data-ui-navlist-group] > div:first-child *,
    [data-ui-sidebar] [data-ui-navlist-group] button,
    [data-ui-sidebar] [data-ui-navlist-group] button * {
        color: #ffffff !important;
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif !important;
        font-weight: 800 !important;
        font-size: 13px !important;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    [data-ui-sidebar] [data-ui-sidebar-item]:hover,
    [data-ui-sidebar] [data-ui-navlist-item]:hover {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.12) !important;
    }

    [data-ui-sidebar] [data-ui-sidebar-item][aria-current="page"],
    [data-ui-sidebar] [data-ui-sidebar-item][data-current],
    [data-ui-sidebar] [data-ui-sidebar-item].current,
    [data-ui-sidebar] [data-ui-navlist-item][aria-current="page"],
    [data-ui-sidebar] [data-ui-navlist-item][data-current],
    [data-ui-sidebar] [data-ui-navlist-item].current {
        color: #007a2f !important;
        background: #facc15 !important;
        border-color: #facc15 !important;
        box-shadow: 0 10px 20px rgba(20, 83, 45, 0.18);
    }

    /* Force all text inside UI navigation to use the heavier weight */
    [data-ui-navbar-item] *,
    [data-ui-sidebar] [data-ui-sidebar-item] *,
    [data-ui-sidebar] [data-ui-navlist-item] * {
        font-family: 'Acumin Pro', 'Acumin Variable Concept', Arial, sans-serif !important;
        font-weight: 800 !important;
    }

    [data-ui-sidebar] [data-ui-sidebar-item][aria-current="page"] *,
    [data-ui-sidebar] [data-ui-sidebar-item][data-current] *,
    [data-ui-sidebar] [data-ui-sidebar-item].current *,
    [data-ui-sidebar] [data-ui-navlist-item][aria-current="page"] *,
    [data-ui-sidebar] [data-ui-navlist-item][data-current] *,
    [data-ui-sidebar] [data-ui-navlist-item].current *,
    [data-ui-navbar-item][aria-current="page"] *,
    [data-ui-navbar-item][data-current] *,
    [data-ui-navbar-item].current * {
        color: #007a2f !important;
    }

    #campus-map.leaflet-container,
    #user-campus-map.leaflet-container {
        z-index: 0;
    }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')
