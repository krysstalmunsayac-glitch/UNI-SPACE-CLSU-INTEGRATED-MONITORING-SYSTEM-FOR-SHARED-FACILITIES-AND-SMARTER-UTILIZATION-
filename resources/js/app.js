import './settings.jsx';
import './app-shell.jsx';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';

window.ScheduleCalendar = {
    Calendar,
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
};

window.dispatchEvent(new CustomEvent('schedule-calendar-ready'));

window.scheduleCalendar = function (initialEvents, livewireView, livewire) {
    return {
        calendar: null,
        events: initialEvents,
        viewMode: livewireView,
        resizeObserver: null,
        resizeTimer: null,
        refreshCleanup: null,

        initCalendar() {
            const element = this.$refs.calendar ?? document.getElementById('fc-calendar');

            if (!element || !window.ScheduleCalendar?.Calendar) return;

            if (this.calendar) {
                this.calendar.updateSize();
                return;
            }

            this.calendar = new window.ScheduleCalendar.Calendar(element, {
                plugins: window.ScheduleCalendar.plugins,
                initialView: livewireView === 'monthly' ? 'dayGridMonth' : 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: '',
                },
                height: 'auto',
                nowIndicator: true,
                slotMinTime: '07:00:00',
                slotMaxTime: '21:00:00',
                allDaySlot: false,
                events: this.events,
                dateClick: info => livewire.create(info.dateStr.split('T')[0]),
                eventClick: info => {
                    const scheduleId = info.event.extendedProps.scheduleId;
                    if (scheduleId) livewire.edit(scheduleId);
                },
                eventDidMount: info => {
                    info.el.style.cursor = 'pointer';

                    const formatDate = date => date?.toLocaleDateString([], {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    }) ?? '—';
                    const formatTime = date => date?.toLocaleTimeString([], {
                        hour: 'numeric',
                        minute: '2-digit',
                    }) ?? '—';
                    const details = info.event.extendedProps;

                    info.el.title = [
                        `Event: ${details.event ?? info.event.title}`,
                        `Facility: ${details.facility ?? info.event.title}`,
                        `Requester: ${details.requester ?? '—'}`,
                        `Purpose: ${details.purpose ?? '—'}`,
                        `Status: ${details.status ?? '—'}`,
                        `Date: ${formatDate(info.event.start)}`,
                        `Time: ${formatTime(info.event.start)}–${formatTime(info.event.end)}`,
                    ].join('\n');
                },
            });

            this.calendar.render();
            this.observeCalendarWidth(element);

            this.refreshCleanup = window.Livewire?.on('calendar-refresh', payload => {
                if (!this.calendar) return;

                this.calendar.removeAllEvents();
                this.calendar.addEventSource(payload.events ?? []);
                this.calendar.updateSize();
            });
        },

        observeCalendarWidth(element) {
            if (typeof ResizeObserver === 'undefined') {
                window.addEventListener('resize', () => this.resizeCalendar());
                return;
            }

            this.resizeObserver = new ResizeObserver(() => this.resizeCalendar());
            this.resizeObserver.observe(element.parentElement);
        },

        resizeCalendar() {
            if (!this.calendar) return;

            window.cancelAnimationFrame(this.resizeTimer);
            this.resizeTimer = window.requestAnimationFrame(() => this.calendar?.updateSize());
        },

        switchView(viewName, mode) {
            if (!this.calendar) return;

            this.calendar.changeView(viewName);
            this.calendar.updateSize();
            this.viewMode = mode;

            const url = new URL(window.location.href);
            url.searchParams.set('view', mode);
            window.history.replaceState(window.history.state, '', url);
        },

        destroy() {
            this.resizeObserver?.disconnect();
            window.cancelAnimationFrame(this.resizeTimer);
            this.refreshCleanup?.();
            this.calendar?.destroy();
            this.calendar = null;
        },
    };
};

let sweetAlertPromise;

const loadSweetAlert = () => {
    if (window.Swal) return Promise.resolve(window.Swal);
    if (sweetAlertPromise) return sweetAlertPromise;

    sweetAlertPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.async = true;
        script.onload = () => resolve(window.Swal);
        script.onerror = reject;
        document.head.appendChild(script);
    });

    return sweetAlertPromise;
};

window.confirmLogout = async () => {
    const Swal = await loadSweetAlert().catch(() => null);

    if (!Swal) {
        return window.confirm('Are you sure you want to log out?');
    }

    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to log out of your account?',
        icon: 'question',
        position: 'center',
        showCancelButton: true,
        confirmButtonText: 'Yes, log out',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#047857',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-2xl',
        },
    });

    return result.isConfirmed;
};

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    const action = new URL(form.action, window.location.origin);
    if (action.pathname !== '/logout') return;

    event.preventDefault();

    if (await window.confirmLogout()) {
        form.submit();
    }
});

window.addEventListener('swal', async event => {
    const {
        title = 'Success',
        text = '',
        icon = 'success',
        timer = 2500,
        showConfirmButton = false,
        position = 'center',
    } = event?.detail ?? {};

    const Swal = await loadSweetAlert().catch(() => null);
    if (!Swal) return;

    Swal.fire({
        title,
        text,
        icon,
        timer,
        showConfirmButton,
        timerProgressBar: true,
        position,
        toast: false,
        customClass: {
            popup: 'rounded-2xl',
        },
    });
});

if (window.pendingSweetAlert) {
    window.dispatchEvent(new CustomEvent('swal', {
        detail: window.pendingSweetAlert,
    }));
    delete window.pendingSweetAlert;
}

window.facilityLocationPicker = function (livewire) {
    return {
        map: null,
        marker: null,
        openTimer: null,
        latitude: null,
        longitude: null,
        searching: false,

        openPicker() {
            window.clearTimeout(this.openTimer);
            this.openTimer = window.setTimeout(() => {
                if (!window.L || !this.$refs.map) return;

                if (this.map && this.map.getContainer() !== this.$refs.map) {
                    this.map.remove();
                    this.map = null;
                    this.marker = null;
                }

                const savedLatitude = Number(livewire.get('Latitude'));
                const savedLongitude = Number(livewire.get('Longitude'));
                const hasSavedPin = Number.isFinite(savedLatitude)
                    && Number.isFinite(savedLongitude)
                    && savedLatitude !== 0
                    && savedLongitude !== 0;
                const center = hasSavedPin
                    ? [savedLatitude, savedLongitude]
                    : [15.7354, 120.9335];

                if (!this.map) {
                    // A Livewire morph can leave Leaflet's container marker behind.
                    // Clear it before attaching a fresh map to the current element.
                    if (this.$refs.map._leaflet_id) {
                        delete this.$refs.map._leaflet_id;
                    }
                    this.$refs.map.replaceChildren();

                    this.map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView(center, hasSavedPin ? 18 : 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 20,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(this.map);
                    this.map.on('click', event => this.setPin(event.latlng.lat, event.latlng.lng));
                }

                if (hasSavedPin) {
                    this.setPin(savedLatitude, savedLongitude, false);
                } else if (this.marker) {
                    this.marker.remove();
                    this.marker = null;
                    this.latitude = null;
                    this.longitude = null;
                }

                const refreshMap = () => {
                    if (!this.map) return;

                    this.map.invalidateSize();
                    this.map.setView(center, hasSavedPin ? 18 : 16, { animate: false });
                };

                window.requestAnimationFrame(refreshMap);
                window.setTimeout(refreshMap, 150);
                window.setTimeout(refreshMap, 350);
            }, 250);
        },

        closePicker() {
            window.clearTimeout(this.openTimer);
            this.openTimer = null;

            if (this.map) {
                this.map.remove();
                this.map = null;
            }

            this.marker = null;
        },

        destroy() {
            this.closePicker();
        },

        setPin(latitude, longitude, updateLivewire = true) {
            this.latitude = Number(latitude).toFixed(7);
            this.longitude = Number(longitude).toFixed(7);

            if (!this.marker) {
                this.marker = L.marker([latitude, longitude], { draggable: true }).addTo(this.map);
                this.marker.on('dragend', event => {
                    const position = event.target.getLatLng();
                    this.setPin(position.lat, position.lng);
                });
            } else {
                this.marker.setLatLng([latitude, longitude]);
            }

            if (updateLivewire) {
                // Keep map clicks as pending form changes. Livewire sends these
                // values with the next action (Update) instead of immediately.
                livewire.set('Latitude', Number(this.latitude), false);
                livewire.set('Longitude', Number(this.longitude), false);
            }
        },

        async findLocation() {
            const location = livewire.get('Location');
            const facilityName = livewire.get('Facility_Name');
            if (!location && !facilityName) return;

            this.searching = true;
            const query = [facilityName, location, 'Central Luzon State University', 'Science City of Muñoz', 'Nueva Ecija']
                .filter(Boolean)
                .join(', ');

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`);
                const result = (await response.json())[0];
                if (result) {
                    this.setPin(Number(result.lat), Number(result.lon));
                    this.map.setView([Number(result.lat), Number(result.lon)], 18);
                }
            } finally {
                this.searching = false;
            }
        },

        clearPin() {
            if (this.marker) {
                this.marker.remove();
                this.marker = null;
            }
            this.latitude = null;
            this.longitude = null;
            livewire.set('Latitude', null, false);
            livewire.set('Longitude', null, false);
        },
    };
};
