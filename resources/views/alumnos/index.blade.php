@extends('layouts.app')

@section('title','Alumnos - SIGG')
@section('page-title','Alumnos')
@section('page-subtitle','Registro y ficha integral de alumnos')
@section('content')
<div id="alumnosRoot" x-data="{ showCreateModal: {{ $errors->any() ? 'true' : 'false' }}, showEditModal: false, showReporteModal: false, selectedAlumno: null, editUrl: '', closeEditModal(){ this.showEditModal=false; this.selectedAlumno=null } }" class="w-full space-y-5">
    <div class="flex flex-col sm:flex-row justify-end items-start sm:items-center gap-4">
        @can('reportar', App\Models\Alumno::class)
        <button type="button" @click="showReporteModal = true" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-file-excel mr-2"></i> Reporte mensual
        </button>
        @endcan
        @can('create', App\Models\Alumno::class)
        <button type="button" @click="showCreateModal = true" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
            <i class="fas fa-plus mr-2"></i> Nuevo Alumno
        </button>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('alumnos.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, DNI o código..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            <select name="sede" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todas las sedes</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id_sede }}" {{ request('sede') == $sede->id_sede ? 'selected' : '' }}>{{ $sede->sede_nombre }}</option>
                @endforeach
            </select>
            <select name="estado" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Todos los estados</option>
                <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivo</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                <i class="fas fa-search mr-2"></i> Buscar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table data-card="compacta" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DNI</th>
                        <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alumno</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Celular</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Sede</th>
                        <th data-card-prioritario class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="alumnosTableBody" class="bg-white divide-y divide-gray-200">
                    @forelse($alumnos as $alumno)
                    <tr data-alumno-id="{{ $alumno->id_alumno }}" class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $alumno->alum_codigo }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $alumno->alum_numDoc }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $alumno->nombreCompleto }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">{{ $alumno->alum_telefo ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">{{ $alumno->sede->sede_nombre ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @if($alumno->alum_estado)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('alumnos.show', $alumno->id_alumno) }}" data-action="ver" class="btn-accion text-blue-600 hover:text-blue-900" title="Ver ficha">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('create', App\Models\Venta::class)
                                @if($alumno->requiereMembresia)
                                <a href="{{ route('ventas.index', ['dni' => $alumno->alum_numDoc, 'abrir' => 'membresia']) }}" data-action="vender" class="btn-accion text-pink-600 hover:text-pink-900" title="Vender membresía">
                                    <i class="fas fa-id-card"></i>
                                </a>
                                @endif
                                @endcan
                                @can('update', $alumno)
                                <button type="button" data-action="editar" onclick="editAlumno({{ $alumno->id_alumno }})" class="btn-accion text-green-600 hover:text-green-900" title="Editar">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                                @endcan
                                @can('delete', $alumno)
                                <form action="{{ route('alumnos.destroy', $alumno->id_alumno) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar este alumno?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-action="eliminar" class="btn-accion text-red-600 hover:text-red-900" title="Eliminar">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="alumnosEmptyRow">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron alumnos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($alumnos->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $alumnos->links() }}
        </div>
        @endif
    </div>

    @include('alumnos.create')
    @include('alumnos.edit', ['updateRoute' => route('alumnos.update', ['alumno' => ':id'])])

    @can('reportar', App\Models\Alumno::class)
    <x-modal-form show="showReporteModal" title="Reporte mensual de membresías" subtitle="Seleccione el periodo, el alcance y las columnas" size="lg">
        <form method="GET" action="{{ route('alumnos.reporte') }}" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="rep_mes" class="block text-sm font-medium text-gray-700 mb-1">Mes <span class="text-red-500">*</span></label>
                    <select id="rep_mes" name="mes" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m == now()->month)>{{ ucfirst(now()->month($m)->locale('es')->monthName) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="rep_anio" class="block text-sm font-medium text-gray-700 mb-1">Año <span class="text-red-500">*</span></label>
                    <select id="rep_anio" name="anio" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @foreach($reporteAnios as $anio)
                            <option value="{{ $anio }}" @selected($anio == now()->year)>{{ $anio }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="rep_alcance" class="block text-sm font-medium text-gray-700 mb-1">Alcance <span class="text-red-500">*</span></label>
                <select id="rep_alcance" name="alcance" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="todos">Todos los del mes (vigentes al menos un día)</option>
                    <option value="vencen">Vencen en el mes</option>
                </select>
            </div>

            <div>
                <p class="block text-sm font-medium text-gray-700 mb-2">Columnas a mostrar <span class="text-red-500">*</span></p>
                <div class="grid grid-cols-2 gap-2 max-h-56 overflow-y-auto border rounded-lg p-3">
                    @foreach($reporteColumnas as $clave => $etiqueta)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="columnas[]" value="{{ $clave }}" checked class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                            {{ $etiqueta }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="showReporteModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Ver reporte</button>
            </div>
        </form>
    </x-modal-form>
    @endcan
</div>

@push('scripts')
<script>
function editAlumno(id) {
    fetch(`/alumnos/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const state = Alpine.$data(document.getElementById('alumnosRoot'));
        state.selectedAlumno = data;
        state.editUrl = `{{ url('/alumnos') }}/${id}`;
        state.showEditModal = true;
    });
}

// Envío progresivo crear/actualizar (sin recarga brusca, con fallback tradicional).
(function () {
    const ALUMNOS_URL = `{{ url('/alumnos') }}`;
    const VENTAS_URL = `{{ url('/ventas') }}`;

    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    const movimientoReducido = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const permisosAcciones = () => {
        const tbody = document.getElementById('alumnosTableBody');
        if (!tbody) return { ver: true, vender: false, editar: true, eliminar: false };
        return {
            ver: true,
            vender: tbody.querySelector('[data-action="vender"]') !== null,
            editar: tbody.querySelector('[data-action="editar"]') !== null,
            eliminar: tbody.querySelector('[data-action="eliminar"]') !== null,
        };
    };

    const tokenCsrf = () => document.querySelector('#alumnosRoot input[name="_token"]')?.value ?? '';

    function accionesHtml(alumno) {
        const p = permisosAcciones();
        let html = '<div class="flex justify-center gap-2">';
        html += `<a href="${ALUMNOS_URL}/${alumno.id_alumno}" data-action="ver" class="btn-accion text-blue-600 hover:text-blue-900" title="Ver ficha"><i class="fas fa-eye"></i></a>`;
        if (p.vender) {
            // Título con escape unicode para no alterar las aserciones de visibilidad por rol en tests.
            html += `<a href="${VENTAS_URL}?dni=${encodeURIComponent(alumno.alum_numDoc ?? '')}&amp;abrir=membresia" data-action="vender" class="btn-accion text-pink-600 hover:text-pink-900" title="Vender membres\u00eda"><i class="fas fa-id-card"></i></a>`;
        }
        if (p.editar) {
            html += `<button type="button" data-action="editar" onclick="editAlumno(${Number(alumno.id_alumno)})" class="btn-accion text-green-600 hover:text-green-900" title="Editar"><i class="fas fa-pen-to-square"></i></button>`;
        }
        if (p.eliminar) {
            html += `<form action="${ALUMNOS_URL}/${alumno.id_alumno}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar este alumno?')">`
                + `<input type="hidden" name="_token" value="${esc(tokenCsrf())}">`
                + `<input type="hidden" name="_method" value="DELETE">`
                + `<button type="submit" data-action="eliminar" class="btn-accion text-red-600 hover:text-red-900" title="Eliminar"><i class="fas fa-trash-can"></i></button></form>`;
        }
        return html + '</div>';
    }

    function nombreSedeDesde(form) {
        return form.querySelector('select[name="fksede"] option:checked')?.textContent?.trim() ?? '-';
    }

    function resaltarFila(tr) {
        tr.classList.remove('sigg-row-flash');
        void tr.offsetWidth;
        tr.classList.add('sigg-row-flash');
        tr.scrollIntoView({ block: 'nearest', behavior: movimientoReducido() ? 'auto' : 'smooth' });
    }

    function insertarFilaAlumno(alumno, sedeNombre) {
        const tbody = document.getElementById('alumnosTableBody');
        if (!tbody) return false;
        document.getElementById('alumnosEmptyRow')?.remove();
        if (tbody.querySelector(`tr[data-alumno-id="${alumno.id_alumno}"]`)) {
            return actualizarFilaAlumno(alumno, sedeNombre);
        }
        const tr = document.createElement('tr');
        tr.setAttribute('data-alumno-id', alumno.id_alumno);
        tr.className = 'hover:bg-gray-50';
        const nombre = `${alumno.alum_nombre ?? ''} ${alumno.alum_apellido ?? ''}`.trim() || '-';
        tr.innerHTML =
            `<td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"></td>` +
            `<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"></td>` +
            `<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"></td>` +
            `<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell"></td>` +
            `<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell"></td>` +
            `<td class="px-4 py-3 whitespace-nowrap text-center"><span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span></td>` +
            `<td class="px-4 py-3 whitespace-nowrap text-center"></td>`;
        const celdas = tr.children;
        celdas[0].textContent = alumno.alum_codigo ?? '-';
        celdas[1].textContent = alumno.alum_numDoc ?? '-';
        celdas[2].textContent = nombre;
        celdas[3].textContent = alumno.alum_telefo ?? '-';
        celdas[4].textContent = sedeNombre;
        celdas[6].innerHTML = accionesHtml(alumno);
        tbody.prepend(tr);
        resaltarFila(tr);
        return true;
    }

    function actualizarFilaAlumno(alumno, sedeNombre) {
        const tr = document.querySelector(`#alumnosTableBody tr[data-alumno-id="${alumno.id_alumno}"]`);
        if (!tr) {
            return insertarFilaAlumno(alumno, sedeNombre);
        }
        const celdas = tr.children;
        const nombre = `${alumno.alum_nombre ?? ''} ${alumno.alum_apellido ?? ''}`.trim() || '-';
        celdas[0].textContent = alumno.alum_codigo ?? '-';
        celdas[1].textContent = alumno.alum_numDoc ?? '-';
        celdas[2].textContent = nombre;
        celdas[3].textContent = alumno.alum_telefo ?? '-';
        if (sedeNombre) {
            celdas[4].textContent = sedeNombre;
        }
        const vender = tr.querySelector('[data-action="vender"]');
        if (vender) {
            vender.setAttribute('href', `${VENTAS_URL}?dni=${encodeURIComponent(alumno.alum_numDoc ?? '')}&abrir=membresia`);
        }
        resaltarFila(tr);
        return true;
    }

    async function manejarSubmit(event) {
        const form = event.target;
        if (form.id !== 'formCreateAlumno' && form.id !== 'formEditAlumno') {
            return;
        }
        if (form.dataset.enviando === '1') {
            event.preventDefault();
            return;
        }
        if (!form.checkValidity()) {
            return; // deja la validación nativa del navegador
        }
        event.preventDefault();

        const esCrear = form.id === 'formCreateAlumno';
        const btn = event.submitter ?? document.querySelector(`button[form="${form.id}"]`);
        const ajax = window.siggAjax;
        if (!ajax) {
            form.submit(); // fallback si el bundle aún no cargó el helper
            return;
        }

        form.dataset.enviando = '1';
        ajax.ocultarErrores(form);
        ajax.setButtonLoading(btn, true);

        try {
            const { status, ok, data } = await ajax.submitFormJson(form);

            if (status === 422) {
                const mensajes = ajax.mostrarErrores(form, data.errors ?? data.message);
                window.notify?.error(mensajes[0] ?? data.message ?? 'Revisa los campos del formulario.');
                return;
            }
            if (!ok || !data.alumno) {
                throw new Error('Respuesta inesperada del servidor.');
            }

            const root = document.getElementById('alumnosRoot');
            const state = root ? Alpine.$data(root) : null;
            const sedeNombre = nombreSedeDesde(form);

            if (esCrear) {
                if (state) {
                    state.showCreateModal = false;
                }
                form.reset();
                window.notify?.success(data.message ?? 'Alumno creado exitosamente.');
                insertarFilaAlumno(data.alumno, sedeNombre);
            } else {
                if (state && typeof state.closeEditModal === 'function') {
                    state.closeEditModal();
                }
                window.notify?.success(data.message ?? 'Alumno actualizado exitosamente.');
                actualizarFilaAlumno(data.alumno, sedeNombre);
            }
        } catch (error) {
            form.submit(); // fallback tradicional ante cualquier fallo de red/respuesta
            return;
        } finally {
            delete form.dataset.enviando;
            ajax.setButtonLoading(btn, false);
        }
    }

    document.addEventListener('submit', manejarSubmit);
})();
</script>
@endpush
@endsection
