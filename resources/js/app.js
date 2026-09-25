import './bootstrap';
import './form-ajax';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Gráficos (Chart.js) solo en páginas que los usan: el bundle se divide
// automáticamente y no engorda el resto de vistas.
if (document.querySelector('canvas[data-grafico]')) {
    import('chart.js/auto').then(({ default: Chart }) => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            Chart.defaults.animation = false;
        }

        document.querySelectorAll('canvas[data-grafico]').forEach((canvas) => {
            const config = JSON.parse(canvas.dataset.grafico);

            if (canvas.dataset.moneda) {
                const soles = (v) => 'S/ ' + Number(v).toLocaleString('es-PE', { minimumFractionDigits: 2 });
                const valor = (c) => c.parsed?.y ?? c.parsed;
                config.options.plugins.tooltip = {
                    callbacks: { label: (c) => ` ${c.dataset.label}: ${soles(valor(c))}` },
                };
                if (config.options.scales?.y) {
                    config.options.scales.y.ticks = { callback: (v) => 'S/ ' + Number(v).toLocaleString('es-PE') };
                }
            }

            new Chart(canvas, config);
        });
    });
}
