@extends('layouts.app')

@section('title', 'Gestionar Alumnos')
@section('page-title', 'Gestionar Alumnos')
@section('page-subtitle', 'Administración de estudiantes del gimnasio')

@section('content')
<div x-data="{
    searchTerm: '',
    filterMembresia: 'todos',
    filterEstado: 'todos',
    modalOpen: false,
    modalType: 'ver',
    selectedAlumno: null,
    showCreateModal: false,
    showEditModal: false,
    editUrl: '',
    alumnoParaEditar: null,
    
    // Datos del servidor convertidos a JS
    alumnos: {{ Js::from($alumnos->items()) }},
    
    get filteredAlumnos() {
        return this.alumnos.filter(alumno => {
            const matchesSearch = (alumno.alum_nombre || '').toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                                 (alumno.alum_codigo || '').toLowerCase().includes(this.searchTerm.toLowerCase());
            const matchesMembresia = this.filterMembresia === 'todos' || alum.membresia_estado === this.filterMembresia;
            const matchesEstado = this.filterEstado === 'todos' || alumno.alum_estado === this.filterEstado;
            return matchesSearch && matchesMembresia && matchesEstado;
        });
    },
    
    openModal(alumno, type) {
        this.selectedAlumno = alumno;
        this.modalType = type;
        this.modalOpen = true;
    },

    closeModal() {
        this.modalOpen = false;
        this.selectedAlumno = null;
        this.modalType = 'ver';
    },
    closeEditModal() {
        this.showEditModal = false;
        this.selectedAlumno = null;
        this.editUrl = '';
    },
    
    closeCreateModal() {
        this.showCreateModal = false;
    },

    openEditModal(alumno) {
        // Crear una copia del objeto para no modificar el original
        this.selectedAlumno = { 
            id_alumno: alumno.id_alumno,
            alum_codigo: alumno.alum_codigo || '',
            alum_nombre: alumno.alum_nombre || '',
            alum_apellido: alumno.alum_apellido || '',
            alum_direccion: alumno.alum_direccion || '',
            alum_correro: alumno.alum_correro || '',
            alum_telefo: alumno.alum_telefo || '',
            alum_numDoc: alumno.alum_numDoc || '',
            alum_documento: alumno.alum_documento || '',
            fksexo: alumno.fksexo || '',
            fksede: alumno.fksede || '',
            fkuser: alumno.fkuser || '',
            fecha_nac: alumno.fecha_nac || '',
            alum_condi: alumno.alum_condi || '',
            alum_estado: alumno.alum_estado || 'A'
        };
        
        this.editUrl = `{{ route('alumnos.update', ['alumno' => ':id']) }}`.replace(':id', alumno.id_alumno);
        this.showEditModal = true;
    },
    
    getBadgeColor(status) {
        const colors = {
            'Pagada': 'bg-green-100 text-green-800',
            'A': 'bg-green-100 text-green-800',
            'Por Pagar': 'bg-yellow-100 text-yellow-800',
            'Vencida': 'bg-red-100 text-red-800',
            'Suspendido': 'bg-red-100 text-red-800',
            'Inactiva': 'bg-gray-100 text-gray-800',
            'E': 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
}" class="space-y-6">

    <!-- Header con botón -->
    <div class="flex justify-between items-center">
        <x-button @click="showCreateModal = true">
            + Nuevo Alumno
        </x-button>
    </div>

    <!-- Filtros -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Buscador -->
        <x-search-input 
            placeholder="Buscar por código o nombre..."
            id="alumnoTexto"
            name="alumnoTexto" 
            model="searchTerm" />

        <!-- Filtro Membresía -->
        <x-select-filter 
            model="filterMembresia"
            id="incripcion"
            name="incripcion"
            default-label="Todas las membresías"
            :options="[
                'Pagada' => 'Pagada',
                'Por Pagar' => 'Por Pagar',
                'Vencida' => 'Vencida',
                'Inactiva' => 'Inactiva'
            ]" />

        <!-- Filtro Estado -->
        <x-select-filter 
            model="filterEstado" 
            default-label="Todos los estados"
            id="estado"
            name="estado"
            :options="[
                'A' => 'Activo',
                'E' => 'Inactivo'
            ]" />
    </div>

    <!-- Tabla -->
    @include('alumnos.table')

    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Mostrando {{ $alumnos->firstItem() ?? 0 }} a {{ $alumnos->lastItem() ?? 0 }} de {{ $alumnos->total() }} alumnos
        </div>
        <div>
            {{ $alumnos->links() }}
        </div>
    </div>

    <!-- Modal Crear -->
    @include('alumnos.create')

     <!-- Modal Ver -->
    @include('alumnos.show')

    <!-- Modal Editar -->
    @include('alumnos.edit', ['updateRoute' => route('alumnos.update', ['alumno' => ':id'])])


    <!-- Modal Eliminar -->
    <x-modal show="modalOpen && modalType === 'eliminar'" size="md">
        <h3 class="text-lg font-bold mb-4 text-gray-900">Eliminar Alumno</h3>
        
        <p class="text-gray-700 mb-6">
            ¿Está seguro de que desea eliminar a <span class="font-semibold" x-text="selectedAlumno?.nombre"></span>?
        </p>
        
        <form :action="`{{ route('alumnos.index') }}/${selectedAlumno?.id}`" method="POST">
            @csrf
            @method('DELETE')
        </form>

        <x-slot:footer>
            <div class="flex gap-3">
                <x-button variant="outline" @click="modalOpen = false" class="flex-1">
                    Cancelar
                </x-button>
                <x-button variant="danger" type="submit" class="flex-1">
                    Eliminar
                </x-button>
            </div>
        </x-slot:footer>
    </x-modal>

</div>
@endsection