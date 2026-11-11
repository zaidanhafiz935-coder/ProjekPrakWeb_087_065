// --- HAMBURGER MENU ---
const tombolHamburger = document.getElementById('tombolHamburger');
const menuLapor = document.getElementById('menuLapor');

if (tombolHamburger && menuLapor) {
    tombolHamburger.addEventListener('click', () => {
        menuLapor.classList.toggle('tampil');
    });
}

// --- CAROUSEL 3D COVERFLOW ---
const slides = Array.from(document.querySelectorAll('.carousel-slide'));
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const carouselContainer = document.getElementById('carouselContainer');

let currentIndex = 0;
let startX = 0;
let isDragging = false;

function updateCarousel() {
    slides.forEach((slide, index) => {
        slide.className = 'carousel-slide';

        if (index === currentIndex) {
            slide.classList.add('active');
        } else if (index === (currentIndex - 1 + slides.length) % slides.length) {
            slide.classList.add('prev');
        } else if (index === (currentIndex + 1) % slides.length) {
            slide.classList.add('next');
        }
    });
}

function showNext() {
    currentIndex = (currentIndex + 1) % slides.length;
    updateCarousel();
}

function showPrev() {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    updateCarousel();
}

// Tombol Navigasi
if (nextBtn) nextBtn.addEventListener('click', showNext);
if (prevBtn) prevBtn.addEventListener('click', showPrev);

// Klik Slide Samping untuk Pindah
slides.forEach((slide, index) => {
    slide.addEventListener('click', () => {
        if (index !== currentIndex) {
            currentIndex = index;
            updateCarousel();
        }
    });
});

// Gesture Drag (Mouse)
if (carouselContainer) {
    carouselContainer.addEventListener('mousedown', (e) => {
        isDragging = true;
        startX = e.clientX;
    });

    carouselContainer.addEventListener('mouseup', (e) => {
        if (!isDragging) return;
        const diffX = e.clientX - startX;
        if (diffX > 40) showPrev();
        else if (diffX < -40) showNext();
        isDragging = false;
    });

    carouselContainer.addEventListener('mouseleave', () => {
        isDragging = false;
    });

    // Gesture Touch (HP)
    carouselContainer.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    });

    carouselContainer.addEventListener('touchend', (e) => {
        const endX = e.changedTouches[0].clientX;
        const diffX = endX - startX;
        if (diffX > 40) showPrev();
        else if (diffX < -40) showNext();
    });
}

// Inisialisasi
updateCarousel();