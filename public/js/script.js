const menu = document.querySelector(".menu-content");
const sidebarClose = document.querySelector("#sidebar-close");
const sidebar = document.querySelector(".sidebar");
const menuItems = document.querySelectorAll(".submenu-item");
const subMenuTitles = document.querySelectorAll(".submenu .menu-title");

// sidebarClose.addEventListener("click", () => {
//     sidebar.classList.toggle("close");
// });

// sidebar dropdown start

const allDropdown = document.querySelectorAll(".sidebar .side-dropdown");
const sidebarBar = document.querySelector(".sidebar");

allDropdown.forEach((item) => {
    const a = item.parentElement.querySelector("a:first-child");
    a.addEventListener("click", function (e) {
        e.preventDefault();

        if (!this.classList.contains("active")) {
            allDropdown.forEach((i) => {
                const aLink = i.parentElement.querySelector("a:first-child");
                aLink.classList.remove("active");
                i.classList.remove("show");
            });
        }

        this.classList.toggle("active");
        item.classList.toggle("show");
    });
});
