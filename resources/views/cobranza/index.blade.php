@extends('layouts.app')

@section('title', 'Cobranza - SIGG')
@section('page-title', 'Cobranza')
@section('page-subtitle', 'Saldos pendientes de productos y membresías')

@section('content')
<div x-data="{ modal: false, venta: {} }" class="w-full space-y-5">
    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('cobranza.vencidas') }}" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"><i class="fas fa-triangle-exclamation mr-2"></i>Cuotas vencidas</a>
        <a href="{{ route('cobranza.historial') }}" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900"><i class="fas fa-clock-rotate-left mr-2"></i>Historial de abonos</a>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow-sm p-4 grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input name="search" value="{{ request('search') }}" placeholder="Alumno o DNI" class="px-4 py-2 border border-gray-300 rounded-lg">
        <select name="tipo_venta" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Productos y membresías</option>
            <option value="producto" @selected(request('tipo_venta') === 'producto')>Productos</option>
            <option value="membresia" @selected(request('tipo_venta') === 'membresia')>Membresías</option>
        </select>
        <select name="estado_pago" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Todos los estados</option>
            <option value="parcial" @selected(request('estado_pago') === 'parcial')>Parcial</option>
            <option value="pendiente" @selected(request('estado_pago') === 'pendiente')>Pendiente</option>
            <option value="vencido" @selected(request('estado_pago') === 'vencido')>Vencido</option>
        </select>
        @if(auth()->user()->hasRole('Administrador'))
        <select name="sede" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Todas las sedes</option>
            @foreach($sedes as $sede)<option value="{{ $sede->id_sede }}" @selected(request('sede') == $sede->id_sede)>{{ $sede->sede_nombre }}</option>@endforeach
        </select>
        @endif
        <button class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700"><i class="fas fa-filter mr-2"></i>Filtrar</button>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table data-card="compacta" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th data-card-oculto class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Venta</th>
                    <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alumno</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pagado</th>
                    <th data-card-prioritario class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th data-card-prioritario class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acción</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($ventas as $venta)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium">#{{ $venta->id_venta }}</td>
                        <td class="px-4 py-3 text-sm">{{ $venta->alumno->nombreCompleto ?? 'Venta rápida' }}</td>
                        <td class="px-4 py-3 text-sm">
                            {{ $venta->tipo_venta === 'membresia' ? ($venta->membresia->mem_nombre ?? $venta->membresiaAlumno?->membresia?->mem_nombre ?? 'Membresía') : 'Productos' }}
                            @if($venta->estado_venta === 'reservado')<span class="block text-xs text-amber-700">Stock apartado</span>@endif
                            @if($venta->stock_liberado_at)<span class="block text-xs text-red-700">Stock liberado por vencimiento</span>@endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right">S/ {{ number_format($venta->venta_total, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-700">S/ {{ number_format($venta->monto_pagado, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-red-700">S/ {{ number_format($venta->saldo, 2) }}</td>
                        <td class="px-4 py-3 text-sm">{{ $venta->fecha_acordada ? \Carbon\Carbon::parse($venta->fecha_acordada)->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @can('cobranza.abonar')
                            @if(!$venta->stock_liberado_at || auth()->user()->hasRole('Administrador'))
                            <button type="button" @click="venta = {{ Js::from(['id' => $venta->id_venta, 'saldo' => (float) $venta->saldo, 'alumno' => $venta->alumno->nombreCompleto ?? 'Venta rápida']) }}; modal = true" class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Registrar abono</button>
                            @else
                            <span class="text-xs text-gray-500">Requiere Administrador</span>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay saldos pendientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ventas->hasPages())<div class="px-4 py-3 border-t">{{ $ventas->links() }}</div>@endif
    </div>

    <x-modal-form show="modal" title="Registrar abono" subtitle="El abono actualizará el saldo y las cuotas pendientes" size="md" headerColor="green">
        <form :action="`{{ url('/cobranza/ventas') }}/${venta.id}/abonos`" method="POST" class="space-y-4">
            @csrf
            <div class="p-3 bg-gray-50 rounded-lg text-sm"><b x-text="venta.alumno"></b><br>Saldo: S/ <span x-text="Number(venta.saldo || 0).toFixed(2)"></span></div>
            <input name="monto" type="number" min="0.01" step="0.01" :max="venta.saldo" required placeholder="Monto" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <select name="fkmetodo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg"><option value="">Método de pago</option>@foreach($metodos as $metodo)<option value="{{ $metodo->id_metod }}">{{ $metodo->metod_nombre }}</option>@endforeach</select>
            <input name="fecha_abono" type="date" value="{{ today()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <input name="num_comprobante" maxlength="50" placeholder="Comprobante (opcional)" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <textarea name="observacion" maxlength="500" rows="2" placeholder="Observación (opcional)" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
            <div class="flex gap-3"><button type="button" @click="modal=false" class="flex-1 px-4 py-2 border rounded-lg">Cancelar</button><button class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg">Guardar abono</button></div>
        </form>
    </x-modal-form>
</div>
@endsection
