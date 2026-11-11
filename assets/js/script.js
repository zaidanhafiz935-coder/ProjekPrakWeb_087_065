const tombolHamburger = document.getElementById('tombolHamburger');
const menuLapor = document.getElementById('menuLapor');

// Jalankan fungsi ini saat tombol hamburger diklik
tombolHamburger.addEventListener('click', () => {
    // Menambah atau menghapus class 'tampil' secara otomatis
    menuLapor.classList.toggle('tampil');
});
