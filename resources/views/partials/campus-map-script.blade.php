                const initializeCampusMap = () => {
                    const mapElement = document.getElementById('user-campus-map');
                    if (!mapElement || mapElement.dataset.initialized) return true;
                    if (!window.L) return false;
                    mapElement.dataset.initialized = 'true';

                    const campusCenter = [15.7354, 120.9335];
                    // CLSU Main Gate at the campus access-road junction with Maharlika Highway.
                    const mainGateCoordinates = [15.7305665, 120.9297932];
                    const map = L.map(mapElement, {
                        scrollWheelZoom: false,
                    }).setView(campusCenter, 16);
                    mapElement.classList.remove('flex', 'items-center', 'justify-center');

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const mainGateIcon = L.divIcon({
                        className: '',
                        html: '<div style="display:grid;place-items:center;width:42px;height:42px;border:4px solid white;border-radius:9999px;background:#009639;color:white;font-size:22px;box-shadow:0 4px 14px rgba(0,0,0,.4)">&#9873;</div>',
                        iconSize: [42, 42],
                        iconAnchor: [21, 21],
                        popupAnchor: [0, -24],
                    });
                    const facilityIcon = L.divIcon({
                        className: '',
                        html: '<svg width="34" height="46" viewBox="0 0 34 46" aria-hidden="true" style="display:block;filter:drop-shadow(0 3px 3px rgba(0,0,0,.35))"><path d="M17 1C8.16 1 1 8.16 1 17c0 11.72 16 28 16 28s16-16.28 16-28C33 8.16 25.84 1 17 1Z" fill="#009639" stroke="#ffffff" stroke-width="2"/><circle cx="17" cy="17" r="6" fill="#ffffff"/></svg>',
                        iconSize: [34, 46],
                        iconAnchor: [17, 45],
                        popupAnchor: [0, -42],
                    });
                    const mainGateMarker = L.marker(mainGateCoordinates, {
                        icon: mainGateIcon,
                        title: 'CLSU Main Gate',
                        zIndexOffset: 2000,
                    }).addTo(map)
                        .bindPopup('<strong>CLSU Main Gate</strong><br><small>Main campus entrance</small>')
                        .bindTooltip('CLSU Main Gate', { permanent: true, direction: 'top', offset: [0, -22], className: 'font-bold' });

                    const facilities = @js($mapFacilities->map(fn ($facility) => [
                        'FID' => $facility->FID,
                        'Facility_Name' => $facility->Facility_Name,
                        'Location' => $facility->Location,
                        'Status' => $facility->Status,
                        'facility_type' => $facility->facility_type,
                        'Capacity' => $facility->Capacity,
                        'Latitude' => $facility->Latitude,
                        'Longitude' => $facility->Longitude,
                    ])->values());
                    const focusedFacilityId = @js($focusedFacilityId ?? null);
                    const bounds = L.latLngBounds();
                    const navigationPanel = document.getElementById('map-navigation-panel');
                    const facilitySelect = document.getElementById('map-facility-filter');
                    const facilityTypeSelect = document.getElementById('map-facility-type');
                    const selectedFacilityName = document.getElementById('map-selected-facility');
                    const selectedFacilityLocation = document.getElementById('map-selected-location');
                    const directionsButton = document.getElementById('get-facility-directions');
                    const myLocationDirectionsButton = document.getElementById('get-my-location-directions');
                    const locationStatus = document.getElementById('map-location-status');
                    const navigationLink = document.getElementById('open-navigation-link');
                    const locateMeButton = document.getElementById('dashboard-locate-me');
                    const userLocationStatus = document.getElementById('dashboard-location-status');
                    let focusedDestination = null;
                    let focusHighlight = null;
                    let guidanceLine = null;
                    let userCoordinates = null;
                    let userLocationMarker = null;
                    let userAccuracyCircle = null;
                    let locationWatchId = null;
                    let hasInitialLocation = false;
                    let lastAutomaticRouteCoordinates = null;
                    let lastAutomaticRouteAt = 0;
                    const facilityMarkers = new Map();
                    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
                    })[character]);
                    const fallbackCoordinates = facility => {
                        const hash = [...String(facility.FID ?? facility.Facility_Name)].reduce((total, character) => ((total * 31) + character.charCodeAt(0)) >>> 0, 0);
                        const angle = (hash % 360) * (Math.PI / 180);
                        const radius = 0.00035 + ((hash % 8) * 0.00012);
                        return [campusCenter[0] + Math.sin(angle) * radius, campusCenter[1] + Math.cos(angle) * radius];
                    };
                    const addFacilityMarker = (facility, coordinates, approximate = false) => {
                        const marker = L.marker(coordinates, {
                            icon: facilityIcon,
                            title: facility.Facility_Name,
                        }).addTo(map).bindPopup(
                            `<strong>${escapeHtml(facility.Facility_Name)}</strong><br>` +
                            `${escapeHtml(facility.Location || 'CLSU Main Campus')}<br>` +
                            `<small>${escapeHtml(facility.facility_type || 'Facility')} · Capacity: ${escapeHtml(facility.Capacity || 'N/A')}</small><br>` +
                            `<small>${escapeHtml(facility.Status || '')}${approximate ? ' · Approximate campus pin' : ''}</small>`
                        );
                        bounds.extend(coordinates);
                        facilityMarkers.set(Number(facility.FID), { facility, coordinates, approximate, marker });
                    };

                    const showDirections = async (useMyLocation = false) => {
                        if (!focusedDestination || !directionsButton) {
                            return;
                        }

                        if (useMyLocation && !userCoordinates) {
                            if (locationStatus) locationStatus.textContent = 'Enable live location before requesting directions from your position.';
                            return;
                        }

                        const routeOrigin = useMyLocation ? userCoordinates : mainGateCoordinates;
                        const routeOriginLabel = useMyLocation ? 'your location' : 'the CLSU Main Gate';
                        if (directionsButton) directionsButton.disabled = true;
                        if (myLocationDirectionsButton) myLocationDirectionsButton.disabled = true;
                        guidanceLine?.remove();
                        guidanceLine = null;
                        if (locationStatus) locationStatus.textContent = `Loading the walkable route from ${routeOriginLabel}...`;
                        if (navigationLink) {
                            const route = `${routeOrigin[0]},${routeOrigin[1]};${focusedDestination.coordinates[0]},${focusedDestination.coordinates[1]}`;
                            navigationLink.href = `https://www.openstreetmap.org/directions?engine=fossgis_osrm_foot&route=${encodeURIComponent(route)}`;
                            navigationLink.classList.remove('hidden');
                            navigationLink.classList.add('inline-flex');
                        }

                        if (focusedDestination.approximate) {
                            if (locationStatus) locationStatus.textContent = 'A route cannot be drawn until exact coordinates are saved for this facility.';
                            if (directionsButton) directionsButton.disabled = false;
                            return;
                        }

                        try {
                            const [destinationLatitude, destinationLongitude] = focusedDestination.coordinates;
                            const routeUrl = `https://routing.openstreetmap.de/routed-foot/route/v1/driving/${routeOrigin[1]},${routeOrigin[0]};${destinationLongitude},${destinationLatitude}?overview=full&geometries=geojson&steps=true`;
                            const response = await fetch(routeUrl, { headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error(`Routing failed with status ${response.status}`);
                            const route = (await response.json()).routes?.[0];
                            if (!route?.geometry?.coordinates?.length) throw new Error('No pedestrian route returned');

                            const routeCoordinates = route.geometry.coordinates.map(([longitude, latitude]) => [latitude, longitude]);
                            guidanceLine = L.polyline(routeCoordinates, {
                                color: '#2563eb',
                                opacity: 0.9,
                                weight: 6,
                            }).addTo(map);
                            map.fitBounds(guidanceLine.getBounds().pad(0.12), { maxZoom: 18 });
                            if (locationStatus) {
                                const routeDistance = route.distance < 1000
                                    ? `${Math.round(route.distance)} m`
                                    : `${(route.distance / 1000).toFixed(1)} km`;
                                const routeMinutes = Math.max(1, Math.round(route.duration / 60));
                                locationStatus.textContent = `Walkable route from ${routeOriginLabel}: ${routeDistance}, about ${routeMinutes} minutes.`;
                            }
                        } catch {
                            if (locationStatus) {
                                locationStatus.textContent = 'A verified walkable pathway could not be loaded. No artificial straight-line route was drawn.';
                            }
                            map.fitBounds(L.latLngBounds([routeOrigin, focusedDestination.coordinates]).pad(0.2), { maxZoom: 18 });
                        } finally {
                            if (directionsButton) directionsButton.disabled = false;
                            if (myLocationDirectionsButton) myLocationDirectionsButton.disabled = !userCoordinates;
                        }

                    };

                    locateMeButton?.addEventListener('click', () => {
                        if (!navigator.geolocation) {
                            if (userLocationStatus) userLocationStatus.textContent = 'Location services are not supported by this browser.';
                            return;
                        }

                        if (locationWatchId !== null) {
                            navigator.geolocation.clearWatch(locationWatchId);
                            locationWatchId = null;
                            locateMeButton.innerHTML = '<span aria-hidden="true">⌖</span> Follow my location';
                            if (userLocationStatus) userLocationStatus.textContent = 'Live location tracking paused.';
                            return;
                        }

                        locateMeButton.disabled = true;
                        if (userLocationStatus) userLocationStatus.textContent = 'Finding your current location...';
                        hasInitialLocation = false;

                        locationWatchId = navigator.geolocation.watchPosition(
                            ({ coords }) => {
                                userCoordinates = [coords.latitude, coords.longitude];

                                if (userAccuracyCircle) {
                                    userAccuracyCircle.setLatLng(userCoordinates).setRadius(coords.accuracy);
                                } else {
                                    userAccuracyCircle = L.circle(userCoordinates, {
                                        radius: coords.accuracy,
                                        color: '#047857',
                                        fillColor: '#10b981',
                                        fillOpacity: 0.12,
                                        weight: 2,
                                    }).addTo(map);
                                }

                                if (userLocationMarker) {
                                    userLocationMarker.setLatLng(userCoordinates);
                                } else {
                                    userLocationMarker = L.circleMarker(userCoordinates, {
                                        radius: 9,
                                        color: '#ffffff',
                                        fillColor: '#047857',
                                        fillOpacity: 1,
                                        weight: 3,
                                    }).addTo(map).bindPopup('<strong>Your live location</strong>').openPopup();
                                }

                                locateMeButton.innerHTML = '<span aria-hidden="true">●</span> Stop following';
                                locateMeButton.disabled = false;
                                if (myLocationDirectionsButton && focusedDestination && !focusedDestination.approximate) {
                                    myLocationDirectionsButton.disabled = false;
                                }
                                if (userLocationStatus) userLocationStatus.textContent = `Following your location within approximately ${Math.round(coords.accuracy)} meters.`;

                                // Recenter only for the first GPS fix. Repeated watchPosition
                                // updates include normal accuracy jitter; centering on every
                                // callback makes Leaflet continually redraw/reload its tiles
                                // even while the user is standing still.
                                if (!hasInitialLocation) {
                                    map.setView(userCoordinates, Math.max(map.getZoom(), 18), { animate: true });
                                }

                                const now = Date.now();
                                const movedDistance = lastAutomaticRouteCoordinates
                                    ? map.distance(lastAutomaticRouteCoordinates, userCoordinates)
                                    : Infinity;
                                if (
                                    focusedDestination
                                    && !focusedDestination.approximate
                                    && movedDistance >= 15
                                    && now - lastAutomaticRouteAt >= 15000
                                ) {
                                    lastAutomaticRouteCoordinates = [...userCoordinates];
                                    lastAutomaticRouteAt = now;
                                    showDirections(true);
                                }

                                hasInitialLocation = true;
                            },
                            (error) => {
                                const messages = {
                                    1: 'Location permission was denied. Allow it in your browser and try again.',
                                    2: 'Your location is currently unavailable. Please try again.',
                                    3: 'Finding your location took too long. Please try again.',
                                };
                                if (userLocationStatus) userLocationStatus.textContent = messages[error.code] || 'Your location could not be found.';
                                locateMeButton.disabled = false;
                                locateMeButton.innerHTML = '<span aria-hidden="true">⌖</span> Follow my location';
                                if (locationWatchId !== null) navigator.geolocation.clearWatch(locationWatchId);
                                locationWatchId = null;
                            },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 },
                        );
                    });

                    directionsButton?.addEventListener('click', () => showDirections(false));
                    myLocationDirectionsButton?.addEventListener('click', () => showDirections(true));
                    const selectFacility = facilityId => {
                        focusHighlight?.remove();
                        focusHighlight = null;
                        guidanceLine?.remove();
                        guidanceLine = null;
                        map.closePopup();

                        if (facilityId === 'all') {
                            focusedDestination = null;
                            if (directionsButton) directionsButton.disabled = true;
                            if (myLocationDirectionsButton) myLocationDirectionsButton.disabled = true;
                            if (locationStatus) locationStatus.textContent = 'Select a facility to view directions.';
                            navigationPanel?.classList.add('hidden');
                            navigationLink?.classList.add('hidden');
                            navigationLink?.classList.remove('inline-flex');
                            const selectedType = facilityTypeSelect?.value || 'all';
                            const visibleBounds = L.latLngBounds();
                            facilityMarkers.forEach(({ facility, coordinates, marker }) => {
                                const matchesType = selectedType === 'all' || (facility.facility_type || 'Other') === selectedType;
                                if (matchesType && !map.hasLayer(marker)) marker.addTo(map);
                                if (!matchesType && map.hasLayer(marker)) map.removeLayer(marker);
                                marker.setOpacity(1);
                                marker.setZIndexOffset(0);
                                if (matchesType) visibleBounds.extend(coordinates);
                            });
                            if (visibleBounds.isValid()) map.fitBounds(visibleBounds.pad(0.18), { maxZoom: 17 });
                            return;
                        }

                        const selected = facilityMarkers.get(Number(facilityId));
                        if (!selected) return;
                        focusedDestination = selected;
                        navigationLink?.classList.add('hidden');
                        navigationLink?.classList.remove('inline-flex');
                        if (directionsButton) directionsButton.disabled = selected.approximate;
                        if (myLocationDirectionsButton) myLocationDirectionsButton.disabled = selected.approximate || !userCoordinates;
                        if (locationStatus) {
                            locationStatus.textContent = selected.approximate
                                ? 'Directions require an exact saved facility location.'
                                : 'Ready to show directions from the CLSU Main Gate.';
                        }
                        facilityMarkers.forEach(({ marker }, id) => {
                            if (!map.hasLayer(marker)) marker.addTo(map);
                            marker.setOpacity(id === Number(facilityId) ? 1 : 0.35);
                            marker.setZIndexOffset(id === Number(facilityId) ? 1000 : 0);
                        });
                        focusHighlight = L.circle(selected.coordinates, {
                            radius: 28,
                            color: '#f59e0b',
                            fillColor: '#fbbf24',
                            fillOpacity: 0.3,
                            weight: 5,
                        }).addTo(map);
                        if (selectedFacilityName) selectedFacilityName.textContent = selected.facility.Facility_Name;
                        if (selectedFacilityLocation) selectedFacilityLocation.textContent = `${selected.facility.Location || 'CLSU Main Campus'} · ${selected.facility.facility_type || 'Facility'} · Capacity: ${selected.facility.Capacity || 'N/A'}`;
                        map.setView(selected.coordinates, 19);
                        selected.marker.openPopup();
                        if (!selected.approximate) {
                            if (userCoordinates) {
                                lastAutomaticRouteCoordinates = [...userCoordinates];
                                lastAutomaticRouteAt = Date.now();
                                showDirections(true);
                            } else {
                                showDirections(false);
                            }
                        }
                    };

                    const populateFacilityFilters = () => {
                        const availableFacilities = facilities.filter(facility => facility.Status === 'Available' || Number(facility.FID) === Number(focusedFacilityId));
                        const facilityTypes = [...new Set(availableFacilities.map(facility => facility.facility_type || 'Other'))].sort();
                        facilityTypes.forEach(type => facilityTypeSelect?.add(new Option(type, type)));

                        const updateFacilityOptions = () => {
                            if (!facilitySelect) return;
                            const selectedType = facilityTypeSelect?.value || 'all';
                            facilitySelect.replaceChildren(new Option('All Facilities', 'all'));
                            availableFacilities
                                .filter(facility => selectedType === 'all' || (facility.facility_type || 'Other') === selectedType)
                                .sort((left, right) => left.Facility_Name.localeCompare(right.Facility_Name))
                                .forEach(facility => facilitySelect.add(new Option(facility.Facility_Name, facility.FID)));
                        };

                        facilityTypeSelect?.addEventListener('change', () => {
                            updateFacilityOptions();
                            selectFacility('all');
                        });
                        facilitySelect?.addEventListener('change', event => selectFacility(event.target.value));
                        updateFacilityOptions();
                    };
                    const locateFacilities = async () => {
                        for (const facility of facilities) {
                            const savedLatitude = Number(facility.Latitude);
                            const savedLongitude = Number(facility.Longitude);
                            if (
                                Number.isFinite(savedLatitude)
                                && Number.isFinite(savedLongitude)
                                && savedLatitude !== 0
                                && savedLongitude !== 0
                            ) {
                                addFacilityMarker(facility, [savedLatitude, savedLongitude]);
                                continue;
                            }

                            const cacheKey = `clsu-facility-map-${facility.FID}-${facility.Location || ''}`;
                            let cached = null;

                            try {
                                cached = JSON.parse(localStorage.getItem(cacheKey) || 'null');
                            } catch {
                                localStorage.removeItem(cacheKey);
                            }

                            if (cached?.length === 2) {
                                addFacilityMarker(facility, cached);
                                continue;
                            }

                            const query = [facility.Facility_Name, facility.Location, 'Central Luzon State University', 'Science City of Muñoz', 'Nueva Ecija'].filter(Boolean).join(', ');

                            try {
                                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`, {
                                    headers: { 'Accept': 'application/json' },
                                });
                                if (!response.ok) throw new Error(`Geocoding failed with status ${response.status}`);
                                const result = (await response.json())[0];
                                const coordinates = result ? [Number(result.lat), Number(result.lon)] : fallbackCoordinates(facility);
                                try {
                                    localStorage.setItem(cacheKey, JSON.stringify(coordinates));
                                } catch {
                                    // The map still works when browser storage is unavailable.
                                }
                                addFacilityMarker(facility, coordinates, !result);
                            } catch {
                                addFacilityMarker(facility, fallbackCoordinates(facility), true);
                            }

                            await new Promise(resolve => setTimeout(resolve, 1050));
                        }

                        populateFacilityFilters();

                        if (focusedFacilityId && facilityMarkers.has(Number(focusedFacilityId))) {
                            if (facilitySelect) facilitySelect.value = String(focusedFacilityId);
                            selectFacility(String(focusedFacilityId));
                        } else {
                            selectFacility('all');
                        }
                    };

                    locateFacilities();

                    setTimeout(() => map.invalidateSize(), 100);
                    return true;
                };

                if (!initializeCampusMap()) {
                    let attempts = 0;
                    const leafletTimer = window.setInterval(() => {
                        attempts += 1;
                        if (initializeCampusMap()) {
                            window.clearInterval(leafletTimer);
                        } else if (attempts >= 40) {
                            window.clearInterval(leafletTimer);
                            const mapElement = document.getElementById('user-campus-map');
                            if (mapElement) mapElement.textContent = 'The campus map could not be loaded. Please refresh and try again.';
                        }
                    }, 250);
                }
