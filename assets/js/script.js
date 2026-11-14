document.addEventListener('DOMContentLoaded', function () {
  const tombol = document.getElementById('tombolHamburger');
  const menu   = document.getElementById('menuLapor');

  if (!tombol || !menu) return;

  tombol.addEventListener('click', function () {
    const terbuka = menu.classList.toggle('terbuka');
    tombol.classList.toggle('terbuka', terbuka);
    tombol.setAttribute('aria-expanded', terbuka);
  });

  // Tutup menu setelah link diklik (mobile)
  menu.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
      menu.classList.remove('terbuka');
      tombol.classList.remove('terbuka');
      tombol.setAttribute('aria-expanded', 'false');
    });
  });
});