<x-modal-form show="showVentaProductoModal" title="Venta de Producto" subtitle="Venta de producto con identificación de alumno" icon='<i class="fas fa-box text-white"></i>' size="md" headerColor="blue">
    <form method="POST" action="{{ route('ventas.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="tipo_venta" value="producto">

        <div>
            <label for="producto_alumno" class="block text-sm font-medium text-gray-700 mb-1">Alumno <span class="text-red-500">*</span></label>
            <select id="producto_alumno" name="fkalum" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Seleccionar alumno...</option>
                <template x-for="alumno in alumnos" :key="alumno.id_alumno">
                    <option :value="alumno.id_alumno" x-text="alumno.alum_nombre + ' ' + alumno.alum_apellido + ' - DNI: ' + alumno.alum_numDoc"></option>
                </template>
            </select>
        </div>

        <div>
            <label for="producto_producto" class="block text-sm font-medium text-gray-700 mb-1">Producto <span class="text-red-500">*</span></label>
            <select id="producto_producto" name="fkproducto" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Seleccionar producto...</option>
                <template x-for="producto in productos" :key="producto.id_productos">
                    <option :value="producto.id_productos" x-text="producto.prod_nombre + ' - S/ ' + parseFloat(producto.prod_precio).toFixed(2) + ' (Stock: ' + producto.prod_cantidad + ')'"></option>
                </template>
            </select>
        </div>

        <div>
            <label for="producto_cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad <span class="text-red-500">*</span></label>
            <input type="number" id="producto_cantidad" name="cantidad" min="1" value="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
        </div>

        <div>
            <label for="producto_metodo" class="block text-sm font-medium text-gray-700 mb-1">Método de Pago <span class="text-red-500">*</span></label>
            <select id="producto_metodo" name="fkmetodo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Seleccionar método...</option>
                <template x-for="metodo in metodos" :key="metodo.id_metod">
                    <option :value="metodo.id_metod" x-text="metodo.metod_nombre"></option>
                </template>
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="producto_estado_venta" class="block text-sm font-medium text-gray-700 mb-1">Estado Venta <span class="text-red-500">*</span></label>
                <select id="producto_estado_venta" name="estado_venta" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="completado">Completado</option>
                    <option value="reservado">Reservado</option>
                    <option value="incompleto">Incompleto</option>
                </select>
            </div>
            <div>
                <label for="producto_estado_pago" class="block text-sm font-medium text-gray-700 mb-1">Estado Pago <span class="text-red-500">*</span></label>
                <select id="producto_estado_pago" name="estado_pago" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="pagado">Pagado</option>
                    <option value="parcial">Parcial</option>
                    <option value="pendiente">Pendiente</option>
                </select>
            </div>
        </div>

        <div>
            <label for="producto_fecha_acordada" class="block text-sm font-medium text-gray-700 mb-1">Fecha Acordada de Pago</label>
            <input type="date" id="producto_fecha_acordada" name="fecha_acordada" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
        </div>

        <div>
            <label for="producto_observacion" class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
            <textarea id="producto_observacion" name="observacion" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="button" @click="showVentaProductoModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </button>
            <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Registrar Venta
            </button>
        </div>
    </form>
</x-modal-form>
