const tombolHamburger = document.getElementById('tombolHamburger');
const menuLapor = document.getElementById('menuLapor');

// Jalankan fungsi ini saat tombol hamburger diklik
tombolHamburger.addEventListener('click', () => {
    // Menambah atau menghapus class 'tampil' secara otomatis
    menuLapor.classList.toggle('tampil');
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