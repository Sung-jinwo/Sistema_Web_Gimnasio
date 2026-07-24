<x-table :headers="['Código', 'Nombre', 'Apellido', 'Membresía', 'Estado', 'Acciones']">
        <template x-for="alumno in filteredAlumnos" :key="alumno.id_alumno">
            <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="alumno.alum_codigo"></td>
                <td class="px-6 py-4 text-sm text-gray-900" x-text="alumno.alum_nombre"></td>
                <td class="px-6 py-4 text-sm text-gray-600" x-text="alumno.alum_apellido"></td>
                <td class="px-6 py-4 text-sm">
                    <span 
                        class="inline-block px-3 py-1 rounded-full text-xs font-medium"
                        :class="{
                            'bg-green-100 text-green-800': alumno.membresia === 'Pagada',
                            'bg-yellow-100 text-yellow-800': alumno.membresia === 'Por Pagar',
                            'bg-red-100 text-red-800': alumno.membresia === 'Vencida',
                            'bg-gray-100 text-gray-800': alumno.membresia === 'Inactiva'
                        }"
                        x-text="alumno.membresia">
                    </span>
                </td>
                <td class="px-6 py-4 text-sm">
                    <span 
                        class="inline-block px-3 py-1 rounded-full text-xs font-medium"
                        :class="getBadgeColor(alumno.alum_estado)"
                        x-text="alumno.alum_estado || 'N/A'">
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex justify-center gap-2">
                        <!-- Ver -->
                        <x-button 
                            variant="ghost" 
                            size="sm"
                            @click="openModal(alumno, 'ver')"
                            class="text-purple-600 hover:bg-purple-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </x-button>
                        
                        <!-- Editar -->
                        <x-button 
                            variant="ghost" 
                            size="sm"
                            @click="openEditModal(alumno)"
                            class="text-blue-600 hover:bg-blue-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </x-button>
                        
                        <!-- Eliminar -->
                        <x-button 
                            variant="ghost" 
                            size="sm"
                            @click="openModal(alumno, 'eliminar')"
                            class="text-red-600 hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </x-button>
                    </div>
                </td>
            </tr>
        </template>
        <tr x-show="filteredAlumnos.length === 0">
            <td colspan="6" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center gap-2">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="text-gray-500 font-medium">No se encontraron alumnos</p>
                    <p class="text-gray-400 text-sm">Intenta con otros filtros de búsqueda</p>
                </div>
            </td>
        </tr>
    </x-table>