<x-modal-form show="showVentaMembresiaModal" title="Venta de Membresía" subtitle="Asignar membresía a alumno" icon='<i class="fas fa-award text-white"></i>' size="md" headerColor="purple">
    <form method="POST" action="{{ route('ventas.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="tipo_venta" value="membresia">
        <input type="hidden" name="estado_venta" value="completado">

        <div>
            <label for="membresia_alumno" class="block text-sm font-medium text-gray-700 mb-1">Alumno <span class="text-red-500">*</span></label>
            <select id="membresia_alumno" name="fkalum" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Seleccionar alumno...</option>
                <template x-for="alumno in alumnos" :key="alumno.id_alumno">
                    <option :value="alumno.id_alumno" x-text="alumno.alum_nombre + ' ' + alumno.alum_apellido + ' - DNI: ' + alumno.alum_numDoc"></option>
                </template>
            </select>
        </div>

        <div>
            <label for="membresia_plan" class="block text-sm font-medium text-gray-700 mb-1">Plan de Membresía <span class="text-red-500">*</span></label>
            <select id="membresia_plan" name="fkmem" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Seleccionar plan...</option>
                <template x-for="membresia in membresias" :key="membresia.id_mem">
                    <option :value="membresia.id_mem" x-text="membresia.mem_nombre + ' - S/ ' + parseFloat(membresia.mem_precio).toFixed(2) + ' (' + membresia.mem_duracion + ' días)'"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Modalidad <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="modalidad" value="por_meses" checked class="form-radio text-pink-600" onchange="toggleMembresiaModalidad(this.value)">
                    <span class="ml-2 text-sm text-gray-700">Por meses</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="modalidad" value="por_fechas" class="form-radio text-pink-600" onchange="toggleMembresiaModalidad(this.value)">
                    <span class="ml-2 text-sm text-gray-700">Por fechas</span>
                </label>
            </div>
        </div>

        <div id="membresiaFechaInicioField">
            <label for="membresia_fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio <span class="text-red-500">*</span></label>
            <input type="date" id="membresia_fecha_inicio" name="fecha_inicio" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
        </div>

        <div id="membresiaFechaFinField" style="display: none;">
            <label for="membresia_fecha_fin" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Fin <span class="text-red-500">*</span></label>
            <input type="date" id="membresia_fecha_fin" name="fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
        </div>

        <div>
            <label for="membresia_metodo" class="block text-sm font-medium text-gray-700 mb-1">Método de Pago <span class="text-red-500">*</span></label>
            <select id="membresia_metodo" name="fkmetodo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Seleccionar método...</option>
                <template x-for="metodo in metodos" :key="metodo.id_metod">
                    <option :value="metodo.id_metod" x-text="metodo.metod_nombre"></option>
                </template>
            </select>
        </div>

        <div>
            <label for="membresia_estado_pago" class="block text-sm font-medium text-gray-700 mb-1">Estado Pago <span class="text-red-500">*</span></label>
            <select id="membresia_estado_pago" name="estado_pago" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="pagado">Pagado</option>
                <option value="parcial">Parcial</option>
                <option value="pendiente">Pendiente</option>
            </select>
        </div>

        <div>
            <label for="membresia_fecha_acordada" class="block text-sm font-medium text-gray-700 mb-1">Fecha Acordada de Pago</label>
            <input type="date" id="membresia_fecha_acordada" name="fecha_acordada" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
        </div>

        <div>
            <label for="membresia_observacion" class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
            <textarea id="membresia_observacion" name="observacion" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="button" @click="showVentaMembresiaModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </button>
            <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                Registrar Venta
            </button>
        </div>
    </form>
</x-modal-form>

@push('scripts')
<script>
function toggleMembresiaModalidad(valor) {
    const fechaInicioField = document.getElementById('membresiaFechaInicioField');
    const fechaFinField = document.getElementById('membresiaFechaFinField');
    const fechaInicioInput = document.getElementById('membresia_fecha_inicio');
    const fechaFinInput = document.getElementById('membresia_fecha_fin');
    
    if (valor === 'por_fechas') {
        fechaFinField.style.display = 'block';
        fechaFinInput.setAttribute('required', 'required');
    } else {
        fechaFinField.style.display = 'none';
        fechaFinInput.removeAttribute('required');
    }
}
</script>
@endpush
