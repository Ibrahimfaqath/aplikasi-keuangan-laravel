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
        padding-right: 2.75rem !important;
    }
    /* Satu ikon (SVG elemen) di kanan lewat wrapper .relative di form-fields.
       Indikator bawaan browser dibuat transparan tetapi tetap mencakup area
       klik kanan agar date picker native jalan (Chrome/Safari). */
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
