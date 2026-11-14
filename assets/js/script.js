document.addEventListener('DOMContentLoaded', function () {

    // Hamburger menu
    const tombolHamburger = document.getElementById('tombolHamburger');
    const menuLapor = document.getElementById('menuLapor');

    if (tombolHamburger && menuLapor) {
        tombolHamburger.addEventListener('click', function () {
            menuLapor.classList.toggle('tampil');
        });
    }

    // Admin sidebar
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('btn-sidebar-toggle');
    const overlay = document.getElementById('sidebar-overlay');

    if (toggle && sidebar && overlay) {

        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

    }

});

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('btn-sidebar-toggle');
    const overlay = document.getElementById('sidebar-overlay');

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
});