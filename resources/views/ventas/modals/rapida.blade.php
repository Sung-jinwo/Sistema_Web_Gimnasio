<x-modal-form show="showVentaRapidaModal" title="Venta Rápida" subtitle="Venta sin identificación de alumno" icon='<i class="fas fa-bolt text-white"></i>' size="md" headerColor="gray">
    <form method="POST" action="{{ route('ventas.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="tipo_venta" value="rapida">
        <input type="hidden" name="estado_venta" value="completado">
        <input type="hidden" name="estado_pago" value="pagado">

        <div>
            <label for="rapida_producto" class="block text-sm font-medium text-gray-700 mb-1">Producto <span class="text-red-500">*</span></label>
            <select id="rapida_producto" name="fkproducto" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" @change="productoSeleccionado = productos.find(p => p.id_productos == $event.target.value)">
                <option value="">Seleccionar producto...</option>
                <template x-for="producto in productos" :key="producto.id_productos">
                    <option :value="producto.id_productos" x-text="producto.prod_nombre + ' - S/ ' + parseFloat(producto.prod_precio).toFixed(2) + ' (Stock: ' + producto.prod_cantidad + ')'"></option>
                </template>
            </select>
        </div>

        <div>
            <label for="rapida_cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad <span class="text-red-500">*</span></label>
            <input type="number" id="rapida_cantidad" name="cantidad" min="1" value="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
        </div>

        <div>
            <label for="rapida_metodo" class="block text-sm font-medium text-gray-700 mb-1">Método de Pago <span class="text-red-500">*</span></label>
            <select id="rapida_metodo" name="fkmetodo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <option value="">Seleccionar método...</option>
                <template x-for="metodo in metodos" :key="metodo.id_metod">
                    <option :value="metodo.id_metod" x-text="metodo.metod_nombre"></option>
                </template>
            </select>
        </div>

        <div>
            <label for="rapida_observacion" class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
            <textarea id="rapida_observacion" name="observacion" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="button" @click="showVentaRapidaModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </button>
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                Registrar Venta
            </button>
        </div>
    </form>
</x-modal-form>
