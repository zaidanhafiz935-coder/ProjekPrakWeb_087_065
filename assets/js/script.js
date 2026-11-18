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

// Preview foto sebelum diunggah
function initPreviewFoto(inputId, previewId, wrapperId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const wrapper = document.getElementById(wrapperId);
    if (!input || !preview || !wrapper) return;

    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) {
            wrapper.classList.add('d-none');
            preview.removeAttribute('src');
            return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            preview.src = event.target.result;
            wrapper.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
}

// Inisialisasi Peta Interaktif Laporan (Leaflet.js)
function initPetaLaporan(elementId, data) {
    const mapEl = document.getElementById(elementId);
    if (!mapEl) return;
    if (typeof L === 'undefined') {
        mapEl.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-danger p-3 text-center">Peta gagal dimuat. Periksa koneksi internet dan muat ulang halaman.</div>';
        return;
    }

    const defaultCenter = [-7.7956, 110.3695];
    const map = L.map(elementId).setView(defaultCenter, 12);

    const fallbackTileUrl = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
    const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    let fallbackActivated = false;
    tileLayer.on('tileerror', function () {
        if (fallbackActivated) return;
        fallbackActivated = true;
        tileLayer.setUrl(fallbackTileUrl);
        tileLayer.options.attribution = '&copy; OpenStreetMap &copy; CARTO';
    });

    const markerLayer = L.layerGroup().addTo(map);

    const statusColors = {
        'Menunggu': '#ffc107',
        'Diverifikasi': '#0d6efd',
        'Diproses': '#fd7e14',
        'Selesai': '#198754',
        'Ditolak': '#dc3545'
    };

    function createCustomIcon(status) {
        const color = statusColors[status] || '#6c757d';
        return L.divIcon({
            className: 'custom-status-marker',
            html: `<span style="display:block;width:16px;height:16px;border-radius:50%;background:${color};border:2.5px solid #fff;box-shadow:0 2px 5px rgba(0,0,0,0.4);"></span>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8],
            popupAnchor: [0, -8]
        });
    }

    function renderMarkers(filteredData) {
        markerLayer.clearLayers();
        const bounds = [];

        filteredData.forEach(item => {
            const lat = parseFloat(item.latitude);
            const lng = parseFloat(item.longitude);
            if (isNaN(lat) || isNaN(lng)) return;

            const badgeColor = statusColors[item.status] || '#6c757d';
            const textColor = item.status === 'Menunggu' ? '#000' : '#fff';
            const fotoHtml = item.foto
                ? `<img src="../uploads/${item.foto}" alt="Foto Laporan" style="width:100%;max-height:110px;object-fit:cover;border-radius:6px;margin-bottom:6px;" onerror="this.style.display='none'">`
                : '';

            const popupContent = `
                <div style="min-width:200px;max-width:240px;font-size:0.85rem;">
                    ${fotoHtml}
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-semibold">${item.nomor_laporan || ''}</small>
                        <span class="badge" style="background:${badgeColor};color:${textColor};">${item.status}</span>
                    </div>
                    <strong class="d-block mb-1">${item.judul}</strong>
                    <div class="text-muted small mb-1"><i class="bi bi-tag"></i> ${item.kategori}</div>
                    <div class="text-muted small mb-1"><i class="bi bi-geo-alt"></i> ${item.alamat_lokasi || '-'}</div>
                    <div class="text-muted" style="font-size:0.75rem;"><i class="bi bi-calendar"></i> ${item.tanggal}</div>
                </div>
            `;

            L.marker([lat, lng], { icon: createCustomIcon(item.status) })
                .bindPopup(popupContent)
                .addTo(markerLayer);

            bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
        }
    }

    function applyFilter() {
        const keyword = (document.getElementById('cari-laporan')?.value || '').toLowerCase().trim();
        const status = document.getElementById('filter-status')?.value || '';
        const kategori = document.getElementById('filter-kategori')?.value || '';

        const filtered = data.filter(item => {
            const matchJudul = !keyword || (item.judul && item.judul.toLowerCase().includes(keyword));
            const matchStatus = !status || item.status === status;
            const matchKategori = !kategori || item.kategori === kategori;
            return matchJudul && matchStatus && matchKategori;
        });

        renderMarkers(filtered);
    }

    document.getElementById('cari-laporan')?.addEventListener('input', applyFilter);
    document.getElementById('filter-status')?.addEventListener('change', applyFilter);
    document.getElementById('filter-kategori')?.addEventListener('change', applyFilter);

    renderMarkers(data);
}

// Inisialisasi Peta Pemilihan Lokasi
function initPetaPilih(elementId, latitudeId, longitudeId, addressId) {
    const mapEl = document.getElementById(elementId);
    if (!mapEl) return;

    if (typeof L === 'undefined') {
        mapEl.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-danger p-3 text-center">Peta gagal dimuat. Anda tetap dapat mengisi alamat secara manual.</div>';
        return;
    }

    const latitudeInput = document.getElementById(latitudeId);
    const longitudeInput = document.getElementById(longitudeId);
    const addressInput = document.getElementById(addressId);
    const locationButton = document.getElementById('btn-lokasi-saya');
    const initialLat = parseFloat(latitudeInput?.value);
    const initialLng = parseFloat(longitudeInput?.value);
    const hasInitialLocation = Number.isFinite(initialLat) && Number.isFinite(initialLng);
    const defaultCenter = [-7.7956, 110.3695];

    const map = L.map(elementId).setView(
        hasInitialLocation ? [initialLat, initialLng] : defaultCenter,
        hasInitialLocation ? 16 : 12
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = null;

    async function updateAddress(lat, lng) {
        if (!addressInput) return;
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`
            );
            if (!response.ok) throw new Error('Reverse geocoding gagal');
            const result = await response.json();
            addressInput.value = result.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        } catch (error) {
            addressInput.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
    }

    function setLocation(lat, lng, updateAddressText = true) {
        const position = [lat, lng];
        if (latitudeInput) latitudeInput.value = lat.toFixed(8);
        if (longitudeInput) longitudeInput.value = lng.toFixed(8);

        if (!marker) {
            marker = L.marker(position, { draggable: true }).addTo(map);
            marker.on('dragend', function (event) {
                const point = event.target.getLatLng();
                setLocation(point.lat, point.lng);
            });
        } else {
            marker.setLatLng(position);
        }

        map.setView(position, 16);
        if (updateAddressText) updateAddress(lat, lng);
    }

    map.on('click', function (event) {
        setLocation(event.latlng.lat, event.latlng.lng);
    });

    if (hasInitialLocation) {
        setLocation(initialLat, initialLng, false);
    }

    if (locationButton) {
        locationButton.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('Browser tidak mendukung GPS/location.');
                return;
            }

            locationButton.disabled = true;
            locationButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mencari lokasi...';

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    setLocation(position.coords.latitude, position.coords.longitude);
                    locationButton.disabled = false;
                    locationButton.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i>Gunakan Lokasi Saya';
                },
                function (error) {
                    const messages = {
                        1: 'Izin lokasi ditolak. Aktifkan izin lokasi di browser.',
                        2: 'Lokasi tidak tersedia. Periksa GPS/perangkat Anda.',
                        3: 'Permintaan lokasi terlalu lama. Coba lagi.'
                    };
                    alert(messages[error.code] || 'Gagal mendapatkan lokasi.');
                    locationButton.disabled = false;
                    locationButton.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i>Gunakan Lokasi Saya';
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        });
    }

    setTimeout(function () {
        map.invalidateSize();
    }, 100);
}