    <style>
        #map-facility-type:focus,
        #map-facility-filter:focus {
            outline: 3px solid rgba(255, 255, 255, 0.35);
            outline-offset: 2px;
            box-shadow: none;
        }

        .campus-map-container {
            max-width: 1600px;
        }

        .campus-map-canvas {
            height: 560px;
            min-height: 560px;
        }

        @media (min-width: 1024px) {
            .campus-map-layout {
                grid-template-columns: 420px minmax(0, 1fr);
                align-items: stretch;
            }

            .campus-map-canvas {
                height: 760px;
                min-height: 760px;
            }
        }
    </style>

    <section id="map" class="scroll-mt-28 border-t border-emerald-900/10 bg-emerald-50/50 py-20 dark:border-white/10 dark:bg-zinc-900" style="scroll-margin-top: 12rem;">
        <div class="campus-map-container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="campus-map-layout grid gap-6">
                <div class="rounded-2xl bg-emerald-800 p-8 text-white">
                    <h2 class="text-4xl font-black">Campus map</h2>
                    <p class="mt-4 text-lg leading-8 text-emerald-50">
                        Explore the CLSU campus map before sending your facility request.
                    </p>

                    <div class="mt-8 space-y-3 text-sm font-semibold text-emerald-50">
                        <p>Central Luzon State University</p>
                        <p>Science City of Munoz, Nueva Ecija</p>
                    </div>

                    <div class="mt-8 space-y-4 rounded-xl border border-white/20 bg-white/10 p-4">
                        <div>
                            <label for="map-facility-type" class="text-xs font-black uppercase tracking-wider text-emerald-100">Facility type</label>
                            <div class="mt-2" style="position: relative;">
                                <select id="map-facility-type" class="w-full rounded-xl border border-white/20 bg-white py-2.5 pl-3 text-sm font-bold text-emerald-950 outline-none focus:ring-4 focus:ring-white/20" style="appearance: none; padding-right: 3rem;">
                                    <option value="all">All facility types</option>
                                </select>
                                <svg class="map-select-chevron" data-select="map-facility-type" role="button" aria-label="Open facility type options" tabindex="0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 1rem; top: 50%; width: 1.1rem; height: 1.1rem; transform: translateY(-50%); color: #047857; cursor: pointer;">
                                    <path d="m6 8 4 4 4-4" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <label for="map-facility-filter" class="text-xs font-black uppercase tracking-wider text-emerald-100">Locate facility</label>
                            <div class="mt-2" style="position: relative;">
                                <select id="map-facility-filter" class="w-full rounded-xl border border-white/20 bg-white py-2.5 pl-3 text-sm font-bold text-emerald-950 outline-none focus:ring-4 focus:ring-white/20" style="appearance: none; padding-right: 3rem;">
                                    <option value="all">All Facilities</option>
                                </select>
                                <svg class="map-select-chevron" data-select="map-facility-filter" role="button" aria-label="Open facility options" tabindex="0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 1rem; top: 50%; width: 1.1rem; height: 1.1rem; transform: translateY(-50%); color: #047857; cursor: pointer;">
                                    <path d="m6 8 4 4 4-4" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <button id="get-facility-directions" type="button" disabled class="w-full rounded-xl bg-white px-4 py-3 text-sm font-black text-emerald-800 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60">
                                Directions from CLSU Main Gate
                            </button>
                            <button id="get-my-location-directions" type="button" disabled class="mt-2 w-full rounded-xl border border-white/40 bg-white/10 px-4 py-3 text-sm font-black text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-60">
                                Directions from My Location
                            </button>
                            <p id="map-location-status" class="mt-2 text-xs leading-5 text-emerald-50" aria-live="polite">Select a facility to view directions.</p>
                            <a id="open-navigation-link" href="#" target="_blank" rel="noopener noreferrer" class="mt-3 hidden w-full items-center justify-center rounded-xl border border-white/40 px-4 py-2.5 text-center text-xs font-black text-white transition hover:bg-white/10">
                                Open Walking Navigation
                            </a>
                        </div>
                        <div>
                            <button id="dashboard-locate-me" type="button" class="w-full rounded-xl border border-white/40 bg-white/10 px-4 py-3 text-sm font-black text-white transition hover:bg-white/20 focus:outline-none focus:ring-4 focus:ring-white/20 disabled:cursor-wait disabled:opacity-60">
                                <span aria-hidden="true">⌖</span> Use my location
                            </button>
                            <p id="dashboard-location-status" class="mt-2 text-xs leading-5 text-emerald-50" aria-live="polite"></p>
                        </div>
                    </div>

                </div>

                <div class="relative z-0 overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/10 dark:border-white/10 dark:bg-zinc-950">
                    <div id="user-campus-map" class="campus-map-canvas relative z-0 flex w-full items-center justify-center bg-emerald-50 text-sm font-bold text-emerald-900 dark:bg-zinc-900 dark:text-emerald-200">
                        Loading campus map...
                    </div>
                </div>
            </div>
        </div>
    </section>
