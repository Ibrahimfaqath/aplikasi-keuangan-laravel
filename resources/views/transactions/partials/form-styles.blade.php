<style>
    body { overflow-x: hidden; }
    [x-cloak] { display: none !important; }

    .select-field {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23737373' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.25em 1.25em;
        padding-right: 2.5rem !important;
    }
    .dark .select-field {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23A3A3A3' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    }
    .date-field {
        position: relative;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23737373' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
        background-position: right 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.15em 1.15em;
        padding-right: 2.5rem !important;
    }
    .dark .date-field {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23A3A3A3' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
    }
    /* Satu ikon saja: indikator bawaan browser dibuat transparan
       tetapi tetap mencakup area klik kanan agar date picker native jalan */
    .date-field::-webkit-calendar-picker-indicator {
        opacity: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: 2.75rem;
        height: 100%;
        cursor: pointer;
    }
    .btn-upload.active {
        background-color: #111111 !important;
        border-color: #111111 !important;
        color: #ffffff !important;
    }
    .dark .btn-upload.active {
        background-color: #FAFAFA !important;
        border-color: #FAFAFA !important;
        color: #0A0A0A !important;
    }
    .cat-chip.active {
        background-color: #111111 !important;
        border-color: #111111 !important;
        color: #ffffff !important;
        box-shadow: 0 1px 2px rgb(0 0 0 / 0.15);
    }
    .dark .cat-chip.active {
        background-color: #FAFAFA !important;
        border-color: #FAFAFA !important;
        color: #0A0A0A !important;
    }
    .cat-chip.active > span:first-child {
        background-color: rgba(255, 255, 255, 0.16) !important;
        color: #ffffff !important;
    }
    .dark .cat-chip.active > span:first-child {
        background-color: rgba(0, 0, 0, 0.08) !important;
        color: #0A0A0A !important;
    }
</style>
