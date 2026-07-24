@extends('layouts.app')

@section('title', 'Caja')

@section('content')
<div class="space-y-6" x-data="{ showApertura: false }">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Gestion de Caja</h2>
            <p class="text-sm text-gray-500">Apertura, cierre y movimientos del turno</p>
        </div>
        @if(!$caja)
            <button @click="showApertura = true"
                class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors">
                <i class="fas fa-cash-register mr-2"></i>Abrir Caja
            </button>
        @endif
    </div>

    @if($caja)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Monto Inicial</p>
                <p class="text-2xl font-bold text-gray-900">S/ {{ number_format($caja->monto_inicial, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500">Ingresos</p>
                <p class="text-2xl font-bold text-gray-900">S/ {{ number_format($caja->movimientos()->where('tipo', 'ingreso')->sum('monto'), 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-5 border-l-4 border-red-500">
                <p class="text-sm text-gray-500">Egresos</p>
                <p class="text-2xl font-bold text-gray-900">S/ {{ number_format($caja->movimientos()->where('tipo', 'egreso')->sum('monto'), 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-5 border-l-4 border-pink-500">
                <p class="text-sm text-gray-500">Saldo Actual</p>
                <p class="text-2xl font-bold text-gray-900">S/ {{ number_format($caja->saldo_actual, 2) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-list mr-2 text-gray-400"></i>Movimientos
                </h3>
                <form method="POST" action="{{ route('caja.cierre', $caja->id_caja) }}"
                    onsubmit="return confirm('Esta seguro de cerrar la caja? Esta accion no se puede deshacer.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm">
                        <i class="fas fa-lock mr-1"></i>Cerrar Caja
                    </button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($caja->movimientos as $mov)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $mov->created_at->format('H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $mov->tipo === 'ingreso' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ ucfirst($mov->tipo) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $mov->concepto }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium {{ $mov->tipo === 'ingreso' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $mov->tipo === 'ingreso' ? '+' : '-' }}S/ {{ number_format($mov->monto, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Sin movimientos registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">
                <i class="fas fa-clock mr-1"></i>
                Abierto por <strong>{{ $caja->usuario->name ?? 'N/A' }}</strong> a las {{ $caja->fecha_apertura->format('d/m/Y H:i') }}
            </p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-cash-register text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay caja abierta</h3>
            <p class="text-gray-500">Presiona "Abrir Caja" para iniciar un nuevo turno</p>
        </div>
    @endif

    <div x-show="showApertura" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6" @click.away="showApertura = false">
            <h3 class="text-lg font-semibold mb-4"><i class="fas fa-cash-register mr-2 text-pink-600"></i>Abrir Caja</h3>
            <form method="POST" action="{{ route('caja.apertura') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto Inicial (S/)</label>
                    <input type="number" name="monto_inicial" step="0.01" min="0" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" @click="showApertura = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="submit"
                        class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700">Abrir Caja</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
