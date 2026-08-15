{{-- Modal Editar Alumno --}}
<template x-if="showEditModal && selectedAlumno">
    <x-modal-form 
        show="showEditModal"
        title="Editar Alumno"
        subtitle="Modifique los campos necesarios (*)"
        size="4xl"
        header-color="pink"
        :scrollable="true">
        
        {{-- Icono del header --}}
        <x-slot:icon>
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
        </x-slot:icon>

        {{-- Contenido del formulario --}}
        <form id="formEditAlumno" :action="editUrl" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Mostrar errores de validación --}}
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                    <div class="font-medium text-red-800">Por favor corrige los siguientes errores:</div>
                    <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Sección 1: Información Personal --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200">
                    <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">Información Personal</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {{-- Código con Generador --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Código de Alumno <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                id="alum_codigo_edit"
                                name="alum_codigo"
                                required
                                readonly
                                maxlength="4"
                                x-model="selectedAlumno.alum_codigo"
                                class="w-28 px-3 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-gray-900 font-medium focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                placeholder="####">
                            <button
                                type="button"
                                @click="selectedAlumno.alum_codigo = Math.floor(1000 + Math.random() * 9000).toString();"
                                class="px-4 py-2.5 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors flex items-center gap-2 whitespace-nowrap"
                                title="Generar código aleatorio">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="hidden sm:inline">Generar</span>
                            </button>
                        </div>
                    </div>

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="alum_nombre"
                            required
                            x-model="selectedAlumno.alum_nombre"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            placeholder="Nombre del alumno">
                    </div>

                    {{-- Apellido --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Apellido <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="alum_apellido"
                            required
                            x-model="selectedAlumno.alum_apellido"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            placeholder="Apellido del alumno">
                    </div>

                    {{-- Sexo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Sexo <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="fksexo" 
                            required
                            x-model="selectedAlumno.fksexo"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Seleccione</option>
                            @foreach($sexos as $sexo)
                                <option value="{{ $sexo->id_sexo }}">{{ $sexo->sexo_nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Fecha de Nacimiento --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Fecha de Nacimiento <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            name="fecha_nac"
                            required
                            x-model="selectedAlumno.fecha_nac"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>

                    {{-- Sede --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Sede <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="fksede" 
                            required
                            x-model="selectedAlumno.fksede"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Seleccione una sede</option>
                            @foreach($sedes as $sede)
                                <option value="{{ $sede->id_sede }}">{{ $sede->sede_nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Sección 2: Documento --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">Documento de Identidad</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Tipo de Documento
                        </label>
                        <select 
                            name="alum_documento" 
                            x-model="selectedAlumno.alum_documento"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Seleccione</option>
                            <option value="DNI">DNI</option>
                            <option value="CE">Carnet de Extranjería</option>
                            <option value="PAS">Pasaporte</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Número de Documento
                        </label>
                        <input
                            type="text"
                            name="alum_numDoc"
                            x-model="selectedAlumno.alum_numDoc"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            placeholder="Ej: 12345678">
                    </div>
                </div>
            </div>

            {{-- Sección 3: Contacto --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">Información de Contacto</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Teléfono <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="tel"
                            name="alum_telefo"
                            required
                            x-model="selectedAlumno.alum_telefo"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            placeholder="987654321">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Email <span class="text-gray-400 text-xs">(opcional)</span>
                        </label>
                        <input
                            type="email"
                            name="alum_correro"
                            x-model="selectedAlumno.alum_correro"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            placeholder="correo@ejemplo.com">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Dirección <span class="text-gray-400 text-xs">(opcional)</span>
                        </label>
                        <input
                            type="text"
                            name="alum_direccion"
                            x-model="selectedAlumno.alum_direccion"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            placeholder="Dirección completa">
                    </div>
                </div>
            </div>

            {{-- Sección 4: Información Adicional --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200">
                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">Información Adicional</h4>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Condiciones Médicas / Observaciones <span class="text-gray-400 text-xs">(opcional)</span>
                    </label>
                    <textarea
                        name="alum_condi"
                        rows="4"
                        x-model="selectedAlumno.alum_condi"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent resize-none"
                        placeholder="Especifique alguna condición médica, lesión o información relevante..."></textarea>
                </div>
            </div>

        </form>

        {{-- Footer con botones --}}
        <x-slot:footer>
            <div class="flex flex-col sm:flex-row gap-3">
                <button
                    type="button"
                    @click="closeEditModal()"
                    class="flex-1 px-4 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancelar
                </button>
                <button
                    type="submit"
                    form="formEditAlumno"
                    class="flex-1 px-4 py-2.5 bg-gradient-to-r from-pink-600 to-pink-700 text-white rounded-lg font-medium hover:from-pink-700 hover:to-pink-800 transition-all shadow-lg shadow-pink-500/50 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Actualizar Alumno
                </button>
            </div>
        </x-slot:footer>

    </x-modal-form>
</template>
