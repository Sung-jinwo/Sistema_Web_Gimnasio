// Envío progresivo de formularios dentro de modales (crear/actualizar sin recarga brusca).
// Si el fetch falla o el servidor no responde JSON, la vista hace fallback al submit tradicional.

function obtenerCsrf(form) {
    const input = form.querySelector('input[name="_token"]');
    if (input && input.value) {
        return input.value;
    }

    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function prefiereMovimientoReducido() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export function setButtonLoading(btn, loading) {
    if (!btn) {
        return;
    }

    if (loading) {
        if (btn.dataset.originalHtml === undefined) {
            btn.dataset.originalHtml = btn.innerHTML;
        }
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        btn.classList.add('sigg-is-loading');
        const texto = btn.dataset.loadingText ?? 'Guardando…';
        btn.innerHTML =
            '<svg class="animate-spin h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" aria-hidden="true">' +
            '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
            '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
            '</svg><span></span>';
        btn.querySelector('span').textContent = texto;
    } else {
        if (btn.dataset.originalHtml !== undefined) {
            btn.innerHTML = btn.dataset.originalHtml;
            delete btn.dataset.originalHtml;
        }
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        btn.classList.remove('sigg-is-loading');
    }
}

export async function submitFormJson(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': obtenerCsrf(form),
        },
        body: new FormData(form),
    });

    const contentType = response.headers.get('content-type') ?? '';
    if (!contentType.includes('application/json')) {
        throw new Error('Respuesta no JSON, se usa el envío tradicional.');
    }

    return { status: response.status, ok: response.ok, data: await response.json() };
}

export function mostrarErrores(form, errors) {
    const box = form.querySelector('[data-form-errors]');
    const list = form.querySelector('[data-form-errors-list]');
    const mensajes = [];

    if (Array.isArray(errors)) {
        mensajes.push(...errors);
    } else if (errors && typeof errors === 'object') {
        Object.values(errors).forEach((valor) => {
            if (Array.isArray(valor)) {
                mensajes.push(...valor);
            } else if (typeof valor === 'string') {
                mensajes.push(valor);
            }
        });
    } else if (typeof errors === 'string') {
        mensajes.push(errors);
    }

    if (!box || !list) {
        return mensajes;
    }

    list.innerHTML = '';
    if (mensajes.length === 0) {
        box.classList.add('hidden');

        return mensajes;
    }

    mensajes.forEach((mensaje) => {
        const li = document.createElement('li');
        li.textContent = mensaje;
        list.appendChild(li);
    });
    box.classList.remove('hidden');
    box.scrollIntoView({ block: 'nearest', behavior: prefiereMovimientoReducido() ? 'auto' : 'smooth' });

    return mensajes;
}

export function ocultarErrores(form) {
    const box = form.querySelector('[data-form-errors]');
    const list = form.querySelector('[data-form-errors-list]');
    if (box) {
        box.classList.add('hidden');
    }
    if (list) {
        list.innerHTML = '';
    }
}

window.siggAjax = { submitFormJson, setButtonLoading, mostrarErrores, ocultarErrores, prefiereMovimientoReducido };
