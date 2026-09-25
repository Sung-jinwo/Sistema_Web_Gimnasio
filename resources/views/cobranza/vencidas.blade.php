@extends('layouts.app')
@section('title', 'Cuotas vencidas - SIGG')
@section('page-title', 'Cuotas vencidas')
@section('page-subtitle', 'Compromisos de pago fuera de fecha')
@section('content')
<div class="space-y-5">
    <a href="{{ route('cobranza.index') }}" class="inline-flex px-4 py-2 border rounded-lg bg-white"><i class="fas fa-arrow-left mr-2 mt-1"></i>Volver a Cobranza</a>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden"><div class="overflow-x-auto"><table data-card="compacta" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50"><tr><th data-card-oculto class="px-4 py-3 text-left text-xs uppercase text-gray-500">Venta</th><th data-card-prioritario class="px-4 py-3 text-left text-xs uppercase text-gray-500">Alumno</th><th data-card-prioritario class="px-4 py-3 text-left text-xs uppercase text-gray-500">Fecha acordada</th><th data-card-prioritario class="px-4 py-3 text-right text-xs uppercase text-gray-500">Saldo</th><th data-card-prioritario class="px-4 py-3 text-center text-xs uppercase text-gray-500">Estado</th></tr></thead>
        <tbody class="divide-y">@forelse($cuotas as $cuota)<tr><td class="px-4 py-3 text-sm font-medium">#{{ $cuota->fkventa }}</td><td class="px-4 py-3 text-sm">{{ $cuota->venta->alumno->nombreCompleto ?? 'Venta rápida' }}</td><td class="px-4 py-3 text-sm">{{ $cuota->fecha_acordada_formato }}</td><td class="px-4 py-3 text-sm text-right font-bold text-red-700">S/ {{ number_format($cuota->saldo, 2) }}</td><td class="px-4 py-3 text-center"><span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Vencida</span></td></tr>@empty<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay cuotas vencidas.</td></tr>@endforelse</tbody>
    </table></div>@if($cuotas->hasPages())<div class="px-4 py-3 border-t">{{ $cuotas->links() }}</div>@endif</div>
</div>
@endsection
