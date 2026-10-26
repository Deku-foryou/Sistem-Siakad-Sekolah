<style>
    .header .dropdown-toggle::after {
        display: none;
    }

    .dropdown-content {
        display: none;
        border-radius: 10px;
        position: absolute;
        background-color: #ffffff;
        min-width: 210px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        z-index: 1000;
        right: 0;
        left: auto;
        padding: 8px;
    }

    .dropdown-content .dropdown-item {
        font-size: 13px;
        border-radius: 6px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        color: #4b5563;
        text-decoration: none;
    }

    .dropdown-content .dropdown-item:hover {
        background-color: #f3f4f6;
        color: #111827;
    }

    .dropdown-content .dropdown-item.text-danger:hover {
        background-color: #fff0f0;
    }

    .show {
        display: block;
    }
</style>

<header class="header border-0 border-bottom pt-3 pb-2"
    style="background: #fff; z-index: 99; border-bottom: 1px solid #f3f4f6 !important;">
    <div class="container-fluid px-4">
        <button class="header-toggler d-flex justify-content-start align-items-center" type="button"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"
            style="color: #6b7280; outline: none; box-shadow: none;">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>

        <ul class="header-nav ms-auto d-flex align-items-center gap-3">

            <!-- Notification Bell -->
            <div class="position-relative me-3" style="cursor: pointer;">
                <i class="fa-regular fa-bell text-secondary" style="font-size: 18px;"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    style="font-size: 8px; border: 2px solid #fff;">
                    2
                </span>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <button onclick="myFunction()"
                    class="btn btn-link p-0 d-flex align-items-center text-decoration-none shadow-none dropdown-toggle"
                    style="outline: none;">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=f15833&color=fff&rounded=true"
                        alt="Profile" style="display: block; width: 35px; height: 35px; border-radius: 50%;">
                </button>
                <div id="myDropdown" class="dropdown-content" style="margin-top: 10px;">
                    <div class="px-3 py-2">
                        <div class="fw-semibold text-muted mb-1" style="font-size: 11px;">Account</div>
                        <div class="d-flex align-items-center">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=0e7d6a&color=fff&rounded=true"
                                alt="" style="border-radius: 50%; width: 35px; height: 35px;">
                            <div class="ms-2">
                                <div class="fw-bold" style="font-size: 13px; color: #111827;">{{ Auth::user()->name }}
                                </div>
                                <div style="font-size: 11px; color: #6b7280;">{{ Auth::user()->email }}</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-1 mx-2">
                    <a href="/setting" class="dropdown-item">
                        <i class="fa-solid fa-user-gear me-2 text-muted"></i> Setting
                    </a>
                    <form action="/logout" method="POST" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger w-100"
                            style="background: transparent; border: none;">
                            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </ul>
    </div>
</header>

<script>
    function myFunction() {
        document.getElementById("myDropdown").classList.toggle("show");
    }

    window.onclick = function (event) {
        if (!event.target.closest('.dropdown')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            var i;
            for (i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>