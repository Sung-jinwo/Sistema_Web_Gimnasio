<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#f9fafb">
    @php
        $tituloPestana = trim($__env->yieldContent('page-title')) ?: trim($__env->yieldContent('title'));
    @endphp
    <title>{{ $tituloPestana ? $tituloPestana.' | SIGG' : 'SIGG' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('icon/icongym.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icon/icongym.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Critical: pinta el fondo antes de que cargue el CSS de Vite (evita flash blanco). */
        html { background-color: #f9fafb; }
        body { background-color: #f9fafb; }
        [x-cloak] { display: none !important; }
        @view-transition { navigation: auto; }
        ::view-transition-old(root) { animation: sigg-fade-out .18s ease both; }
        ::view-transition-new(root) { animation: sigg-fade-in .25s ease both; }
        @keyframes sigg-fade-out { to { opacity: 0; } }
        @keyframes sigg-fade-in { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) {
            ::view-transition-old(root), ::view-transition-new(root) { animation: none; }
            #sigg-topbar { display: none; }
        }
        #sigg-topbar { position: fixed; top: 0; left: 0; height: 3px; width: 0; z-index: 60;
            background: linear-gradient(90deg, #ec4899, #8b5cf6);
            box-shadow: 0 0 8px rgba(236, 72, 153, .7);
            opacity: 0; transition: opacity .2s ease; }
        #sigg-topbar.activa { opacity: 1; animation: sigg-topbar-slide 1.1s ease-in-out infinite; }
        @keyframes sigg-topbar-slide {
            0% { width: 0; margin-left: 0; }
            50% { width: 45%; margin-left: 30%; }
            100% { width: 0; margin-left: 100%; }
        }
        .sigg-contenido { animation: sigg-fade-in .22s ease both; }
        @media (prefers-reduced-motion: reduce) { .sigg-contenido { animation: none; } }
        /* Fila recién creada/actualizada vía AJAX: resaltado temporal. */
        @keyframes sigg-row-highlight { 0% { background-color: #fce7f3; } 100% { background-color: transparent; } }
        tr.sigg-row-flash { animation: sigg-row-highlight 2.2s ease both; }
        @media (prefers-reduced-motion: reduce) { tr.sigg-row-flash { animation: none; } }
        /* Skeleton de tabla durante navegación lenta. */
        .sigg-skeleton-row { pointer-events: none; }
        @media (prefers-reduced-motion: reduce) { .sigg-skeleton-row .animate-pulse { animation: none; } }
        /* Botones de acción en tablas: pastilla con el color del icono + zoom al hover */
        .btn-accion { display: inline-flex; align-items: center; justify-content: center;
            width: 2rem; height: 2rem; border-radius: 9999px; line-height: 1;
            transition: background-color .15s ease, transform .15s ease, color .15s ease; }
        .btn-accion:hover { background-color: color-mix(in srgb, currentColor 14%, transparent);
            transform: scale(1.2); }
        .btn-accion:active { transform: scale(1.05); }
        .btn-accion:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
        @media (prefers-reduced-motion: reduce) { .btn-accion, .btn-accion:hover { transform: none; } }
        main table.responsive-cards > tbody > tr.sigg-ver-mas-row { display: none; }
        @media (max-width: 767px) {
            main table.responsive-cards, main table.responsive-cards > tbody { display: block; width: 100%; }
            main table.responsive-cards > thead { display: none; }
            main table.responsive-cards > tbody > tr { display: block; margin: .75rem; padding: .75rem; border: 1px solid #e5e7eb; border-radius: .75rem; background: white; box-shadow: 0 1px 2px rgb(0 0 0 / .05); }
            main table.responsive-cards > tbody > tr > td { display: flex !important; justify-content: space-between; align-items: center; gap: 1rem; width: 100%; padding: .5rem !important; text-align: right !important; white-space: normal !important; overflow-wrap: anywhere; }
            main table.responsive-cards > tbody > tr > td::before { content: attr(data-label); color: #6b7280; font-weight: 600; text-align: left; flex-shrink: 0; max-width: 42%; }
            main table.responsive-cards > tbody > tr > td > :is(span, a, div, p) { min-width: 0; }
            main table.responsive-cards > tbody > tr > td[colspan] { display: block !important; text-align: center !important; }
            main table.responsive-cards > tbody > tr > td[colspan]::before { display: none; }
            /* Campos marcados para ocultar en móvil. */
            main table.responsive-cards > tbody > tr > td[data-card-rol="oculto"] { display: none !important; }
            /* Tarjeta compacta: lo secundario se revela con "Ver más". */
            main table[data-card="compacta"].responsive-cards > tbody > tr > td[data-card-rol="secundario"] { display: none !important; }
            main table[data-card="compacta"].responsive-cards > tbody > tr.sigg-expandida > td[data-card-rol="secundario"] { display: flex !important; }
            /* Fila de acciones: botones centrados sin etiqueta y en tamaño táctil. */
            main table.responsive-cards > tbody > tr > td[data-card-rol="acciones"] { justify-content: center; }
            main table.responsive-cards > tbody > tr > td[data-card-rol="acciones"]::before { display: none; }
            main table.responsive-cards .btn-accion { width: 2.75rem; height: 2.75rem; font-size: 1.05rem; }
            main table.responsive-cards > tbody > tr > td .btn-accion:hover { transform: none; }
            /* Botón "Ver más / Ver menos" como pie de la tarjeta. */
            main table.responsive-cards > tbody > tr.sigg-con-toggle { margin-bottom: 0; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
            main table.responsive-cards > tbody > tr.sigg-ver-mas-row { display: block; margin: 0 .75rem .75rem; padding: .25rem .75rem .6rem; border: 1px solid #e5e7eb; border-top: 0; border-radius: 0 0 .75rem .75rem; background: white; }
            main table.responsive-cards > tbody > tr.sigg-ver-mas-row > td { display: block !important; text-align: center !important; padding: 0 !important; }
            .sigg-ver-mas-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1rem; min-height: 44px; font-size: .8rem; font-weight: 600; color: #db2777; border-radius: 9999px; }
            .sigg-ver-mas-btn:hover { background-color: #fdf2f8; }
            .sigg-ver-mas-btn:focus-visible { outline: 2px solid #db2777; outline-offset: 2px; }
            /* Inputs dentro de tarjetas (comisiones, caja): sin desborde. */
            main table.responsive-cards input, main table.responsive-cards select, main table.responsive-cards textarea { max-width: 100%; min-width: 0; }
            /* Tablas anidadas: scroll horizontal interno, no tarjeta-dentro-de-tarjeta. */
            main table.responsive-cards > tbody > tr > td > table { display: block; overflow-x: auto; min-width: 0; flex: 1 1 auto; }
        }
        @media (prefers-reduced-motion: reduce) {
            main table.responsive-cards .btn-accion { transition: none; }
        }
    </style>
    @stack('styles')
    
</head>
<body class="bg-gray-50" x-data="{
    sidebarOpen: window.innerWidth >= 1024,
    mobileMenuOpen: false,
    expandedMenus: {},
    isMobile: window.innerWidth < 1024,
    sidebarCollapsed: false,
    navegando: false,

    toggleSubmenu(menuId) {
        this.expandedMenus[menuId] = !this.expandedMenus[menuId];
    },
    isExpanded(menuId) {
        return this.expandedMenus[menuId] || false;
    },
    init() {
        this.sidebarOpen = window.innerWidth >= 1024;
        
        // Escuchar cambios de tamaño
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.sidebarOpen = true;
                this.mobileMenuOpen = false;
            } else {
                this.sidebarOpen = false;
            }
        });
    }

    
}" x-init="init()">
  
    <x-toast-notifications />
    <x-page-loader />
    <div id="sigg-topbar" aria-hidden="true"></div>

    <div class="flex h-screen bg-gray-50">
        <x-sidebar />
        <main class="flex-1 flex flex-col overflow-hidden w-full">

            @include('partials.header')

            <div class="flex-1 overflow-auto p-4 lg:p-8 bg-gray-50 sigg-contenido">
                @include('partials.validation-errors')
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
    <script>
        // Transición de navegación SIGG: barra de progreso inmediata + overlay solo en cargas lentas.
        (() => {
            const topbar = () => document.getElementById('sigg-topbar');
            let overlayTimer = null;
            const OVERLAY_DELAY_MS = 400;

            const iniciarTransicion = () => {
                topbar()?.classList.add('activa');
                clearTimeout(overlayTimer);
                overlayTimer = setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('navegando-iniciar'));
                }, OVERLAY_DELAY_MS);
            };

            const esNavegacionInterna = (a) => {
                if (!a || !a.href) return false;
                if (a.target && a.target !== '_self') return false;
                if (a.hasAttribute('download') || a.hasAttribute('data-no-loader')) return false;
                if (a.href.startsWith('javascript:') || a.href.startsWith('mailto:') || a.href.startsWith('tel:')) return false;
                let url;
                try { url = new URL(a.href, window.location.origin); } catch { return false; }
                if (url.origin !== window.location.origin) return false;
                if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;
                return true;
            };

            document.addEventListener('click', (e) => {
                const a = e.target.closest('a');
                if (a && esNavegacionInterna(a)) iniciarTransicion();
            });

            document.addEventListener('submit', (e) => {
                const form = e.target.closest('form');
                if (form && !form.hasAttribute('data-no-loader')) iniciarTransicion();
            });

            // Precarga al pasar el mouse por el menú: la siguiente vista abre casi al instante.
            const precargados = new Set();
            document.querySelectorAll('aside a[href]').forEach((a) => {
                if (!esNavegacionInterna(a)) return;
                a.addEventListener('mouseenter', () => {
                    if (precargados.has(a.href)) return;
                    precargados.add(a.href);
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = a.href;
                    document.head.appendChild(link);
                }, { once: true });
            });
        })();
    </script>
    <script>
        // Reemplaza el etiquetador inicial: siggEtiquetas() es idempotente, procesa filas nuevas
        // insertadas por AJAX y respeta las clases de tarjeta compacta.
        (() => {
            const ACCION_REGEX = /acci[oó]n(?:es)?$/i;
            const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function esColAccion(th) {
                return ACCION_REGEX.test(th.textContent.trim());
            }

            function leerPlanTh(tabla) {
                const ths = [...tabla.querySelectorAll(':scope > thead > tr > th')];
                return ths.map((th, idx) => {
                    if (esColAccion(th)) return { idx, rol: 'acciones' };
                    if (th.hasAttribute('data-card-prioritario')) return { idx, rol: 'prioritario' };
                    if (th.hasAttribute('data-card-oculto')) return { idx, rol: 'oculto' };
                    return { idx, rol: tabla.dataset.card === 'compacta' ? 'secundario' : 'visible' };
                });
            }

            function aplicarPlan(fila, plan) {
                fila.querySelectorAll(':scope > td').forEach((td, i) => {
                    const p = plan[i];
                    if (!p) return;
                    td.dataset.cardRol = p.rol;
                    if (p.rol === 'acciones') {
                        td.style.justifyContent = 'center';
                        td.style.textAlign = 'center';
                    }
                });
            }

            function necesitaToggle(fila, plan) {
                return plan.some(p => p.rol === 'secundario') && [...fila.children].some(td => td.dataset.cardRol === 'secundario');
            }

            function construirBotonToggle() {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'sigg-ver-mas-btn';
                btn.setAttribute('aria-expanded', 'false');
                btn.innerHTML = '<i class="fas fa-chevron-down" aria-hidden="true"></i><span>Ver más</span>';
                return btn;
            }

            function inyectarToggle(fila) {
                if (fila.nextElementSibling && fila.nextElementSibling.classList.contains('sigg-ver-mas-row')) return;
                fila.classList.add('sigg-con-toggle');
                const toggleRow = document.createElement('tr');
                toggleRow.className = 'sigg-ver-mas-row';
                const toggleCell = document.createElement('td');
                toggleCell.colSpan = fila.children.length;
                toggleCell.appendChild(construirBotonToggle());
                toggleRow.appendChild(toggleCell);
                fila.parentNode.insertBefore(toggleRow, fila.nextSibling);
                toggleRow.addEventListener('click', () => {
                    const expandida = fila.classList.toggle('sigg-expandida');
                    toggleRow.querySelector('button').setAttribute('aria-expanded', String(expandida));
                    toggleRow.querySelector('span').textContent = expandida ? 'Ver menos' : 'Ver más';
                    const icono = toggleRow.querySelector('i');
                    icono.className = `fas fa-${expandida ? 'chevron-up' : 'chevron-down'}`;
                });
            }

            function procesarTabla(tabla) {
                if (tabla.classList.contains('responsive-cards')) {
                    tabla.querySelectorAll(':scope > tbody > tr:not(.sigg-ver-mas-row):not([colspan])').forEach(tr => {
                        if (!tr.querySelector('td') || tr.querySelector(':scope > td[colspan]')) return;
                        const plan = leerPlanTh(tabla);
                        aplicarPlan(tr, plan);
                        if (tabla.dataset.card === 'compacta' && necesitaToggle(tr, plan)) {
                            inyectarToggle(tr);
                        }
                    });
                    return;
                }
                if (tabla.dataset.responsive === 'off') return;
                const plan = leerPlanTh(tabla);
                if (!plan.length) return;
                tabla.classList.add('responsive-cards');
                tabla.querySelectorAll(':scope > tbody > tr').forEach(tr => {
                    if (!tr.querySelector('td') || tr.querySelector(':scope > td[colspan]')) return;
                    tr.querySelectorAll(':scope > td').forEach((td, i) => {
                        const p = plan[i];
                        if (!p) return;
                        const th = tabla.querySelector(`:scope > thead > tr > th:nth-child(${i + 1})`);
                        if (th) td.dataset.label = th.textContent.trim();
                        td.dataset.cardRol = p.rol;
                        if (p.rol === 'acciones') {
                            td.style.justifyContent = 'center';
                            td.style.textAlign = 'center';
                        }
                    });
                    if (tabla.dataset.card === 'compacta' && necesitaToggle(tr, plan)) {
                        inyectarToggle(tr);
                    }
                });
            }

            const siggEtiquetas = (raiz = document) => {
                raiz.querySelectorAll('main table').forEach(procesarTabla);
            };

            document.addEventListener('DOMContentLoaded', () => siggEtiquetas());

            // Observer para filas añadidas por JS (Alpine/AJAX).
            if (typeof MutationObserver !== 'undefined') {
                const mo = new MutationObserver(mutations => {
                    mutations.forEach(m => {
                        m.addedNodes.forEach(node => {
                            if (node.nodeType !== 1) return;
                            if (node.tagName === 'TABLE') siggEtiquetas(node.parentNode ?? document);
                            else if (node.querySelector) siggEtiquetas(node);
                        });
                    });
                });
                mo.observe(document.documentElement, { childList: true, subtree: true });
            }

            window.siggEtiquetas = siggEtiquetas;
        })();
    </script>

</body>
</html>
