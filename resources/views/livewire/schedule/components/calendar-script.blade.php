<script>
function scheduleCalendar(initialEvents, livewireView) {
    return {
        calendar: null,
        events: initialEvents,

        initCalendar() {
            const el = document.getElementById('fc-calendar');

            if (!el || typeof FullCalendar === 'undefined') {
                return;
            }

            const fcView = livewireView === 'monthly'
                ? 'dayGridMonth'
                : 'timeGridWeek';

            this.calendar = new FullCalendar.Calendar(el, {
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

            Livewire.on('calendar-refresh', (payload) => {
                const events = payload.events ?? [];

                this.calendar.removeAllEvents();
                this.calendar.addEventSource(events);
                this.calendar.updateSize();
            });
        },

        switchView(fcViewName) {
            if (!this.calendar) {
                return;
            }

            this.calendar.changeView(fcViewName);
            this.calendar.updateSize();
        },
    };
}
</script>
