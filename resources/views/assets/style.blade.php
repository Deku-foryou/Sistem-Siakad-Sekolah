{{-- Jquery --}}
<script src="{{ asset('asset_offline/js/jquery-3.6.0.min.js') }}"></script>
<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
{{-- highchart --}}
<script src="{{ asset('highcharts/code/highcharts.js') }}"></script>
<script src="{{ asset('highcharts/code/modules/data.js') }}"></script>
<script src="{{ asset('highcharts/code/modules/drilldown.js') }}"></script>
<script src="{{ asset('highcharts/code/modules/exporting.js') }}"></script>
<script src="{{ asset('highcharts/code/modules/export-data.js') }}"></script>
<script src="{{ asset('highcharts/code/modules/accessibility.js') }}"></script>
<!-- Style 1-->
<link href="{{ asset('css/main.css') }}" rel="stylesheet">
<!-- Vendors styles-->
<link href="{{ asset('css/simplebar.css') }}" rel="stylesheet">
{{-- Icon Font Awesome --}}
<link href="{{ asset('asset_offline/fontawesome/css/all.css') }}" rel="stylesheet">
{{-- SweatAlert --}}
<script src="{{ asset('asset_offline/js/sweetalert2.js') }}"></script>
{{-- font --}}
<link rel="stylesheet" href="{{ asset('asset_offline/font/font.css') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap"
    rel="stylesheet">

<x-livewire-alert::scripts />




<style>
    /* Style Icon */
    * {
        font-size: 14px;
    }

    thead {
        color: #fff;
        background: #0e7d6a;
        /* border-top: 3px solid #0E7D6A; */
        text-transform: capitalize;
    }

    .label {
        font-size: 13px;
        font-weight: bold;
        text-transform: capitalize;
        color: #333333;
    }

    .modal-header {
        background: #0e7d6a;
        /* border-top: 3px solid #0E7D6A; */
        color: #fff;
        padding: 5px 10px;
    }

    .modal-header h5 {
        font-size: 16px;
    }


    .btn-primary {
        background: #0C8C57;
        color: white;
        border: none;
    }

    .btn-primary:hover {
        background: #16a66a;
    }


    .text-badge {
        color: #7e0a9b;
        background: #f9e2ff;
        border: 1px solid #eea9ff;
        font-weight: 500;
    }


    .loading-spinner {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
    }

    /* HTML: <div class="loader"></div> */
    .loader {
        width: 50px;
        aspect-ratio: 1;
        --_c: no-repeat radial-gradient(farthest-side, #50128A 92%, #0000);
        background:
            var(--_c) top,
            var(--_c) left,
            var(--_c) right,
            var(--_c) bottom;
        background-size: 12px 12px;
        animation: l7 1s infinite;
    }

    @keyframes l7 {
        to {
            transform: rotate(.5turn)
        }
    }



    .loading-spinner.loading-show {
        display: block;
        /* background-color: rgba(255, 255, 255, 0.7); */
    }


    /* The entire scrollbar */
    ::-webkit-scrollbar {
        width: 5px;
        /* Width of the scrollbar */
        height: 5px;
        /* Height of the scrollbar (for horizontal scrollbar) */
    }

    /* The scrollbar track */
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        /* Background color of the track */
    }

    /* The scrollbar handle (thumb) */
    ::-webkit-scrollbar-thumb {
        background: #bebebe;
        /* Background color of the handle */
        border-radius: 2px;
        /* Rounded corners */
    }

    /* The scrollbar handle (thumb) when hovered */
    ::-webkit-scrollbar-thumb:hover {
        background: #b5b5b5;
        /* Background color when hovered */
    }


    /* SIdebar */

    /* Warna text HEader */
    ul li.nav-title {
        color: #0e7d6a !important;
        font-size: 15px;
        font-weight: 700;
    }


    /* Warna sidebar Head */
    .sidebar-brand {
        background: #ffffff;
        padding: 20px;
    }

    /* Warna sidebar All */
    .sidebar_bg {
        background: #ffffff;
        overflow: auto;
    }

    .padding_sidebar {
        padding: 0px 13px;
    }

    /* Warna sidebar text active */
    .sidebar_bg . .nav-link.active {
        color: white;
        font-weight: bold;
        font-size: 16px;
    }

    /* Warna sidebar text logo active */
    .sidebar_bg . .nav-link.active>i {
        color: white;
    }

    /* Warna sidebar active */
    .sidebar_bg .nav-link.active {
        background: #16a66a;
        font-weight: 500;
        border-radius: 10px;
    }

    /* Warna sidebar Group */
    .sidebar-nav .nav-group.show .nav-group-toggle {
        background: #e2e2e2;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        font-weight: 500;
        color: white;
    }

    /* Warna sidebar Group */
    .sidebar-nav .nav-group.show {
        background: white;
    }

    /* Warna sidebar Group All Items */
    .sidebar_bg .nav-group-items . {
        background: white;
    }

    /* Warna sidebar Group Items Focus */
    .sidebar_bg . .nav-link.sidebar_items_text.active {
        background: #198a19;
        border-radius: 0;
    }


    .sidebar_bg . .nav-link {
        color: white;
        padding: 10px 20px;
        margin: 2px 0px
    }

    .sidebar_bg .nav-group .nav-link {

        color: white;
    }

    .sidebar-nav .nav-group-toggle::after {
        filter: invert(100%);
    }

    .inActive {
        color: red;
    }

    .sidebar-nav .nav-link {
        display: flex;
        flex: 1;
        align-items: center;
        padding: 5px 10px;
        color: #6b7280;
        text-decoration: none;
        white-space: nowrap;
        background: var(--cui-sidebar-nav-link-bg);
        border: var(--cui-sidebar-nav-link-border);
        border-radius: var(--cui-sidebar-nav-link-border-radius);
        transition: background 0.15s ease, color 0.15s ease;
    }

    /* Style Icon */
    .icon_sidebar {
        width: 17px;
        height: 17px;
        filter: invert(46%) sepia(7%) saturate(602%) hue-rotate(176deg) brightness(94%) contrast(85%);
    }

    /* Active state: icon image turns green */
    .nav-link.active .icon_sidebar,
    .nav-item.active .nav-link .icon_sidebar {
        filter: invert(33%) sepia(62%) saturate(401%) hue-rotate(130deg) brightness(82%) contrast(91%);
    }

    .icon_page {
        width: 19px;
        height: 19px;
    }

    .icon_box {
        transition: ease-in-out 0.3s;
    }

    .icon_box:hover {
        filter: invert(1);
        border: none;
    }
</style>