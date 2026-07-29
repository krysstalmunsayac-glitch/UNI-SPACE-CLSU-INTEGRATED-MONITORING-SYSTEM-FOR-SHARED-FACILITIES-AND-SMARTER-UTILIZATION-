<style>
    .admin-booking-calendar {
        display: grid;
        grid-template-columns: 220px minmax(0, 1fr);
        width: 100%;
        max-width: 100%;
        border-radius: 1rem;
    }

    #fc-calendar,
    #fc-calendar .fc,
    #fc-calendar .fc-view-harness,
    #fc-calendar .fc-view-harness-active {
        min-width: 0;
        max-width: 100%;
    }

    .admin-booking-sidebar {
        border-right: 1px solid rgb(209 250 229);
        background: rgb(236 253 245);
        padding: 1.25rem;
    }

    .admin-booking-date-card {
        margin-bottom: 1.25rem;
        border: 1px solid rgb(209 250 229);
        border-radius: 0.875rem;
        background: #fff;
        padding: 1.25rem;
        text-align: center;
        box-shadow: 0 10px 25px rgba(6, 78, 59, 0.06);
    }

    .admin-calendar-control {
        width: 100%;
        border: 1px solid rgb(209 250 229);
        border-radius: 0.625rem;
        background: #fff;
        padding: 0.7rem 0.8rem;
        color: rgb(6 78 59);
        font-size: 0.8125rem;
        font-weight: 700;
        transition: 150ms ease;
    }

    .admin-calendar-control:hover,
    .admin-calendar-control.is-active {
        border-color: rgb(5 150 105);
        background: rgb(6 95 70);
        color: #fff;
    }

    #fc-calendar .fc {
        --fc-border-color: rgb(229 231 235);
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: rgb(249 250 251);
        --fc-today-bg-color: rgba(16, 185, 129, 0.08);
        color: rgb(17 24 39);
        font-size: 0.875rem;
    }

    #fc-calendar .fc-toolbar {
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    #fc-calendar .fc-toolbar-title {
        color: rgb(17 24 39);
        font-size: 1.15rem;
        font-weight: 700;
    }

    #fc-calendar .fc-button {
        border-radius: 0.5rem;
        border-color: rgb(209 213 219);
        background: rgb(255 255 255);
        box-shadow: none;
        color: rgb(55 65 81);
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 0.45rem 0.7rem;
        text-transform: capitalize;
    }

    #fc-calendar .fc-button:hover,
    #fc-calendar .fc-button:focus {
        border-color: rgb(16 185 129);
        background: rgb(236 253 245);
        color: rgb(6 95 70);
    }

    #fc-calendar .fc-button-primary:disabled {
        border-color: rgb(229 231 235);
        background: rgb(243 244 246);
        color: rgb(107 114 128);
        opacity: 1;
    }

    #fc-calendar .fc-col-header-cell {
        background: rgb(249 250 251);
        padding: 0.4rem 0;
    }

    #fc-calendar .fc-col-header-cell-cushion {
        color: rgb(75 85 99);
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        text-transform: uppercase;
    }

    #fc-calendar .fc-daygrid-day-number,
    #fc-calendar .fc-timegrid-slot-label-cushion {
        color: rgb(75 85 99);
        text-decoration: none;
    }

    #fc-calendar .fc-timegrid-slot {
        height: 2.6rem;
    }

    #fc-calendar .fc-timegrid-axis,
    #fc-calendar .fc-timegrid-slot-label {
        background: rgb(249 250 251);
    }

    #fc-calendar .fc-day-today .fc-daygrid-day-number {
        color: rgb(4 120 87);
        font-weight: 800;
    }

    #fc-calendar .fc-event {
        border-radius: 0.5rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
        font-size: 0.75rem;
        font-weight: 650;
        line-height: 1.2;
        padding: 2px 5px;
    }

    #fc-calendar .fc-daygrid-event {
        margin: 2px 4px;
    }

    #fc-calendar .fc-event-title,
    #fc-calendar .fc-event-time {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #fc-calendar .fc-scrollgrid {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .dark #fc-calendar .fc {
        --fc-border-color: rgb(55 65 81);
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: rgb(31 41 55);
        --fc-list-event-hover-bg-color: rgb(55 65 81);
        --fc-today-bg-color: rgba(16, 185, 129, 0.12);
        color: rgb(229 231 235);
    }

    .dark #fc-calendar .fc-toolbar-title {
        color: rgb(244 244 245);
    }

    .dark #fc-calendar .fc-col-header-cell,
    .dark #fc-calendar .fc-timegrid-axis,
    .dark #fc-calendar .fc-timegrid-slot-label {
        background: rgb(24 24 27);
    }

    .dark #fc-calendar .fc-col-header-cell-cushion,
    .dark #fc-calendar .fc-daygrid-day-number,
    .dark #fc-calendar .fc-timegrid-slot-label-cushion {
        color: rgb(209 213 219);
    }

    .dark #fc-calendar .fc-button {
        background-color: rgb(39 39 42);
        border-color: rgb(63 63 70);
        color: rgb(229 231 235);
    }

    .dark #fc-calendar .fc-button:hover,
    .dark #fc-calendar .fc-button:focus {
        background-color: rgba(6, 78, 59, 0.55);
        border-color: rgb(16 185 129);
        color: rgb(209 250 229);
    }

    .dark #fc-calendar .fc-button-primary:disabled {
        background-color: rgb(39 39 42);
        border-color: rgb(63 63 70);
        color: rgb(161 161 170);
    }

    .dark #fc-calendar .fc-event {
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
    }

    .dark .admin-booking-sidebar {
        border-color: rgb(63 63 70);
        background: rgb(24 24 27);
    }

    .dark .admin-booking-date-card,
    .dark .admin-calendar-control {
        border-color: rgb(63 63 70);
        background: rgb(9 9 11);
    }

    .dark .admin-calendar-control {
        color: rgb(167 243 208);
    }

    .dark .admin-calendar-control:hover,
    .dark .admin-calendar-control.is-active {
        border-color: rgb(52 211 153);
        background: rgb(6 95 70);
        color: #fff;
    }

    @media (max-width: 900px) {
        .admin-booking-calendar {
            grid-template-columns: 1fr;
        }

        .admin-booking-sidebar {
            border-right: 0;
            border-bottom: 1px solid rgb(209 250 229);
        }

        .dark .admin-booking-sidebar {
            border-bottom-color: rgb(63 63 70);
        }
    }

    @media (max-width: 640px) {
        #fc-calendar .fc-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        #fc-calendar .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
        }

        #fc-calendar .fc-toolbar-title {
            font-size: 1rem;
            text-align: center;
        }
    }
</style>
