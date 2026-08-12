@props([
    'calendarId' => 'booking-calendar',
    'events' => [],
])

<div id="{{ $calendarId }}" class="booking-calendar-app" data-events="{{ base64_encode(json_encode($events)) }}">
    <aside class="booking-calendar-side">
        <div class="booking-date-card">
            <div class="booking-weekday" data-calendar-weekday>Thu.</div>
            <div class="booking-month" data-calendar-month>July 2026</div>
            <div class="booking-day" data-calendar-day>9</div>
            <div class="booking-meta" data-calendar-meta>Day 190, Week 28</div>
        </div>

        <div class="booking-controls">
            <span class="booking-select-wrap">
                <select data-calendar-year aria-label="Year"></select>
            </span>
            <span class="booking-select-wrap">
                <select data-calendar-month-select aria-label="Month"></select>
            </span>
            <span class="booking-select-wrap">
                <select data-calendar-start-day aria-label="Week starts on">
                    <option value="0">Start Sun</option>
                    <option value="1">Start Mon</option>
                </select>
            </span>
            <span class="booking-select-wrap">
                <select data-calendar-view aria-label="Calendar view">
                    <option value="month">Month</option>
                    <option value="week">Week</option>
                </select>
            </span>
            <button type="button" data-calendar-today>Today</button>
        </div>
    </aside>

    <section class="booking-calendar-panel">
        <div class="booking-calendar-filterbar">
            <label>
                <span>Facility</span>
                <span class="booking-select-wrap">
                    <select data-calendar-facility aria-label="Facility">
                        <option value="all">All facilities</option>
                    </select>
                </span>
            </label>
        </div>

        <div class="booking-calendar-top">
            <div class="booking-calendar-title" data-calendar-label>July 2026</div>
            <div class="booking-calendar-nav">
                <button type="button" data-calendar-prev aria-label="Previous">&lt;</button>
                <button type="button" data-calendar-next aria-label="Next">&gt;</button>
            </div>
        </div>

        <div class="booking-calendar-grid" data-calendar-grid></div>
    </section>
</div>

@once
    @push('scripts')
        <script>
            window.initBookingCalendar = window.initBookingCalendar || function (calendarId) {
                const root = document.getElementById(calendarId);
                if (!root || root.dataset.initialized === 'true') return;

                root.dataset.initialized = 'true';

                const yearSelect = root.querySelector('[data-calendar-year]');
                const monthSelect = root.querySelector('[data-calendar-month-select]');
                const startDaySelect = root.querySelector('[data-calendar-start-day]');
                const viewSelect = root.querySelector('[data-calendar-view]');
                const facilitySelect = root.querySelector('[data-calendar-facility]');
                const todayBtn = root.querySelector('[data-calendar-today]');
                const prevBtn = root.querySelector('[data-calendar-prev]');
                const nextBtn = root.querySelector('[data-calendar-next]');
                const calendarGrid = root.querySelector('[data-calendar-grid]');
                const monthLabel = root.querySelector('[data-calendar-label]');
                const leftWeekday = root.querySelector('[data-calendar-weekday]');
                const leftMonth = root.querySelector('[data-calendar-month]');
                const leftDay = root.querySelector('[data-calendar-day]');
                const leftMeta = root.querySelector('[data-calendar-meta]');
                const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const months = Array.from({ length: 12 }, (_, i) => new Date(2000, i, 1).toLocaleString(undefined, { month: 'short' }));
                const events = JSON.parse(atob(root.dataset.events || 'W10='));
                let selected = new Date();
                let viewMode = 'month';

                const dateKey = (date) => [
                    date.getFullYear(),
                    String(date.getMonth() + 1).padStart(2, '0'),
                    String(date.getDate()).padStart(2, '0'),
                ].join('-');

                const eventFacilityName = (event) => event.facility || event.title || 'Reserved';

                const filteredEvents = () => {
                    const selectedFacility = facilitySelect.value;
                    if (selectedFacility === 'all') return events;

                    return events.filter((event) => eventFacilityName(event) === selectedFacility);
                };

                const eventsForDate = (date) => filteredEvents().filter((event) => {
                    if (!event.start) return false;
                    return event.start.slice(0, 10) === dateKey(date);
                });

                const eventsForDateHour = (date, hour) => eventsForDate(date).filter((event) => {
                    const eventDate = new Date(event.start);
                    return eventDate.getHours() === hour;
                });

                const eventRangeStyle = (event) => {
                    const start = event.start ? new Date(event.start) : null;
                    const end = event.end ? new Date(event.end) : null;
                    if (!start || !end || Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
                        return { top: 0, height: 40 };
                    }

                    const minutesFromHour = start.getMinutes();
                    const durationMinutes = Math.max(30, (end - start) / 60000);

                    return {
                        top: (minutesFromHour / 60) * 72,
                        height: (durationMinutes / 60) * 72,
                    };
                };

                const formatEventTime = (event) => {
                    const start = event.start ? new Date(event.start) : null;
                    const end = event.end ? new Date(event.end) : null;
                    if (!start || Number.isNaN(start.getTime())) return '';

                    const format = (date) => date.toLocaleTimeString([], {
                        hour: 'numeric',
                        minute: '2-digit',
                    });

                    return end && !Number.isNaN(end.getTime())
                        ? `${format(start)} - ${format(end)}`
                        : format(start);
                };

                const formatEventDate = (event) => {
                    const start = event.start ? new Date(event.start) : null;
                    if (!start || Number.isNaN(start.getTime())) return '';

                    return start.toLocaleDateString([], {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    });
                };

                const eventDetails = (event) => [
                    event.event && event.event !== event.title ? `Event: ${event.event}` : null,
                    event.facility ? `Facility: ${event.facility}` : null,
                    formatEventDate(event) ? `Date: ${formatEventDate(event)}` : null,
                    formatEventTime(event) ? `Time: ${formatEventTime(event)}` : null,
                    event.requester ? `Requested by: ${event.requester}` : null,
                    event.status ? `Status: ${event.status}` : null,
                ].filter(Boolean);

                const addEventTooltip = (item, event) => {
                    item.tabIndex = 0;
                    item.setAttribute('aria-label', [event.title || 'Reserved', ...eventDetails(event)].join('. '));

                    const tooltip = document.createElement('span');
                    tooltip.className = 'booking-calendar-tooltip';

                    const title = document.createElement('strong');
                    title.textContent = event.title || 'Reserved';
                    tooltip.appendChild(title);

                    eventDetails(event).forEach((detail) => {
                        const line = document.createElement('span');
                        line.textContent = detail;
                        tooltip.appendChild(line);
                    });

                    item.appendChild(tooltip);
                };

                const isoWeekNumber = (date) => {
                    const target = new Date(date.valueOf());
                    const dayNr = (date.getDay() + 6) % 7;
                    target.setDate(target.getDate() - dayNr + 3);
                    const firstThursday = target.valueOf();
                    target.setMonth(0, 1);
                    if (target.getDay() !== 4) {
                        target.setMonth(0, 1 + ((4 - target.getDay()) + 7) % 7);
                    }

                    return 1 + Math.round((firstThursday - target) / (7 * 24 * 3600 * 1000));
                };

                const renderLeft = (date) => {
                    const yearStart = new Date(date.getFullYear(), 0, 1);
                    leftWeekday.textContent = `${date.toLocaleString(undefined, { weekday: 'short' })}.`;
                    leftMonth.textContent = date.toLocaleString(undefined, { month: 'long', year: 'numeric' });
                    leftDay.textContent = date.getDate();
                    leftMeta.textContent = `Day ${Math.ceil((date - yearStart) / 86400000) + 1}, Week ${isoWeekNumber(date)}`;
                };

                const appendEvents = (cell, date) => {
                    const dayEvents = eventsForDate(date).slice(0, 3);
                    if (!dayEvents.length) return;

                    const list = document.createElement('div');
                    list.className = 'booking-calendar-events';

                    dayEvents.forEach((event) => {
                        const item = document.createElement('div');
                        item.className = 'booking-calendar-event';
                        item.textContent = event.title || 'Reserved';
                        item.style.backgroundColor = event.backgroundColor || event.color || '#009639';
                        item.style.color = event.textColor || '#ffffff';
                        addEventTooltip(item, event);
                        list.appendChild(item);
                    });

                    cell.appendChild(list);
                };

                const selectDate = (date) => {
                    selected = new Date(date);
                    renderCalendar();
                    renderLeft(selected);
                };

                const appendHeader = () => {
                    const startDay = parseInt(startDaySelect.value, 10);
                    weekdays.slice(startDay).concat(weekdays.slice(0, startDay)).forEach((weekday) => {
                        const header = document.createElement('div');
                        header.className = 'booking-calendar-header';
                        header.textContent = weekday;
                        calendarGrid.appendChild(header);
                    });
                };

                const appendCell = (date, isOut = false) => {
                    const today = new Date();
                    const cell = document.createElement('button');
                    cell.type = 'button';
                    cell.className = `booking-calendar-cell${isOut ? ' is-out' : ''}`;
                    const number = document.createElement('span');
                    number.className = 'booking-date-num';
                    number.textContent = date.getDate();

                    if (date.toDateString() === today.toDateString()) number.classList.add('is-today');
                    if (date.toDateString() === selected.toDateString()) number.classList.add('is-selected');

                    cell.appendChild(number);
                    appendEvents(cell, date);
                    cell.addEventListener('click', () => selectDate(date));
                    calendarGrid.appendChild(cell);
                };

                const renderMonthView = () => {
                    calendarGrid.innerHTML = '';
                    calendarGrid.classList.remove('is-week-view');
                    appendHeader();

                    const startDay = parseInt(startDaySelect.value, 10);
                    const year = parseInt(yearSelect.value, 10);
                    const month = parseInt(monthSelect.value, 10);
                    const first = new Date(year, month, 1);
                    const last = new Date(year, month + 1, 0);
                    const offset = (first.getDay() - startDay + 7) % 7;

                    for (let i = offset - 1; i >= 0; i--) {
                        appendCell(new Date(year, month, -i), true);
                    }

                    for (let day = 1; day <= last.getDate(); day++) {
                        appendCell(new Date(year, month, day));
                    }

                    const totalCells = offset + last.getDate();
                    const tail = (7 - (totalCells % 7)) % 7;
                    for (let day = 1; day <= tail; day++) {
                        appendCell(new Date(year, month + 1, day), true);
                    }

                    monthLabel.textContent = `${months[month]} ${year}`;
                };

                const renderWeekView = () => {
                    calendarGrid.innerHTML = '';
                    calendarGrid.classList.add('is-week-view');

                    const startDay = parseInt(startDaySelect.value, 10);
                    const delta = (selected.getDay() - startDay + 7) % 7;
                    const weekStart = new Date(selected);
                    weekStart.setDate(selected.getDate() - delta);
                    const weekDates = Array.from({ length: 7 }, (_, index) => {
                        const date = new Date(weekStart);
                        date.setDate(weekStart.getDate() + index);
                        return date;
                    });

                    const corner = document.createElement('div');
                    corner.className = 'booking-calendar-time-corner';
                    calendarGrid.appendChild(corner);

                    weekDates.forEach((date) => {
                        const header = document.createElement('button');
                        header.type = 'button';
                        header.className = 'booking-calendar-week-header';
                        if (date.toDateString() === selected.toDateString()) header.classList.add('is-selected');
                        if (date.toDateString() === new Date().toDateString()) header.classList.add('is-today');
                        header.innerHTML = `<span>${weekdays[date.getDay()]}</span><strong>${date.getDate()}</strong>`;
                        header.addEventListener('click', () => selectDate(date));
                        calendarGrid.appendChild(header);
                    });

                    for (let hour = 8; hour <= 18; hour++) {
                        const timeLabel = document.createElement('div');
                        timeLabel.className = 'booking-calendar-time-label';
                        timeLabel.textContent = new Date(2000, 0, 1, hour).toLocaleTimeString([], {
                            hour: 'numeric',
                            minute: '2-digit',
                        });
                        calendarGrid.appendChild(timeLabel);

                        weekDates.forEach((date) => {
                            const cell = document.createElement('button');
                            cell.type = 'button';
                            cell.className = 'booking-calendar-hour-cell';
                            if (date.toDateString() === selected.toDateString()) cell.classList.add('is-selected-day');
                            cell.addEventListener('click', () => selectDate(date));

                            eventsForDateHour(date, hour).forEach((event) => {
                                const item = document.createElement('div');
                                const title = document.createElement('span');
                                const time = document.createElement('small');
                                const range = eventRangeStyle(event);

                                item.className = 'booking-calendar-event is-time-range';
                                title.textContent = event.title || 'Reserved';
                                time.textContent = formatEventTime(event);
                                item.style.backgroundColor = event.backgroundColor || event.color || '#009639';
                                item.style.color = event.textColor || '#ffffff';
                                item.style.top = `${range.top}px`;
                                item.style.height = `${range.height}px`;
                                item.append(title, time);
                                addEventTooltip(item, event);
                                cell.appendChild(item);
                            });

                            calendarGrid.appendChild(cell);
                        });
                    }

                    monthLabel.textContent = `${months[weekStart.getMonth()]} ${weekStart.getFullYear()} - Week`;
                };

                const renderCalendar = () => {
                    viewMode = viewSelect.value || 'month';
                    if (viewMode === 'month') renderMonthView();
                    if (viewMode === 'week') renderWeekView();
                };

                const populateControls = () => {
                    const currentYear = new Date().getFullYear();
                    for (let year = currentYear - 5; year <= currentYear + 5; year++) {
                        const option = document.createElement('option');
                        option.value = year;
                        option.textContent = year;
                        option.selected = year === selected.getFullYear();
                        yearSelect.appendChild(option);
                    }

                    months.forEach((month, index) => {
                        const option = document.createElement('option');
                        option.value = index;
                        option.textContent = month;
                        option.selected = index === selected.getMonth();
                        monthSelect.appendChild(option);
                    });

                    [...new Set(events.map(eventFacilityName).filter(Boolean))]
                        .sort((a, b) => a.localeCompare(b))
                        .forEach((facility) => {
                            const option = document.createElement('option');
                            option.value = facility;
                            option.textContent = facility;
                            facilitySelect.appendChild(option);
                        });
                };

                prevBtn.addEventListener('click', () => {
                    if (viewMode === 'month') {
                        const month = parseInt(monthSelect.value, 10);
                        if (month === 0) {
                            monthSelect.value = 11;
                            yearSelect.value = parseInt(yearSelect.value, 10) - 1;
                        } else {
                            monthSelect.value = month - 1;
                        }
                    } else {
                        selected.setDate(selected.getDate() - 7);
                        yearSelect.value = selected.getFullYear();
                        monthSelect.value = selected.getMonth();
                        renderLeft(selected);
                    }

                    renderCalendar();
                });

                nextBtn.addEventListener('click', () => {
                    if (viewMode === 'month') {
                        const month = parseInt(monthSelect.value, 10);
                        if (month === 11) {
                            monthSelect.value = 0;
                            yearSelect.value = parseInt(yearSelect.value, 10) + 1;
                        } else {
                            monthSelect.value = month + 1;
                        }
                    } else {
                        selected.setDate(selected.getDate() + 7);
                        yearSelect.value = selected.getFullYear();
                        monthSelect.value = selected.getMonth();
                        renderLeft(selected);
                    }

                    renderCalendar();
                });

                yearSelect.addEventListener('change', renderCalendar);
                monthSelect.addEventListener('change', renderCalendar);
                startDaySelect.addEventListener('change', renderCalendar);
                viewSelect.addEventListener('change', renderCalendar);
                facilitySelect.addEventListener('change', renderCalendar);
                todayBtn.addEventListener('click', () => {
                    selected = new Date();
                    yearSelect.value = selected.getFullYear();
                    monthSelect.value = selected.getMonth();
                    renderCalendar();
                    renderLeft(selected);
                });

                populateControls();
                renderCalendar();
                renderLeft(selected);
            };

            window.initBookingCalendars = window.initBookingCalendars || function () {
                document.querySelectorAll('.booking-calendar-app[id]').forEach((calendar) => {
                    window.initBookingCalendar(calendar.id);
                });
            };

            document.addEventListener('DOMContentLoaded', window.initBookingCalendars);
            document.addEventListener('livewire:navigated', window.initBookingCalendars);
            window.addEventListener('pageshow', window.initBookingCalendars);

            if (document.readyState !== 'loading') {
                window.initBookingCalendars();
            }
        </script>

        <style>
            .booking-calendar-app {
                display: grid;
                grid-template-columns: 180px 1fr;
                gap: 18px;
                width: 100%;
            }

            .booking-calendar-side,
            .booking-calendar-panel {
                border: 1px solid rgba(6, 78, 59, 0.1);
                border-radius: 10px;
                background: #ffffff;
                padding: 18px;
                box-shadow: 0 1px 2px rgba(6, 78, 59, 0.06);
            }

            .booking-calendar-side {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .booking-date-card {
                display: flex;
                width: 100%;
                flex-direction: column;
                align-items: center;
                border-radius: 10px;
                background: #f6f8fa;
                padding: 12px 14px;
                color: #007a2f;
            }

            .booking-weekday,
            .booking-month,
            .booking-meta {
                font-size: 13px;
                color: #64748b;
            }

            .booking-day {
                margin: 6px 0;
                font-size: 44px;
                font-weight: 800;
                line-height: 1;
            }

            .booking-controls {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .booking-select-wrap {
                position: relative;
                display: inline-flex;
                min-width: 0;
            }

            .booking-select-wrap::after {
                content: "⌄";
                pointer-events: none;
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-52%);
                color: #047857;
                font-size: 16px;
                font-weight: 800;
                line-height: 1;
            }

            .booking-controls select,
            .booking-controls button,
            .booking-calendar-nav button {
                border: 1px solid #e3e7eb;
                border-radius: 8px;
                background: #ffffff;
                padding: 8px 10px;
                color: #007a2f;
                font-size: 13px;
                font-weight: 700;
            }

            .booking-controls select,
            .booking-calendar-filterbar select {
                appearance: none;
                min-width: 100%;
                padding-right: 30px;
            }

            .booking-calendar-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 12px;
            }

            .booking-calendar-filterbar {
                display: flex;
                justify-content: flex-end;
                margin-bottom: 12px;
            }

            .booking-calendar-filterbar label {
                display: grid;
                gap: 6px;
                min-width: min(100%, 260px);
                color: #64748b;
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
            }

            .booking-calendar-filterbar .booking-select-wrap {
                width: 100%;
            }

            .booking-calendar-filterbar select {
                border: 1px solid #e3e7eb;
                border-radius: 8px;
                background: #ffffff;
                padding: 9px 30px 9px 10px;
                color: #007a2f;
                font-size: 13px;
                font-weight: 700;
                text-transform: none;
            }

            .booking-calendar-title {
                color: #007a2f;
                font-size: 18px;
                font-weight: 800;
            }

            .booking-calendar-nav {
                display: flex;
                gap: 8px;
            }

            .booking-calendar-grid {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 8px;
            }

            .booking-calendar-grid.is-week-view {
                grid-template-columns: 72px repeat(7, minmax(86px, 1fr));
                gap: 0;
                overflow-x: auto;
                border: 1px solid rgba(6, 78, 59, 0.1);
                border-radius: 10px;
            }

            .booking-calendar-header {
                padding: 6px 0;
                text-align: center;
                color: #64748b;
                font-size: 13px;
                font-weight: 800;
            }

            .booking-calendar-cell {
                min-height: 86px;
                border: 0;
                border-radius: 8px;
                background: transparent;
                padding: 8px;
                text-align: left;
                cursor: pointer;
            }

            .booking-calendar-cell:hover {
                background: #ecfdf5;
            }

            .booking-calendar-cell.is-out {
                color: #b0b7bd;
            }

            .booking-date-num {
                display: inline-block;
                border-radius: 10px;
                padding: 8px 10px;
                color: #007a2f;
                font-weight: 800;
            }

            .booking-date-num.is-selected {
                border: 2px solid #0b66c2;
                background: transparent;
            }

            .booking-date-num.is-today {
                background: #0b66c2;
                color: #ffffff;
            }

            .booking-calendar-events {
                margin-top: 6px;
                display: grid;
                gap: 4px;
            }

            .booking-calendar-event {
                position: relative;
                overflow: hidden;
                border-radius: 6px;
                padding: 3px 6px;
                font-size: 11px;
                font-weight: 800;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .booking-calendar-event:hover,
            .booking-calendar-event:focus {
                z-index: 20;
                overflow: visible;
                outline: none;
            }

            .booking-calendar-tooltip {
                position: absolute;
                left: 0;
                bottom: calc(100% + 8px);
                z-index: 30;
                display: grid;
                width: max-content;
                max-width: 280px;
                gap: 4px;
                border: 1px solid rgba(6, 78, 59, 0.12);
                border-radius: 8px;
                background: #ffffff;
                padding: 10px 12px;
                color: #007a2f;
                box-shadow: 0 16px 32px rgba(6, 78, 59, 0.18);
                font-size: 12px;
                font-weight: 600;
                line-height: 1.35;
                opacity: 0;
                pointer-events: none;
                text-align: left;
                transform: translateY(4px);
                transition: opacity 150ms ease, transform 150ms ease;
                white-space: normal;
            }

            .booking-calendar-tooltip strong {
                color: #064e3b;
                font-size: 13px;
                font-weight: 900;
            }

            .booking-calendar-event:hover .booking-calendar-tooltip,
            .booking-calendar-event:focus .booking-calendar-tooltip {
                opacity: 1;
                transform: translateY(0);
            }

            .booking-calendar-event small {
                display: block;
                margin-top: 2px;
                font-size: 10px;
                font-weight: 700;
                opacity: 0.86;
            }

            .booking-calendar-event.is-time-range {
                position: absolute;
                left: 6px;
                right: 6px;
                z-index: 3;
                display: flex;
                min-height: 30px;
                flex-direction: column;
                justify-content: center;
                box-shadow: 0 8px 18px rgba(6, 78, 59, 0.16);
            }

            .booking-calendar-time-corner,
            .booking-calendar-week-header,
            .booking-calendar-time-label,
            .booking-calendar-hour-cell {
                border-bottom: 1px solid rgba(6, 78, 59, 0.1);
                border-left: 1px solid rgba(6, 78, 59, 0.1);
            }

            .booking-calendar-time-corner {
                position: sticky;
                left: 0;
                z-index: 2;
                border-left: 0;
                background: #f8fafc;
            }

            .booking-calendar-week-header {
                min-height: 54px;
                border-top: 0;
                background: #ffffff;
                padding: 8px;
                text-align: center;
                color: #007a2f;
                font-weight: 800;
            }

            .booking-calendar-week-header span,
            .booking-calendar-week-header strong {
                display: block;
            }

            .booking-calendar-week-header span {
                color: #64748b;
                font-size: 12px;
            }

            .booking-calendar-week-header strong {
                margin-top: 2px;
                font-size: 18px;
            }

            .booking-calendar-week-header.is-selected {
                background: #ecfdf5;
                box-shadow: inset 0 -3px #0b66c2;
            }

            .booking-calendar-week-header.is-today strong {
                color: #0b66c2;
            }

            .booking-calendar-time-label {
                position: sticky;
                left: 0;
                z-index: 1;
                min-height: 72px;
                border-left: 0;
                background: #f8fafc;
                padding: 10px 8px;
                color: #64748b;
                font-size: 12px;
                font-weight: 800;
                text-align: right;
            }

            .booking-calendar-hour-cell {
                position: relative;
                min-height: 72px;
                border-top: 0;
                background: #ffffff;
                padding: 6px;
                text-align: left;
                cursor: pointer;
                overflow: visible;
            }

            .booking-calendar-hour-cell:hover,
            .booking-calendar-hour-cell.is-selected-day {
                background: #ecfdf5;
            }

            .dark .booking-calendar-side,
            .dark .booking-calendar-panel {
                border-color: rgba(255, 255, 255, 0.1);
                background: #18181b;
            }

            .dark .booking-date-card {
                background: #09090b;
                color: #f4f4f5;
            }

            .dark .booking-weekday,
            .dark .booking-month,
            .dark .booking-meta,
            .dark .booking-calendar-header {
                color: #a1a1aa;
            }

            .dark .booking-calendar-title,
            .dark .booking-date-num,
            .dark .booking-controls select,
            .dark .booking-controls button,
            .dark .booking-calendar-nav button,
            .dark .booking-calendar-filterbar select {
                color: #f4f4f5;
            }

            .dark .booking-controls select,
            .dark .booking-controls button,
            .dark .booking-calendar-nav button,
            .dark .booking-calendar-filterbar select {
                border-color: rgba(255, 255, 255, 0.1);
                background: #27272a;
            }

            .dark .booking-select-wrap::after {
                color: #6ee7b7;
            }

            .dark .booking-calendar-filterbar label {
                color: #a1a1aa;
            }

            .dark .booking-calendar-cell:hover {
                background: rgba(16, 185, 129, 0.12);
            }

            .dark .booking-calendar-grid.is-week-view,
            .dark .booking-calendar-time-corner,
            .dark .booking-calendar-week-header,
            .dark .booking-calendar-time-label,
            .dark .booking-calendar-hour-cell {
                border-color: rgba(255, 255, 255, 0.1);
            }

            .dark .booking-calendar-time-corner,
            .dark .booking-calendar-time-label {
                background: #09090b;
            }

            .dark .booking-calendar-week-header,
            .dark .booking-calendar-hour-cell {
                background: #18181b;
                color: #f4f4f5;
            }

            .dark .booking-calendar-week-header span,
            .dark .booking-calendar-time-label {
                color: #a1a1aa;
            }

            .dark .booking-calendar-week-header.is-selected,
            .dark .booking-calendar-hour-cell:hover,
            .dark .booking-calendar-hour-cell.is-selected-day {
                background: rgba(16, 185, 129, 0.12);
            }

            .dark .booking-calendar-tooltip {
                border-color: rgba(255, 255, 255, 0.1);
                background: #27272a;
                color: #e4e4e7;
                box-shadow: 0 16px 32px rgba(0, 0, 0, 0.35);
            }

            .dark .booking-calendar-tooltip strong {
                color: #bbf7d0;
            }

            @media (max-width: 880px) {
                .booking-calendar-app {
                    grid-template-columns: 1fr;
                }

                .booking-calendar-cell {
                    min-height: 76px;
                    padding: 6px;
                }

                .booking-calendar-grid.is-week-view {
                    grid-template-columns: 64px repeat(7, minmax(92px, 1fr));
                }
            }
        </style>
    @endpush
@endonce
