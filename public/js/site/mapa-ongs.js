/**
 * Mapa publico de ONG y selector de ubicacion del panel.
 *
 * Leaflet se carga solo en las paginas que lo necesitan (ver
 * @push('scripts') en las vistas). Si el CDN no responde, el mapa
 * muestra un aviso y la lista de ONG sigue siendo navegable.
 */
(function () {
    'use strict';

    const CUSCO = { lat: -13.53195, lng: -71.96746 };

    function leerMarcadores(contenedor) {
        const fuente = contenedor.querySelector('[data-ongs-markers]')
            || contenedor.parentElement?.querySelector('[data-ongs-markers]');

        if (!fuente) return [];

        try {
            return JSON.parse(fuente.textContent || '[]');
        } catch (error) {
            return [];
        }
    }

    function crearMapa(contenedor) {
        if (!window.L) {
            contenedor.classList.add('map-sin-leaflet');
            contenedor.innerHTML = '<p class="map-aviso">No pudimos cargar el mapa interactivo. Revisa la conexion e intentalo de nuevo.</p>';
            return null;
        }

        const lat = parseFloat(contenedor.dataset.centerLat) || CUSCO.lat;
        const lng = parseFloat(contenedor.dataset.centerLng) || CUSCO.lng;
        const zoom = parseInt(contenedor.dataset.zoom, 10) || 13;

        const mapa = L.map(contenedor, {
            center: [lat, lng],
            zoom,
            scrollWheelZoom: false,
            zoomControl: true,
        });

        contenedor.querySelectorAll('.map-aviso').forEach((aviso) => aviso.remove());

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
        }).addTo(mapa);

        return mapa;
    }

    function iconoOng(ong, activo) {
        const contenido = ong.logo
            ? '<img src="' + escapar(ong.logo) + '" alt="" loading="lazy" onerror="this.remove()">'
            : '<b>' + escapar(ong.iniciales || 'ONG') + '</b>';

        return L.divIcon({
            className: 'ong-marker' + (activo ? ' active' : ''),
            html: '<span class="ong-marker-pin">' + contenido + '</span>',
            iconSize: [46, 56],
            iconAnchor: [23, 54],
            popupAnchor: [0, -48],
        });
    }

    function popupOng(ong) {
        const contenedor = document.createElement('div');
        contenedor.className = 'ong-popup';

        const titulo = document.createElement('strong');
        titulo.textContent = ong.nombre;
        contenedor.appendChild(titulo);

        const meta = document.createElement('small');
        meta.textContent = [ong.sector, ong.ciudad].filter(Boolean).join(' · ');
        contenedor.appendChild(meta);

        if (ong.oportunidades > 0) {
            const oportunidades = document.createElement('span');
            oportunidades.className = 'ong-popup-count';
            oportunidades.textContent = ong.oportunidades + ' oportunidad' + (ong.oportunidades === 1 ? '' : 'es') + ' abierta' + (ong.oportunidades === 1 ? '' : 's');
            contenedor.appendChild(oportunidades);
        }

        if (ong.url) {
            const enlace = document.createElement('a');
            enlace.href = ong.url;
            enlace.textContent = 'Ver su ficha';
            contenedor.appendChild(enlace);
        }

        return contenedor;
    }

    function escapar(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, (caracter) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        })[caracter]);
    }

    function iniciarMapa(contenedor) {
        const mapa = crearMapa(contenedor);
        if (!mapa) return;

        const marcadores = leerMarcadores(contenedor);
        const autoFit = contenedor.dataset.autoFit !== 'false';
        const explorador = contenedor.closest('[data-ongs-explorer]') || document;
        const capa = L.layerGroup().addTo(mapa);
        const porId = new Map();

        const items = Array.from(explorador.querySelectorAll('[data-ong-item]'));

        function activar(id, hacerScroll) {
            items.forEach((item) => {
                const activo = item.dataset.ongItem === String(id);
                item.classList.toggle('active', activo);

                if (activo && hacerScroll) {
                    item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });

            const marcador = porId.get(String(id));
            if (marcador) {
                mapa.flyTo(marcador.getLatLng(), Math.max(mapa.getZoom(), 14), { duration: 0.7 });
                marcador.openPopup();
            }
        }

        marcadores.forEach((ong) => {
            const marcador = L.marker([ong.lat, ong.lng], {
                icon: iconoOng(ong, false),
                title: ong.nombre,
            });

            marcador.bindPopup(popupOng(ong), { closeButton: true, minWidth: 190 });
            marcador.addTo(capa);
            porId.set(String(ong.id), marcador);

            marcador.on('click', () => activar(ong.id, true));

            const item = items.find((nodo) => nodo.dataset.ongItem === String(ong.id));
            if (item) {
                item.addEventListener('mouseenter', () => {
                    if (!window.matchMedia('(hover: hover)').matches) return;
                    const actual = porId.get(String(ong.id));
                    if (actual) actual.setIcon(iconoOng(ong, true));
                });
                item.addEventListener('mouseleave', () => {
                    const actual = porId.get(String(ong.id));
                    if (actual) actual.setIcon(iconoOng(ong, false));
                });
            }
        });

        if (autoFit) {
            // En el directorio conviven ONG de Cusco y de otras regiones:
            // el primer encuadre prioriza la red cusqueña y deja Lima
            // disponible al alejar el mapa.
            const enCusco = marcadores.filter((ong) => (ong.ciudad || '').startsWith('Cusco'));
            const paraAjustar = enCusco.length > 1 ? enCusco : marcadores;

            if (paraAjustar.length > 1) {
                const limites = L.latLngBounds(paraAjustar.map((ong) => [ong.lat, ong.lng]));
                mapa.fitBounds(limites, { padding: [46, 46], maxZoom: 13 });
            }
        }

        // Filtro en vivo: busca por nombre, sector o ciudad y sincroniza
        // los puntos del mapa con las tarjetas visibles.
        const buscador = explorador.querySelector('[data-ongs-search]');
        const chips = Array.from(explorador.querySelectorAll('[data-ongs-sector]'));
        const vacio = explorador.querySelector('[data-ongs-empty]');
        let sectorActivo = '';

        function aplicarFiltros() {
            const texto = (buscador?.value || '').trim().toLowerCase();
            let visibles = 0;

            items.forEach((item) => {
                const coincideTexto = !texto || (item.dataset.search || '').includes(texto);
                const coincideSector = !sectorActivo || item.dataset.sector === sectorActivo;
                const visible = coincideTexto && coincideSector;

                item.hidden = !visible;
                if (visible) visibles++;

                const id = item.dataset.ongItem;
                const marcador = porId.get(id);

                if (!marcador) return;

                if (visible && !capa.hasLayer(marcador)) {
                    capa.addLayer(marcador);
                } else if (!visible && capa.hasLayer(marcador)) {
                    capa.removeLayer(marcador);
                }
            });

            if (vacio) vacio.hidden = visibles !== 0;

            const visiblesEnMapa = Array.from(porId.values()).filter((marcador) => capa.hasLayer(marcador));
            if (visiblesEnMapa.length > 1) {
                mapa.fitBounds(L.latLngBounds(visiblesEnMapa.map((marcador) => marcador.getLatLng())), {
                    padding: [46, 46],
                    maxZoom: 14,
                });
            }
        }

        buscador?.addEventListener('input', aplicarFiltros);

        chips.forEach((chip) => {
            chip.addEventListener('click', () => {
                sectorActivo = chip.dataset.ongsSector || '';

                chips.forEach((otro) => {
                    otro.classList.toggle('active', otro === chip);
                    otro.setAttribute('aria-pressed', otro === chip ? 'true' : 'false');
                });

                aplicarFiltros();
            });
        });

        explorador.querySelector('[data-ongs-recenter]')?.addEventListener('click', () => {
            mapa.flyTo([CUSCO.lat, CUSCO.lng], 13, { duration: 0.8 });
        });
    }

    function iniciarSelector(contenedor) {
        if (!window.L) {
            contenedor.classList.add('map-sin-leaflet');
            contenedor.innerHTML = '<p class="map-aviso">El selector de mapa necesita conexion a internet.</p>';
            return;
        }

        const latInput = document.querySelector(contenedor.dataset.latInput);
        const lngInput = document.querySelector(contenedor.dataset.lngInput);
        const pista = document.querySelector(contenedor.dataset.hint || '[data-ongs-picker-hint]');

        const latInicial = parseFloat(latInput?.value);
        const lngInicial = parseFloat(lngInput?.value);
        const tienePunto = Number.isFinite(latInicial) && Number.isFinite(lngInicial);

        const mapa = L.map(contenedor, {
            center: tienePunto ? [latInicial, lngInicial] : [CUSCO.lat, CUSCO.lng],
            zoom: tienePunto ? 15 : 13,
            scrollWheelZoom: false,
        });

        contenedor.querySelectorAll('.map-aviso').forEach((aviso) => aviso.remove());

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(mapa);

        const marcador = L.marker(
            tienePunto ? [latInicial, lngInicial] : [CUSCO.lat, CUSCO.lng],
            { draggable: true },
        ).addTo(mapa);

        function guardar(posicion) {
            if (latInput) latInput.value = posicion.lat.toFixed(7);
            if (lngInput) lngInput.value = posicion.lng.toFixed(7);
            if (pista) pista.textContent = 'Ubicacion guardada: ' + posicion.lat.toFixed(5) + ', ' + posicion.lng.toFixed(5);
        }

        mapa.on('click', (evento) => {
            marcador.setLatLng(evento.latlng);
            guardar(evento.latlng);
        });

        marcador.on('dragend', () => guardar(marcador.getLatLng()));

        setTimeout(() => mapa.invalidateSize(), 200);
    }

    function iniciar() {
        document.querySelectorAll('[data-ongs-map]').forEach(iniciarMapa);
        document.querySelectorAll('[data-ongs-picker]').forEach(iniciarSelector);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
