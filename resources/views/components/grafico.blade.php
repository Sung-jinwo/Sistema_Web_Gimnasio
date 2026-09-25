<div class="bg-white rounded-lg shadow-sm p-6">
    @if($titulo)
        <h3 class="text-lg font-bold text-gray-900">{{ $titulo }}</h3>
    @endif
    @if($subtitulo)
        <p class="text-sm text-gray-500 mb-4">{{ $subtitulo }}</p>
    @endif
    <div class="relative w-full" style="height: {{ $altura }}; min-height: 220px">
        @if($vacio())
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-gray-400">
                <i class="fas fa-chart-column text-4xl text-gray-300"></i>
                <p class="text-sm font-medium">Sin datos en el periodo</p>
            </div>
        @else
            <canvas data-grafico='@js($config())' @if($moneda) data-moneda="1" @endif></canvas>
        @endif
    </div>
</div>
