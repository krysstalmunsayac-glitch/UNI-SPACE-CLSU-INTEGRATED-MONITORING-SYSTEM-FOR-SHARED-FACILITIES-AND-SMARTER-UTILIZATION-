<style>
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
