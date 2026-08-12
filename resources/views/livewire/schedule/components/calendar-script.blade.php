<script>
function scheduleCalendar(initialEvents, livewireView) {
    return {
        calendar: null,
        events: initialEvents,
        resizeObserver: null,
        resizeTimer: null,
        refreshCleanup: null,
        readyHandler: null,

        initCalendar() {
            const el = document.getElementById('fc-calendar');

            if (!el) {
                return;
            }

            if (!window.ScheduleCalendar?.Calendar) {
                this.readyHandler ??= () => {
                    this.readyHandler = null;
                    this.initCalendar();
                };
                window.addEventListener('schedule-calendar-ready', this.readyHandler, { once: true });
                return;
            }

            if (this.calendar) {
                this.calendar.updateSize();
                return;
            }

            const fcView = livewireView === 'monthly'
                ? 'dayGridMonth'
                : 'timeGridWeek';

            this.calendar = new window.ScheduleCalendar.Calendar(el, {
                plugins: window.ScheduleCalendar.plugins,
                initialView: fcView,

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

                dateClick: (info) => {
                    const date = info.dateStr.split('T')[0];
                    @this.create(date);
                },

                eventClick: (info) => {
                    const scheduleId = info.event.extendedProps.scheduleId;

                    if (scheduleId) {
                        @this.edit(scheduleId);
                    }
                },

                eventDidMount: (info) => {
                    info.el.style.cursor = 'pointer';
                },
            });

            this.calendar.render();
            this.observeCalendarWidth(el);

            this.refreshCleanup = Livewire.on('calendar-refresh', (payload) => {
                const events = payload.events ?? [];

                if (!this.calendar) {
                    return;
                }

                this.calendar.removeAllEvents();
                this.calendar.addEventSource(events);
                this.calendar.updateSize();
            });
        },

        observeCalendarWidth(el) {
            if (typeof ResizeObserver === 'undefined') {
                window.addEventListener('resize', () => this.resizeCalendar());
                return;
            }

            this.resizeObserver = new ResizeObserver(() => this.resizeCalendar());
            this.resizeObserver.observe(el.parentElement);
        },

        resizeCalendar() {
            if (!this.calendar) {
                return;
            }

            window.cancelAnimationFrame(this.resizeTimer);
            this.resizeTimer = window.requestAnimationFrame(() => {
                this.calendar?.updateSize();
            });
        },

        switchView(fcViewName) {
            if (!this.calendar) {
                return;
            }

            this.calendar.changeView(fcViewName);
            this.calendar.updateSize();
        },

        destroy() {
            this.resizeObserver?.disconnect();
            window.cancelAnimationFrame(this.resizeTimer);
            this.refreshCleanup?.();

            if (this.readyHandler) {
                window.removeEventListener('schedule-calendar-ready', this.readyHandler);
            }

            this.calendar?.destroy();
            this.calendar = null;
        },
    };
}
</script>
