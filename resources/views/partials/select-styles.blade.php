<style>
    :root {
        --select-picker-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%23047857' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
    }

    select:not([multiple]):not([size]),
    select:not([multiple])[size="1"],
    .select-picker-trigger {
        appearance: none;
        background-image: var(--select-picker-icon) !important;
        background-repeat: no-repeat !important;
        background-position: right 0.875rem center !important;
        background-size: 1rem 1rem !important;
        padding-right: 2.75rem !important;
    }

    /* Keep native selection and form bindings, with the Schedule list appearance. */
    @supports (appearance: base-select) {
        select:not([multiple]):not([size]),
        select[size="1"] {
            appearance: base-select !important;
            display: inline-flex;
            align-items: center;
            text-align: left;
        }

        select:not([multiple]):not([size])::picker(select),
        select[size="1"]::picker(select) {
            appearance: base-select;
            max-height: calc(12rem + 4px);
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 0;
            margin-block: 4px;
            border: 2px solid #009639;
            border-radius: 10px;
            background: #fff;
            color: #18181b;
            box-shadow: 0 8px 18px rgb(0 0 0 / 14%);
            font-size: 14px;
            font-weight: 400;
            scrollbar-width: auto;
            scrollbar-color: #8b8b8b #f5f5f5;
        }

        select:not([multiple]):not([size]) option,
        select[size="1"] option {
            min-height: 2rem;
            padding: 0.375rem 0.75rem;
            line-height: 1.25rem;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        select:not([multiple]):not([size]) option:checked,
        select:not([multiple]):not([size]) option:not(:disabled):hover,
        select[size="1"] option:checked,
        select[size="1"] option:not(:disabled):hover {
            background: #1967d2;
            color: #fff;
        }

        select option::checkmark {
            display: none;
        }

        /* The shared background icon is the only dropdown arrow. */
        select::picker-icon {
            display: none;
        }

        .dark select:not([multiple]):not([size])::picker(select),
        .dark select[size="1"]::picker(select) {
            background: #18181b;
            color: #f4f4f5;
            scrollbar-color: #71717a #27272a;
        }
    }
</style>
