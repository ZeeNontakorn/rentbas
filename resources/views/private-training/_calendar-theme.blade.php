<style>
    .private-calendar-theme {
        color: #334155;
    }

    .private-calendar-theme .fc-theme-standard,
    .private-calendar-theme .fc-scrollgrid,
    .private-calendar-theme .fc-list,
    .private-calendar-theme .fc-list-table {
        color: #334155;
    }

    .private-calendar-theme .fc-toolbar-title {
        color: #0f172a !important;
    }

    .private-calendar-theme .fc-col-header-cell-cushion,
    .private-calendar-theme .fc-daygrid-day-number,
    .private-calendar-theme .fc-timegrid-axis-cushion,
    .private-calendar-theme .fc-timegrid-slot-label-cushion,
    .private-calendar-theme .fc-list-day-text,
    .private-calendar-theme .fc-list-day-side-text {
        color: #475569 !important;
    }

    .private-calendar-theme .fc-list-day-cushion {
        background-color: #f1f5f9;
    }

    .private-calendar-theme .fc-event,
    .private-calendar-theme .fc-event-main,
    .private-calendar-theme .fc-event-time,
    .private-calendar-theme .fc-event-title {
        color: #fff !important;
    }

    .private-calendar-theme .fc-list-event .fc-list-event-time,
    .private-calendar-theme .fc-list-event .fc-list-event-title,
    .private-calendar-theme .fc-list-event .fc-list-event-title a {
        color: #1e293b !important;
    }

    /* Month view renders timed events as a colored dot on a white background. */
    .private-calendar-theme .fc-daygrid-dot-event,
    .private-calendar-theme .fc-daygrid-dot-event .fc-event-main,
    .private-calendar-theme .fc-daygrid-dot-event .fc-event-time,
    .private-calendar-theme .fc-daygrid-dot-event .fc-event-title {
        color: #334155 !important;
    }

    .private-calendar-theme .fc-daygrid-dot-event:hover {
        background-color: #f1f5f9;
    }

    .private-calendar-theme .fc-more-link,
    .private-calendar-theme .fc-daygrid-more-link,
    .private-calendar-theme .fc-popover-title {
        color: #334155 !important;
    }

    .private-calendar-theme .fc-button {
        min-height: 2.5rem;
        font-size: 0.8rem !important;
    }

    .private-calendar-theme .fc-event-title,
    .private-calendar-theme .fc-event-time {
        overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
        .private-calendar-theme .fc-header-toolbar {
            align-items: stretch;
            flex-direction: column;
            gap: 0.65rem;
        }

        .private-calendar-theme .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
        }

        .private-calendar-theme .fc-toolbar-title {
            font-size: 1.05rem !important;
        }

        .private-calendar-theme .fc-button {
            padding: 0.45rem 0.65rem !important;
        }
    }
</style>
