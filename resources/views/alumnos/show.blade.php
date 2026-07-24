<x-modal show="modalOpen && modalType === 'ver'" size="md">
    <h3 class="text-lg font-bold mb-4 text-gray-900">Detalles del Alumno</h3>
    
    <div class="space-y-3">
        <div>
            <p class="text-sm text-gray-600">Código</p>
            <p class="font-medium text-gray-900" x-text="selectedAlumno?.codigo"></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Nombre</p>
            <p class="font-medium text-gray-900" x-text="selectedAlumno?.nombre"></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Email</p>
            <p class="font-medium text-gray-900" x-text="selectedAlumno?.email"></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Membresía</p>
            <p class="font-medium text-gray-900" x-text="selectedAlumno?.membresia"></p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Estado</p>
            <p class="font-medium text-gray-900" x-text="selectedAlumno?.estado"></p>
        </div>
    </div>

    <x-slot:footer>
        <x-button variant="outline" @click="modalOpen = false" class="w-full">
            Cerrar
        </x-button>
    </x-slot:footer>
</x-modal>